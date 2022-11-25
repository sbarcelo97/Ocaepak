<?php

namespace RgOcaEpak\Controllers\Admin;

use ModuleCore;
use PrestaShop\PrestaShop\Adapter\SymfonyContainer;
use PrestaShop\PrestaShop\Core\Grid\Search\SearchCriteria;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use RgOcaEpak\Classes\OcaEpakOperative;
use RgOcaEpak\Form\Type\RgOcaepakOperativeType;
use RgOcaEpak\Form\Type\RgOcaepakRegistrationType;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\Request;

class RgOcaEpakController extends FrameworkBundleAdminController
{
    public $module = null;

    public function index()
    {
        $admission_branches = $this->getBranches();
        $form = $this->createForm(RgOcaepakRegistrationType::class, ['admission_branches' => $admission_branches]);
        $gridFactory = $this->get('rgocaepak.grid_factory');
        $emptySearchCriteria = new SearchCriteria();
        $grid = $gridFactory->getGrid($emptySearchCriteria);
        $params = $this->module->getConfigFormValues();
        $admissions = false;
        $pickups = false;
        if ($params && !empty($params)) {
            $form->submit($params);
            $admissions = (bool) isset($params['RG_OCAEPAK_ADMISSIONS_ENABLED']);
            $pickups = (bool) isset($params['RG_OCAEPAK_PICKUPS_ENABLED']);
        }

        return $this->render('@Modules/rg_ocaepak/views/templates/admin/index.html.twig', [
            'form' => $form->createView(),
            'admissions' => $admissions,
            'pickups' => $pickups,
            'boxes' => json_decode($params['RG_OCAEPAK_BOXES'], true),
            'save' => false,
            'grid' => $this->presentGrid($grid),
        ]);
    }

    public function save(Request $request)
    {
        $parameters = $request->request->all();
        if ($params = $parameters['rg_ocaepak_registration']) {
            $validation = $this->validateForm($parameters);
            $errors = $validation['errors'];
            $boxes = $validation['boxes'];
            if (empty($errors)) {
                $this->get('prestashop.adapter.legacy.configuration')->set('RG_OCAEPAK_BOXES', json_encode($boxes));
                foreach ($params as $key => $value) {
                    if ($key != '_token') {
                        $this->get('prestashop.adapter.legacy.configuration')->set($key, $value);
                    }
                }
            }

            $admission_branches = $this->getBranches();
            $params['admission_branches'] = $admission_branches;
            $form = $this->createForm(RgOcaepakRegistrationType::class, $params);
            $gridFactory = $this->get('rgocaepak.grid_factory');
            $emptySearchCriteria = new SearchCriteria();
            $grid = $gridFactory->getGrid($emptySearchCriteria);
            $form->submit($params);

            return $this->render('@Modules/rg_ocaepak/views/templates/admin/index.html.twig', [
                'form' => $form->createView(),
                'admissions' => (bool) isset($params['RG_OCAEPAK_ADMISSIONS_ENABLED']),
                'pickups' => (bool) isset($params['RG_OCAEPAK_PICKUPS_ENABLED']),
                'boxes' => $boxes,
                'grid' => $this->presentGrid($grid),
                'save' => true,
                'errors' => $errors,
            ]);
        } else {
            return $this->redirectToRoute('admin_rg_ocaepak_index');
        }
    }

    public function deleteOperatives(Request $request)
    {
        $operative_id = $request->query->get('id');
        $operative = new OcaEpakOperative($operative_id);
        if ($operative->carrier_reference) {
            $operative->delete();
        }

        return $this->redirectToRoute('admin_rg_ocaepak_index');
    }

    public function addUpdateOperative(Request $request)
    {
        if (!empty($request->request->all())) {
            $operative_data = $request->request->get('rg_ocaepak_operative');
            $op = isset($_REQUEST['id']) ? new OcaEpakOperative($_REQUEST['id']) : new OcaEpakOperative();
            $op->reference = $operative_data['OP_REFERENCE'];
            $shopContext = $this->get('prestashop.adapter.shop.context');
            $op->id_shop = $shopContext->getContextShopID();
            $op->description = $operative_data['OP_DESC'];
            $op->addfee = $operative_data['OP_FEE'];
            $op->type = $operative_data['OP_TYPE'];
            $op->insured = (bool) isset($operative_data['OP_INSURED']);

            $error = $this->validateOperativeAndConnection($op);
            if ($error == null) {
                $op->save();

                return $this->redirectToRoute('admin_rg_ocaepak_index');
            }
        }
        if ($request->query->get('id') !== null) {
            $op = new OcaEpakOperative($request->query->get('id'));
            $params = ['OP_REFERENCE' => $op->reference, 'OP_DESC' => $op->description, 'OP_FEE' => $op->addfee, 'OP_TYPE' => $op->type, 'OP_INSURED' => $op->insured];
            $form = $this->createForm(RgOcaepakOperativeType::class, $params);
        } else {
            $form = $this->createForm(RgOcaepakOperativeType::class);
        }
        $backroute = SymfonyContainer::getInstance()->get('router')->generate('admin_rg_ocaepak_index');

        return $this->render('@Modules/rg_ocaepak/views/templates/admin/operative.html.twig', [
            'form' => $form->createView(),
            'backroute' => $backroute,
            'error' => $error ?? null,
        ]);
    }

    private function getBranches()
    {
        $this->module = ModuleCore::getInstanceByName('rg_ocaepak');
        $admission_branches = [];
        foreach ($this->module->getAdmissionBranches() as $branch) {
            $admission_branches[$branch['text']] = (string) $branch['value'];
        }

        return $admission_branches;
    }

    private function validateOperativeAndConnection(OcaEpakOperative $op)
    {
        if (!isset($this->module)) {
            $this->module = ModuleCore::getInstanceByName('rg_ocaepak');
        }
        try {
            $response = $this->module->executeWebservice('Tarifar_Envio_Corporativo', [
                'PesoTotal' => '1',
                'VolumenTotal' => '0.05',
                'ValorDeclarado' => '100',
                'CodigoPostalOrigen' => $this->get('prestashop.adapter.legacy.configuration')->get($this->module::CONFIG_PREFIX . 'POSTCODE'),
                'CodigoPostalDestino' => $this->get('prestashop.adapter.legacy.configuration')->get($this->module::CONFIG_PREFIX . 'POSTCODE') == 9120 ? 1924 : 9120,
                'CantidadPaquetes' => 1,
                'Cuit' => $this->get('prestashop.adapter.legacy.configuration')->get($this->module::CONFIG_PREFIX . 'CUIT'),
                'Operativa' => $op->reference,
            ]);
            if ($response->Error) {
                throw new Exception($response->Error);
            }
        } catch (Exception $e) {
            if ($e->getMessage() == 'No results from OCA webservice') {
                $error = 'There seems to be an error in the OCA operative';
            } else {
                $error = $e->getMessage();
            }
        }

        return $error ?? null;
    }

    public function validateForm($params)
    {
        $errors = [];
        $boxes = [];
        if (!isset($this->module)) {
            $this->module = ModuleCore::getInstanceByName('rg_ocaepak');
        }
        foreach ($params as $key => $value) {
            if (str_contains($key, 'oca-box-l')) {
                $i = substr($key, -1, 1);
                $boxes[] = [
                    'l' => $params['oca-box-l-' . $i],
                    'd' => $params['oca-box-d-' . $i],
                    'h' => $params['oca-box-h-' . $i],
                    'xw' => $params['oca-box-xw-' . $i],
                    'isd' => isset($params['oca-box-isd-' . $i]) ? 1 : 0,
                ];
            }
        }
        foreach (OcaEpakOperative::getOperativeIds() as $op_id) {
            $op = new OcaEpakOperative($op_id);
            if ($resp = $this->validateOperativeAndConnection($op)) {
                if (!in_array($resp, $errors)) {
                    $errors[] = $resp;
                }
            }
        }

        $config = $params['rg_ocaepak_registration'];
        if (empty($boxes) && ($config['RG_OCAEPAK_ADMISSIONS_ENABLED'] or $config['RG_OCAEPAK_PICKUPS_ENABLED'])) {
            $errors[] = 'Debe agregar al menos 1 caja';
        }

        return ['errors' => $errors, 'boxes' => $boxes];
    }
}
