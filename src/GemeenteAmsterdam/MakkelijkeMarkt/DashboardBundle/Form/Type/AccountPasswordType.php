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

use GemeenteAmsterdam\MakkelijkeMarkt\DashboardBundle\Enum\Roles;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use GemeenteAmsterdam\MakkelijkeMarkt\DashboardBundle\Enum\ActiveStates;

class AccountPasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('password', 'password', ['required' => false, 'label' => 'Wachtwoord'])
            ->add('save', 'submit', ['label' => 'Wijzig']);
    }

    public function getName()
    {
        return str_replace('\\', '_', __CLASS__);
    }
}