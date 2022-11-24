

<div id="oca-delivery-options">
     {if $psver < 1.6}
        <h3 class="carrier_title">{l s='Selecciona la sucursal donde recibirás tu pedido:' mod='rg_ocaepak'}</h3>
    {else}
        <p class="carrier_title">{l s='Selecciona la sucursal donde recibirás tu pedido:' mod='rg_ocaepak'}</p>
    {/if}

    <div id="oca-dropdown">
        {if $ocaepak_branch_sel_type == 0}
            <div class="row">
                <div class="col-xs-12 col-sm-4 radius-input">
                    <label for="ocaStateSelect" class="col-xs-3 col-sm-4">{l s='State' mod='rg_ocaepak'}:</label>
                    <select name="oca_state" id="ocaStateSelect" class="col-xs-9 col-sm-8 chosen">
                        {foreach $ocaepak_states as $state}
                            <option value="{$state['name']}">{$state['name']}</option>
                        {/foreach}
                    </select>
                </div>
                <div class="col-xs-12 col-sm-8 radius-input">
                    <label for="ocaBranchSelect" class="col-xs-3">{l s='Branch' mod='rg_ocaepak'}:</label>
                    <select name="oca_branch" id="ocaBranchSelect" class="col-xs-9 chosen"></select>
                </div>
            </div>
        {/if}
{*        <div id="oca-map"></div>*}
        {if $ocaepak_branch_sel_type == 1}
            <div class="radius-input">
                <label for="ocaBranchSelect">{l s='Sucursal seleccionada' mod='rg_ocaepak'}:</label>
                <select name="branch" id="ocaBranchSelect" class="form-control"></select>
            </div>
        {/if}
    </div>
</div>
<hr />

<script>
var customerAddress = JSON.parse('{$customerAddress|@json_encode|escape:'quotes':'UTF-8' nofilter}');
var customerStateCode = '{$customerStateCode|escape:'quotes':'UTF-8'}';
var ocaSelectedRelay = {if $ocaepak_selected_relay}{$ocaepak_selected_relay|escape:'quotes':'UTF-8'}{else}null{/if};
var ocaRelayAuto = {if $ocaepak_relay_auto}{$ocaepak_relay_auto|escape:'quotes':'UTF-8'}{else}null{/if};
if (typeof ocaEpakCallback !== 'undefined')
    ocaEpakCallback();
</script>
