<?php

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
