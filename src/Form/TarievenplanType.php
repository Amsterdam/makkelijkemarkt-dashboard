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

use DateTime;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class TarievenplanType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // New plans will typically miss a lot of data about the plan
        $tarievenplan = $options['data']['tarievenplan'];
        $tarieven = $tarievenplan['tarieven'] ?? [];
        $tariefSoortIdsInTarieven = array_column($tarieven, 'tariefSoortId');
        $dateFrom = (isset($tarievenplan['dateFrom']) && $tarievenplan['dateFrom'])
            ? new DateTime($tarievenplan['dateFrom'])
            : null;
        $dateUntil = (isset($tarievenplan['dateUntil']) && $tarievenplan['dateUntil'])
            ? new DateTime($tarievenplan['dateUntil'])
            : null;

        $builder
            ->add('name', TextType::class, [
                'data' => $tarievenplan['name'] ?? '',
                'label' => 'Naam',
                'required' => true,
            ])
            ->add('type', TextType::class, [
                'data' => $tarievenplan['type'],
                'label' => 'Type',
                'disabled' => true,
            ])

            // TODO toevoegen tijdens werk aan flexibele tarievenplannen
            // $builder->add('isDefault', ChoiceType::class, [
            //     'label' => 'Voorwaarden',
            //     'choices' => [
            //         'Standaard tariefplan' => 1,
            //         'Tariefplan met voorwaarden' => 0
            //     ],
            //     // 'data' => $tarievenplan['is_default'], TODO eerst dat amigreren
            //     'expanded' => true,
            //     'required' => true
            // ]);

            // TODO uitzoeken tijdens testen met MB welke date range we willen
            // Alle plannen die voor de date range vallen, worden niet lekker getoond..
            ->add('dateFrom', DateType::class, [
                'label' => 'Geldig vanaf',
                'widget' => 'choice',
                'years' => range(date('Y') - 5, date('Y') + 3),
                'data' => $dateFrom,
            ])
            ->add('dateUntil', DateType::class, [
                'label' => 'Geldig tot',
                'widget' => 'choice',
                'required' => false,
                'years' => range(date('Y') - 5, date('Y') + 3),
                'data' => $dateUntil,
            ])

            // TODO toevoegen wanneer we flexibele tarievenplannen gaan introduceren
            // ->add(
            //     $builder->create('conditions', FormType::class, [
            //         'label' => false
            //     ])
            // )
            // ->get('conditions')->add('days', ChoiceType::class, [
            //     'label' => 'Dagen in de week actief',
            //     'multiple' => true,
            //     'expanded' => true,

            //     // Days of the week are mapped to bitwise operators
            //     'choices' => [
            //         'maandag' => 2,
            //         'dinsdag' => 4,
            //         'woensdag' => 8,
            //         'donderdag' => 16,
            //         'vrijdag' => 32,
            //         'zaterdag' => 64,
            //         'zondag' => 1
            //     ]
            // ])

            // Create a tarieven group in which all tarieven will be saved
            ->add(
                $builder->create('tariefSoortIdWithTarief', FormType::class, [
                    'label' => false,
                    'required' => false,
                ])
            );

        // // Create elements for all the tariefsoorten that are possible in the plan
        foreach ($options['data']['tariefSoorten'] as $tariefSoort) {
            $index = array_search($tariefSoort['id'], $tariefSoortIdsInTarieven);
            $tarief = false !== $index ? $tarieven[$index]['tarief'] : 0;

            $builder->get('tariefSoortIdWithTarief')->add($tariefSoort['id'], NumberType::class, [
                'label' => $tariefSoort['label'],
                'data' => $tarief,

                // Sort on tarief amount so that empty tarieven will go to the bottom
                // The sorting cant handle floats so we multiply it by 100
                // TODO tijdens testen met Marktbureau: welke sortering willen ze?
                'priority' => (int) ($tarief * 100),
                'required' => false,
            ]);
        }

        $builder->add('save', SubmitType::class, ['label' => 'Opslaan']);
    }
}
