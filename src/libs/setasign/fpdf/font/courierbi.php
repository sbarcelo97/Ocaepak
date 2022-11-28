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
$type = 'Core';
$name = 'Courier-BoldOblique';
$up = -100;
$ut = 50;
for ($i = 0; $i <= 255; ++$i) {
    $cw[chr($i)] = 600;
}
$enc = 'cp1252';
$uv = [0 => [0, 128], 128 => 8364, 130 => 8218, 131 => 402, 132 => 8222, 133 => 8230, 134 => [8224, 2], 136 => 710, 137 => 8240, 138 => 352, 139 => 8249, 140 => 338, 142 => 381, 145 => [8216, 2], 147 => [8220, 2], 149 => 8226, 150 => [8211, 2], 152 => 732, 153 => 8482, 154 => 353, 155 => 8250, 156 => 339, 158 => 382, 159 => 376, 160 => [160, 96]];
