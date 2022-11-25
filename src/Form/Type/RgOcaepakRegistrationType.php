<?php
namespace RgOcaEpak\Form\Type;

use PrestaShopBundle\Form\Admin\Type\EmailType;
use PrestaShopBundle\Form\Admin\Type\SwitchType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use  Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class RgOcaepakRegistrationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('RG_OCAEPAK_EMAIL', TextType::class,['required'=>true])
            ->add('RG_OCAEPAK_PASSWORD', PasswordType::class,['required'=>true, 'always_empty'=>false])
            ->add('RG_OCAEPAK_ACCOUNT', TextType::class,['required'=>true])
            ->add('RG_OCAEPAK_CUIT',  TextType::class,['required'=>true])
            ->add('RG_OCAEPAK_CUIT',  TextType::class,['required'=>true])
            ->add('RG_OCAEPAK_POSTCODE',  NumberType::class,['required'=>true])
            ->add('RG_OCAEPAK_ADMISSIONS_ENABLED',  SwitchType::class,['required'=>false])
            ->add('RG_OCAEPAK_PICKUPS_ENABLED',  SwitchType::class,['required'=>false])
            ->add('RG_OCAEPAK_BRANCH_SEL_TYPE',  ChoiceType::class,['required'=>false, 'choices'=>['Mostrar todas las sucursales'=>0, 'Mostrar solo las sucursales de ese código postal'=>1]])
            ->add('RG_OCAEPAK_DEFWEIGHT',  TextType::class,['required'=>true])
            ->add('RG_OCAEPAK_DEFVOLUME',  TextType::class,['required'=>true])
            ->add('RG_OCAEPAK_FAILCOST',  TextType::class,['required'=>true])
            ->add('RG_OCAEPAK_ORDERDAYS',NumberType::class,['required'=>true])
            ->add('RG_OCAEPAK_ADMISSION_BRANCH',  ChoiceType::class,['required'=>false, 'choices'=>$options['data']['admission_branches']])
            ->add('RG_OCAEPAK_STREET',  TextType::class,['required'=>false])
            ->add('RG_OCAEPAK_NUMBER',  TextType::class,['required'=>false])
            ->add('RG_OCAEPAK_FLOOR',  TextType::class,['required'=>false])
            ->add('RG_OCAEPAK_APARTMENT',  TextType::class,['required'=>false])
            ->add('RG_OCAEPAK_LOCALITY',  TextType::class,['required'=>false])
            ->add('RG_OCAEPAK_PROVINCE',  TextType::class,['required'=>false])
            ->add('RG_OCAEPAK_CONTACT',  TextType::class,['required'=>false])
            ->add('RG_OCAEPAK_REQUESTOR',  TextType::class,['required'=>false])
            ->add('RG_OCAEPAK_OBSERVATIONS',  TextType::class,['required'=>false])
            ->add('RG_OCAEPAK_TIMESLOT',  ChoiceType::class,['required'=>false, 'choices'=>['8:00 - 17:00'=>1, '8:00 - 12:00'=>2, '14:00 - 17:00'=>3]])
        ;
    }
}
