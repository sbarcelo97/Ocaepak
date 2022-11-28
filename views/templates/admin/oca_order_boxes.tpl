{**
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
 *}

<div class="panel">
    {if empty($oca_boxes)}
        <div class="alert alert-danger">{l s='Es necesario agregar al menos una caja para generar ordenes' mod='rg_ocaepak'}</div>
    {/if}
    {foreach $oca_boxes as $ind=>$box}
        <div class="form-group">
            <h4 style="display: inline-block; margin-right: 16px;">{l s='Caja' mod='rg_ocaepak'}: {$box['l']|escape:'htmlall':'UTF-8'}cm×{$box['d']|escape:'htmlall':'UTF-8'}cm×{$box['h']|escape:'htmlall':'UTF-8'}cm</h4>
            {l s='Cantidad' mod='rg_ocaepak'}: <input type="number" name="oca-box-q-{$ind|escape:'htmlall':'UTF-8'}" id="oca-box-q-{$ind|escape:'htmlall':'UTF-8'}" min="0" step="1" value="0" class="fixed-width-sm" style="display: inline-block;  margin-right: 16px;">
        </div>
    {/foreach}
</div>
