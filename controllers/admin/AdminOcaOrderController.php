<?php
/**
* Copyright 2022 Region Global
*
* Licensed under the Apache License, Version 2.0 (the "License");
* you may not use this file except in compliance with the License.
* You may obtain a copy of the License at
*
* http://www.apache.org/licenses/LICENSE-2.0
*
* Unless required by applicable law or agreed to in writing, software
* distributed under the License is distributed on an "AS IS" BASIS,
* WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
* See the License for the specific language governing permissions and
* limitations under the License.
*
* @author Region Global
* @copyright 2022 Region Global 
* @license http://www.apache.org/licenses/LICENSE-2.0
*/
*/
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
