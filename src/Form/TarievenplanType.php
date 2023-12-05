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

use App\Constants\Translations;
use App\Service\TarievenplanService;
use App\Service\TranslationService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class TarievenplanType extends AbstractType
{
    public const VARIANTS = TarievenplanService::VARIANTS;
    public const WEEKDAYS = Translations::WEEKDAYS;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // New plans will typically miss a lot of data about the plan
        $tarievenplan = $options['data']['tarievenplan'];
        $tarieven = $tarievenplan['tarieven'] ?? [];

        $currentWeekdays = $tarievenplan['weekdays'] ?? [];
        $chosen = TranslationService::translateArray($currentWeekdays, self::WEEKDAYS, false);

        $tariefSoortIdsInTarieven = array_column($tarieven, 'tariefSoortId');
        $dateFrom = (isset($tarievenplan['dateFrom']) && $tarievenplan['dateFrom'])
            ? new \DateTime($tarievenplan['dateFrom'])
            : new \DateTime();
        $dateUntil = (isset($tarievenplan['dateUntil']) && $tarievenplan['dateUntil'])
            ? new \DateTime($tarievenplan['dateUntil'])
            : null;

        $isNonStandardPlan = in_array(
            $tarievenplan['variant'],
            [
                self::VARIANTS['DAYS_OF_WEEK'],
                self::VARIANTS['SPECIFIC'],
            ]
        );

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

            ->add('variant', TextType::class, [
                'data' => Translations::VARIANTS[$tarievenplan['variant']],
                'label' => 'Variant',
                'disabled' => true,
            ]);

        if ($isNonStandardPlan) {
            $builder->add('ignoreVastePlaats', CheckboxType::class, [
                'data' => $tarievenplan['ignoreVastePlaats'] ?? false,
                'label' => 'Vergunde plaatsen negeren',
                'required' => false,
            ]);
        }

        $builder->add('dateFrom', DateType::class, [
            'label' => 'Geldig vanaf',
            'widget' => 'choice',
            'years' => range(date('Y') - 5, date('Y') + 3),
            'data' => $dateFrom,
        ]);

        if ($isNonStandardPlan) {
            $builder->add('dateUntil', DateType::class, [
                'label' => 'Geldig tot en met',
                'widget' => 'choice',
                'required' => $isNonStandardPlan,
                'years' => range(date('Y') - 5, date('Y') + 3),
                'data' => $dateUntil ?? new \DateTime('1 year'),
            ]);
        }

        // We need to show the translated value, but we have to match the chosen days by key (which are english)
        if ($tarievenplan['variant'] === self::VARIANTS['DAYS_OF_WEEK']) {
            $builder->add('weekdays', ChoiceType::class, [
                'label' => 'Dagen actief',
                'multiple' => true,
                'expanded' => true,
                'choices' => self::WEEKDAYS,
                'choice_label' => function ($choice, $key, $value) {
                    return $value;
                },
                'data' => $chosen,
            ]);
        }

        // Create a tarieven group in which all tarieven will be saved
        $builder->add(
            $builder->create('tarieven', FormType::class, [
                'label' => false,
                'required' => false,
            ])
        );

        // // Create elements for all the tariefsoorten that are possible in the plan
        foreach ($options['data']['tariefSoorten'] as $tariefSoort) {
            $index = array_search($tariefSoort['id'], $tariefSoortIdsInTarieven);
            $tarief = false !== $index ? $tarieven[$index]['tarief'] : 0;

            $builder->get('tarieven')->add($tariefSoort['id'], NumberType::class, [
                'label' => $tariefSoort['label'],
                'data' => $tarief,
                'required' => false,
            ]);
        }

        $builder->add('save', SubmitType::class, ['label' => 'Opslaan']);
    }
}
