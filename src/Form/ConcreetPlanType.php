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

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class ConcreetPlanType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('naam', TextType::class, [])
            ->add('geldigVanaf', DateType::class, [
                'label' => 'Geldig vanaf',
            ])
            ->add('geldigTot', DateType::class, [
                'label' => 'Geldig tot',
            ])
            ->add('een_meter', NumberType::class, [
                'label' => 'Een meter',
            ])
            ->add('drie_meter', NumberType::class, [
                'label' => 'Drie meter',
            ])
            ->add('vier_meter', NumberType::class, [
                'label' => 'Vier meter',
            ])
            ->add('elektra', NumberType::class, [
                'label' => 'Elektra',
            ])
            ->add('promotieGeldenPerMeter', NumberType::class, [
                'label' => 'Promotie gelden per meter',
            ])
            ->add('promotieGeldenPerKraam', NumberType::class, [
                'label' => 'Promotie gelden per kraam',
            ])
            ->add('afvaleiland', NumberType::class, [
                'label' => 'Afvaleiland',
            ])
            ->add('eenmaligElektra', NumberType::class, [
                'label' => 'Eenmalig elektra',
            ])
            ->add('save', SubmitType::class, ['label' => 'Opslaan']);
    }

    public function getName(): string
    {
        return str_replace('\\', '_', __CLASS__);
    }
}
