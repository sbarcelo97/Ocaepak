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
