<?php
/**
* Copyright 2022 Region Global
*
* Licensed under the Apache License, Version 2.0 (the "License");
* you may not use this file except in compliance with the License.
* You may obtain a copy of the License at
*
* http://www.apache.org/licenses/LICENSE-2.0
*
* Unless required by applicable law or agreed to in writing, software
* distributed under the License is distributed on an "AS IS" BASIS,
* WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
* See the License for the specific language governing permissions and
* limitations under the License.
*
*@author Region Global
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
