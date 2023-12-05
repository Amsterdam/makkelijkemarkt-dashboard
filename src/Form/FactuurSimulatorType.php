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
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;

class FactuurSimulatorType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('dag', DateType::class, [
                'data' => new \DateTime(),
                'label' => 'Dag aangemaakt',
                'required' => true,
            ])
            // Create a tarieven group in which all tarieven will be saved
            ->add('paid', FormType::class, [
                    'label' => false,
                    'required' => false,
                ])
            ->add('unpaid', FormType::class, [
                    'label' => false,
                    'required' => false,
                ]);
        // // Create elements for all the tariefsoorten that are possible in the plan
        foreach ($options['data']['markt']['products'] as $mapping) {
            $builder->get('paid')->add($mapping['id'], NumberType::class, [
                'required' => false,
                'label' => $mapping['appLabel'],
            ]);
            $builder->get('unpaid')->add($mapping['id'], NumberType::class, [
                'required' => false,
                'label' => $mapping['appLabel'],
            ]);
        }

        $builder->add('save', SubmitType::class, ['label' => 'Opslaan']);
    }
}
