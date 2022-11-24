
<div class="form-group">
    <h4 style="display: inline-block; margin-right: 16px;">{l s='Dirección Completa' mod='ocaepak'}
    {if $oca_geocoded}<abbr title="{l s='Dirección geodecodificada satisfactoriamente' mod='ocaepak'}">*</abbr>{/if}:
    </h4>
    <br>{$oca_order_address->address1|escape:'htmlall':'UTF-8'}
    <br>{$oca_order_address->address2|escape:'htmlall':'UTF-8'}
    <br>{$oca_order_address->city|escape:'htmlall':'UTF-8'}
    <br>{$oca_order_address->other|escape:'htmlall':'UTF-8'}
</div>
