{*
* 2007-2014 PrestaShop
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
*  @copyright 2007-2014 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA

*}
<div class="row card-body">
    <div class="col-xs-6">
        {if !empty($quoteError)}
            <p class="warn alert alert-danger">
                {$quoteError|trim|escape:'htmlall':'UTF-8'}
            </p>
        {/if}
        <dl class="well list-detail">
            <dt>{l s='Operativa' mod='rg_ocaepak'}</dt>
            <dd>{$operative->reference|trim|escape:'htmlall':'UTF-8'} ({$operative->type|trim|escape:'htmlall':'UTF-8'}{if $operative->insured} {l s='Insured' mod='rg_ocaepak'}{/if})</dd>
            <dt>{l s='Peso calculado de la orden' mod='rg_ocaepak'}</dt>
            <dd>{$cartData['weight']|trim|escape:'htmlall':'UTF-8'} kg</dd>
            <dt>{l s='Volumen calculado' mod='rg_ocaepak'}</dt>
            <dd>{$cartData['volume']|trim|escape:'htmlall':'UTF-8'} m³</dd>
            {if !empty($quoteData)}
                <dt>{l s='Tiempo de entrega estimados' mod='rg_ocaepak'}</dt>
                <dd>{$quoteData->PlazoEntrega|trim|escape:'htmlall':'UTF-8'} {l s='días laborales' mod='rg_ocaepak'}</dd>
            {/if}
            {if ($paidFee != 0)}
                <dt>{l s='Cargo adicional' mod='rg_ocaepak'}</dt>
                <dd>{$currencySign|trim|escape:'htmlall':'UTF-8'}{$paidFee|trim|escape:'htmlall':'UTF-8'}</dd>
            {/if}
            {if $quote}
                <dt>{l s='Costo' mod='rg_ocaepak'}</dt>
                <dd>{$currencySign|trim|escape:'htmlall':'UTF-8'}{$quote|trim|escape:'htmlall':'UTF-8'}</dd>
            {/if}

        </dl>
    </div>
    <div class="col-xs-6">
        {if !empty($distributionCenter)}
            <dl class="well list-detail">
                <dt>{l s='Sucursal elegida por el cliente' mod='rg_ocaepak'}</dt>
                <dd>{$distributionCenter['Sucursal']|trim|lower|capitalize|escape:'htmlall':'UTF-8'}</dd>
                <dt>{l s='ID Sucursal' mod='rg_ocaepak'}</dt>
                <dd>{$distributionCenter['IdCentroImposicion']|trim|escape:'htmlall':'UTF-8'}</dd>
                <dt>{l s='Código Sucursal' mod='rg_ocaepak'}</dt>
                <dd>{$distributionCenter['Sigla']|trim|escape:'htmlall':'UTF-8'}</dd>
                <dt>{l s='Dirección Sucursal' mod='rg_ocaepak'}</dt>
                <dd>
                    {$distributionCenter['Calle']|trim|lower|capitalize|escape:'htmlall':'UTF-8'} {if !$distributionCenter['Numero']|is_array}{$distributionCenter['Numero']|trim|lower|capitalize|escape:'htmlall':'UTF-8'}{/if}<br/>
                    {if (!$distributionCenter['Piso']|is_array && $distributionCenter['Piso']|trim) != ''}
                        {l s='Piso' mod='rg_ocaepak'} :
                        {$distributionCenter['Piso']|trim|lower|capitalize|escape:'htmlall':'UTF-8'}<br/>
                    {/if}
                    {$distributionCenter['Localidad']|trim|lower|capitalize|escape:'htmlall':'UTF-8'},
                    {$distributionCenter['Provincia']|trim|lower|capitalize|escape:'htmlall':'UTF-8'}
                </dd>
                <dt>{l s='Código Postal Sucursal' mod='rg_ocaepak'}</dt>
                <dd>{$distributionCenter['CodigoPostal']|trim|escape:'htmlall':'UTF-8'}</dd>
            </dl>
        {/if}
    </div>
</div>
