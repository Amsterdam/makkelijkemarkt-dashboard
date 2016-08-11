<?php

namespace GemeenteAmsterdam\MakkelijkeMarkt\DashboardBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

class LineairplanType extends AbstractType
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
            ->add('tariefPerMeter', 'number', [
                'label'   => 'Tarief per meter'
            ])
            ->add('reinigingPerMeter', 'number', [
                'label'   => 'Reiniging per meter'
            ])
            ->add('toeslagBedrijfsafvalPerMeter', 'number', [
                'label'   => 'Toeslag bedrijfsafval per meter'
            ])
            ->add('toeslagKrachtstroomPerAansluiting', 'number', [
                'label'   => 'Toeslag krachtstroom per aansluiting'
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