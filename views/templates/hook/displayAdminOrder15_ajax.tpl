
<div class="row">
    <div class="col-xs-6">
        {if !empty($quoteError)}
            <div class="alert alert-danger">
                {$quoteError|trim|escape:'htmlall':'UTF-8'}
            </div>
        {/if}
        {l s='Operative' mod='rg_ocaepak'}: <b>{$operative->reference|trim|escape:'htmlall':'UTF-8'} ({$operative->type|trim|escape:'htmlall':'UTF-8'}{if $operative->insured} {l s='Insured' mod='rg_ocaepak'}{/if})</b><br/>
        {l s='Calculated Order Weight' mod='rg_ocaepak'}: <b>{$cartData['weight']|trim|escape:'htmlall':'UTF-8'} kg</b><br/>
        {l s='Calculated Order Volume (with padding)' mod='rg_ocaepak'}: <b>{$cartData['volume']|trim|escape:'htmlall':'UTF-8'} m³</b><br/>
        {if !empty($quoteData)}
            {l s='Delivery time estimate' mod='rg_ocaepak'}: <b>{$quoteData->PlazoEntrega|trim|escape:'htmlall':'UTF-8'} {l s='working days' mod='rg_ocaepak'}</b><br/>
                {/if}
        {if ($paidFee != 0)}
            {l s='Additional fee' mod='rg_ocaepak'}: <b>{$currencySign|trim|escape:'htmlall':'UTF-8'}{$paidFee|trim|escape:'htmlall':'UTF-8'}</b><br/>
            {/if}
        {if $quote}
            {l s='Live quote' mod='rg_ocaepak'}: <b>{$currencySign|trim|escape:'htmlall':'UTF-8'}{$quote|trim|escape:'htmlall':'UTF-8'}</b><br/><br/>
            {/if}
    </div>
    <div class="col-xs-6">
        {if !empty($distributionCenter)}
            {l s='Delivery branch selected by customer' mod='rg_ocaepak'}: <b>{$distributionCenter['Sucursal']|trim|lower|capitalize|escape:'htmlall':'UTF-8'}</b><br/>
            {l s='Branch ID' mod='rg_ocaepak'}: <b>{$distributionCenter['IdCentroImposicion']|trim|escape:'htmlall':'UTF-8'}</b><br/>
        {l s='Branch Code' mod='rg_ocaepak'}: <b>{$distributionCenter['Sigla']|trim|escape:'htmlall':'UTF-8'}</b><br/>
        {l s='Branch Address' mod='rg_ocaepak'}: <br/>
            <b>
                {$distributionCenter['Calle']|trim|lower|capitalize|escape:'htmlall':'UTF-8'} {$distributionCenter['Numero']|trim|lower|capitalize|escape:'htmlall':'UTF-8'}<br/>
                {if ($distributionCenter['Piso']|trim) != ''}
                    {l s='Floor' mod='rg_ocaepak'} :
                    {$distributionCenter['Piso']|trim|lower|capitalize|escape:'htmlall':'UTF-8'}<br/>
                {/if}
                {$distributionCenter['Localidad']|trim|lower|capitalize|escape:'htmlall':'UTF-8'},
                {$distributionCenter['Provincia']|trim|lower|capitalize|escape:'htmlall':'UTF-8'}
        </b><br/>
        {l s='Branch Post Code' mod='rg_ocaepak'}: <b>{$distributionCenter['CodigoPostal']|trim|escape:'htmlall':'UTF-8'}</b><br/>
        {/if}
    </div>
</div>
