<?php

use RgOcaEpak\Classes\OcaEpakOrder;

class AdminOcaOrderController extends ModuleAdminController
{
    public function ajaxProcessSticker()
    {
        $ocaOrder = new OcaEpakOrder((int) Tools::getValue('id_oca_order'));
        $sticker = $this->module->executeWebservice(
            'GetHtmlDeEtiquetasPorOrdenOrNumeroEnvio',
            ['idOrdenRetiro' => $ocaOrder->reference],
            true
        );
        exit(
            str_replace(
                ['<div id="etiquetas"><div style="page-break-before: always;">', "<div id='etiquetas'><div style='page-break-before: always;'>"],
                ['<div id="etiquetas"><div>', "<div id='etiquetas'><div>"],
                $sticker)
        );
    }
}
