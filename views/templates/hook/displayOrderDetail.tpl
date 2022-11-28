{*
* 2007-2022 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2022 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA

*}
<div class="row">
    <div class="col-xs-12 col-md-6">
        <div class="address alternate_item box">
            <h3 class="page-subheading">{l s='Información Oca Epak' mod='rg_ocaepak'}</h3>
            <dl class="list-detail">
                <dt>{l s='Sucursal seleccionada' mod='rg_ocaepak'}</dt>
                <dd>{$distributionCenter['Sucursal']|trim|lower|capitalize|escape:'htmlall':'UTF-8'} - {$distributionCenter['Sigla']|trim|escape:'htmlall':'UTF-8'}</dd>
                <dt>{l s='Dirección de la Sucursal' mod='rg_ocaepak'}</dt>
                <dd>
                    {$distributionCenter['Calle']|trim|lower|capitalize|escape:'htmlall':'UTF-8'} {if !$distributionCenter['Numero']|is_array}{$distributionCenter['Numero']|trim|lower|capitalize|escape:'htmlall':'UTF-8'}{/if}<br/>
                    {if (!$distributionCenter['Piso']|is_array && $distributionCenter['Piso']|trim) != ''}
                        {l s='Floor' mod='rg_ocaepak'} :
                        {$distributionCenter['Piso']|trim|lower|capitalize|escape:'htmlall':'UTF-8'}<br/>
                    {/if}
                    {$distributionCenter['Localidad']|trim|lower|capitalize|escape:'htmlall':'UTF-8'},
                    {$distributionCenter['Provincia']|trim|lower|capitalize|escape:'htmlall':'UTF-8'}
                    <br>(<a href="http://maps.google.com/maps?z=18&q={$distributionCenter['Latitud']|trim|escape:'htmlall':'UTF-8'},{$distributionCenter['Longitud']|trim|escape:'htmlall':'UTF-8'}" target="_blank">{l s='How to get there' mod='rg_ocaepak'}</a>)
                </dd>
                <dt>{l s='Código Postal de la Sucursal' mod='rg_ocaepak'}</dt>
                <dd>{$distributionCenter['CodigoPostal']|trim|escape:'htmlall':'UTF-8'}</dd>
                <dt>{l s='Número telefónico' mod='rg_ocaepak'}</dt>
                <dd>{$distributionCenter['Telefono']|trim|escape:'htmlall':'UTF-8'}</dd>
            </dl>
        </div>
    </div>
</div>
