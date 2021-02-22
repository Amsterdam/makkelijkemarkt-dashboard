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
namespace App\Controller;

use App\Service\MarktApi;
use App\Service\PdfLijstService;
use App\Service\PdfBarcodeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;


class LijstController extends AbstractController
{
    /**
     * @Route("/lijsten")
     * @Route("/lijsten/{dag}", name="app_index_met_dag")
     * @Template()
     * @Security("is_granted('IS_AUTHENTICATED_FULLY')")
     */
    public function indexAction( string $dag = null, MarktApi $api): array
    {
        $markten = $api->getMarkten();

        if (null === $dag) {
            $maandag = new \DateTime();
            $maandag->setTimestamp(strtotime('monday this week'));

            $zondag = new \DateTime();
            $zondag->setTimestamp(strtotime('sunday this week'));
        } else {
            $maandag = new \DateTime($dag);

            $zondag = clone $maandag;
            $zondag->modify('+6 days');
        }
        $vorigeMaandag = clone $maandag;
        $vorigeMaandag = $vorigeMaandag->modify('-7 days');
        $volgendeMaandag = clone $maandag;
        $volgendeMaandag = $volgendeMaandag->modify('+7 days');


        return [
            'markten'         => $markten,
            'maandag'         => $maandag,
            'zondag'          => $zondag,
            'vorigeMaandag'   => $vorigeMaandag,
            'volgendeMaandag' => $volgendeMaandag
        ];
    }

    /**
     * @Route("/lijsten/week/sollicitanten/{marktId}/{dag}")
     * @Security("is_granted('IS_AUTHENTICATED_FULLY')")
     */
    public function weeklijstSollicitantenPdfAction(int $marktId, string $dag, MarktApi $api, PdfLijstService $pdfLijst): void
    {

        $maandag = new \DateTime($dag);
        $tweeMaandenTerug = clone $maandag;
        $tweeMaandenTerug->modify('-2 months');
        $zondag = clone $maandag;
        $zondag->modify('+6 days');

        $markt = $api->getMarkt($marktId);

        $parts = array(
            'Personen'  => $api->getLijstenMetDatum($marktId,array('soll'),$tweeMaandenTerug, $maandag)
        );

        $pdf = $pdfLijst->generate($markt['naam'], 'Weeklijst Sollicitanten ' . $maandag->format('d-m-Y') . ' - ' . $zondag->format('d-m-Y'), $parts);
        $pdf->Output('weeklijst_sollicitanten.pdf', 'I');
        
        die;
    }

    /**
     * @Route("/lijsten/barcode/{marktId}/{dag}")
     * @Security("is_granted('IS_AUTHENTICATED_FULLY')")
     */
    public function lijstBarcodePdfAction(int $marktId, string $dag, MarktApi $api, PdfBarcodeService $pdfBarcode): void 
    {
        
        $maandag = new \DateTime($dag);
        $tweeMaandenTerug = clone $maandag;
        $tweeMaandenTerug->modify('-2 months');
        $zondag = clone $maandag;
        $zondag->modify('+6 days');

        $markt = $api->getMarkt($marktId);

        $parts = array(
            'Personen'  => $api->getLijstenMetDatum($marktId,array('vpl','soll','vkk', 'tvpl', 'tvplz', 'exp', 'expf'),$tweeMaandenTerug, $maandag)
        );

        $pdf = $pdfBarcode->generate($markt['naam'], 'Barcode lijst ' . $maandag->format('d-m-Y') . ' - ' . $zondag->format('d-m-Y'), $parts);
        $pdf->Output('barcode_lijst.pdf', 'I');
        die;
    }

    /**
     * @Route("/lijsten/week/vasteplaatsen/{marktId}/{dag}")
     * @Security("is_granted('IS_AUTHENTICATED_FULLY')")
     */
    public function weeklijstVastePlaatsenPdfAction(int $marktId, string $dag, MarktApi $api, PdfLijstService $pdfLijst): void
    {
    
        $maandag = new \DateTime($dag);
        $tweeMaandenTerug = clone $maandag;
        $tweeMaandenTerug->modify('-2 months');
        $zondag = clone $maandag;
        $zondag->modify('+6 days');

        $markt = $api->getMarkt($marktId);

        $parts = array(
            'Vaste plaatsen'  => $api->getLijstenMetDatum($marktId,array('vpl'),$tweeMaandenTerug, $maandag),
            'Voorkeurs kaart, Tijdelijke Vaste Plaatshouders, Experimentele zone' => $api->getLijstenMetDatum($marktId,array('vkk', 'tvpl', 'tvplz', 'exp', 'expf'),$tweeMaandenTerug, $maandag)
        );

        $pdf = $pdfLijst->generate($markt['naam'], 'Weeklijst ' . $maandag->format('d-m-Y') . ' - ' . $zondag->format('d-m-Y'), $parts);
        $pdf->Output('weeklijst_vaste_plaatsen.pdf', 'I');
        die;
    }

    /**
     * @Route("/lijsten/a/{marktId}/{dag}")
     * @Security("is_granted('IS_AUTHENTICATED_FULLY')")
     */
    public function alijstPdfAction(int $marktId, string $dag, MarktApi $api, PdfLijstService $pdfLijst): void 
    {

        $maandag = new \DateTime($dag);
        $zondag = clone $maandag;
        $zondag->modify('+6 days');
        $donderdag = clone $maandag;
        $donderdag->modify('+3 days');

        $markt = $api->getMarkt($marktId);

        $parts = array(
            'Personen'  => array_merge(
                $api->getLijstenMetDatum($marktId,array('soll'),$maandag, $donderdag)
                ),
        );

        $pdf = $pdfLijst->generate($markt['naam'], 'A-lijst Weekend ' . $maandag->format('d-m-Y') . ' - ' . $zondag->format('d-m-Y'), $parts);
        $pdf->Output('weekend_a_lijst.pdf', 'I');
        die;
    }

    /**
     * @Route("/lijsten/b/{marktId}/{dag}")
     * @Security("is_granted('IS_AUTHENTICATED_FULLY')")
     */
    public function blijstPdfAction(int $marktId, string $dag, MarktApi $api, PdfLijstService $pdfLijst): void 
    {

        $maandag = new \DateTime($dag);
        $zondag = clone $maandag;
        $zondag->modify('-1 day');
        $donderdag = clone $maandag;
        $donderdag->modify('+3 days');

        $tweeMaandenTerug = clone $maandag;
        $tweeMaandenTerug->modify('-2 months');

        $personenAlijst = $api->getLijstenMetDatum($marktId,array('soll','vpl','vkk', 'tvpl', 'tvplz', 'exp', 'expf'),$maandag, $donderdag);
        $laatsteMaanden = array_merge(
                $api->getLijstenMetDatum($marktId,array('soll'),$tweeMaandenTerug, $zondag)
        );

        $ids = array();
        foreach ($personenAlijst as $persoon) {
            $ids[] = $persoon['id'];
        }

        $personen = array();
        foreach ($laatsteMaanden as $key => $persoon) {
            if (!in_array($persoon->id, $ids)) {
                $personen[] = $persoon;
            }
        }

        $markt = $api->getMarkt($marktId);

        $parts = array(
            'Personen'  => $personen,
        );

        $pdf = $pdfLijst->generate($markt['naam'], 'B-lijst Weekend ' . $maandag->format('d-m-Y') . ' - ' . $zondag->format('d-m-Y'), $parts);
        $pdf->Output('weekend_b_lijst.pdf', 'I');
        die;
    }
}
