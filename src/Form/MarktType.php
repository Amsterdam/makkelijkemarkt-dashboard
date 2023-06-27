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
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class MarktType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $markt = $options['data']['markt'];
        $dagvergunningMappings = $options['data']['dagvergunningMappings'];

        $mappingIdsAndAppLabel = $this->prepareDagvergunningenForChoiceType($dagvergunningMappings);
        $chosenMappingIds = $this->prepareDagvergunningenForChoiceType($markt['products']);

        $builder
            ->add('aantalKramen', IntegerType::class, [
                'label' => 'Aantal kramen (capaciteit)',
                'required' => true,
                'data' => $markt['aantalKramen'],
            ])
            ->add('maxAantalKramenPerOndernemer', IntegerType::class, [
                'label' => 'Maximaal aantal kramen per ondernemer',
                'required' => false,
                'data' => $markt['maxAantalKramenPerOndernemer'],
            ])
            ->add('aantalMeter', IntegerType::class, [
                'label' => 'Aantal meters (capaciteit)',
                'required' => true,
                'data' => $markt['aantalMeter'],
            ])
            ->add('auditMax', IntegerType::class, [
                'label' => 'Steekproef maximaal aanvullen tot',
                'required' => true,
                'data' => $markt['auditMax'],
            ])
            ->add('indelingstype', ChoiceType::class, [
                'label' => 'Indelingstype',
                'required' => true,
                'choices' => [
                    'traditioneel' => 'traditioneel',
                    'a/b-lijst' => 'a/b-lijst',
                ],
                'data' => $markt['indelingstype'],
            ])
            ->add('marktDagenTekst', TextType::class, [
                'label' => 'Marktdagen, als tekstuele omschrijving',
                'required' => false,
                'data' => $markt['marktDagenTekst'],
            ])
            ->add('marktDagen', ChoiceType::class, [
                'label' => 'Marktdagen',
                'expanded' => true,
                'multiple' => true,
                'required' => false,
                'choices' => [
                    'Maandag' => 'ma',
                    'Dinsdag' => 'di',
                    'Woensdag' => 'wo',
                    'Donderdag' => 'do',
                    'Vrijdag' => 'vr',
                    'Zaterdag' => 'za',
                    'Zondag' => 'zo',
                ],
                'data' => $markt['marktDagen'],
            ])
            ->add('indelingsTijdstipTekst', TextType::class, [
                'label' => 'Indelingstijdstip, als tekstuele omschrijving',
                'required' => false,
                'data' => $markt['indelingsTijdstipTekst'],
            ])
            ->add('telefoonNummerContact', TextType::class, [
                'label' => 'Contact telefoonnummer',
                'required' => false,
                'data' => $markt['telefoonNummerContact'],
            ])
            ->add('makkelijkeMarktActief', CheckboxType::class, [
                'label' => 'Zichtbaar in Makkelijke Markt app?',
                'required' => false,
                'data' => $markt['makkelijkeMarktActief'],
            ])
            ->add('kiesJeKraamActief', CheckboxType::class, [
                'label' => 'Zichtbaar in Kies Je Kraam app?',
                'required' => false,
                'data' => $markt['kiesJeKraamActief'],
            ])
            ->add('marktBeeindigd', CheckboxType::class, [
                'label' => 'Einddatum verstreken',
                'required' => false,
                'data' => $markt['marktBeeindigd'],
            ])
            ->add('kiesJeKraamFase', ChoiceType::class, [
                'label' => 'In welke fase bevind zich de implementatie van Kies je kraam op deze markt?',
                'required' => false,
                'choices' => [
                    'voorbereiding' => 'voorbereiding',
                    'activatie' => 'activatie',
                    'wenperiode' => 'wenperiode',
                    'live' => 'live',
                ],
                'data' => $markt['kiesJeKraamFase'],
            ])
            ->add('kiesJeKraamMededelingActief', CheckboxType::class, [
                'label' => 'Mededeling binnen Kies je kraam weergeven',
                'required' => false,
                'data' => $markt['kiesJeKraamMededelingActief'],
            ])
            ->add('kiesJeKraamMededelingTitel', TextType::class, [
                'label' => 'Mededeling: Titel',
                'required' => false,
                'data' => $markt['kiesJeKraamMededelingTitel'],
            ])
            ->add('kiesJeKraamMededelingTekst', TextType::class, [
                'label' => 'Mededeling: Tekst',
                'required' => false,
                'data' => $markt['kiesJeKraamMededelingTekst'],
            ])
            ->add('kiesJeKraamGeblokkeerdePlaatsen', TextType::class, [
                'label' => 'Kies je kraam: Geblokkeerde plaatsen op deze markt (er kan niet worden ingedeeld op deze plaatsen), komma gescheiden',
                'required' => false,
                'data' => $markt['kiesJeKraamGeblokkeerdePlaatsen'],
            ])
            ->add('kiesJeKraamGeblokkeerdeData', TextType::class, [
                'label' => 'Kies je kraam: Geblokkeerde data op deze markt (er wordt niet ingedeeld op deze data), komma gescheiden, invoeren als yyyy-mm-dd',
                'required' => false,
                'data' => $markt['kiesJeKraamGeblokkeerdeData'],
            ])
            ->add('kiesJeKraamEmailKramenzetter', TextType::class, [
                'label' => 'Kies je kraam: Stuur ook een e-mail met de indeling van de markt naar deze e-mailadressen, meerdere adressen kommagescheiden invoeren. Bijvoorbeeld: kramenzetter',
                'required' => false,
                'data' => $markt['kiesJeKraamEmailKramenzetter'],
            ])
            ->add('products', ChoiceType::class, [
                'label' => 'Dagvergunning producten in MM App',
                'required' => false,
                'choices' => array_flip($mappingIdsAndAppLabel),
                'data' => array_flip($chosenMappingIds),
                'multiple' => true,
                'expanded' => true,
                'choice_label' => function ($choice, $key, $value) {
                    return $key;
                },
            ])
            ->add('save', SubmitType::class, ['label' => 'Opslaan'])
        ;
    }

    private function prepareDagvergunningenForChoiceType($array)
    {
        $returnArray = [];
        foreach ($array as $mapping) {
            $returnArray[$mapping['id']] = $mapping['tariefType'].' - '.$mapping['appLabel'];
        }

        return $returnArray;
    }

    public function getName(): string
    {
        return str_replace('\\', '_', __CLASS__);
    }
}
