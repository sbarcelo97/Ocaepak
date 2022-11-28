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

use ModuleCore;
use PrestaShop\PrestaShop\Adapter\Entity\Db;

class OcaEpakBranches
{
    public static $expiry = 24; // hours

    /**
     * @throws PrestaShopDatabaseException
     */
    public static function retrieve($postcode)
    {
        $module = ModuleCore::getInstanceByName('rg_ocaepak');
        $query = OcaCarrierTools::interpolateSql(
            "SELECT *
            FROM `{TABLE}`
            WHERE postcode = '{POSTCODE}'
            AND `date` > DATE_SUB(NOW(), INTERVAL {EXPIRY} HOUR)",
            [
                '{TABLE}' => _DB_PREFIX_ . $module::BRANCHES_TABLE,
                '{POSTCODE}' => $postcode,
                '{EXPIRY}' => self::$expiry,
            ]
        );
        $result = Db::getInstance()->executeS($query);
        if (!is_array($result)) {
            return [];
        }
        $branches = [];
        foreach ($result as $branch) {
            $branches[$branch['IdCentroImposicion']] = $branch;
        }

        return $branches;
    }

    public static function markasvalid($idbranch)
    {
        $module = ModuleCore::getInstanceByName('rg_ocaepak');
        $sql = 'UPDATE ' . _DB_PREFIX_ . $module::BRANCHES_TABLE . ' SET entrega_paquetes = 1 where IdCentroImposicion =' . $idbranch;
        Db::getInstance()->execute($sql);
    }

    public static function isValid($idbranch)
    {
        $module = ModuleCore::getInstanceByName('rg_ocaepak');
        $sql = 'SELECT 1 FROM ' . _DB_PREFIX_ . $module::BRANCHES_TABLE . ' WHERE entrega_paquetes = 1 AND IdCentroImposicion =' . $idbranch;
        $resp = Db::getInstance()->executeS($sql);

        return !empty($resp);
    }

    public static function remove($idbranch)
    {
        $module = ModuleCore::getInstanceByName('rg_ocaepak');
        $sql = 'DELETE FROM ' . _DB_PREFIX_ . $module::BRANCHES_TABLE . ' WHERE IdCentroImposicion =' . $idbranch;
        Db::getInstance()->execute($sql);
    }

    public static function insert($postcode, $branches)
    {
        $module = ModuleCore::getInstanceByName('rg_ocaepak');
        $res = true;
        foreach ($branches as $branch) {
            $query = OcaCarrierTools::interpolateSql(
                "REPLACE INTO `{TABLE}`
                (`IdCentroImposicion`, `Sucursal`, `Calle`, `Numero`, `Localidad`, `Provincia`, `Latitud`, `Longitud`, `CodigoPostal`, `postcode`, `date`)
                VALUES
                ('{IdCentroImposicion}',
                '{Sucursal}',
                '{Calle}',
                '{Numero}',
                '{Localidad}',
                '{Provincia}',
                '{Latitud}',
                '{Longitud}',
                '{CodigoPostal}',
                '{POSTCODE}',
                NOW())",
                [
                    '{TABLE}' => _DB_PREFIX_ . $module::BRANCHES_TABLE,
                    '{POSTCODE}' => $postcode,
                    '{IdCentroImposicion}' => trim($branch['IdCentroImposicion']),
                    '{Sucursal}' => trim($branch['Sucursal']),
                    '{Calle}' => trim($branch['Calle']),
                    '{Numero}' => trim($branch['Numero']),
                    // '{Piso}' => trim($branch['Piso']),
                    '{Localidad}' => trim($branch['Localidad']),
                    '{Provincia}' => trim($branch['Provincia']),
                    '{Latitud}' => trim($branch['Latitud']),
                    '{Longitud}' => trim($branch['Longitud']),
                    '{CodigoPostal}' => trim($branch['CodigoPostal']),
                ]
            );
            $res &= Db::getInstance()->execute($query);
        }

        return $res;
    }

    public static function clear()
    {
        $module = ModuleCore::getInstanceByName('rg_ocaepak');
        $query = OcaCarrierTools::interpolateSql(
            'DELETE FROM `{TABLE}` WHERE 1',
            [
                '{TABLE}' => _DB_PREFIX_ . $module::BRANCHES_TABLE,
            ]
        );

        return Db::getInstance()->execute($query);
    }
}
