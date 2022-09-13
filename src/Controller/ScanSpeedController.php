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
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ScanSpeedController extends AbstractController
{
    /**
     * @Route("/scan-speed")
     * @Template()
     * @Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_SENIOR')")
     */
    public function indexAction(Request $request, MarktApi $api): array
    {
        $accounts = $api->getAccounts();
        $markten = $api->getMarkten();

        $marktId = $request->query->get('markt');
        $accountId = $request->query->get('account');
        $datum = $request->query->get('datum');
        $pauze = $request->query->get('pauze');

        if (!is_null($marktId) && !is_null($accountId) && !is_null($datum) && !is_null($pauze)) {
            $settings = (object) [
                'marktId' => null,
                'dag' => new \DateTime(),
                'accountId' => null,
                'pauseDetect' => 60 * 5,
            ];

            $dagvergunningen = $api->getDagvergunningen([
                'marktId' => $marktId,
                'dag' => $datum,
                'accountId' => $accountId,
                'doorgehaald' => -1,
            ], 0, 10000);

            $periods = [];
            $currentPeriod = null;
            $prevDagvergunning = null;
            $fnNewPeriod = function ($start) use (&$periods) {
                $new = (object) [
                    'start' => $start,
                    'end' => 0,
                    'duration' => 0,
                    'scans' => 0,
                    'avgTimePerScan' => 0,
                    'avgScansPerHour' => 0,
                ];
                $periods[] = $new;

                return $new;
            };
            $dagvergunningen = array_reverse($dagvergunningen);

            foreach ($dagvergunningen as $dagvergunning) {
                if (null === $currentPeriod || null === $prevDagvergunning) {
                    $currentPeriod = $fnNewPeriod(strtotime($dagvergunning['registratieDatumtijd']));
                    ++$currentPeriod->scans;
                    $prevDagvergunning = $dagvergunning;
                } elseif ((strtotime($dagvergunning['registratieDatumtijd']) - strtotime($prevDagvergunning['registratieDatumtijd'])) > $settings->pauseDetect) {
                    $currentPeriod->end = strtotime($prevDagvergunning['registratieDatumtijd']);
                    $currentPeriod = $fnNewPeriod(strtotime($dagvergunning['registratieDatumtijd']));
                    ++$currentPeriod->scans;
                    $prevDagvergunning = $dagvergunning;
                } else {
                    ++$currentPeriod->scans;
                    $prevDagvergunning = $dagvergunning;
                }
            }
            if (null !== $currentPeriod) {
                $currentPeriod->end = strtotime($prevDagvergunning['registratieDatumtijd']);
            }
            foreach ($periods as $period) {
                $period->duration = $period->end - $period->start;
                if (0 === $period->duration) {
                    $period->duration = 1;
                    $period->avgTimePerScan = -1;
                    $period->avgScansPerHour = -1;
                } else {
                    $period->avgTimePerScan = $period->duration / $period->scans;
                    $period->avgScansPerHour = (60 * 60) / $period->avgTimePerScan;
                }
            }

            return [
                'accounts' => $accounts,
                'accountId' => $accountId,
                'markten' => $markten,
                'marktId' => $marktId,
                'datum' => $datum,
                'pauze' => $pauze,
                'periods' => $periods,
            ];
        }

        $pauze = null === $pauze ? 300 : $pauze;

        return [
            'accounts' => $accounts,
            'markten' => $markten,
            'pauze' => $pauze,
            'marktId' => $marktId,
        ];
    }
}
