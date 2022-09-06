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

class DagvergunningController extends AbstractController
{
    /**
     * @Route("/dagvergunningen")
     * @Route("/dagvergunningen/")
     * @Template()
     * @Security("is_granted('ROLE_USER')")
     */
    public function indexAction(Request $request, MarktApi $api)
    {
        if ($request->query->get('marktId') && $request->query->get('datum')) {
            $dag = implode('-', array_reverse(explode('-', $request->query->get('datum'))));

            return $this->redirectToRoute(
                'app_dagvergunning_dayview',
                ['marktId' => $request->query->get('marktId'), 'dag' => $dag]
            );
        }

        $markten = $api->getNonExpiredMarkten();
        $defaultDag = date('Y-m-d');

        return ['markten' => $markten, 'dag' => $defaultDag];
    }

    /**
     * @Route("/dagvergunningen/{marktId}/{dag}")
     * @Template
     * @Security("is_granted('ROLE_USER')")
     */
    public function dayviewAction(MarktApi $api, int $marktId, string $dag): array
    {
        $markten = $api->getNonExpiredMarkten();
        $selectedMarkt = '';
        foreach ($markten as $markt) {
            if ($markt['id'] == $marktId) {
                $selectedMarkt = $markt;
            }
        }

        $today = new \DateTime();
        $day = new \DateTime($dag);
        $tomorrow = clone $day;
        $tomorrow->add(new \DateInterval('P1D'));
        $yesterday = clone $day;
        $yesterday->sub(new \DateInterval('P1D'));

        $pinTotaalInclusief = 0;

        $dagvergunningen = $api->getDagvergunningen([
            'marktId' => $selectedMarkt['id'],
            'dag' => $dag,
            'doorgehaald' => -1,
        ], 0, 1000);

        $stats = [
            'total' => 0,
            'doorgehaald' => 0,
            'status.?' => 0,
            'status.soll' => 0,
            'status.vpl' => 0,
            'status.vkk' => 0,
            'status.tvpl' => 0,
            'status.tvplz' => 0,
            'status.exp' => 0,
            'status.expf' => 0,
            'status.lot' => 0,
            'aanwezig.?' => 0,
            'aanwezig.zelf' => 0,
            'aanwezig.partner' => 0,
            'aanwezig.vervanger_met_toestemming' => 0,
            'aanwezig.vervanger_zonder_toestemming' => 0,
            'aanwezig.vervanger_met_ontheffing' => 0,
            'meters.aantal_3m' => 0,
            'meters.aantal_4m' => 0,
            'meters.aantal_1m' => 0,
            'meters.aantal_groot' => 0,
            'meters.aantal_klein' => 0,
            'meters.totaal' => 0,
            'extra.elektra_afgenomen' => 0,
            'extra.elektra_totaal' => 0,
            'extra.krachtstroom' => 0,
            'extra.reiniging' => 0,
            'extra.agf' => 0,
        ];

        $multipleOnSameMarket = [];

        $audits = [
            'total' => 0,
            'first' => 0,
            'second' => 0,
        ];

        foreach ($dagvergunningen as &$dagvergunning) {
            if (false === $dagvergunning['doorgehaald']) {
                if (false === isset($multipleOnSameMarket[$dagvergunning['erkenningsnummer']])) {
                    $multipleOnSameMarket[$dagvergunning['erkenningsnummer']] = 0;
                }
                ++$multipleOnSameMarket[$dagvergunning['erkenningsnummer']];

                // totaal dagvergunningen (actief)
                ++$stats['total'];
                // dagvergunningen per status
                if (true === isset($stats['status.'.$dagvergunning['status']])) {
                    ++$stats['status.'.$dagvergunning['status']];
                } else {
                    ++$stats['status.?'];
                }
                // per aanwezigheid
                if (true === isset($stats['aanwezig.'.$dagvergunning['aanwezig']])) {
                    ++$stats['aanwezig.'.$dagvergunning['aanwezig']];
                } else {
                    ++$stats['aanwezig.?'];
                }
                // per kraamlengte en totale kraamlengte
                $stats['meters.aantal_3m'] += $dagvergunning['aantal3MeterKramen'];
                $stats['meters.aantal_4m'] += $dagvergunning['aantal4MeterKramen'];
                $stats['meters.aantal_1m'] += $dagvergunning['extraMeters'];
                $stats['meters.aantal_groot'] += $dagvergunning['grootPerMeter'];
                $stats['meters.aantal_klein'] += $dagvergunning['kleinPerMeter'];
                $stats['meters.totaal'] =
                    $stats['meters.totaal']
                    + ($dagvergunning['aantal3MeterKramen'] * 3)
                    + ($dagvergunning['aantal4MeterKramen'] * 4)
                    + ($dagvergunning['extraMeters'] * 1)
                    + ($dagvergunning['grootPerMeter'] * 1)
                    + ($dagvergunning['kleinPerMeter'] * 1);
                // extra's
                if ($dagvergunning['aantalElektra'] > 0) {
                    ++$stats['extra.elektra_afgenomen'];
                }
                $stats['extra.elektra_totaal'] = $stats['extra.elektra_totaal'] + $dagvergunning['aantalElektra'];
                if (true === $dagvergunning['krachtstroom']) {
                    ++$stats['extra.krachtstroom'];
                }
                if (true === $dagvergunning['reiniging']) {
                    ++$stats['extra.reiniging'];
                }

                if ($dagvergunning['afvalEilandAgf'] > 0) {
                    $stats['extra.agf'] += $dagvergunning['afvalEilandAgf'];
                }
            } else {
                // doorgehaald
                ++$stats['doorgehaald'];
            }

            if ($dagvergunning['audit']) {
                ++$audits['total'];
            }

            if (isset($dagvergunning['controles'])) {
                foreach ($dagvergunning['controles'] as &$controle) {
                    $controle['registratieDatumtijd'] = new \DateTime($controle['registratieDatumtijd']);

                    switch ($controle['ronde']) {
                        case 1:
                            $audits['first']++;
                            break;
                        case 2:
                            $audits['second']++;
                            break;
                    }
                }
            }

            if (isset($dagvergunning['factuur']) && isset($dagvergunning['factuur']['producten'])) {
                if (false === $dagvergunning['doorgehaald']) {
                    foreach ($dagvergunning['factuur']['producten'] as $product) {
                        $pinTotaalInclusief += (float) $product['totaal_inclusief'];
                    }
                }
            }

            if (isset($dagvergunning['koopman']['handhavingsVerzoek'])) {
                $dagvergunning['koopman']['handhavingsVerzoek'] = new \DateTime($dagvergunning['koopman']['handhavingsVerzoek']);
            }
        }

        $multipleOnSameMarket = array_filter($multipleOnSameMarket, function ($value) {
            return $value > 1;
        });

        $methodes = [
            'handmatig' => 'HND',
            'scan-nfc' => 'NFC',
            'scan-barcode' => 'BAR',
        ];

        return [
            'markten' => $markten,
            'dag' => $day,
            'gisteren' => $yesterday,
            'morgen' => $tomorrow,
            'vandaag' => $today,
            'selectedMarkt' => $selectedMarkt,
            'dagvergunningen' => $dagvergunningen,
            'stats' => $stats,
            'multipleOnSameMarktet' => $multipleOnSameMarket,
            'methodes' => $methodes,
            'pinTotaalInclusief' => $pinTotaalInclusief,
            'audits' => $audits,
            'marktId' => $marktId,
        ];
    }

    /**
     * @Route("/dagvergunningen/controlelijst_delete/{marktId}/{date}", methods={"POST"})
     * @Template
     * @Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_SENIOR')")
     */
    public function deleteControlelijstAction(Request $client, MarktApi $api, int $marktId, string $date): array
    {
        $markt = $api->getMarkt($marktId);
        $dag = new \DateTime($date);

        if (false === $this->isCsrfTokenValid('delete-controlelijst', $client->request->get('csrf'))) {
            throw $this->createAccessDeniedException();
        }

        $api->resetAudit($marktId, $dag);

        return [
            'markt' => $markt,
            'dag' => $dag,
        ];
    }
}
