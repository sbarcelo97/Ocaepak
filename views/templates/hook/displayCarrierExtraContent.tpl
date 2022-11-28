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
<div id="oca-delivery-options-{$currentOcaCarrier|escape:'htmlall':'UTF-8'}">
<p class="carrier_title">{l s='Selecciona tu sucursal:' mod='rg_ocaepak'}</p>
{if $ocaepak_branch_sel_type == 0}
    <div class="row">
        <label for="ocaStateSelect-{$currentOcaCarrier|escape:'htmlall':'UTF-8'}" class="col-xs-2">{l s='Provincia' mod='rg_ocaepak'}:</label>
        <div class="col-xs-10 radius-input">
            <select name="oca_state" id="ocaStateSelect-{$currentOcaCarrier|escape:'htmlall':'UTF-8'}" class="chosen">
                {foreach $ocaepak_states as $state}
                    <option value="{$state['name']}">{$state['name']}</option>
                {/foreach}
            </select>
        </div>
    </div>
    <div class="row">
        <label for="ocaBranchSelect-{$currentOcaCarrier|escape:'htmlall':'UTF-8'}" class="col-xs-2">{l s='Sucursal' mod='rg_ocaepak'}:</label>
        <div class="col-xs-10 radius-input">
            <select name="oca_branch" id="ocaBranchSelect-{$currentOcaCarrier|escape:'htmlall':'UTF-8'}" class="chosen"></select>
        </div>
    </div>
{/if}
{*    <div id="oca-map-{$currentOcaCarrier|escape:'htmlall':'UTF-8'}"></div>*}
{if $ocaepak_branch_sel_type == 1}
    <div class="radius-input">
        <label for="ocaBranchSelect-{$currentOcaCarrier|escape:'htmlall':'UTF-8'}">{l s='Sucursal seleccionada' mod='rg_ocaepak'}:</label>
        <select name="branch" id="ocaBranchSelect-{$currentOcaCarrier|escape:'htmlall':'UTF-8'}" class="form-control"></select>
    </div>
{/if}
</div>
<hr />

<script>
{if !empty($ocaepak_relays2)}
var ocaRelays = JSON.parse(('{$ocaepak_relays2|@json_encode|escape:'quotes':'UTF-8'}').replaceAll('&quot;','"'));
var ocaRelayCarriers = JSON.parse(('{$relayed_carriers}').replaceAll('&quot;','"'));
{*var ocaRelayUrl = '{$ocaepak_relay_url|escape:'quotes':'UTF-8' nofilter}';*}
{if isset($ocaepak_states)}var ocaStates = JSON.parse(('{$ocaepak_states|@json_encode|escape:'quotes':'UTF-8'}').replaceAll('&quot;','"'));{/if}
var ocaGmapsKey = '{$gmaps_api_key|escape:'htmlall':'UTF-8'}';
var ocaBranchSelType = '{$ocaepak_branch_sel_type|escape:'htmlall':'UTF-8'}';
{/if}
var customerAddress = JSON.parse(('{$customerAddress|@json_encode|escape:'quotes':'UTF-8'}').replaceAll('&quot;','"'));
var customerStateCode = '{$customerStateCode|escape:'quotes':'UTF-8'}';
var ocaSelectedRelay = {if $ocaepak_selected_relay}{$ocaepak_selected_relay|escape:'quotes':'UTF-8'}{else}null{/if};
var ocaRelayAuto = {if $ocaepak_relay_auto}{$ocaepak_relay_auto|escape:'quotes':'UTF-8'}{else}null{/if};
if ((typeof ocaeInitialized !== 'undefined') && ocaeInitialized && ($('#oca-map-{$currentOcaCarrier|escape:'htmlall':'UTF-8'}').length && !$('#oca-map-{$currentOcaCarrier|escape:'htmlall':'UTF-8'}').children().length))
ocaeInit();
</script>
