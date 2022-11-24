<?php

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
            ->add('OP_REFERENCE', TextType::class,['required'=>true])
            ->add('OP_DESC', TextType::class,['required'=>true])
            ->add('OP_TYPE', ChoiceType::class,['required'=>true, 'choices'=>['Puerta a Puerta (PaP)'=>'PaP',
                'Puerta a Sucursal (PaS)'=>'PaS', 'Sucursal a Puerta  (SaP)'=>'SaP', 'Sucursal a Sucursal (SaS)'=>'SaS']])
            ->add('OP_INSURED',  SwitchType::class,['required'=>false])
            ->add('OP_FEE',  TextType::class,['required'=>true, 'data'=>'0.00%']);
    }

}
