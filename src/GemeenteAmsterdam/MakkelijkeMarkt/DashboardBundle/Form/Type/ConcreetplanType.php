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
            ->add('afvaleiland', 'number', [
                'label'   => 'Afvaleiland'
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