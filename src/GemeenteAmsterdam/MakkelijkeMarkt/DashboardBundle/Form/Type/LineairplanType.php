<?php
/*
 *  Copyright (C) 2017 X Gemeente
 *                     X Amsterdam
 *                     X Onderzoek, Informatie en Statistiek
 *
 *  This Source Code Form is subject to the terms of the Mozilla Public
 *  License, v. 2.0. If a copy of the MPL was not distributed with this
 *  file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace GemeenteAmsterdam\MakkelijkeMarkt\DashboardBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

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
            ->add('afvaleiland', 'number', [
                'label'   => 'Afvaleiland'
            ])
            ->add('elektra', 'number', [
                'label'   => 'Elektra per aansluiting'
            ])
            ->add('eenmaligElektra', 'number', [
                'label'   => 'Eenmalig elektra'
            ])
            ->add('save', 'submit', ['label' => 'Opslaan']);
    }

    public function getName()
    {
        return str_replace('\\', '_', __CLASS__);
    }
}