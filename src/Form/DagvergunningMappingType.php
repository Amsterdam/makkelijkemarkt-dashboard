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
use DateTimeImmutable;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class DagvergunningMappingType extends AbstractType
{
    const TARIEFPLAN_TYPES = [
        'lineair',
        'concreet',
    ];

    const APP_INPUT_TYPES = [
        'number',
        'toggle',
    ];

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $mapping = $options['data']['mapping'];
        $isUpdate = $options['data']['isUpdate'];

        if (false === $isUpdate && isset($options['data']['tariefSoorten'])) {
            $tariefSoorten = $this->prepareTariefSoorten($options['data']['tariefSoorten']);
        } else {
            $tariefSoorten = [$mapping['tariefType'].' - '.$mapping['tariefSoortLabel'] => $mapping['tariefSoortId']];
        }

        $builder
            ->add('dagvergunningKey', TextType::class, [
                'label' => 'Dagvergunning Key',
                'data' => $mapping['dagvergunningKey'] ?? '',
                'disabled' => $isUpdate,
            ])
            ->add('tariefType', ChoiceType::class, [
                'label' => 'Tariefplan Type',
                'data' => $mapping['tariefTypeLabel'] ?? null,
                'choices' => $isUpdate ? [$mapping['tariefType']] : [self::TARIEFPLAN_TYPES],
                'disabled' => $isUpdate,
                'choice_label' => function ($choice, $key, $value) {
                    return $value;
                },
            ])
            ->add('tariefSoort', ChoiceType::class, [
                'label' => 'Tariefsoort',
                'choices' => $tariefSoorten,
                'disabled' => $isUpdate,
                'choice_label' => function ($choice, $key, $value) {
                    if ($choice) {
                        return $key;
                    } else {
                        return 'Niet gekoppeld.';
                    }
                },
            ])
            ->add('unit', ChoiceType::class, [
                'label' => 'Eenheid',
                'choices' => $isUpdate ? [TRANSLATIONS::UNITS[$mapping['unit']]] : [array_values(TRANSLATIONS::UNITS)],
                'data' => $isUpdate ? TRANSLATIONS::UNITS[$mapping['unit']] : '',
                'disabled' => $isUpdate,
                'choice_label' => function ($choice, $key, $value) {
                    return $value;
                },
            ])
            ->add('translatedToUnit', IntegerType::class, [
                'label' => 'Omrekenen naar',
                'disabled' => $isUpdate,
                'data' => $mapping['translatedToUnit'] ?? null,
            ])
            ->add('appLabel', TextType::class, [
                'label' => 'App Label',
                'data' => $mapping['appLabel'] ?? '',
            ])
            ->add('inputType', ChoiceType::class, [
                'label' => 'Type input in de MM App',
                'data' => $mapping['inputType'] ?? null,
                'choices' => self::APP_INPUT_TYPES,
                'choice_label' => function ($choice, $key, $value) {
                    return $value;
                },
            ])
            ->add('mercatoKey', TextType::class, [
                'label' => 'Koppelingscode Mercato',
                'data' => $mapping['mercatoKey'] ?? '',
                'required' => false,
            ])

            ->add('archivedOn', DateType::class, [
                'label' => 'Archiveren vanaf',
                'widget' => 'choice',
                'data' => $isUpdate && $mapping['archivedOn'] ? new DateTimeImmutable($mapping['archivedOn']) : null,
                'years' => range(date('Y') - 1, date('Y') + 5),
                'required' => false,
            ])

            ->add('save', SubmitType::class, ['label' => 'Opslaan']);
    }

    private function prepareTariefSoorten($tariefSoorten)
    {
        $prepared = [];

        array_walk($tariefSoorten, function ($tariefSoort) use (&$prepared) {
            $prepared[$tariefSoort['tariefType'].' - '.$tariefSoort['label']] = $tariefSoort['id'];
        });

        return $prepared;
    }

    public function getName(): string
    {
        return str_replace('\\', '_', __CLASS__);
    }
}
