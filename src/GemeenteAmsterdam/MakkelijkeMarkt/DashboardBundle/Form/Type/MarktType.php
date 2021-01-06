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

class MarktType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('aantalKramen', 'integer', [
                'label' => 'Aantal kramen (capaciteit)',
                'required' => true
            ])
            ->add('aantalMeter', 'integer', [
                'label' => 'Aantal meters (capaciteit)',
                'required' => true
            ])
            ->add('auditMax', 'integer', [
                'label' => 'Steekproef maximaal aanvullen tot',
                'required' => true
            ])
            ->add('indelingstype', 'choice', [
                'label' => 'Indelingstype',
                'required' => true,
                'choices' => [
                    'traditioneel' => 'traditioneel',
                    'a/b-lijst' => 'a/b-lijst'
                ]
            ])
            ->add('marktDagenTekst', 'text', [
                'label' => 'Marktdagen, als tekstuele omschrijving',
                'required' => false,
            ])
            ->add('indelingsTijdstipTekst', 'text', [
                'label' => 'Indelingstijdstip, als tekstuele omschrijving',
                'required' => false,
            ])
            ->add('telefoonNummerContact', 'text', [
                'label' => 'Contact telefoonnummer',
                'required' => false,
            ])
            ->add('makkelijkeMarktActief', 'checkbox', [
                'label' => 'Zichtbaar in Makkelijke Markt app?',
                'required' => false
            ])
            ->add('kiesJeKraamActief', 'checkbox', [
                'label' => 'Zichtbaar in Kies Je Kraam app?',
                'required' => false
            ])
            ->add('kiesJeKraamFase', 'choice', [
                'label' => 'In welke fase bevind zich de implementatie van Kies je kraam op deze markt?',
                'required' => false,
                'choices' => [
                    'voorbereiding' => 'voorbereiding',
                    'activatie' => 'activatie',
                    'wenperiode' => 'wenperiode',
                    'live' => 'live'
                ]
            ])
            ->add('kiesJeKraamMededelingActief', 'checkbox', [
                'label' => 'Mededeling binnen Kies je kraam weergeven',
                'required' => false
            ])
            ->add('kiesJeKraamMededelingTitel', 'text', [
                'label' => 'Mededeling: Titel',
                'required' => false,
            ])
            ->add('kiesJeKraamMededelingTekst', 'text', [
                'label' => 'Mededeling: Tekst',
                'required' => false,
            ])
            ->add('kiesJeKraamGeblokkeerdePlaatsen', 'text', [
                'label' => 'Kies je kraam: Geblokkeerde plaatsen op deze markt (er kan niet worden ingedeeld op deze plaatsen), komma gescheiden',
                'required' => false,
            ])
            ->add('kiesJeKraamGeblokkeerdeData', 'text', [
                'label' => 'Kies je kraam: Geblokkeerde data op deze markt (er wordt niet ingedeeld op deze data), komma gescheiden, invoeren als yyyy-mm-dd',
                'required' => false,
            ])
            ->add('kiesJeKraamEmailKramenzetter', 'text', [
                'label' => 'Kies je kraam: Stuur ook een e-mail met de indeling van de markt naar deze e-mailadressen, meerdere adressen kommagescheiden invoeren. Bijvoorbeeld: kramenzetter',
                'required' => false,
            ])
            ->add('save', 'submit', ['label' => 'Opslaan'])
        ;
    }

    public function getName()
    {
        return str_replace('\\', '_', __CLASS__);
    }
}