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
