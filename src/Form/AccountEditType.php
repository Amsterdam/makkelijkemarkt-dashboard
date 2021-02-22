<?php
/*
 *  Copyright (C) 2021 X Gemeente
 *                     X Amsterdam
 *                     X Onderzoek, Informatie en Statistiek
 *
 *  This Source Code Form is subject to the terms of the Mozilla Public
 *  License, v. 2.0. If a copy of the MPL was not distributed with this
 *  file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

declare(strict_types=1);
namespace App\Form;

use App\Enum\Roles;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;



class AccountEditType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('naam', TextType::class, [])
            ->add('email', EmailType::class, [])
            ->add('username', TextType::class, ['label' => 'Gebruikersnaam'])
            ->add('password', PasswordType::class, ['required' => false, 'label' => 'Wachtwoord'])
            ->add('role', ChoiceType::class, [
                'choices' => array_flip(Roles::all()),
                'label'   => 'Rol'
            ])
            ->add('active', ChoiceType::class, [
                'choices' => ['Actief' => true, 'Inactief' => false],
                'label'   => 'Actief?'
            ])
            ->add('save', SubmitType::class, ['label' => 'Opslaan']);
    }

    public function getName(): string
    {
        return str_replace('\\', '_', __CLASS__);
    }
}