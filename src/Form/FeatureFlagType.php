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
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class FeatureFlagType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isUpdating = false;
        if (isset($options['data']['id'])) {
            $featureFlag = $options['data'];
            $isUpdating = true;
        }

        $builder->add('feature', TextType::class, [
            'data' => $isUpdating ? $featureFlag['feature'] : '',
            'label' => 'Feature',
            'required' => true,
        ])
        ->add('enabled', ChoiceType::class, [
            'data' => $isUpdating ? $featureFlag['enabled'] : false,
            'label' => 'AAN / UIT',
            'required' => true,
            'choices' => [
                'AAN' => true,
                'UIT' => false,
            ],
        ])
        ->add('save', SubmitType::class, ['label' => 'Opslaan']);
    }
}
