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
