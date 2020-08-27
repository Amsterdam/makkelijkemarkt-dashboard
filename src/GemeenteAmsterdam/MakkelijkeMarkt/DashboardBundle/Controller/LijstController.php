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

namespace GemeenteAmsterdam\MakkelijkeMarkt\DashboardBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use GemeenteAmsterdam\MakkelijkeMarkt\DashboardBundle\Form\Type\AccountEditType;
use Symfony\Component\HttpFoundation\Request;
use GemeenteAmsterdam\MakkelijkeMarkt\DashboardBundle\Form\Type\AccountCreateType;

class LijstController extends Controller
{
    /**
     * @Route("/lijsten")
     * @Route("/lijsten/{dag}", name="gemeenteamsterdam_makkelijkemarkt_dashboard_dagvergunning_index_met_dag")
     * @Template()
     * @Security("is_granted('IS_AUTHENTICATED_FULLY')")
     */
    public function indexAction($dag = null, Request $request)
    {
        $markten = $this->get('markt_api')->getMarkten();

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
    public function weeklijstSollicitantenPdfAction($marktId,$dag) {
        $pdfService = $this->get('pdf_lijst');
        $maandag = new \DateTime($dag);
        $tweeMaandenTerug = clone $maandag;
        $tweeMaandenTerug->modify('-2 months');
        $zondag = clone $maandag;
        $zondag->modify('+6 days');

        $marktApi = $this->get('markt_api');

        $markt = $marktApi->getMarkt($marktId);

        $parts = array(
            'Personen'  => $marktApi->getLijstenMetDatum($marktId,array('soll'),$tweeMaandenTerug, $maandag)
        );

        $pdf = $pdfService->generate($markt->naam, 'Weeklijst Sollicitanten ' . $maandag->format('d-m-Y') . ' - ' . $zondag->format('d-m-Y'), $parts);
        $pdf->Output('weeklijst_sollicitanten.pdf', 'I');
        die;
    }

    /**
     * @Route("/lijsten/barcode/{marktId}/{dag}")
     * @Security("is_granted('IS_AUTHENTICATED_FULLY')")
     */
    public function lijstBarcodePdfAction($marktId,$dag) {
        $pdfService = $this->get('pdf_barcode');
        $maandag = new \DateTime($dag);
        $tweeMaandenTerug = clone $maandag;
        $tweeMaandenTerug->modify('-2 months');
        $zondag = clone $maandag;
        $zondag->modify('+6 days');

        $marktApi = $this->get('markt_api');

        $markt = $marktApi->getMarkt($marktId);

        $parts = array(
            'Personen'  => $marktApi->getLijstenMetDatum($marktId,array('vpl','soll','vkk', 'tvpl', 'tvplz', 'exp', 'expf'),$tweeMaandenTerug, $maandag)
        );

        $pdf = $pdfService->generate($markt->naam, 'Barcode lijst ' . $maandag->format('d-m-Y') . ' - ' . $zondag->format('d-m-Y'), $parts);
        $pdf->Output('barcode_lijst.pdf', 'I');
        die;
    }

    /**
     * @Route("/lijsten/week/vasteplaatsen/{marktId}/{dag}")
     * @Security("is_granted('IS_AUTHENTICATED_FULLY')")
     */
    public function weeklijstVastePlaatsenPdfAction($marktId,$dag) {
        $pdfService = $this->get('pdf_lijst');
        $maandag = new \DateTime($dag);
        $tweeMaandenTerug = clone $maandag;
        $tweeMaandenTerug->modify('-2 months');
        $zondag = clone $maandag;
        $zondag->modify('+6 days');

        $marktApi = $this->get('markt_api');

        $markt = $marktApi->getMarkt($marktId);

        $parts = array(
            'Vaste plaatsen'  => $marktApi->getLijstenMetDatum($marktId,array('vpl'),$tweeMaandenTerug, $maandag),
            'Voorkeurs kaart, Tijdelijke Vaste Plaatshouders, Experimentele zone' => $marktApi->getLijstenMetDatum($marktId,array('vkk', 'tvpl', 'tvplz', 'exp', 'expf'),$tweeMaandenTerug, $maandag)
        );

        $pdf = $pdfService->generate($markt->naam, 'Weeklijst ' . $maandag->format('d-m-Y') . ' - ' . $zondag->format('d-m-Y'), $parts);
        $pdf->Output('weeklijst_vaste_plaatsen.pdf', 'I');
        die;
    }

    /**
     * @Route("/lijsten/a/{marktId}/{dag}")
     * @Security("is_granted('IS_AUTHENTICATED_FULLY')")
     */
    public function alijstPdfAction($marktId,$dag) {
        $pdfService = $this->get('pdf_lijst');
        $maandag = new \DateTime($dag);
        $zondag = clone $maandag;
        $zondag->modify('+6 days');
        $donderdag = clone $maandag;
        $donderdag->modify('+3 days');

        $marktApi = $this->get('markt_api');

        $markt = $marktApi->getMarkt($marktId);

        $parts = array(
            'Personen'  => array_merge(
                $marktApi->getLijstenMetDatum($marktId,array('soll'),$maandag, $donderdag)
                ),
        );

        $pdf = $pdfService->generate($markt->naam, 'A-lijst Weekend ' . $maandag->format('d-m-Y') . ' - ' . $zondag->format('d-m-Y'), $parts);
        $pdf->Output('weekend_a_lijst.pdf', 'I');
        die;
    }

    /**
     * @Route("/lijsten/b/{marktId}/{dag}")
     * @Security("is_granted('IS_AUTHENTICATED_FULLY')")
     */
    public function blijstPdfAction($marktId,$dag) {
        $pdfService = $this->get('pdf_lijst');
        $maandag = new \DateTime($dag);
        $zondag = clone $maandag;
        $zondag->modify('-1 day');
        $donderdag = clone $maandag;
        $donderdag->modify('+3 days');

        $tweeMaandenTerug = clone $maandag;
        $tweeMaandenTerug->modify('-2 months');


        $marktApi = $this->get('markt_api');

        $personenAlijst = $marktApi->getLijstenMetDatum($marktId,array('soll','vpl','vkk', 'tvpl', 'tvplz', 'exp', 'expf'),$maandag, $donderdag);
        $laatsteMaanden = array_merge(
                $marktApi->getLijstenMetDatum($marktId,array('soll'),$tweeMaandenTerug, $zondag)
        );

        $ids = array();
        foreach ($personenAlijst as $persoon) {
            $ids[] = $persoon->id;
        }

        $personen = array();
        foreach ($laatsteMaanden as $key => $persoon) {
            if (!in_array($persoon->id, $ids)) {
                $personen[] = $persoon;
            }
        }

        $markt = $marktApi->getMarkt($marktId);

        $parts = array(
            'Personen'  => $personen,
        );

        $pdf = $pdfService->generate($markt->naam, 'B-lijst Weekend ' . $maandag->format('d-m-Y') . ' - ' . $zondag->format('d-m-Y'), $parts);
        $pdf->Output('weekend_b_lijst.pdf', 'I');
        die;
    }
}
