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
* @author Region Global
* @copyright 2022 Region Global 
* @license http://www.apache.org/licenses/LICENSE-2.0
*/
*/
namespace RgOcaEpak\Classes;

use PrestaShop\PrestaShop\Adapter\Configuration;
use PrestaShop\PrestaShop\Adapter\Entity\Currency;
use PrestaShop\PrestaShop\Adapter\Entity\Product;
use PrestaShop\PrestaShop\Adapter\Entity\Tools;
use Symfony\Component\Config\Definition\Exception\Exception;

class OcaCarrierTools
{
    /**
     * Apply a fee to a payment amount
     *
     * @param float $netAmount
     * @param string $fee (float with optional percent sign at the end)
     */
    public static function applyFee($netAmount, $fee)
    {
        $fee = strlen($fee) ? $fee : '0';

        return strpos($fee, '%') ? (float) $netAmount * (1 + (float) substr($fee, 0, -1) / 100) : (float) $netAmount + (float) $fee;
    }

    public static function cleanPostcode($postcode)
    {
        return preg_replace('/[^0-9]/', '', $postcode);
    }

    public static function convertCurrencyFromIso($quantity, $iso, $currencyId)
    {
        if (($curId = Currency::getIdByIsoCode($iso)) != $currencyId) {
            $currentCurrency = new Currency($currencyId);
            $cur = new Currency($curId);
            $quantity = $quantity * $currentCurrency->conversion_rate / $cur->conversion_rate;
        }

        return $quantity;
    }

    /**
     * Returns cart data in kg and cubic m
     *
     * @param $cart
     * @param $id_carrier
     * @param $defWeight
     * @param $defVolume
     * @param $defPadding
     *
     * @return array
     */
    public static function getCartPhysicalData($cart, $id_carrier, $defWeight, $defVolume, $defPadding)
    {
        $configuration = new Configuration();
        $products = $cart->getProducts();
        $weight = 0;
        $volume = 0;
        $cost = 0;
        switch ($configuration->get('PS_DIMENSION_UNIT')) {
            case 'm':
                $divider = 1;
                break;
            case 'in':
                $divider = 39.37 * 39.37 * 39.37;  // 39.37 in to 1 m
                break;
            case 'cm':
            default:
                $divider = 1000000;
                break;
        }
        $padding = $defPadding / 100;

        switch ($configuration->get('PS_WEIGHT_UNIT')) {
            case 'lb':
                $multiplier = 0.453592;
                break;
            case 'g':
                $multiplier = 0.001;
                break;
            case 'kg':
            default:
                $multiplier = 1;
                break;
        }
        foreach ($products as $product) {
            $productObj = new Product($product['id_product']);
            $carriers = $productObj->getCarriers();
            $isProductCarrier = false;
            foreach ($carriers as $carrier) {
                if (!$id_carrier || $carrier['id_carrier'] == $id_carrier) {
                    $isProductCarrier = true;
                    continue;
                }
            }
            if ($product['is_virtual'] or (count($carriers) && !$isProductCarrier)) {
                continue;
            }
            $weight += ($product['weight'] > 0 ? ($product['weight'] * $multiplier) : $defWeight) * $product['cart_quantity'];
            $volume += ($product['width'] * $product['height'] * $product['depth'] > 0 ? ($product['width'] * $product['height'] * $product['depth']) / $divider : $defVolume) * $product['cart_quantity'];
            $cost += $productObj->getPrice() * $product['cart_quantity'];
        }
        $paddedVolume = round(pow(pow($volume, 1 / 3) + 2 * $padding, 3), 6);

        return ['weight' => $weight, 'volume' => $paddedVolume, 'cost' => $cost];
    }

    public static function interpolateSql($sql, $replacements)
    {
        foreach ($replacements as $var => $repl) {
            $replacements[$var] = pSQL($repl);
        }

        return str_replace(array_keys($replacements), array_values($replacements), $sql);
    }

    public static function interpolateSqlFile($moduleName, $fileName, $replacements)
    {
        $filePath = _PS_MODULE_DIR_ . "{$moduleName}/sql/{$fileName}.sql";
        if (!file_exists($filePath)) {
            throw new Exception('Wrong SQL Interpolation File Name: ' . $fileName);
        }
        $file = Tools::file_get_contents($filePath);
        foreach ($replacements as $var => $repl) {
            $replacements[$var] = pSQL($repl);
        }

        return str_replace(array_keys($replacements), array_values($replacements), $file);
    }
}
