<?php
namespace RgOcaEpak\Controllers\Admin;
use ModuleCore;
use PrestaShop\PrestaShop\Adapter\Entity\Address;
use Clegginabox\PDFMerger\PDFMerger;
use setasign\Fpdi\Fpdi;
use PrestaShop\PrestaShop\Adapter\Entity\Cart;
use PrestaShop\PrestaShop\Adapter\Entity\Customer;
use PrestaShop\PrestaShop\Adapter\Entity\Db;
use PrestaShop\PrestaShop\Adapter\Entity\OrderCarrier;
use PrestaShop\PrestaShop\Adapter\SymfonyContainer;
use Rg_OcaEpak;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use PrestaShop\PrestaShop\Adapter\Entity\Order;
use PrestaShop\PrestaShop\Adapter\Entity\Carrier;
use RgOcaEpak\Classes\OcaEpakOperative;
use RgOcaEpak\Classes\OcaEpakRelay;
use RgOcaEpak\Classes\OcaEpakOrder;
use RgOcaEpak\Classes\OcaCarrierTools;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\RedirectResponse;
use ZipArchive;


class RgOcaEpakOrdersController extends Controller
{

public function submitOrders(){
    $stickers = [];
    foreach ($_GET['order_orders_bulk'] as $order_id) {
        $order = new Order($order_id);
        $carrier = new Carrier($order->id_carrier);
        $op = OcaEpakOperative::getByFieldId('carrier_reference', $carrier->id_reference);
        if (!$op) {
            continue;
        }
        if (!$ocaOrder = OcaEpakOrder::getByFieldId('id_order', $order_id)) {
            $address = new Address($order->id_address_delivery);
            $carrier = new Carrier($order->id_carrier);
            $customer = new Customer($order->id_customer);
            $cart = new Cart($order->id_cart);
            if (in_array($op->type, array('PaS', 'SaS'))) {
                $relayId = OcaEpakRelay::getByCartId($order->id_cart)->distribution_center_id;
            } else {
                $relayId = null;
            }
            $cartData = OcaCarrierTools::getCartPhysicalData(
                $cart,
                $carrier->id,
                $this->get('prestashop.adapter.legacy.configuration')->get(Rg_OcaEpak::CONFIG_PREFIX . 'DEFWEIGHT'),
                $this->get('prestashop.adapter.legacy.configuration')->get(Rg_OcaEpak::CONFIG_PREFIX . 'DEFVOLUME'),
                Rg_OcaEpak::PADDING
            );
            $module = ModuleCore::getInstanceByName('rg_ocaepak');
            $cartData['address'] = $address;
            if ($preOrder = $module->getValidateOcaForm($cartData)) {
                $xmlRetiro = OcaEpakOrder::generateOrderXml(array_merge($preOrder, array(
                    'address' => $address,
                    'operative' => $op,
                    'order' => $order,
                    'customer' => $customer,
                    'cost_center_id' => in_array($op->type, array('PaP', 'PaS')) ? '0' : '1',
                    'imposition_center_id' => $relayId,
                    'origin_imposition_center_id' => in_array($op->type, array('SaP', 'SaS')) ? $this->get('prestashop.adapter.legacy.configuration')->get(Rg_OcaEpak::CONFIG_PREFIX . 'ADMISSION_BRANCH') : false,
                    'postcode' => OcaCarrierTools::cleanPostcode($address->postcode)
                )));
                $data = [];
                try {
                    $data = $module->executeWebservice('IngresoORMultiplesRetiros', array(
                        'usr' => $this->get('prestashop.adapter.legacy.configuration')->get(Rg_OcaEpak::CONFIG_PREFIX . 'EMAIL'),
                        'psw' => $this->get('prestashop.adapter.legacy.configuration')->get(Rg_OcaEpak::CONFIG_PREFIX . 'PASSWORD'),
                        'ConfirmarRetiro' => true,
                        'xml_Datos' => $xmlRetiro
                    ));
                    if (!isset($data->Resumen)) {
                        throw new Exception('Error generating OCA order');
                    }
                    if (isset($data->Errores)) {
                        throw new Exception('Error generating OCA order' . ': ' . (string)$data->Errores->Error->Descripcion);
                    }
                    $ocaOrder = new OcaEpakOrder();
                    $ocaOrder->id_order = $order->id;
                    $ocaOrder->reference = (int)$data->DetalleIngresos->OrdenRetiro;
                    $ocaOrder->tracking = (string)$data->DetalleIngresos->NumeroEnvio;
                    $ocaOrder->operation_code = (int)$data->Resumen->CodigoOperacion;
                    $ocaOrder->save();
                    if (!$order->shipping_number && $ocaOrder->tracking) {
                        $id_order_carrier = Db::getInstance()->getValue('
                        SELECT `id_order_carrier`
                        FROM `' . _DB_PREFIX_ . 'order_carrier`
                        WHERE `id_order` = ' . (int)$order->id
                        );
                        if ($id_order_carrier) {
                            $_GET['tracking_number'] = $ocaOrder->tracking;
                            $_GET['submitShippingNumber'] = 1;
                            $_GET['id_order_carrier'] = $id_order_carrier;
                            $ordercarrier = new OrderCarrier($id_order_carrier);
                            $ordercarrier->tracking_number= $ocaOrder->tracking;
                            $ordercarrier->update();
                        }
                       $stickers[$order_id]=$this->getSticker($ocaOrder->id);
                    }
                    unset($ocaOrder);
                } catch (Exception $e) {
                    if ($module::LOG_DEBUG) {
                        $module->logError($e->getMessage());
                        $module->logError($data);
                    }
                }
            }
        } else {
            $stickers[$order_id]=$this->getSticker($ocaOrder->id);
        }

    }
    if(!empty($stickers)){
        ob_start();
        $sql = 'SELECT o.id_order, COUNT(cp.id_product) as \'cant\' FROM ps_orders o JOIN ps_cart_product cp ON (o.`id_cart` = cp.`id_cart`)
                 WHERE id_order IN ('.implode(',',array_keys($stickers)).')'.
            'GROUP BY (cp.id_cart)
                 ORDER BY o.id_order';
        $cants = Db::getInstance()->executeS($sql);
        $cont=1;
        $datadir = __DIR__.'/etiquetas/';
        $files = glob($datadir.'*'); //obtenemos todos los nombres de los ficheros
        foreach($files as $file){
            if(is_file($file)){
                unlink($file);
            }
        }
        $fileName ='etiquetas.zip';
        $filePath = $datadir. $fileName;
        $zip = new \ZipArchive();
        if ($zip->open($filePath, ZipArchive::CREATE)!==TRUE) {
            exit("cannot open zip\n");
        }
        $pdfmerged = new PDFMerger();
        $outputNamepdf = $datadir."all_etiquetas.pdf";
        $output =$datadir.'etiquetas.pdf';

        foreach ($stickers as $id=>$sticker) {
            $name ='etiquetas_'.$id.'.pdf';
            $path = $datadir . $name;
            $pdf = fopen($path, 'a+');
            fwrite($pdf, base64_decode($sticker));
            fclose($pdf);
            $pdfmerged->addPDF($path);
            $pdfmerged->merge('file',$outputNamepdf,'L');
        }
        $pdf = new Fpdi();

        $pageCount = $pdf->setSourceFile($outputNamepdf);

        $width = $pdf->GetPageWidth() / 2 - 15;
        $height = 0;

        $_x = $x = 10;
        $_y = $y = 10;

        $pdf->AddPage();
        for ($n = 1; $n <= $pageCount; $n++) {
            $pageId = $pdf->importPage($n);
            $size = $pdf->useImportedPage($pageId, $x, $y, 270);
            $height = 150;
            if ($n % 2 == 0) {
                $y += $height;
                $x = $_x;
                $height = 0;
            } else {
                $x += $width + 10;
            }

            if ($n % 4 == 0 && $n != $pageCount) {
                $pdf->AddPage();
                $x = $_x;
                $y = $_y;
            }
        }

        $pdf->Output('F', $output);
        if (!empty($stickers) && file_exists($output)) {
            $zip->addFile($output, 'etiquetas.pdf');
        }
        ob_end_flush();

        $zip->close();

        // Define headers
        header("Content-Description: File Transfer");
        header("Content-Disposition: Attachment; filename=$fileName");
        header("Content-Type: application/octet-stream");

        // Read the file
        readfile($filePath);
        unlink($filePath);
        unlink($outputNamepdf);
        unset($pdfmerged);

    }

    return (new RedirectResponse(SymfonyContainer::getInstance()->get('router')->generate('admin_orders_index')));
    }

    public function getSticker($id_order){
        $module = ModuleCore::getInstanceByName('rg_ocaepak');
        $ocaOrder = new OcaEpakOrder($id_order);
        $sticker = $module->executeWebservice(
            'GetPdfDeEtiquetasPorOrdenOrNumeroEnvio',
            array('idOrdenRetiro' => $ocaOrder->reference),
            true
        );
       return $sticker;

    }
}
