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

use DateTimeImmutable;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class BtwPlanType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $btwPlan = $options['data']['btwPlan'] ?? null;
        $btwTypes = $options['data']['btwTypes'];
        $tariefSoorten = $options['data']['tariefSoorten'] ?? null;
        $isUpdating = null !== $btwPlan;

        $builder
            ->add('tariefPlanType', ChoiceType::class, [
                'label' => 'Tarief Plan',
                'choices' => [$options['data']['planType']],
                'disabled' => true,
                'choice_label' => function ($choice, $key, $value) {
                    return $value;
                },
            ])
            ->add('tariefSoort', ChoiceType::class, [
                'label' => 'Tariefsoort',
                'data' => $btwPlan['tariefLabel'] ?? '',
                'choices' => $isUpdating ? [$btwPlan['tariefLabel']] : array_column($tariefSoorten, 'label'),
                'disabled' => $isUpdating,
                'required' => true,
                'choice_label' => function ($choice, $key, $value) {
                    return $value;
                },
            ])
            ->add('btwType', ChoiceType::class, [
                'label' => 'BTW Type',
                'choices' => array_column($btwTypes, 'label'),
                'required' => true,
                'data' => $btwPlan['btwType'] ?? '',
                'choice_label' => function ($choice, $key, $value) {
                    return $value;
                },
            ])
            ->add('dateFrom', DateType::class, [
                'label' => 'Ingangsdatum',
                'widget' => 'choice',
                'data' => $isUpdating ? new DateTimeImmutable($btwPlan['dateFrom']) : null,
                'required' => true,
                'years' => range(date('Y') - 1, date('Y') + 5),
            ])
            ->add('markt', TextType::class, [
                'label' => 'Markt ID',
                'data' => $isUpdating ? $btwPlan['marktId'] : '',
                'required' => false,
            ])
            ->add('save', SubmitType::class, ['label' => 'Opslaan']);
    }

    public function getName(): string
    {
        return str_replace('\\', '_', __CLASS__);
    }
}
