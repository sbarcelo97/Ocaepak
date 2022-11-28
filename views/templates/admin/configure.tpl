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
{if $psver < 1.6}{literal}
    <style>
        .panel {
            border: 1px solid #CCCED7;
            padding: 6px;
        }
        .panel-heading {
            color: #585a69;
            text-shadow: 0 1px 0 #fff;
            font-weight: bold;
            font-size: 14px;
        }
        .icon-trash-o:before, .icon-trash:before, #content .process-icon-delete:before, #content .process-icon-uninstall:before {
            content: "\24cd";
        }
        [class^="process-icon-"] {
            display: inline-block;
            position: relative;
            bottom: 6px;
            left: 4px;
            margin: 0 auto;
            font-size: 14px;
            color: #585a69;
            float: right;
            border-left: 1px solid #CCCED7;
            border-bottom: 1px solid #CCCED7;
        }
        [class^="process-icon-"]:hover {
            background-color: #F8F8F8;
            color: #000;
        }
    </style>
{/literal}{else}{literal}
    <style>
        #form2 .chosen-container {
            width: 100% !important;
        }
    </style>
{/literal}{/if}
{literal}
    <script>
        $(document).ready(function() {
            {/literal}{if $psver < 1.6}{literal}
                var $form1 = $('#content>form').first().attr("id", "form1");
                var $form2 = $('#content>form').last().attr("id", "form2");
                var $table = $('#content>form').not($form1).not($form2).attr("id", 'ocae_operatives');
            {/literal}{else}{literal}
                var $form1 = $('#form1');
                var $form2 = $('#form2');
                var $table = $('form[id$="ocae_operatives"]');
            {/literal}{/if}{literal}
            function syncInputs(event) {
                event.data.target.find("[name='"+$(this).attr("name")+"']").val($(this).val());
                $table.find("[name='"+$(this).attr("name")+"']").val($(this).val());
            }
            $('#desc-ocae_operatives-refresh, #content>form a[href="javascript:location.reload();"]').hide();
            $('tr.filter').hide();
            $('#desc-ocae_operatives-new').bind('click', function() {
                $table.attr('action', $(this).attr('href')).submit();
                return !$table.length;
            });
            $form1.add($form2).find("input[type='hidden']").clone().appendTo($table);
            $form1.find("input[type='text']").bind('change', {target: $form2}, syncInputs);
            $form2.find("input[type='text']").bind('change', {target: $form1}, syncInputs);
        });
    </script>
{/literal}
