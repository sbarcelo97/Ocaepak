<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */
use RgOcaEpak\Classes\OcaCarrierTools;
use RgOcaEpak\Classes\OcaEpakOperative;
use RgOcaEpak\Classes\OcaEpakRelay;

class AdminOcaEpakController extends ModuleAdminController
{
    /**
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public $module = null;

    public function ajaxProcessCarrier()
    {
        $this->module = Module::getInstanceByName('rg_ocaepak');
        $order = new Order((int) Tools::getValue('order_id'));
        $cart = new Cart($order->id_cart);
        $address = new Address($cart->id_address_delivery);
        $currency = new Currency($cart->id_currency);
        $carrier = new Carrier($cart->id_carrier);
        $op = OcaEpakOperative::getByFieldId('carrier_reference', $carrier->id_reference);
        if (!$op) {
            return null;
        }
        // $customer = new Customer($order->id_customer);
        $cartData = OcaCarrierTools::getCartPhysicalData($cart, $cart->id_carrier, Configuration::get($this->module::CONFIG_PREFIX . 'DEFWEIGHT'), Configuration::get(rg_ocaepak::CONFIG_PREFIX . 'DEFVOLUME'), Rg_OcaEpak::PADDING);
        $shipping = $cart->getTotalShippingCost(null, false);
        $totalToPay = Tools::ps_round(OcaCarrierTools::applyFee($shipping, $op->addfee), 2);
        $paidFee = $totalToPay - $shipping;
        $relay = OcaEpakRelay::getByCartId($order->id_cart);
        try {
            $data = $this->module->executeWebservice('Tarifar_Envio_Corporativo', [
                'PesoTotal' => $cartData['weight'],
                'VolumenTotal' => ($cartData['volume'] > 0.0001) ? $cartData['volume'] : 0.0001,
                'ValorDeclarado' => $cartData['cost'],
                'CodigoPostalOrigen' => Configuration::get($this->module::CONFIG_PREFIX . 'POSTCODE'),
                'CodigoPostalDestino' => OcaCarrierTools::cleanPostcode($address->postcode),
                'CantidadPaquetes' => 1,
                'Cuit' => Configuration::get($this->module::CONFIG_PREFIX . 'CUIT'),
                'Operativa' => $op->reference,
            ]);
            $quote = Tools::ps_round(OcaCarrierTools::convertCurrencyFromIso($data->Total, 'ARS', $cart->id_currency), 2);
            $quoteError = null;
        } catch (Exception $e) {
            $quoteError = $e->getMessage();
            $data = null;
            $quote = null;
        }
        $distributionCenter = [];
        if (in_array($op->type, ['PaS', 'SaS']) && $relay) {
            $distributionCenter = $this->module->retrieveOcaBranchData($relay->distribution_center_id);
        }
        $this->context->smarty->assign([
            'moduleName' => $this->module::MODULE_NAME,
            'currencySign' => $currency->sign,
            'operative' => $op,
            'cartData' => $cartData,
            'quote' => $quote,
            'quoteData' => $data,
            'quoteError' => $quoteError,
            'paidFee' => $paidFee,
            'distributionCenter' => $distributionCenter,
        ]);
        exit($this->module->display(_PS_MODULE_DIR_ . $this->module->name . DIRECTORY_SEPARATOR . $this->module->name . '.php', _PS_VERSION_ < '1.6' ? 'displayAdminOrder15_ajax.tpl' : 'displayAdminOrder_ajax.tpl'));
    }
}
