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
<div class="panel" id="oca-box-1">
    <div class="panel-heading">
        {l s='Caja' mod='rg_ocaepak'} <span class="num">1</span>
        <span class="panel-heading-action">
            <a class="list-toolbar-btn box-delete">
                <span title="{l s='Borrar' mod='rg_ocaepak'}">
                    <i class="process-icon-delete"></i>
                </span>
            </a>
        </span>
    </div>
    <div class="form-group">
        <label for="boxes-box" class="control-label col-lg-3 ">
            {l s='Dimensiones' mod='rg_ocaepak'}
        </label>
        <div class="col-lg-9">
            <input type="text" name="oca-box-l-1" id="oca-box-l-1" value="" class="fixed-width-sm" style="display: inline-block;" size="8"> cm ×
            <input type="text" name="oca-box-d-1" id="oca-box-d-1" value="" class="fixed-width-sm" style="display: inline-block;" size="8"> cm ×
            <input type="text" name="oca-box-h-1" id="oca-box-h-1" value="" class="fixed-width-sm" style="display: inline-block;" size="8"> cm
        </div>
    </div>
    <div class="form-group">
        <label for="boxes-box" class="control-label col-lg-3 ">
            {l s='Peso máximo del contenido' mod='rg_ocaepak'}
        </label>
        <div class="col-lg-9">
            <input type="text" name="oca-box-xw-1" id="oca-box-xw-1" value="" class="fixed-width-sm" style="display: inline-block;" size="8"> kg
        </div>
    </div>
</div>
<div class="row-margin-bottom row-margin-top order_action margin-form">
    <button id="add_oca_box" class="btn btn-default" type="button">
        <i class="icon-plus"></i>
        {l s='Agregar caja' mod='rg_ocaepak'}
    </button>
</div>
{literal}<script>
    (function() {
        var $box = $('#oca-box-1');
        var boxnum = 1;
        var boxesJson = [];
        var container = $box.prop('outerHTML');
        $('#add_oca_box').click(function() {
            boxnum += 1;
            var $newbox = $('#oca-box-1').clone().attr('id', 'oca-box-'+boxnum);
            $newbox.find('input').each(function(){
                var split = $(this).attr('name').lastIndexOf('-')+1;
                $(this).attr('name', $(this).attr('name').substr(0,split)+boxnum);
                $(this).attr('id', $(this).attr('id').substr(0,split)+boxnum);
            });
            $newbox.find('.panel-heading>span.num').html(boxnum);
            $('#add_oca_box').parent().before($newbox);
            serializeBoxes();
        });
        $(document).on("change", '[id^="oca-box-"] input', function(event) {
            serializeBoxes();
        });
        $(document).on("click", '.box-delete', function(event) {
            var $div = $(this).closest('div[id^="oca-box-"]');
            var split = $div.attr('id').lastIndexOf('-')+1;
            var num = $div.attr('id').substr(split);
            boxesJson.splice(num-1, 1);
            $.grep(boxesJson,function(n){ return(n) });     //fix js null elements in array quirk
            $('input[name="boxes"]').val(JSON.stringify(boxesJson, null, 2));
            renderBoxes();
        });

        function serializeBoxes() {
            boxesJson = [];
            $('[id^="oca-box-"]').each(function () {
                var split = $(this).attr('id').lastIndexOf('-')+1;
                var num = $(this).attr('id').substr(split);
                var dimensions = [
                    $('input[name="oca-box-l-'+num+'"]').val() || 0,
                    $('input[name="oca-box-d-'+num+'"]').val() || 0,
                    $('input[name="oca-box-h-'+num+'"]').val() || 0
                ];
                dimensions.sort(function(a, b){ return b-a; });
                boxesJson[num-1] = {
                    l: dimensions.shift(),
                    d: dimensions.shift(),
                    h: dimensions.shift(),
                    xw: $('input[name="oca-box-xw-'+num+'"]').val() || 0
                };
            });
            $('input[name="boxes"]').val(JSON.stringify(boxesJson, null, 2));
        }

        function renderBoxes() {
            $('[id^="oca-box-"]').remove();
            boxesJson = JSON.parse($('input[name="boxes"]').val());
            $.each(boxesJson, function (index, value) {
                var $newbox = $(container);
                $newbox.attr('id', 'oca-box-'+(1+index));
                $newbox.find('input[name="oca-box-l-1"]').attr('name', 'oca-box-l-'+(1+index)).val(value.l);
                $newbox.find('input[name="oca-box-d-1"]').attr('name', 'oca-box-d-'+(1+index)).val(value.d);
                $newbox.find('input[name="oca-box-h-1"]').attr('name', 'oca-box-h-'+(1+index)).val(value.h);
                $newbox.find('input[name="oca-box-xw-1"]').attr('name', 'oca-box-xw-'+(1+index)).val(value.xw);
                $newbox.find('.panel-heading>span.num').html(1+index);
                $('#add_oca_box').parent().before($newbox);
            });
            boxnum = boxesJson.length || 1;
            if (boxesJson.length == 0)
                $('#add_oca_box').parent().before($(container));
        }
        renderBoxes();

        $('input[name=oca_admissions]').change(toggleAdmission);
        toggleAdmission();
        $('input[name=oca_pickups]').change(togglePickup);
        togglePickup();
        function toggleAdmission() {
            if ($('input[name=oca_admissions]:checked').val() == 1) {
                $('[id ^= "fieldset_admissions"]').show();
                $('[id ^= "fieldset_packaging"]').show();
            } else {
                $('[id ^= "fieldset_admissions"]').hide();
                if (!parseInt($('input[name=oca_pickups]:checked').val()))
                    $('[id ^= "fieldset_packaging"]').hide();
            }
        }
        function togglePickup() {
            if ($('input[name=oca_pickups]:checked').val() == 1) {
                $('[id ^= "fieldset_pickups"]').show();
                $('[id ^= "fieldset_packaging"]').show();
            } else {
                $('[id ^= "fieldset_pickups"]').hide();
                if (!parseInt($('input[name=oca_admissions]:checked').val()))
                    $('[id ^= "fieldset_packaging"]').hide();
            }
        }
    })();
</script>{/literal}
