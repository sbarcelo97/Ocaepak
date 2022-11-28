
<script>
var ocaRelaysText =('{$ocaepak_relays|@json_encode|escape:'quotes':'UTF-8'}').replaceAll('&quot;','"');
var ocaRelays = JSON.parse(ocaRelaysText);
{*var ocaRelayUrl = '{$link->getModuleLink($ocaepak_name, 'relay', [], $force_ssl)|escape:'quotes':'UTF-8' nofilter}';*}
var ocaRelaysCarriersText =('{$relayed_carriers}').replaceAll('&quot;','"');
var ocaRelayCarriers = JSON.parse(ocaRelaysCarriersText);
{if isset($ocaepak_states)}
    var ocaStatesText=(('{$ocaepak_states|@json_encode|escape:'quotes':'UTF-8'}').replaceAll('&quot;','"'))
    var ocaStates = JSON.parse('{$ocaepak_states|@json_encode|escape:'quotes':'UTF-8' nofilter}');
{/if}
var ocaGmapsKey = '{$gmaps_api_key|escape:'htmlall':'UTF-8'}';
var ocaBranchSelType = '{$ocaepak_branch_sel_type|escape:'htmlall':'UTF-8'}';
</script>
