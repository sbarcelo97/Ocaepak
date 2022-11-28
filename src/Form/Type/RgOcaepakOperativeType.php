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
*@author Region Global
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
