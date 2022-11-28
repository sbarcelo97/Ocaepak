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

use PrestaShop\PrestaShop\Adapter\Entity\Db;
use PrestaShop\PrestaShop\Adapter\Entity\ObjectModel;

class OcaEpakRelay extends ObjectModel
{
    public $id_cart;
    public $distribution_center_id;
    public $auto;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table' => 'ocae_relays',
        'primary' => 'id_ocae_relays',
        'multishop' => true,
        'fields' => [
            'id_cart' => ['type' => self::TYPE_INT, 'validate' => 'isunsignedInt', 'required' => true],
            'distribution_center_id' => ['type' => self::TYPE_INT, 'validate' => 'isunsignedInt', 'required' => true],
            'auto' => ['type' => self::TYPE_INT, 'validate' => 'isBool', 'required' => false],
        ],
    ];

    public static function getByCartId($id_cart)
    {
        $query = OcaCarrierTools::interpolateSql(
            "SELECT `{ID}`
            FROM `{TABLE}`
            WHERE `id_cart` = '{CART}'",
            [
                '{TABLE}' => _DB_PREFIX_ . 'ocae_relays',
                '{ID}' => 'id_ocae_relays',
                '{CART}' => $id_cart,
            ]
        );
        $id = Db::getInstance()->ExecuteS($query);
        $id = $id[array_key_last($id)]['id_ocae_relays'];

        return $id ? (new OcaEpakRelay($id)) : null;
    }
}
