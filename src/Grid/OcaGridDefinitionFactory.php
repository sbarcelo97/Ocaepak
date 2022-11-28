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


namespace RgOcaEpak\Grid;

use PrestaShop\PrestaShop\Core\Grid\Action\GridActionCollection;
use PrestaShop\PrestaShop\Core\Grid\Action\Row\RowActionCollection;
use PrestaShop\PrestaShop\Core\Grid\Action\Row\Type\LinkRowAction;
use PrestaShop\PrestaShop\Core\Grid\Action\Type\LinkGridAction;
use PrestaShop\PrestaShop\Core\Grid\Column\ColumnCollection;
use PrestaShop\PrestaShop\Core\Grid\Column\Type\Common\ActionColumn;
use PrestaShop\PrestaShop\Core\Grid\Column\Type\DataColumn;
use PrestaShop\PrestaShop\Core\Grid\Definition\Factory\AbstractGridDefinitionFactory;

class OcaGridDefinitionFactory extends AbstractGridDefinitionFactory
{
    protected function getId()
    {
        // TODO: Implement getId() method.
        return 'OcaEpak';
    }

    protected function getName()
    {
        // TODO: Implement getName() method.
        return $this->trans('Operativas Oca', [], 'Modules.Rgocaepak.Form');
    }

    protected function getColumns()
    {
        return (new ColumnCollection())
            ->add((new DataColumn('operatives_reference'))
                ->setName($this->trans('Referencia de la Operativa', [], 'Modules.Rgocaepak.Form'))
                ->setOptions([
                    'field' => 'reference',
                ])
            )
            ->add((new DataColumn('operatives_description'))
                ->setName($this->trans('Descripción', [], 'Modules.Rgocaepak.Form'))
                ->setOptions([
                    'field' => 'description',
                ])
            )
            ->add((new DataColumn('operatives_type'))
                ->setName($this->trans('Tipo', [], 'Modules.Rgocaepak.Form'))
                ->setOptions([
                    'field' => 'type',
                ])
            )
            ->add((new DataColumn('operatives_fee'))
                ->setName($this->trans('Cargo adicional', [], 'Modules.Rgocaepak.Form'))
                ->setOptions([
                    'field' => 'addfee',
                ])
            )
            ->add((new DataColumn('operatives_insurance'))
                ->setName($this->trans('Asegurado por Oca', [], 'Modules.Rgocaepak.Form'))
                ->setOptions([
                    'field' => 'insured',
                ])
            )
            ->add(
                (new ActionColumn('actions'))
                    ->setName($this->trans('Acciones', [], 'Admin.Global'))
                    ->setOptions([
                        'actions' => $this->getRowActions(),
                    ])
            );
    }

    private function getRowActions()
    {
        return (new RowActionCollection())
            ->add(
                (new LinkRowAction('edit'))
                    ->setName($this->trans('Editar', [], 'Admin.Actions'))
                    ->setIcon('edit')
                    ->setOptions([
                        'route' => 'admin_rg_ocaepak_update_operative',
                        'route_param_name' => 'id',
                        'route_param_field' => 'id_ocae_operatives',
                        'clickable_row' => true,
                    ])
            )
            ->add(
                (new LinkRowAction('delete'))
                    ->setName($this->trans('Borrar', [], 'Admin.Actions'))
                    ->setIcon('delete')
                    ->setOptions([
                        'route' => 'admin_rg_ocaepak_delete_operative',
                        'route_param_name' => 'id',
                        'route_param_field' => 'id_ocae_operatives',
                        'clickable_row' => true,
                    ])
            );
    }

    protected function getGridActions()
    {
        return (new GridActionCollection())
            ->add(
                (new LinkGridAction('rg_ocaepak_add_operative_action'))
                    ->setName($this->trans('Añadir Operativa', [], 'Modules.Rgocaepak.Actions'))
                    ->setIcon('add')
                    ->setOptions([
                        'route' => 'admin_rg_ocaepak_add_operative',
                    ])
            );
    }
}
