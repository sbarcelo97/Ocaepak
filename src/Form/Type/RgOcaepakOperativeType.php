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
namespace RgOcaEpak\Form\Type;

use PrestaShopBundle\Form\Admin\Type\SwitchType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class RgOcaepakOperativeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('OP_REFERENCE', TextType::class, ['required' => true])
            ->add('OP_DESC', TextType::class, ['required' => true])
            ->add('OP_TYPE', ChoiceType::class, ['required' => true, 'choices' => ['Puerta a Puerta (PaP)' => 'PaP',
                'Puerta a Sucursal (PaS)' => 'PaS', 'Sucursal a Puerta  (SaP)' => 'SaP', 'Sucursal a Sucursal (SaS)' => 'SaS', ]])
            ->add('OP_INSURED', SwitchType::class, ['required' => false])
            ->add('OP_FEE', TextType::class, ['required' => true, 'data' => '0.00%']);
    }
}
