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

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class ScanSpeedSelectorType extends AbstractType
{
    protected $markten = [];

    protected $accounts = [];

    public function __construct(array $markten, array $accounts)
    {
        $this->markten = $markten;
        $this->accounts = $accounts;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('marktId', 'choice', ['choices' => $this->markten, 'label' => 'Markt'])
            ->add('dag', 'date', ['format' => 'dd MMMM yyyy', 'label' => 'Dag'])
            ->add('accountId', 'choice', ['choices' => $this->accounts, 'label' => 'Account'])
            ->add('pauseDetect', 'integer', ['label' => 'Pauze detectie in sec.'])
            ->add('save', 'submit', ['label' => 'Selecteer']);
    }

    public function getName()
    {
        return str_replace('\\', '_', __CLASS__);
    }
}