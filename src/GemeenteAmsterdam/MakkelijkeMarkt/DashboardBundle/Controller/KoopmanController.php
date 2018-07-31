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
use Symfony\Component\HttpFoundation\Request;

class KoopmanController extends Controller
{
    /**
     * @Route("/koopmannen")
     * @Template()
     * @Security("has_role('ROLE_ADMIN') or has_role('ROLE_SENIOR')")
     */
    public function indexAction(Request $request)
    {
        $page = $request->query->get('page', 0);
        $size = 30;

        $q = ['freeSearch' => $request->query->get('q'), 'erkenningsnummer' => $request->query->get('erkenningsnummer'), 'status' => $request->query->get('status', -1)];

        $koopmannen = $this->get('markt_api')->getKoopmannen($q, $page * $size, $size);

        return [
            'koopmannen' => $koopmannen,
            'pageNumber' => $page,
            'pageSize' => $size,
            'q' => $request->query->get('q'),
            'erkenningsnummer' => $request->query->get('erkenningsnummer'),
            'status' => $request->query->get('status', -1)
        ];
    }

    /**
     * @Route("/koopmannen/detail/{id}")
     * @Template()
     * @Security("has_role('ROLE_ADMIN') or has_role('ROLE_SENIOR')")
     */
    public function detailAction(Request $request, $id)
    {
        $koopman = $this->get('markt_api')->getKoopman($id);

        $dagvergunningenStartDatum = false;
        $dagvergunningenEindDatum = false;
        if ($request->query->has('dagvergunningenEindDatum') && $request->query->has('dagvergunningenStartDatum')) {
            $dagvergunningenStartDatum = \DateTime::createFromFormat('d-m-Y', $request->query->get('dagvergunningenStartDatum'));
            $dagvergunningenEindDatum = \DateTime::createFromFormat('d-m-Y', $request->query->get('dagvergunningenEindDatum'));
        }
        if ($dagvergunningenStartDatum === false || $dagvergunningenEindDatum === false || $dagvergunningenEindDatum->diff($dagvergunningenStartDatum)->days > 732) {
            $dagvergunningenEindDatum = new \DateTime();
            $dagvergunningenStartDatum = clone $dagvergunningenEindDatum;
            $dagvergunningenStartDatum->sub(new \DateInterval('P1M'));
        }

        $markten = $this->get('markt_api')->getMarkten();
        $marktId = $request->query->get('marktId', null);
        $markt = array_reduce($markten['results'], function ($carry, $markt) use ($marktId) {
            if ($markt->id == $marktId) {
                return $markt;
            }
            return $carry;
        });

        $params = ['koopmanId' => $koopman->id, 'dagStart' => $dagvergunningenStartDatum->format('Y-m-d'), 'dagEind' => $dagvergunningenEindDatum->format('Y-m-d')];
        if ($markt !== null) {
            $params['marktId'] = $markt->id;
        }
        $dagvergunningen = $this->get('markt_api')->getDagvergunningen($params, 0, 500);

        $stats = [
            'total' => 0,
            'doorgehaald' => 0,
            'status.?' => 0,
            'status.soll' => 0,
            'status.vpl' => 0,
            'status.vkk' => 0,
            'status.lot' => 0,
            'aanwezig.?' => 0,
            'aanwezig.zelf' => 0,
            'aanwezig.partner' => 0,
            'aanwezig.vervanger_met_toestemming' => 0,
            'aanwezig.vervanger_zonder_toestemming' => 0,
            'aanwezig.niet_aanwezig' => 0,
            'meters.aantal_3m' => 0,
            'meters.aantal_4m' => 0,
            'meters.aantal_1m' => 0,
            'meters.totaal' => 0,
            'extra.elektra_afgenomen' => 0,
            'extra.elektra_totaal' => 0,
            'extra.krachtstroom' => 0,
            'extra.reiniging' => 0,
        ];
        foreach ($dagvergunningen['results'] as $dagvergunning) {
            if ($dagvergunning->doorgehaald === false) {
                // totaal dagvergunningen (actief)
                $stats['total'] ++;
                // dagvergunningen per status
                if (isset($stats['status.' . $dagvergunning->status]) === true)
                    $stats['status.' . $dagvergunning->status] ++;
                else
                    $stats['status.?'] ++;
                // per aanwezigheid
                if (isset($stats['aanwezig.' . $dagvergunning->aanwezig]) === true)
                    $stats['aanwezig.' . $dagvergunning->aanwezig] ++;
                else
                    $stats['aanwezig.?'] ++;
                // per kraamlengte en totale kraamlengte
                $stats['meters.aantal_3m'] = $stats['meters.aantal_3m'] + $dagvergunning->aantal3MeterKramen;
                $stats['meters.aantal_4m'] = $stats['meters.aantal_4m'] + $dagvergunning->aantal4MeterKramen;
                $stats['meters.aantal_1m'] = $stats['meters.aantal_1m'] + $dagvergunning->extraMeters;
                $stats['meters.totaal'] = $stats['meters.totaal'] + ($dagvergunning->aantal3MeterKramen * 3) + ($dagvergunning->aantal4MeterKramen * 4) + ($dagvergunning->extraMeters * 1);
                // extra's
                if ($dagvergunning->aantalElektra > 0)
                    $stats['extra.elektra_afgenomen'] ++;
                    $stats['extra.elektra_totaal'] = $stats['extra.elektra_totaal'] + $dagvergunning->aantalElektra;
                if ($dagvergunning->krachtstroom === true)
                    $stats['extra.krachtstroom'] ++;
                if ($dagvergunning->reiniging === true)
                    $stats['extra.reiniging'] ++;
            } else {
                // doorgehaald
                $stats['doorgehaald'] ++;
            }
        }

        $lastQuarter = new \DateTime();
        $lastQuarter->modify('-3 months');
        list($startDate , $endDate) = $this->getQuarter($lastQuarter);

        $laatsteMaanden = [];
        $eersteDagVanDeMaand = new \DateTime();
        $eersteDagVanDeMaand->setDate($eersteDagVanDeMaand->format('Y'), $eersteDagVanDeMaand->format('m'), 1);
        $laatsteDagVanDeMaand = clone $eersteDagVanDeMaand;
        $laatsteDagVanDeMaand->setDate($laatsteDagVanDeMaand->format('Y'), $laatsteDagVanDeMaand->format('m'), cal_days_in_month(CAL_GREGORIAN, $laatsteDagVanDeMaand->format('m'), $laatsteDagVanDeMaand->format('Y')));
        $laatsteMaanden[] = ['label' => $eersteDagVanDeMaand->format('m-Y'), 'start' => clone $eersteDagVanDeMaand, 'eind' => clone $laatsteDagVanDeMaand];
        $eersteDagVanDeMaand->sub(new \DateInterval('P1M'));
        $laatsteDagVanDeMaand = clone $eersteDagVanDeMaand;
        $laatsteDagVanDeMaand->setDate($laatsteDagVanDeMaand->format('Y'), $laatsteDagVanDeMaand->format('m'), cal_days_in_month(CAL_GREGORIAN, $laatsteDagVanDeMaand->format('m'), $laatsteDagVanDeMaand->format('Y')));
        $laatsteMaanden[] = ['label' => $eersteDagVanDeMaand->format('m-Y'), 'start' => clone $eersteDagVanDeMaand, 'eind' => clone $laatsteDagVanDeMaand];
        $eersteDagVanDeMaand->sub(new \DateInterval('P1M'));
        $laatsteDagVanDeMaand = clone $eersteDagVanDeMaand;
        $laatsteDagVanDeMaand->setDate($laatsteDagVanDeMaand->format('Y'), $laatsteDagVanDeMaand->format('m'), cal_days_in_month(CAL_GREGORIAN, $laatsteDagVanDeMaand->format('m'), $laatsteDagVanDeMaand->format('Y')));
        $laatsteMaanden[] = ['label' => $eersteDagVanDeMaand->format('m-Y'), 'start' => clone $eersteDagVanDeMaand, 'eind' => clone $laatsteDagVanDeMaand];
        $eersteDagVanDeMaand->sub(new \DateInterval('P1M'));
        $laatsteDagVanDeMaand = clone $eersteDagVanDeMaand;
        $laatsteDagVanDeMaand->setDate($laatsteDagVanDeMaand->format('Y'), $laatsteDagVanDeMaand->format('m'), cal_days_in_month(CAL_GREGORIAN, $laatsteDagVanDeMaand->format('m'), $laatsteDagVanDeMaand->format('Y')));
        $laatsteMaanden[] = ['label' => $eersteDagVanDeMaand->format('m-Y'), 'start' => clone $eersteDagVanDeMaand, 'eind' => clone $laatsteDagVanDeMaand];

        return [
            'koopman' => $koopman,
            'dagvergunningen' => $dagvergunningen,
            'stats' => $stats,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'dagvergunningenEindDatum' => $dagvergunningenEindDatum,
            'dagvergunningenStartDatum' => $dagvergunningenStartDatum,
            'markten' => $markten,
            'markt' => $markt,
            'laatsteMaanden' => $laatsteMaanden
        ];
    }

    /**
     * @Route("/koopmannen/factuur/{id}/{startDate}/{endDate}")
     * @Route("/koopmannen/factuur/", name="factuur_blank")
     * @Security("has_role('ROLE_ADMIN') or has_role('ROLE_SENIOR')")
     */
    public function factuurOverzichtAction(Request $request, $id, $startDate, $endDate)
    {
        $api = $this->get('markt_api');

        $sDate = \DateTime::createFromFormat('d-m-Y', $startDate);
        $eDate = \DateTime::createFromFormat('d-m-Y', $endDate);

        $koopman = $api->getKoopman($id);
        $dagvergunningen = $api->getDagvergunningenByDate($id, $sDate, $eDate);

        $pdfService = $this->get('pdf_factuur');


        $pdf = $pdfService->generate($koopman, $dagvergunningen, $sDate, $eDate);
        $pdf->Output('factuur_' . $koopman->erkenningsnummer . '_' . $sDate->format('d-m-Y') . '_'  . $eDate->format('d-m-Y') . '.pdf', 'I');
        die;
    }

    /**
     * @param \DateTime $date
     * @return \DateTime[]
     */
    protected function getQuarter(\DateTime $date) {
        $startMonth = 1 + (ceil($date->format('m') / 3) - 1) * 3;
        $startDate = new \DateTime($date->format('Y') . '-' . $startMonth . '-' . '01');
        $endDate = clone $startDate;
        $endDate->modify('+2 months');
        $endDate->modify('last day of this month');
        return [$startDate, $endDate];
    }
}
