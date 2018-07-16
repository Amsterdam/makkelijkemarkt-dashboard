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

class AccountCreateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('naam', 'text', [])
            ->add('email', 'email', [])
            ->add('username', 'text', [])
            ->add('password', 'password', ['label' => 'Wachtwoord'])
            ->add('role', 'choice', [
                'choices' => Roles::all(),
                'label'   => 'Rol'
            ])
            ->add('save', 'submit', ['label' => 'Aanmaken']);
    }

    public function getName()
    {
        return str_replace('\\', '_', __CLASS__);
    }
}