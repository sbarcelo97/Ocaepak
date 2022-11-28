<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */


if (!defined('_PS_VERSION_')) {
    exit;
}
use PrestaShop\PrestaShop\Adapter\SymfonyContainer;
use RgOcaEpak\Classes\OcaCarrierTools;
use RgOcaEpak\Classes\OcaEpakBranches;
use RgOcaEpak\Classes\OcaEpakOperative;
use RgOcaEpak\Classes\OcaEpakOrder;
use RgOcaEpak\Classes\OcaEpakQuote;
use RgOcaEpak\Classes\OcaEpakRelay;

class Rg_OcaEpak extends CarrierModule
{
    const MODULE_NAME = 'rg_ocaepak';                  // DON'T CHANGE!!
    const CONFIG_PREFIX = 'RG_OCAEPAK_';               // prefix for all internal config constants
    const CARRIER_NAME = 'Oca ePak';                // Carrier name string
    const CARRIER_DELAY = '2 a 8 días hábiles';     // Carrier default delay string
    const OPERATIVES_TABLE = 'ocae_operatives';     // DB table for Operatives
    const OPERATIVES_ID = 'id_ocae_operatives';     // DB table id for Operatives
    const BRANCHES_TABLE = 'ocae_branches';         // DB table for branches
    const ORDERS_TABLE = 'ocae_orders';             // DB table for orders
    const ORDERS_ID = 'id_ocae_orders';             // DB table id for orders
    const QUOTES_TABLE = 'ocae_quotes';             // DB table for quotes
    const RELAYS_TABLE = 'ocae_relays';             // DB table for relays
    const RELAYS_ID = 'id_ocae_relays';             // DB table id for relays
    const TRACKING_URL = 'https://www1.oca.com.ar/OEPTrackingWeb/trackingenvio.asp?numero1=@';
    const OCA_URL = 'http://webservice.oca.com.ar/ePak_tracking/Oep_TrackEPak.asmx?wsdl';
    const OCA_PREVIOUS_URL = 'http://webservice.oca.com.ar/oep_tracking/Oep_Track.asmx?WSDL';
    const OCA_SERVICE_ADMISSION = 1;
    const OCA_SERVICE_DELIVERY = 2;
    const OCA_WEBSERVICE_CODE_ERROR = 130;
    const OCA_WEBSERVICE_CODE_ORDER_DELETED = 100;

    const PADDING = 0;          // space to add around the cart for volume calculations, in cm
    const LOG_DEBUG = true;
    public $branchStateNames = [
        'Ciudad de Buenos Aires' => 'CAPITAL FEDERAL',
        'Santiago del Estero' => 'SGO. DEL ESTERO',
    ];

    public $id_carrier;
    private $soapClients = [];
    protected $guiHeader = '';
    private $boxes = [];

    public function __construct()
    {
        $this->name = 'rg_ocaepak';            // DON'T CHANGE!!
        $this->tab = 'shipping_logistics';
        $this->version = '1.0.0';
        $this->author = 'Region Global';
        $this->need_instance = 1;
        $this->ps_versions_compliancy = ['min' => '1.7', 'max' => '1.7'];
        $this->bootstrap = true;
        if (!class_exists('OcaCarrierTools')) {
            include_once _PS_MODULE_DIR_ . "{$this->name}/src/Classes/OcaCarrierTools.php";
        }
        include_once _PS_MODULE_DIR_ . "{$this->name}/src/Classes/OcaEpakOperative.php";
        include_once _PS_MODULE_DIR_ . "{$this->name}/src/Classes/OcaEpakOrder.php";
        include_once _PS_MODULE_DIR_ . "{$this->name}/src/Classes/OcaEpakQuote.php";
        include_once _PS_MODULE_DIR_ . "{$this->name}/src/Classes/OcaEpakRelay.php";
        include_once _PS_MODULE_DIR_ . "{$this->name}/src/Classes/OcaEpakBranches.php";

        parent::__construct();
        $this->displayName = 'OCA e-Pak';
        $this->description = $this->l('Ofrece a tus clientes calculo del costo de envío en tiempo real');
        $this->confirmUninstall = $this->l('Borrará todas las configuraciones del módulo. Continuar?');
        $warnings = [];
        if (
            !Tools::strlen(Configuration::get(self::CONFIG_PREFIX . 'CUIT'))
            || !Tools::strlen(Configuration::get(self::CONFIG_PREFIX . 'ACCOUNT'))
        ) {
            array_push($warnings, $this->l('Necesitas configurar el módulo.'));
        }
        $this->warning = implode(' | ', $warnings);
    }

    /**
     * @throws PrestaShopException
     * @throws Exception
     */
    public function install()
    {
        // make translator aware of strings used in models
        $this->l('El formato del costo adicional es incorrecto. Debes ser un monto fijo, como 7.50, o un porcentaje, como 6.99%', 'OcaEpak');
        if (!extension_loaded('soap')) {
            $this->_errors[] = $this->l('Tienes la extensión SOAP de PHP deshabilitada. Este módulo lo requiere para conectarse con los webservices de OCA.');
        }
        if (count($this->_errors)) {
            return false;
        }

        $db = Db::getInstance();

        return
            $db->Execute(
                OcaCarrierTools::interpolateSqlFile($this->name, 'create-operatives-table', [
                    '{$DB_PREFIX}' => _DB_PREFIX_,
                    '{$TABLE_NAME}' => self::OPERATIVES_TABLE,
                    '{$TABLE_ID}' => self::OPERATIVES_ID,
                ])
            ) and
            $db->Execute(
                OcaCarrierTools::interpolateSqlFile($this->name, 'create-quotes-table', [
                    '{$DB_PREFIX}' => _DB_PREFIX_,
                    '{$TABLE_NAME}' => self::QUOTES_TABLE,
                ])
            ) and
            $db->Execute(
                OcaCarrierTools::interpolateSqlFile($this->name, 'create-relays-table', [
                    '{$DB_PREFIX}' => _DB_PREFIX_,
                    '{$TABLE_NAME}' => self::RELAYS_TABLE,
                    '{$TABLE_ID}' => self::RELAYS_ID,
                ])
            ) and
            $db->Execute(
                OcaCarrierTools::interpolateSqlFile($this->name, 'create-orders-table', [
                    '{$DB_PREFIX}' => _DB_PREFIX_,
                    '{$TABLE_NAME}' => self::ORDERS_TABLE,
                    '{$TABLE_ID}' => self::ORDERS_ID,
                ])
            ) and
            $db->Execute(
                OcaCarrierTools::interpolateSqlFile($this->name, 'create-branches-table', [
                    '{$DB_PREFIX}' => _DB_PREFIX_,
                    '{$TABLE_NAME}' => self::BRANCHES_TABLE,
                ])
            ) and
            parent::install() and
            $this->registerHook('displayCarrierExtraContent') and
            $this->registerHook('displayAdminOrder') and
            $this->registerHook('displayOrderDetail') and
            $this->registerHook('actionAdminPerformanceControllerBefore') and
            $this->registerHook('displayHeader') and
            $this->registerHook('actionOrderGridDefinitionModifier') and
            $this->registerHook('actionAdminControllerSetMedia') and
            $this->registerHook('actionValidateStepComplete') and

            Configuration::updateValue(self::CONFIG_PREFIX . 'ACCOUNT', '') and
            Configuration::updateValue(self::CONFIG_PREFIX . 'EMAIL', '') and
            Configuration::updateValue(self::CONFIG_PREFIX . 'PASSWORD', '') and
            Configuration::updateValue(self::CONFIG_PREFIX . 'CUIT', '') and
            Configuration::updateValue(self::CONFIG_PREFIX . 'DEFWEIGHT', '0.25') and
            Configuration::updateValue(self::CONFIG_PREFIX . 'DEFVOLUME', '0.125') and
            Configuration::updateValue(self::CONFIG_PREFIX . 'FAILCOST', '63.37') and
            Configuration::updateValue(self::CONFIG_PREFIX . 'POSTCODE', '') and
            Configuration::updateValue(self::CONFIG_PREFIX . 'STREET', '') and
            Configuration::updateValue(self::CONFIG_PREFIX . 'NUMBER', '') and
            Configuration::updateValue(self::CONFIG_PREFIX . 'FLOOR', '') and
            Configuration::updateValue(self::CONFIG_PREFIX . 'APARTMENT', '') and
            Configuration::updateValue(self::CONFIG_PREFIX . 'LOCALITY', '') and
            Configuration::updateValue(self::CONFIG_PREFIX . 'PROVINCE', '') and
            Configuration::updateValue(self::CONFIG_PREFIX . 'CONTACT', '') and
            Configuration::updateValue(self::CONFIG_PREFIX . 'REQUESTOR', '') and
            Configuration::updateValue(self::CONFIG_PREFIX . 'OBSERVATIONS', '') and
            Configuration::updateValue(self::CONFIG_PREFIX . 'BOXES', '') and
            Configuration::updateValue(self::CONFIG_PREFIX . 'TIMESLOT', '') and
            Configuration::updateValue(self::CONFIG_PREFIX . 'BRANCH_SEL_TYPE', '0') and
            Configuration::updateValue(self::CONFIG_PREFIX . 'ADMISSION_BRANCH', '') and
            Configuration::updateValue(self::CONFIG_PREFIX . 'ADMISSIONS_ENABLED', false) and
            Configuration::updateValue(self::CONFIG_PREFIX . 'PICKUPS_ENABLED', false);
    }

    public function hookactionValidateStepComplete($params)
    {
        if (($branch = $params['request_params']['branch']) != null) {
            if (!$this->isValidBranch($branch)) {
                OcaEpakBranches::remove($branch);
                $this->context->controller->errors[] = $this->trans('La sucursal seleccionada no está disponible, por favor elegí otra', [], 'Modules.Rgocaepak.Errors');
                $params['completed'] = false;
            } else {
                OcaEpakBranches::markasvalid($branch);
                $this->saveRelay($branch, $params['cart']->id);
            }
        }
    }

    public function saveRelay($branch, $id_cart)
    {
        $relay = new OcaEpakRelay();
        $relay->id_cart = $id_cart;
        $relay->distribution_center_id = (int) $branch;
        $relay->auto = 0;
        $relay->save();
    }

    /**
     * @throws PrestaShopException
     * @throws PrestaShopDatabaseException
     */
    public function uninstall()
    {
        $db = Db::getInstance();
        OcaEpakOperative::purgeCarriers();

        return
            parent::uninstall()
            and $db->Execute(
                'DROP TABLE IF EXISTS ' . pSQL(_DB_PREFIX_ . self::OPERATIVES_TABLE)
            ) and $db->Execute(
                'DROP TABLE IF EXISTS ' . pSQL(_DB_PREFIX_ . self::QUOTES_TABLE)
            ) and
            /*$db->Execute(
                'DROP TABLE IF EXISTS '.pSQL(_DB_PREFIX_.self::RELAYS_TABLE)
            ) AND*/
            $db->Execute(
                'DROP TABLE IF EXISTS ' . pSQL(_DB_PREFIX_ . self::BRANCHES_TABLE)
            ) and
            Configuration::deleteByName(self::CONFIG_PREFIX . 'ACCOUNT') and
            Configuration::deleteByName(self::CONFIG_PREFIX . 'EMAIL') and
            Configuration::deleteByName(self::CONFIG_PREFIX . 'PASSWORD') and
            Configuration::deleteByName(self::CONFIG_PREFIX . 'CUIT') and
            Configuration::deleteByName(self::CONFIG_PREFIX . 'DEFWEIGHT') and
            Configuration::deleteByName(self::CONFIG_PREFIX . 'DEFVOLUME') and
            Configuration::deleteByName(self::CONFIG_PREFIX . 'FAILCOST') and
            Configuration::deleteByName(self::CONFIG_PREFIX . 'POSTCODE') and
            Configuration::deleteByName(self::CONFIG_PREFIX . 'STREET') and
            Configuration::deleteByName(self::CONFIG_PREFIX . 'NUMBER') and
            Configuration::deleteByName(self::CONFIG_PREFIX . 'FLOOR') and
            Configuration::deleteByName(self::CONFIG_PREFIX . 'APARTMENT') and
            Configuration::deleteByName(self::CONFIG_PREFIX . 'LOCALITY') and
            Configuration::deleteByName(self::CONFIG_PREFIX . 'PROVINCE') and
            Configuration::deleteByName(self::CONFIG_PREFIX . 'CONTACT') and
            Configuration::deleteByName(self::CONFIG_PREFIX . 'REQUESTOR') and
            Configuration::deleteByName(self::CONFIG_PREFIX . 'OBSERVATIONS') and
            Configuration::deleteByName(self::CONFIG_PREFIX . 'BOXES') and
            Configuration::deleteByName(self::CONFIG_PREFIX . 'TIMESLOT') and
            Configuration::deleteByName(self::CONFIG_PREFIX . 'ADMISSION_BRANCH') and
            Configuration::deleteByName(self::CONFIG_PREFIX . 'BRANCH_SEL_TYPE') and
            Configuration::deleteByName(self::CONFIG_PREFIX . 'ADMISSIONS_ENABLED') and
            Configuration::deleteByName(self::CONFIG_PREFIX . 'PICKUPS_ENABLED') and
            Configuration::deleteByName(self::CONFIG_PREFIX . 'ORDER_DAYS')
        ;
    }

    /**
     * @throws PrestaShopException
     * @throws PrestaShopDatabaseException
     * @throws SmartyException
     */
    public function getContent()
    {
        Tools::redirectAdmin(SymfonyContainer::getInstance()->get('router')->generate('admin_rg_ocaepak_index'));
    }

    public function getAdmissionBranches()
    {
        try {
            $impositionCenters = $this->executeWebservice('GetCentrosImposicionConServicios');
            foreach ($impositionCenters as $k => $obj) {
                $admits = false;
                foreach ($obj->Servicios->Servicio as $serv) {
                    if ((string) $serv->IdTipoServicio === '1') {
                        $admits = true;
                    }
                }
                if (!$admits) {
                    continue;
                }
                $icFields[(string) $obj->CodigoPostal . '.' . $k] = [
                    'text' => (string) $obj->Sucursal . ': ' . (string) $obj->Calle . ' ' . (string) $obj->Numero . ', ' . (string) $obj->Localidad . ', ' . (string) $obj->Provincia . ' | CP: ' . (string) $obj->CodigoPostal,
                    'value' => (string) $obj->IdCentroImposicion,
                ];
            }
            ksort($icFields);

            return $icFields;
        } catch (Exception $e) {
            $this->guiAddToHeader($this->displayError($this->l('Error obteniendo sucursales OCA')));

            return null;
        }
    }

    public function hookactionOrderGridDefinitionModifier($params)
    {
        $params['definition']->getBulkActions()
        ->add(
            (new \PrestaShop\PrestaShop\Core\Grid\Action\Bulk\Type\SubmitBulkAction('imprimir'))
                ->setName('Imprimir Etiquetas Oca')
                ->setOptions([
                    //  in most cases submit action should be implemented by module
                    'submit_route' => 'admin_rg_ocaepak_orders_print',
                    'route_params' => ['order_id' => 'order_id'],
                ])
        );
    }

    /**
     * @throws SmartyException
     */
    public function getConfigFormValues()
    {
        return [
            self::CONFIG_PREFIX . 'ACCOUNT' => Tools::getValue('account', Configuration::get(self::CONFIG_PREFIX . 'ACCOUNT')),
            self::CONFIG_PREFIX . 'EMAIL' => Tools::getValue('email', Configuration::get(self::CONFIG_PREFIX . 'EMAIL')),
            self::CONFIG_PREFIX . 'PASSWORD' => Tools::getValue('password', Configuration::get(self::CONFIG_PREFIX . 'PASSWORD')),
            self::CONFIG_PREFIX . 'CUIT' => Tools::getValue('cuit', Configuration::get(self::CONFIG_PREFIX . 'CUIT')),
            self::CONFIG_PREFIX . 'DEFWEIGHT' => Tools::getValue('defweight', Configuration::get(self::CONFIG_PREFIX . 'DEFWEIGHT')),
            self::CONFIG_PREFIX . 'DEFVOLUME' => Tools::getValue('defvolume', Configuration::get(self::CONFIG_PREFIX . 'DEFVOLUME')),
            self::CONFIG_PREFIX . 'POSTCODE' => Tools::getValue('postcode', Configuration::get(self::CONFIG_PREFIX . 'POSTCODE')),
            self::CONFIG_PREFIX . 'BRANCH_SEL_TYPE' => Tools::getValue('branch_sel_type', Configuration::get(self::CONFIG_PREFIX . 'BRANCH_SEL_TYPE')),
            self::CONFIG_PREFIX . 'FAILCOST' => Tools::getValue('failcost', Configuration::get(self::CONFIG_PREFIX . 'FAILCOST')),
            self::CONFIG_PREFIX . 'ADMISSIONS_ENABLED' => Tools::getValue('oca_admissions', Configuration::get(self::CONFIG_PREFIX . 'ADMISSIONS_ENABLED')),
            self::CONFIG_PREFIX . 'PICKUPS_ENABLED' => Tools::getValue('oca_pickups', Configuration::get(self::CONFIG_PREFIX . 'PICKUPS_ENABLED')),
            self::CONFIG_PREFIX . 'STREET' => Tools::getValue('street', Configuration::get(self::CONFIG_PREFIX . 'STREET')),
            self::CONFIG_PREFIX . 'NUMBER' => Tools::getValue('number', Configuration::get(self::CONFIG_PREFIX . 'NUMBER')),
            self::CONFIG_PREFIX . 'FLOOR' => Tools::getValue('floor', Configuration::get(self::CONFIG_PREFIX . 'FLOOR')),
            self::CONFIG_PREFIX . 'APARTMENT' => Tools::getValue('apartment', Configuration::get(self::CONFIG_PREFIX . 'APARTMENT')),
            self::CONFIG_PREFIX . 'LOCALITY' => Tools::getValue('locality', Configuration::get(self::CONFIG_PREFIX . 'LOCALITY')),
            self::CONFIG_PREFIX . 'PROVINCE' => Tools::getValue('province', Configuration::get(self::CONFIG_PREFIX . 'PROVINCE')),
            self::CONFIG_PREFIX . 'CONTACT' => Tools::getValue('contact', Configuration::get(self::CONFIG_PREFIX . 'CONTACT')),
            self::CONFIG_PREFIX . 'REQUESTOR' => Tools::getValue('requestor', Configuration::get(self::CONFIG_PREFIX . 'REQUESTOR')),
            self::CONFIG_PREFIX . 'OBSERVATIONS' => Tools::getValue('observations', Configuration::get(self::CONFIG_PREFIX . 'OBSERVATIONS')),
            self::CONFIG_PREFIX . 'TIMESLOT' => Tools::getValue('timeslot', Configuration::get(self::CONFIG_PREFIX . 'TIMESLOT') ? Configuration::get(self::CONFIG_PREFIX . 'TIMESLOT') : 1),
            self::CONFIG_PREFIX . 'COSTCENTER' => Tools::getValue('costcenter', Configuration::get(self::CONFIG_PREFIX . 'COSTCENTER') ? Configuration::get(self::CONFIG_PREFIX . 'COSTCENTER') : 1),
            self::CONFIG_PREFIX . 'ADMISSION_BRANCH' => Tools::getValue('branch', Configuration::get(self::CONFIG_PREFIX . 'ADMISSION_BRANCH') ? Configuration::get(self::CONFIG_PREFIX . 'ADMISSION_BRANCH') : 39),
            self::CONFIG_PREFIX . 'BOXES' => Tools::getValue('boxes', Configuration::get(self::CONFIG_PREFIX . 'BOXES') ? Configuration::get(self::CONFIG_PREFIX . 'BOXES') : '[]'),
            self::CONFIG_PREFIX . 'ORDERDAYS' => Tools::getValue('days', Configuration::get(self::CONFIG_PREFIX . 'ORDERDAYS') ? Configuration::get(self::CONFIG_PREFIX . 'ORDERDAYS') : 1),
        ];
    }

    public function hookactionAdminControllerSetMedia()
    {
        $this->context->controller->addJS(_PS_MODULE_DIR_ . $this->name . '/views/js/adminjs.js');
    }

    /**
     * @throws SmartyException
     */
    protected function validateConfig($boxes)
    {
        $error = [];

        if (!OcaEpakOperative::isCurrentlyUsed(Rg_OcaEpak::OPERATIVES_TABLE)) {
            $error[] = $this->l('Necesitas utilizar al menos una operativa');
        }

        foreach ($boxes as $box) {
            if (($box['l'] + $box['d'] + $box['h'] + $box['xw']) > 0) {
                if (
                    !is_numeric($box['l'])
                    || !is_numeric($box['d'])
                    || !is_numeric($box['h'])
                    || !is_numeric($box['xw'])
                ) {
                    $error[] = $this->l('Algunas de las cajas tiene dimensiones no numéricas');
                } elseif (
                    $box['l'] == 0
                    || $box['d'] == 0
                    || $box['h'] == 0
                    || $box['xw'] == 0
                ) {
                    $error[] = $this->l('Alguna de las cajas tiene dimension 0');
                } else {
                    $this->boxes[] = $box;
                }
            }
        }

        return $error;
    }

    /**
     * @throws PrestaShopException
     * @throws SmartyException
     * @throws PrestaShopDatabaseException
     */
    public function renderOrderGeneratorForm($address, $parsedAddress, $type)
    {
        $this->context->controller->addJqueryUI('ui.datepicker');
        $this->context->smarty->assign([
            'oca_boxes' => Tools::jsonDecode(Configuration::get(self::CONFIG_PREFIX . 'BOXES'), true),
            'oca_order_address' => $address,
            'oca_geocoded' => $parsedAddress['geocoded'],
        ]);
        $boxBox = $this->context->smarty->fetch($this->local_path . 'views/templates/admin/oca_order_boxes.tpl');
        $fullAddress = $this->context->smarty->fetch($this->local_path . 'views/templates/admin/oca_order_address.tpl');

        $fields_form = [
            [
                'form' => [
                    'id_form' => 'oca-form',
                    'legend' => [
                        'title' => $this->l('Verificador de direcciones OCA'),
                    ],
                    'description' => $parsedAddress['discrepancy'] ? $this->l('Por favor chequqe la información de la dirección del cliente y corrígela') : $this->l('Dirección procesada satisfactoriamente'),
                    'input' => [
                        [
                            'type' => 'free',
                            'name' => 'oca-full-address',
                        ],
                        [
                            'type' => 'text',
                            'name' => 'oca-street',
                            'label' => $this->l('Calle'),
                            'required' => true,
                        ],
                        [
                            'type' => 'text',
                            'name' => 'oca-number',
                            'label' => $this->l('Número'),
                            'required' => true,
                        ],
                        [
                            'type' => 'text',
                            'name' => 'oca-floor',
                            'label' => $this->l('Piso'),
                        ],
                        [
                            'type' => 'text',
                            'name' => 'oca-apartment',
                            'label' => $this->l('Departamento'),
                        ],
                        [
                            'type' => 'text',
                            'name' => 'oca-city',
                            'label' => $this->l('Localidad'),
                            'required' => true,
                        ],
                        [
                            'type' => 'text',
                            'name' => 'oca-state',
                            'label' => $this->l('Provincia'),
                            'required' => true,
                        ],
                        [
                            'type' => 'textarea',
                            'label' => $this->l('Observaciones'),
                            'name' => 'oca-other',
                            'cols' => '50',
                            'rows' => '2',
                        ],
                    ],
                ],
            ],
            [
                'form' => [
                    'legend' => [
                        'title' => $this->l('Generador de ordenes OCA'),
                    ],
                    'input' => [
                        [
                            'type' => 'date',
                            'label' => in_array($type, ['PaP', 'PaS']) ? $this->l('Fecha de recolección') : $this->l('Fecha de admisión'),
                            'name' => 'oca-date',
                            'class' => 'col-xs-6 datepicker',
                            'size' => 6,
                            'required' => true,
                            'desc' => in_array($type, ['PaP', 'PaS']) ? $this->l('Cuándo viene OCA por los paquetes') : $this->l('Cuándo llevarás los paquetes a OCA'),
                        ],
                        [
                            'type' => 'free',
                            'name' => 'boxes',
                            'label' => $this->l('Packaging'),
                        ],
                    ],
                    'submit' => [
                        'title' => $this->l('Generar orden'),
                        'name' => 'oca-order-submit',
                    ],
                ],
            ],
        ];
        $today = date('Y-m-d');
        $days = Configuration::get('RG_OCAEPAK_ORDERDAYS');
        $def_date = date('Y-m-d', strtotime($today . '+ ' . $days . 'days'));
        $fields_value = [
            'oca-street' => Tools::getValue('oca-street', $parsedAddress['street']),
            'oca-number' => Tools::getValue('oca-number', $parsedAddress['number']),
            'oca-floor' => Tools::getValue('oca-floor', $parsedAddress['floor']),
            'oca-apartment' => Tools::getValue('oca-apartment', $parsedAddress['apartment']),
            'oca-city' => Tools::getValue('oca-city', $parsedAddress['city']),
            'oca-state' => Tools::getValue('oca-state', $parsedAddress['state']),
            'oca-other' => Tools::getValue('oca-other', $parsedAddress['other']),
            'oca-date' => Tools::getValue('oca-date', $def_date),
            'boxes' => $boxBox,
            'oca-full-address' => $fullAddress,
        ];
        $helper = new HelperForm();
        $helper->base_folder = _PS_ADMIN_DIR_ . '/themes/default/template/helpers/form/'; // PS 1.7.7+ bug
        // $helper->module = $this;
        $helper->title = $this->displayName;
        $helper->name_controller = $this->name;
        $helper->identifier = $this->identifier;
        $helper->token = Tools::getAdminTokenLite('AdminOrders');
        $helper->currentIndex = 'index.php?controller=AdminOrders&id_order=' . Tools::getValue('id_order') . '&vieworder';
        $lang = new Language((int) Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->show_toolbar = false;
        $helper->submit_action = '';
        $helper->fields_value = $fields_value;

        return $helper->generateForm($fields_form);
    }

    public function getValidateOcaForm($cartData)
    {
        if (
            Tools::getValue('oca-date')
            && Tools::getValue('oca-street')
            && Tools::getValue('oca-number')
            && Tools::getValue('oca-city')
            && Tools::getValue('oca-state')
        ) {
            $form = [
                'street' => Tools::getValue('oca-street'),
                'number' => Tools::getValue('oca-number'),
                'floor' => Tools::getValue('oca-floor', ''),
                'apartment' => Tools::getValue('oca-apartment', ''),
                'locality' => Tools::getValue('oca-city'),
                'province' => Tools::getValue('oca-state'),
                'observations' => Tools::getValue('oca-other'),
                'date' => str_replace('-', '', Tools::getValue('oca-date')),
                'boxes' => [],
            ];
            $boxes = Tools::jsonDecode(Configuration::get(self::CONFIG_PREFIX . 'BOXES'), true);
            $boxVolume = 0;
            foreach ($boxes as $ind => $box) {
                if (!is_numeric(Tools::getValue('oca-box-q-' . $ind, 0))) {
                    $this->guiAddToHeader($this->displayError($this->l('One of the boxes has a non-numeric quantity')));

                    return false;
                }
                if (Tools::getValue('oca-box-q-' . $ind, 0) <= 0) {
                    continue;
                }
                $form['boxes'][] = [
                    'l' => number_format((float) $box['l']),
                    'd' => number_format((float) $box['d']),
                    'h' => number_format((float) $box['h']),
                    'q' => Tools::getValue('oca-box-q-' . $ind),
                ];
                $boxVolume = $boxVolume + ($box['l'] * $box['d'] * $box['h']) * Tools::getValue('oca-box-q-' . $ind);
            }
            if (count($form['boxes']) == 0) {
                $this->guiAddToHeader($this->displayError($this->l('You need to add at least one box')));

                return false;
            } else {
                foreach ($form['boxes'] as &$box) {      // split cost and weight proportionally
                    $vol = ($box['l'] * $box['d'] * $box['h']);
                    $volumePercentage = $vol / $boxVolume;
                    $box['v'] = number_format((float) $volumePercentage * $cartData['cost'], 2, '.', '');
                    $box['w'] = number_format((float) $volumePercentage * $cartData['weight'], 2, '.', '');
                }
            }

            return $form;
        } elseif ($addresinfo = OcaEpakOrder::parseOcaAddress($cartData['address'])) {
            $form = [
                'street' => $addresinfo['street'],
                'number' => $addresinfo['number'],
                'floor' => $addresinfo['floor'],
                'apartment' => $addresinfo['apartment'],
                'locality' => $addresinfo['city'],
                'province' => $addresinfo['state'],
                'observations' => $addresinfo['other'],
                'date' => str_replace('-', '', date('Y-m-d')),
                'boxes' => [],
            ];
            $boxes = Tools::jsonDecode(Configuration::get(self::CONFIG_PREFIX . 'BOXES'), true);
            $boxVolume = 0;
            foreach ($boxes as $ind => $box) {
                if (!is_numeric(Tools::getValue('oca-box-q-' . $ind, 0))) {
                    $this->guiAddToHeader($this->displayError($this->l('Una de las cajas tiene dimensiones no numéricas')));

                    return false;
                }
                if (Tools::getValue('oca-box-q-' . $ind, 0) <= 0) {
                    continue;
                }
                $form['boxes'][] = [
                    'l' => number_format((float) $box['l']),
                    'd' => number_format((float) $box['d']),
                    'h' => number_format((float) $box['h']),
                    'q' => Tools::getValue('oca-box-q-' . $ind),
                ];
                $boxVolume = $boxVolume + ($box['l'] * $box['d'] * $box['h']) * Tools::getValue('oca-box-q-' . $ind);
            }
            // for printing use default box
            $def_boxes = array_filter($boxes, function ($box) { return $box['isd']; });
            if (count($form['boxes']) == 0) {
                foreach ($def_boxes as $def_box) {
                    $form['boxes'][] = [
                        'l' => number_format((float) $def_box['l']),
                        'd' => number_format((float) $def_box['d']),
                        'h' => number_format((float) $def_box['h']),
                        'q' => 1,
                    ];
                    $boxVolume = $boxVolume + ($def_box['l'] * $def_box['d'] * $def_box['h']);
                }
            }
            foreach ($form['boxes'] as &$box) {      // split cost and weight proportionally
                $vol = ($box['l'] * $box['d'] * $box['h']);
                $volumePercentage = $vol / $boxVolume;
                $box['v'] = number_format((float) $volumePercentage * $cartData['cost'], 2, '.', '');
                $box['w'] = number_format((float) $volumePercentage * $cartData['weight'], 2, '.', '');
            }

            return $form;
        } else {
            $this->guiAddToHeader($this->displayError($this->l('Falta información en el generador de orden')));

            return false;
        }
    }

    /**
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookDisplayAdminOrder($params)
    {
        $order = new Order($params['id_order']);
        $carrier = new Carrier($order->id_carrier);
        $op = OcaEpakOperative::getByFieldId('carrier_reference', $carrier->id_reference);
        if (!$op) {
            return null;
        }
        $address = new Address($order->id_address_delivery);
        $carrier = new Carrier($order->id_carrier);
        $customer = new Customer($order->id_customer);
        $cart = new Cart($order->id_cart);
        if (in_array($op->type, ['PaS', 'SaS'])) {
            $relayId = OcaEpakRelay::getByCartId($order->id_cart)->distribution_center_id;
        } else {
            $relayId = null;
        }

        if (
            Tools::isSubmit('oca-order-cancel')
            && ($ocaOrder = OcaEpakOrder::getByFieldId('id_order', $order->id))
        ) {
            try {
                $cancel = $this->executeWebservice('AnularOrdenGenerada', [
                    'usr' => Configuration::get(Rg_OcaEpak::CONFIG_PREFIX . 'EMAIL'),
                    'psw' => Configuration::get(Rg_OcaEpak::CONFIG_PREFIX . 'PASSWORD'),
                    'IdOrdenRetiro' => $ocaOrder->reference,
                ]);
                $result = (int) $cancel->IdResult;
                $message = (string) $cancel->Mensaje;
                switch ($result) {
                    case self::OCA_WEBSERVICE_CODE_ORDER_DELETED:
                        $this->guiAddToHeader($this->displayConfirmation($message));
                        $ocaOrder->delete();
                        break;
                    case self::OCA_WEBSERVICE_CODE_ERROR:
                        $this->guiAddToHeader($this->displayError($message));
                        break;
                    default:
                        $this->guiAddToHeader($this->displayError($result . ': ' . $message));
                        break;
                }
            } catch (Exception $e) {
                if (self::LOG_DEBUG) {
                    $this->logError($e->getMessage());
                }
                $this->guiAddToHeader(Tools::displayError($this->l('Error al cancelar la orden OCA') . ': ' . $e->getMessage()));
            }
        } elseif (Tools::isSubmit('oca-order-submit')) {
            $cartData = OcaCarrierTools::getCartPhysicalData(
                $cart,
                $carrier->id,
                Configuration::get(self::CONFIG_PREFIX . 'DEFWEIGHT'),
                Configuration::get(self::CONFIG_PREFIX . 'DEFVOLUME'),
                self::PADDING
            );
            if ($preOrder = $this->getValidateOcaForm($cartData)) {
                $xmlRetiro = OcaEpakOrder::generateOrderXml(array_merge($preOrder, [
                    'address' => $address,
                    'operative' => $op,
                    'order' => $order,
                    'customer' => $customer,
                    'cost_center_id' => in_array($op->type, ['PaP', 'PaS']) ? '0' : '1',
                    'imposition_center_id' => $relayId,
                    'origin_imposition_center_id' => in_array($op->type, ['SaP', 'SaS']) ? Configuration::get(Rg_OcaEpak::CONFIG_PREFIX . 'ADMISSION_BRANCH') : false,
                    'postcode' => OcaCarrierTools::cleanPostcode($address->postcode),
                ]));
                $data = [];

                try {
                    $data = $this->executeWebservice('IngresoORMultiplesRetiros', [
                        'usr' => Configuration::get(Rg_OcaEpak::CONFIG_PREFIX . 'EMAIL'),
                        'psw' => Configuration::get(Rg_OcaEpak::CONFIG_PREFIX . 'PASSWORD'),
                        'ConfirmarRetiro' => true,
                        'xml_Datos' => $xmlRetiro,
                    ]);
                    if (!isset($data->Resumen)) {
                        throw new Exception($this->l('Error generando la orden OCA'));
                    }
                    if (isset($data->Errores)) {
                        throw new Exception($this->l('Error generando la orden OCA') . ': ' . (string) $data->Errores->Error->Descripcion);
                    }
                    $ocaOrder = new OcaEpakOrder();
                    $ocaOrder->id_order = $order->id;
                    $ocaOrder->reference = (int) $data->DetalleIngresos->OrdenRetiro;
                    $ocaOrder->tracking = (string) $data->DetalleIngresos->NumeroEnvio;
                    $ocaOrder->operation_code = (int) $data->Resumen->CodigoOperacion;
                    $ocaOrder->save();
                    if (!$order->shipping_number && $ocaOrder->tracking) {
                        $id_order_carrier = Db::getInstance()->getValue('
						    SELECT `id_order_carrier`
						    FROM `' . _DB_PREFIX_ . 'order_carrier`
						    WHERE `id_order` = ' . (int) $order->id
                        );
                        if ($id_order_carrier) {
                            $_GET['tracking_number'] = $ocaOrder->tracking;
                            $_GET['submitShippingNumber'] = 1;
                            $_GET['id_order_carrier'] = $id_order_carrier;
                            $this->context->controller->postProcess();
                        }

                        $ordercarrier = new OrderCarrier($id_order_carrier);
                        $ordercarrier->tracking_number = $ocaOrder->tracking;
                        $ordercarrier->update();
                    }
                    unset($ocaOrder);
                } catch (Exception $e) {
                    if (self::LOG_DEBUG) {
                        $this->logError($e->getMessage());
                        $this->logError($data);
                    }
                    $this->guiAddToHeader($this->displayError($e->getMessage()));
                }
            }
        }

        $ajaxUrl = str_replace('index.php', 'ajax-tab.php', $this->context->link->getAdminLink('AdminOcaEpak', true));
        $this->context->smarty->assign([
            'moduleName' => self::MODULE_NAME,
            'ocaImagePath' => Tools::getShopDomainSsl(true, true) . $this->_path . 'views/img/',
            'ocaAjaxUrl' => $ajaxUrl,
            'ocaOrderId' => $order->id,
            'ocaOrdersEnabled' => (in_array($op->type, ['SaP', 'SaS']) && Configuration::get(self::CONFIG_PREFIX . 'ADMISSIONS_ENABLED')) || (in_array($op->type, ['PaS', 'PaP']) && Configuration::get(self::CONFIG_PREFIX . 'PICKUPS_ENABLED')),
        ]);
        if ($ocaOrder = OcaEpakOrder::getByFieldId('id_order', $order->id)) {
            $stickerUrl = str_replace('index.php', 'ajax-tab.php', $this->context->link->getAdminLink('AdminOcaOrder', true)) . '&action=sticker&id_oca_order=' . $ocaOrder->id;
            try {
                $admission = $this->executeWebservice('GetORResult', [
                    'idCabecera' => $ocaOrder->operation_code,
                    'Usr' => Configuration::get(Rg_OcaEpak::CONFIG_PREFIX . 'EMAIL'),
                    'Psw' => Configuration::get(Rg_OcaEpak::CONFIG_PREFIX . 'PASSWORD'),
                ]);
                if (isset($admission->Error)) {
                    throw new Exception((string) $admission->Error->Descripcion);
                }
                $status = (string) $admission->DetalleIngresos->Estado;
                $accepts = (int) $admission->Resumen->CantidadIngresados;
                $rejects = (int) $admission->Resumen->CantidadRechazados;
            } catch (Exception $e) {
                if (self::LOG_DEBUG) {
                    $this->logError($e->getMessage());
                }
                $this->guiAddToHeader($this->displayError($e->getMessage()));
                $status = 'Error adquiriendo estado';
                $accepts = $rejects = 0;
            }
            $this->context->smarty->assign([
                'ocaStatus' => $status,
                'ocaAccepts' => $accepts,
                'ocaRejects' => $rejects,
                'ocaOrder' => $ocaOrder,
                'stickerUrl' => $stickerUrl,
                'ocaGuiHeader' => $this->guiHeader,
                'ocaOrderStatus' => 'submitted',
            ]);
            $template = Tools::str_replace_once('%HEADER_GOES_HERE%', $this->guiHeader, $this->display(__FILE__, _PS_VERSION_ < '1.6' ? 'displayAdminOrder15.tpl' : 'displayAdminOrder.tpl'));
        } elseif (
            (
                (
                    Configuration::get(self::CONFIG_PREFIX . 'ADMISSIONS_ENABLED')
                    && in_array($op->type, ['SaP', 'SaS'])
                ) || (
                    Configuration::get(self::CONFIG_PREFIX . 'PICKUPS_ENABLED')
                    && in_array($op->type, ['PaP', 'PaS'])
                )
            ) && !$order->shipping_number
        ) {
            $parsedAddress = OcaEpakOrder::parseOcaAddress($address);
            if (self::LOG_DEBUG && $parsedAddress['discrepancy']) {
                $this->logError(['Problematic address' => $address->address1 . ' | ' . $address->address2 . ' | ' . $address->city . ' | ' . $address->other . ' | ' . $address->postcode . ' | ']);
            }
            $form = $this->renderOrderGeneratorForm($address, $parsedAddress, $op->type);
            $this->context->smarty->assign([
                'ocaGuiHeader' => $this->guiHeader,
                'ocaOrderStatus' => 'unsubmitted',
            ]);
            $pretemplate = Tools::str_replace_once('%HEADER_GOES_HERE%', $this->guiHeader, $this->display(__FILE__, _PS_VERSION_ < '1.6' ? 'displayAdminOrder15.tpl' : 'displayAdminOrder.tpl'));
            $template = Tools::str_replace_once('%ORDER_GENERATOR_GOES_HERE%', $form, $pretemplate);
        } else {
            $this->context->smarty->assign([
                'ocaGuiHeader' => $this->guiHeader,
                'ocaOrderStatus' => 'disabled',
            ]);
            $template = Tools::str_replace_once('%HEADER_GOES_HERE%', $this->guiHeader, $this->display(__FILE__, _PS_VERSION_ < '1.6' ? 'displayAdminOrder15.tpl' : 'displayAdminOrder.tpl'));
        }

        return $template;
    }

    public function hookDisplayHeader($params)
    {
        if (
            $this->context->controller->php_self !== 'order'
            || $this->context->controller->page_name !== 'checkout'
        ) {
            return null;
        }
        $this->context->controller->registerStylesheet(
            'modules-ocaepak-checkout',
            'modules/' . $this->name . '/views/css/checkout.css',
            ['position' => 'bottom', 'media' => 'all', 'priority' => 150]
        );
        $this->context->controller->registerJavascript(
            'remote-gmaps',
            'http' . (Configuration::get('PS_SSL_ENABLED') ? 's' : '') . '://maps.google.com/maps/api/js?region=AR&key=' . null,
            ['server' => 'remote', 'position' => 'head', 'priority' => 20]
        );
        $this->context->controller->registerJavascript(
            'modules-ocaepak-map',
            'modules/' . $this->name . '/views/js/maps17.js',
            ['position' => 'bottom', 'media' => 'all', 'priority' => 150]
        );
        if (Configuration::get(self::CONFIG_PREFIX . 'BRANCH_SEL_TYPE') !== '1'
        ) {
            $this->context->controller->registerStylesheet(
                'modules-ocaepak-chosen',
                'modules/' . $this->name . '/views/css/jquery.chosen.css',
                ['position' => 'bottom', 'media' => 'all', 'priority' => 200]
            );
            $this->context->controller->registerJavascript(
                'modules-ocaepak-chosen',
                'modules/' . $this->name . '/views/js/jquery.chosen.js',
                ['position' => 'bottom', 'media' => 'all', 'priority' => 200]
            );
        }

        $carrierIds = OcaEpakOperative::getRelayedCarrierIds();
        if (count($carrierIds)) {
            $langs = Language::getLanguages(true);
            $postcodes = [];
            $addresses = $this->context->customer->getAddresses($langs[0]['id_lang']);
            foreach ($addresses as $address) {
                if ($address['id_country'] == Country::getByIso('AR')) {
                    $postcodes[$address['postcode']] = $address['postcode'];
                }
            }
            if (!count($postcodes)) {
                if ($this->context->cookie->ocaCartPostcode) {
                    $postcodes[] = unserialize($this->context->cookie->ocaCartPostcode);
                } else {
                    return null;
                }
            }
            try {
                $branches = [];
                $curCp = 'NULL';
                if (Configuration::get(self::CONFIG_PREFIX . 'BRANCH_SEL_TYPE') === '1') {
                    foreach ($postcodes as $postcode) {
                        $curCp = $postcode;
                        $brs = OcaEpakBranches::retrieve(OcaCarrierTools::cleanPostcode($postcode));      // get cache
                        if (count($brs)) {
                            $branches[$postcode] = $brs;
                        } else {
                            $branches[$postcode] = $this->retrieveOcaBranches(self::OCA_SERVICE_DELIVERY,
                                OcaCarrierTools::cleanPostcode($postcode));
                            if (count($branches[$postcode])) {
                                OcaEpakBranches::insert(OcaCarrierTools::cleanPostcode($postcode), $branches[$postcode]);
                            }
                        }
                    }
                } else {
                    $brs = OcaEpakBranches::retrieve('0');      // get cache
                    if (count($brs)) {
                        $branches[0] = $brs;
                    } else {
                        $branches[0] = $this->retrieveOcaBranches(self::OCA_SERVICE_DELIVERY);
                        if (count($branches[0])) {
                            OcaEpakBranches::insert('0', $branches[0]);
                        }
                    }
                    $this->context->smarty->assign([
                        'ocaepak_states' => $this->getStatesWithAlias(),
                    ]);
                }
                $this->context->smarty->assign([
                    'ocaepak_relays' => $branches,
                    'relayed_carriers' => Tools::jsonEncode($carrierIds),
                    'ocaepak_name' => $this->name,
                    'gmaps_api_key' => null,
                    'ocaepak_branch_sel_type' => Tools::strlen(Configuration::get(self::CONFIG_PREFIX . 'BRANCH_SEL_TYPE')) ? Configuration::get(self::CONFIG_PREFIX . 'BRANCH_SEL_TYPE') : '0',
                    'force_ssl' => Configuration::get('PS_SSL_ENABLED') || Configuration::get('PS_SSL_ENABLED_EVERYWHERE'),
                ]);

                return $this->display(__FILE__, 'displayHeader.tpl');
            } catch (Exception $e) {
                Logger::AddLog('Ocaepak: ' . $this->l('Error obteniendo las sucursales del código postal') . " {$curCp}");

                return null;
            }
        }

        return null;
    }

    /**
     * @param array $params [carrier, cookie, cart, altern]
     *
     * @return false|string|null
     */
    public function hookDisplayCarrierExtraContent($params)
    {
        $carrierIds = OcaEpakOperative::getRelayedCarrierIds();
        if (
            count($carrierIds)
            && in_array($params['carrier']['id'], $carrierIds)
        ) {
            $cart = new Cart($this->context->cookie->id_cart);
            $address = new Address($cart->id_address_delivery);
            try {
                $relay = OcaEpakRelay::getByCartId($this->context->cookie->id_cart);
                if ($address->id_state) {
                    $state = new State($address->id_state);
                    $stateCode = trim($state->iso_code);
                } else {
                    $stateCode = '';
                }

                $this->context->smarty->assign([
                    'customerAddress' => str_replace('"', '', get_object_vars($address)),
                    'ocaepak_selected_relay' => $relay ? $relay->distribution_center_id : null,
                    'ocaepak_relay_auto' => $relay ? $relay->auto : null,
                    'customerStateCode' => Tools::strlen($stateCode) === 1 ? $stateCode : '',
                    'psver' => _PS_VERSION_,
                    'force_ssl' => Configuration::get('PS_SSL_ENABLED') || Configuration::get('PS_SSL_ENABLED_EVERYWHERE'),
                    'currentOcaCarrier' => $params['carrier']['id'],
                    'ocaepak_branch_sel_type' => Tools::strlen(Configuration::get(self::CONFIG_PREFIX . 'BRANCH_SEL_TYPE')) ? Configuration::get(self::CONFIG_PREFIX . 'BRANCH_SEL_TYPE') : '0',
                ]);

                return $this->display(__FILE__, 'displayCarrierExtraContent.tpl');
            } catch (Exception $e) {
                Logger::AddLog('Ocaepak: ' . $this->l('Error obteniendo sucursales para el código postal') . " {$address->postcode}");

                return false;
            }
        }

        return null;
    }

    public function hookDisplayOrderDetail($params)
    {
        $carrier = new Carrier($params['order']->id_carrier);
        $op = OcaEpakOperative::getByFieldId('carrier_reference', $carrier->id_reference);
        if (!$op || !in_array($op->type, ['PaS', 'SaS'])) {
            return null;
        }
        $relay = OcaEpakRelay::getByCartId($params['order']->id_cart);
        if (!$relay) {
            return false;
        }
        $distributionCenter = $this->retrieveOcaBranchData($relay->distribution_center_id);
        if (!$distributionCenter) {
            return false;
        }
        $this->context->smarty->assign([
            'distributionCenter' => $distributionCenter,
        ]);

        return $this->display(__FILE__, 'displayOrderDetail.tpl');
    }

    public function hookActionAdminPerformanceControllerBefore()
    {
        if (Tools::getValue('empty_smarty_cache')) {
            $this->clear();
        }
    }

    public function clear()
    {
        OcaEpakQuote::clear();
        OcaEpakBranches::clear();
    }

    /**
     * @throws \MaxMind\Db\Reader\InvalidDatabaseException
     */
    public function getOrderShippingCost($cart, $shipping_cost)
    {
        if (Tools::getValue('action') === 'searchCarts') {    // prevent BO new order stalling
            return false;
        }
        $address = new Address($cart->id_address_delivery);
        if (!$address->id || !$address->postcode) {
            $geoFile = defined(_PS_GEOIP_CITY_FILE_) ? _PS_GEOIP_CITY_FILE_ : 'GeoLiteCity.dat';
            if ($this->context->cookie->ocaCartPostcode) {
                $postcode = OcaCarrierTools::cleanPostcode(unserialize($this->context->cookie->ocaCartPostcode));
            } elseif (@filemtime(_PS_GEOIP_DIR_ . $geoFile)) {
                if (_PS_VERSION_ < 1.7) {
                    include_once _PS_GEOIP_DIR_ . 'geoipcity.inc';
                    include_once _PS_GEOIP_DIR_ . 'geoipregionvars.php';
                    $gi = geoip_open(realpath(_PS_GEOIP_DIR_ . $geoFile), GEOIP_STANDARD);
                    $record = geoip_record_by_addr($gi, Tools::getRemoteAddr());
                } else {
                    $reader = new GeoIp2\Database\Reader(_PS_GEOIP_DIR_ . _PS_GEOIP_CITY_FILE_);
                    try {
                        $record = $reader->city(Tools::getRemoteAddr());
                    } catch (\GeoIp2\Exception\AddressNotFoundException $e) {
                        $record = null;
                    }
                }
                if (
                    is_object($record)
                    && $record->country_code === 'AR'
                    && $record->postal_code
                ) {
                    $this->context->cookie->ocaCartPostcode = serialize($record->postal_code);
                    $postcode = OcaCarrierTools::cleanPostcode($record->postal_code);
                } else {
                    return false;
                }
            } else {
                return false;
            }
        } else {
            $postcode = OcaCarrierTools::cleanPostcode($address->postcode);
        }
        // $this->id_carrier ?? Carrier::getCarrierByReference()
        $carrier = new Carrier($this->id_carrier);
        $op = OcaEpakOperative::getByFieldId('carrier_reference', $carrier->id_reference);
        if (
            !$this->active
            or $address->id_country != Country::getByIso('AR')
            or !$op
        ) {
            return false;
        }
        $customer = isset($this->context->customer->id) ? new Customer($this->context->customer->id) : null;
        if (Configuration::get('PS_SHIPPING_FREE_PRICE') > 0 && $cart->getOrderTotal(true, Cart::BOTH_WITHOUT_SHIPPING) >= Configuration::get('PS_SHIPPING_FREE_PRICE')
        && (is_null($customer) or !in_array($customer->id_default_group, [4, 5]))) {
            return 0;
        }

        if ($carrier->shipping_method == Carrier::SHIPPING_METHOD_PRICE) {
            return $shipping_cost;
        }
        try {
            $relay = OcaEpakRelay::getByCartId($cart->id);
            if (in_array($op->type, ['SaS', 'PaS'])) {
                if (!$relay) {
                    $branches = $this->executeWebservice('GetCentrosImposicionPorCP', [
                        'CodigoPostal' => $postcode,
                    ]);
                    if (!count($branches) || !isset($branches[0]->idCentroImposicion)) {
                        return false;
                    }
                    $relay = new OcaEpakRelay();
                    $relay->id_cart = $cart->id;
                    $relay->distribution_center_id = (int) $branches[0]->idCentroImposicion;
                    $relay->auto = 1;
                    $relay->save();
                    $postcode = (string) $branches[0]->CodigoPostal;
                    if (!$this->context->cookie->ocaRelayPostcode) {
                        $this->context->cookie->ocaRelayPostcode = serialize($postcode);
                    }
                } else {
                    if ($this->context->cookie->ocaRelayPostcode) {
                        $postcode = OcaCarrierTools::cleanPostcode(unserialize($this->context->cookie->ocaRelayPostcode));
                    } else {
                        $branch = $this->retrieveOcaBranchData($relay->distribution_center_id);
                        $postcode = (string) $branch['CodigoPostal'];
                        $this->context->cookie->ocaRelayPostcode = serialize($postcode);
                    }
                }
            }
            $cartData = OcaCarrierTools::getCartPhysicalData(
                $cart,
                $carrier->id,
                Configuration::get(self::CONFIG_PREFIX . 'DEFWEIGHT'),
                Configuration::get(self::CONFIG_PREFIX . 'DEFVOLUME'),
                self::PADDING
            );
            if ($cot = OcaEpakQuote::retrieve(
                $op->reference,
                $postcode,
                Configuration::get(self::CONFIG_PREFIX . 'POSTCODE'),
                $cartData['volume'],
                $cartData['weight'],
                $cartData['cost']
            )) {     // get cache
                return (float) Tools::ps_round(
                    $shipping_cost + OcaCarrierTools::convertCurrencyFromIso(
                        OcaCarrierTools::applyFee($cot, $op->addfee),
                        'ARS',
                        $cart->id_currency
                    ),
                    2
                );
            }
            $data = $this->executeWebservice('Tarifar_Envio_Corporativo', [
                'PesoTotal' => $cartData['weight'],
                'VolumenTotal' => ($cartData['volume'] > 0.0001) ? $cartData['volume'] : 0.0001,
                'ValorDeclarado' => $cartData['cost'],
                'CodigoPostalOrigen' => Configuration::get(self::CONFIG_PREFIX . 'POSTCODE'),
                'CodigoPostalDestino' => OcaCarrierTools::cleanPostcode($address->postcode),
                'CantidadPaquetes' => 1,
                'Cuit' => Configuration::get(self::CONFIG_PREFIX . 'CUIT'),
                'Operativa' => $op->reference,
            ]);
            if ($data->Total > 0) {       // set cache
                OcaEpakQuote::insert(
                    $op->reference,
                    OcaCarrierTools::cleanPostcode($postcode),
                    Configuration::get(self::CONFIG_PREFIX . 'POSTCODE'),
                    $cartData['volume'],
                    $cartData['weight'],
                    $cartData['cost'],
                    $data->Total
                );
            }
        } catch (Exception $e) {
            Logger::AddLog('Ocaepak: ' . $this->l('Error obteniendo el precio para el carrito') . " {$cart->id}");

            return (float) OcaCarrierTools::convertCurrencyFromIso(
                Configuration::get(self::CONFIG_PREFIX . 'FAILCOST'),
                'ARS',
                $cart->id_currency
            );
        }

        return (float) Tools::ps_round(
            $shipping_cost + OcaCarrierTools::convertCurrencyFromIso(
                OcaCarrierTools::applyFee($data->Total, $op->addfee),
                'ARS',
                $cart->id_currency
            ),
            2
        );
    }

    /**
     * @throws \MaxMind\Db\Reader\InvalidDatabaseException
     */
    public function getOrderShippingCostExternal($params)
    {
        return $this->getOrderShippingCost($params, Configuration::get(self::CONFIG_PREFIX . 'FAILCOST'));
    }

    /**
     * @return array
     *
     * @throws PrestaShopDatabaseException
     */
    protected function getStatesWithAlias()
    {
        $states = Db::getInstance()->ExecuteS('SELECT s.id_state AS state, s.name FROM ' . pSQL(_DB_PREFIX_) . 'state s WHERE s.id_country=' . (int) Country::getByIso('AR'));
        foreach ($states as $sid => $state) {
            if (isset($this->branchStateNames[$state['name']])) {
                $states[$sid]['alias'] = Tools::strtolower($this->branchStateNames[$state['name']]);
            } else {
                $states[$sid]['alias'] = Tools::strtolower(Tools::replaceAccentedChars($state['name']));
            }
        }

        return $states;
    }

    public function retrieveOcaBranchData($id_branch)
    {
        try {
            $data = $this->executeWebservice('GetCentrosImposicionConServicios');
            foreach ($data as $table) {
                if (trim((string) $table->IdCentroImposicion) === (string) $id_branch) {
                    return Tools::jsonDecode(str_replace('{}', '""', Tools::jsonEncode((array) $table)), true);
                }
            }
        } catch (Exception $e) {
            Logger::AddLog('Ocaepak: ' . $this->l('Error obteniendo puntos de retiro para la sucursal') . " {$id_branch}");
        }

        return false;
    }

    public function validateAllBranches($serviceId = self::OCA_SERVICE_DELIVERY)
    {
        OcaEpakBranches::insert(0, $this->retrieveOcaBranches(self::OCA_SERVICE_DELIVERY));
        $data = $this->executeWebservice('GetCentrosImposicionConServicios');
        if (!is_array($data)) {
            $data = [$data];
        }
        foreach ($data as $table) {
            $rel = Tools::jsonDecode(str_replace('{}', '""', Tools::jsonEncode((array) $table)), true);
            $servs = $rel['Servicios']['Servicio'];
            $valid = false;
            if (isset($servs['IdTipoServicio'])) {
                $valid = trim((string) $servs['IdTipoServicio']) == (string) $serviceId;
            } else {
                $valid = !empty(array_filter($servs, function ($serv) use ($serviceId) {
                    return trim((string) $serv['IdTipoServicio']) == (string) $serviceId;
                }));
            }

            if ($valid) {
                OcaEpakBranches::markasvalid($rel['IdCentroImposicion']);
            } else {
                OcaEpakBranches::remove($rel['IdCentroImposicion']);
            }
        }
    }

    public function isValidBranch($idBranch, $serviceId = self::OCA_SERVICE_DELIVERY)
    {
        if (OcaEpakBranches::isValid($idBranch)) {
            return true;
        }
        $data = $this->executeWebservice('GetCentrosImposicionConServicios');
        if (!is_array($data)) {
            $data = [$data];
        }
        foreach ($data as $table) {
            $rel = Tools::jsonDecode(str_replace('{}', '""', Tools::jsonEncode((array) $table)), true);
            $servs = $rel['Servicios']['Servicio'];
            if ($rel['IdCentroImposicion'] == $idBranch) {
                if (isset($servs['IdTipoServicio'])) {
                    return trim((string) $servs['IdTipoServicio']) == (string) $serviceId;
                }

                return !empty(array_filter($servs, function ($serv) use ($serviceId) {
                    return trim((string) $serv['IdTipoServicio']) == (string) $serviceId;
                }));
            }
        }
    }

    public function retrieveOcaBranches($serviceId, $postcode = null)
    {
        try {
            $data = (
                $postcode
                ? $this->executeWebservice('GetCentrosImposicionConServiciosByCP', ['CodigoPostal' => $postcode])
                : $this->executeWebservice('GetCentrosImposicionConServicios')
            );
            $relays = [];
            if (!is_array($data)) {
                $data = [$data];
            }
            foreach ($data as $table) {
                $rel = Tools::jsonDecode(str_replace('{}', '""', Tools::jsonEncode((array) $table)), true);
                $servs = $rel['Servicios']['Servicio'];
                if (isset($servs['IdTipoServicio'])) {
                    if (trim((string) $servs['IdTipoServicio']) === (string) $serviceId) {
                        $relays[$rel['IdCentroImposicion']] = Tools::jsonDecode(
                            str_replace(
                                ['{}', '  ', '\t', "\t"],
                                ['""', '', '', ''],
                                Tools::jsonEncode((array) $table)
                            ),
                            true
                        );
                    }
                } else {
                    foreach ($servs as $serv) {
                        if (
                            isset($serv['IdTipoServicio'])
                            && trim((string) $serv['IdTipoServicio']) === (string) $serviceId
                        ) {
                            $relays[$rel['IdCentroImposicion']] = Tools::jsonDecode(
                                str_replace(
                                    ['{}', '  ', '\t', "\t"],
                                    ['""', '', '', ''],
                                    Tools::jsonEncode((array) $table)
                                ),
                                true
                            );
                            break;
                        }
                    }
                }
            }

            return $relays;
        } catch (Exception $e) {
            Logger::AddLog('Ocaepak: ' . $this->l('Error obteniendo sucursales'));
        }

        return false;
    }

    protected function guiAddToHeader($html)
    {
        $this->guiHeader .= "\n$html";

        return $this;
    }

    protected function guiMakeErrorFriendly($error)
    {
        $replacements = [
            'Property OcaEpakOperative->reference' => $this->l('Referencia'),
            'Property OcaEpakOperative->description' => $this->l('Descripción'),
        ];

        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $error
        );
    }

    public function logError($data)
    {
        $logger = new FileLogger();
        $logger->setFilename(_PS_MODULE_DIR_ . $this->name . '/logs/' . date('Ymd') . '.log');
        $logger->logError(print_r($data, true));
    }

    /**
     * @throws SoapFault
     */
    protected function _getSoapClient($url)
    {
        if (isset($this->soapClients[$url])) {
            return $this->soapClients[$url];
        }
        $this->soapClients[$url] = new SoapClient(
            $url,
            [
                'trace' => _PS_MODE_DEV_,
                'exceptions' => 1,
                'cache_wsdl' => 0,
            ]
        );

        return $this->soapClients[$url];
    }

    /**
     * @throws Exception
     */
    public function executeWebservice($method, $params = [], $returnRaw = false, $forceUrl = null)
    {
        $services = [
            self::OCA_PREVIOUS_URL => [
                // 'AnularOrdenGenerada',
                'GenerarConsolidacionDeOrdenesDeRetiro',
                'GenerateListQrPorEnvio',
                'GenerateQRParaPaquetes',
                'GenerateQrByOrdenDeRetiro',
                // 'GetCentroCostoPorOperativa',
                // 'GetCentrosImposicion',
                'GetCentrosImposicionAdmision',
                'GetCentrosImposicionAdmisionPorCP',
                'GetCentrosImposicionPorCP',
                // 'GetDatosDeEtiquetasPorOrdenOrNumeroEnvio',
                'GetELockerOCA',
                'GetEnviosUltimoEstado',
                'GetHtmlDeEtiquetasLockersPorOrdenOrNumeroEnvio',
                'GetHtmlDeEtiquetasLockersPorOrdenOrNumeroEnvioParaEtiquetadora',
                // 'GetHtmlDeEtiquetasPorOrdenOrNumeroEnvio',
                // 'GetHtmlDeEtiquetasPorOrdenOrNumeroEnvioParaEtiquetadora',
                'GetLocalidadesByProvincia',
                'GetPdfDeEtiquetasPorOrdenOrNumeroEnvio',
                // 'GetProvincias',
                'GetServiciosDeCentrosImposicion',
                'GetServiciosDeCentrosImposicion_xProvincia',
                // 'IngresoOR',
                // 'List_Envios',
                // 'Tarifar_Envio_Corporativo',
                'TrackingEnvio_EstadoActual',
                'Tracking_OrdenRetiro',
                // 'Tracking_Pieza',
            ],
            self::OCA_URL => [
                'AnularOrdenGenerada',
                'DescripcionError',
                'GetAmbitoByCPs',
                'GetCSSDeEtiquetasPorOrdenOrNumeroEnvio',
                'GetCentroCostoPorOperativa',
                'GetCentrosImposicion',
                'GetCentrosImposicionConServicios',
                'GetCentrosImposicionConServiciosByCP',
                'GetCodigosPostalesXCentroImposicion',
                'GetDatosDeEtiquetasPorOrdenOrNumeroEnvio',
                'GetDivDeEtiquetaByIdPieza',
                'GetDivDeEtiquetasPorOrdenOrNumeroEnvio',
                'GetEPackUserForMail',
                'GetEnvioEstadoActual',
                'GetEPackUser',
                'GetHtmlDeEtiquetasPorOrdenOrNumeroEnvio',
                'GetHtmlDeEtiquetasPorOrdenOrNumeroEnvioParaEtiquetadora',
                'GetHtmlDeEtiquetasPorOrdenes',
                'GetHtmlDeEtiquetasPorOrdenesParaEtiquetadora',
                'GetLoginData',
                'GetORResult',
                'GetOperativasByUsuario',
                'GetPdfDeEtiquetasPorOrdenOrNumeroEnvio',
                'GetPdfDeEtiquetasPorOrdenOrNumeroEnvioAdidas',
                'GetPdfDeEtiquetasPorOrdenOrNumeroEnvioParaEtiquetadora',
                'GetProvincias',
                'GetReporteRemTramXNumeroTracking',
                'GetSucursalByProvincia',
                'GetUserFromLoginData',
                'IngresoOR',
                'IngresoORAndreani',
                'IngresoORMultiplesRetiros',
                'List_Envios',
                'ObtenerAcuseDigital',
                'ObtenerEtiquetasZPL',
                'ObtenerRemitoPDF',
                'ObtenerRemitoPDF_Contingencia',
                'Ordenretiro_CSV2XML',
                'Tarifar_Envio_Corporativo',
                'Tracking_Pieza',
                'Tracking_PiezaExtendido',
                'Tracking_PiezaNumeroEnvio',
                'Tracking_Pieza_ConIdEstado',
            ],
        ];
        if ($forceUrl) {
            $url = $forceUrl;
        } elseif (in_array($method, $services[self::OCA_PREVIOUS_URL])) {
            $url = self::OCA_PREVIOUS_URL;
        } elseif (in_array($method, $services[self::OCA_URL])) {
            $url = self::OCA_URL;
        } else {
            throw new Exception('No existe el método en los webservices: ' . $method);
        }
        try {
            $response = $this->_getSoapClient($url)->{$method}($params);
            if ($returnRaw) {
                return $response->{$method . 'Result'};
            }
            $xml = new SimpleXMLElement($response->{$method . 'Result'}->any);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
        if (!count($xml->children())) {
            throw new Exception('El webservice no arrojo resultados');      // String compared in operatives test
        }
        if (property_exists($xml, 'NewDataSet')) {
            return reset($xml->NewDataSet);
        } else {
            return reset($xml);
        }
    }
}
