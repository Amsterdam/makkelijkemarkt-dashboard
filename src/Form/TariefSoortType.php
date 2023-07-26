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
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class TariefSoortType extends AbstractType
{
    const TARIEFPLAN_TYPES = [
        'lineair',
        'concreet',
    ];

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $tariefSoort = $options['data'];
        $isUpdate = $tariefSoort['isUpdate'];

        $builder
            ->add('label', TextType::class, [
                'label' => 'Naam',
                'data' => $tariefSoort['label'] ?? '',
            ])
            ->add('tariefType', ChoiceType::class, [
                'label' => 'Tariefplan Type',
                'data' => $tariefSoort['tariefType'] ?? null,
                'choices' => $isUpdate ? [$tariefSoort['tariefType']] : [self::TARIEFPLAN_TYPES],
                'disabled' => $isUpdate,
                'choice_label' => function ($choice, $key, $value) {
                    return $value;
                },
            ])
            ->add('unit', ChoiceType::class, [
                'label' => 'Eenheid',
                'choices' => array_values(TRANSLATIONS::UNITS),
                'data' => $isUpdate ? TRANSLATIONS::UNITS[$tariefSoort['unit']] : '',
                'choice_label' => function ($choice, $key, $value) {
                    return $value;
                },
            ])
            ->add('factuurLabel', TextType::class, [
                'label' => 'Label op factuur',
                'data' => $tariefSoort['factuurLabel'] ?? '',
            ])

            ->add('deleted', CheckboxType::class, [
                'label' => 'Tariefsoort gearchiveerd',
                'data' => $tariefSoort['deleted'] ?? false,
                'required' => false,
            ])
            ->add('buttons', FormType::class, [
                'mapped' => false,
                'label' => false,
                'attr' => [
                    'class' => 'd-inline-flex w-100 justify-content-between',
                ],
            ])

            ->get('buttons')
                ->add('back', ButtonType::class, [
                    'label' => 'Terug',
                    'attr' => [
                        'onclick' => 'window.history.back()',
                    ],
                ])
                ->add('save', SubmitType::class, [
                    'label' => 'Opslaan',
                    'attr' => [
                        'onclick' => $isUpdate ?
                            'return confirm("Weet je zeker dat je deze tariefsoort wilt aanpassen? Dit heeft gevolgen voor facturen aanmaken.")'
                            : '',
                    ],
                ]);
    }

    public function getName(): string
    {
        return str_replace('\\', '_', __CLASS__);
    }
}
