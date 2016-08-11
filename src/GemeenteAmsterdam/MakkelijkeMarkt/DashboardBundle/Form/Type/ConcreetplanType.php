<?php

namespace GemeenteAmsterdam\MakkelijkeMarkt\DashboardBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

class ConcreetplanType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('naam', 'text', [])
            ->add('geldigVanaf', 'date', [
                'label'   => 'Geldig vanaf'
            ])
            ->add('geldigTot', 'date', [
                'label'   => 'Geldig tot'
            ])
            ->add('een_meter', 'number', [
                'label'   => 'Een meter'
            ])
            ->add('drie_meter', 'number', [
                'label'   => 'Drie meter'
            ])
            ->add('vier_meter', 'number', [
                'label'   => 'Vier meter'
            ])
            ->add('elektra', 'number', [
                'label'   => 'Elektra'
            ])
            ->add('promotieGeldenPerMeter', 'number', [
                'label'   => 'Promotie gelden per meter'
            ])
            ->add('promotieGeldenPerKraam', 'number', [
                'label'   => 'Promotie gelden per kraam'
            ])
            ->add('save', 'submit', ['label' => 'Opslaan']);
    }

    public function getName()
    {
        return str_replace('\\', '_', __CLASS__);
    }
}