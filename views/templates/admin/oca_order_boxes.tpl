
<div class="panel">
    {if empty($oca_boxes)}
        <div class="alert alert-danger">{l s='Es necesario agregar al menos una caja para generar ordenes' mod='ocaepak'}</div>
    {/if}
    {foreach $oca_boxes as $ind=>$box}
        <div class="form-group">
            <h4 style="display: inline-block; margin-right: 16px;">{l s='Caja' mod='ocaepak'}: {$box['l']|escape:'htmlall':'UTF-8'}cm×{$box['d']|escape:'htmlall':'UTF-8'}cm×{$box['h']|escape:'htmlall':'UTF-8'}cm</h4>
            {l s='Cantidad' mod='ocaepak'}: <input type="number" name="oca-box-q-{$ind|escape:'htmlall':'UTF-8'}" id="oca-box-q-{$ind|escape:'htmlall':'UTF-8'}" min="0" step="1" value="0" class="fixed-width-sm" style="display: inline-block;  margin-right: 16px;">
        </div>
    {/foreach}
</div>
