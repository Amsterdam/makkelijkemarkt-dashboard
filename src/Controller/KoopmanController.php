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
use App\Service\PdfFactuurService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class KoopmanController extends AbstractController
{
    /**
     * @Route("/koopmannen")
     *
     * @Template()
     *
     * @Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_SENIOR')")
     */
    public function indexAction(Request $request, MarktApi $api): array
    {
        $page = $request->query->get('page', 0);
        $size = 30;

        $q = ['freeSearch' => $request->query->get('q'), 'erkenningsnummer' => $request->query->get('erkenningsnummer'), 'status' => $request->query->get('status', -1)];

        $koopmannen = $api->getKoopmannen($q, $page * $size, $size);

        return [
            'koopmannen' => $koopmannen,
            'pageNumber' => $page,
            'pageSize' => $size,
            'q' => $request->query->get('q'),
            'erkenningsnummer' => $request->query->get('erkenningsnummer'),
            'status' => $request->query->get('status', -1),
        ];
    }

    /**
     * @Route("/koopmannen/detail/{id}")
     *
     * @Template()
     *
     * @Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_SENIOR')")
     */
    public function detailAction(Request $request, MarktApi $api, int $id)
    {
        $formBuilder = $this->createFormBuilder();

        $formBuilder->add('beginDatum', DateType::class, [
            'label' => false,
            'widget' => 'single_text',
            'format' => 'dd-MM-yyyy',
            'html5' => false,
        ]);

        $formBuilder->add('eindDatum', DateType::class, [
            'label' => false,
            'widget' => 'single_text',
            'format' => 'dd-MM-yyyy',
            'html5' => false,
        ]);

        $formBuilder->add('download', SubmitType::class, [
            'label' => 'Download',
        ]);

        $form = $formBuilder->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $startDate = $form->getData()['beginDatum']->format('d-m-Y');
            $endDate = $form->getData()['eindDatum']->format('d-m-Y');

            return $this->redirectToRoute('app_koopman_factuuroverzicht', [
                'id' => $id,
                'startDate' => $startDate,
                'endDate' => $endDate,
                ]);
        }

        $koopman = $api->getKoopman($id);

        $dagvergunningenStartDatum = false;
        $dagvergunningenEindDatum = false;
        if ($request->query->has('dagvergunningenEindDatum') && $request->query->has('dagvergunningenStartDatum')) {
            $dagvergunningenStartDatum = \DateTime::createFromFormat('d-m-Y', $request->query->get('dagvergunningenStartDatum'));
            $dagvergunningenEindDatum = \DateTime::createFromFormat('d-m-Y', $request->query->get('dagvergunningenEindDatum'));
        }
        if (false === $dagvergunningenStartDatum || false === $dagvergunningenEindDatum || $dagvergunningenEindDatum->diff($dagvergunningenStartDatum)->days > 732) {
            $dagvergunningenEindDatum = new \DateTime();
            $dagvergunningenStartDatum = clone $dagvergunningenEindDatum;
            $dagvergunningenStartDatum->sub(new \DateInterval('P1M'));
        }

        if ($koopman['handhavingsVerzoek']) {
            $koopman['handhavingsVerzoek'] = new \DateTime($koopman['handhavingsVerzoek']);
        }

        $markten = $api->getMarkten();
        $marktId = $request->query->get('marktId', null);
        $markt = array_reduce($markten, function ($carry, $markt) use ($marktId) {
            if ($markt['id'] == $marktId) {
                return $markt;
            }

            return $carry;
        });

        $marktIds = $marktId ? [$marktId] : [];
        $staanverplichtingRapport = $api->getRapportStaanverplichting($marktIds, $dagvergunningenStartDatum->format('Y-m-d'), $dagvergunningenEindDatum->format('Y-m-d'), 'alle');
        $rapportVanKoopman = current(array_filter($staanverplichtingRapport['output'], function ($record) use ($koopman) {
            return $record['koopman']['erkenningsnummer'] == $koopman['erkenningsnummer'];
        }));

        $params = ['koopmanId' => $koopman['id'], 'dagStart' => $dagvergunningenStartDatum->format('Y-m-d'), 'dagEind' => $dagvergunningenEindDatum->format('Y-m-d')];
        if (null !== $markt) {
            $params['marktId'] = $markt['id'];
        }
        $dagvergunningen = $api->getDagvergunningen($params, 0, 500);

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
            'aanwezig.niet_aanwezig' => 0,
            'aanwezig.zelf_aanw_na_controle' => 0,
            'aanwezig.niet_zelf_aanw_na_controle' => 0,
            'meters.aantal_3m' => 0,
            'meters.aantal_4m' => 0,
            'meters.aantal_1m' => 0,
            'meters.totaal' => 0,
            'extra.elektra_afgenomen' => 0,
            'extra.elektra_totaal' => 0,
            'extra.krachtstroom' => 0,
            'extra.reiniging' => 0,
        ];
        if ($rapportVanKoopman) {
            $stats['aanwezig.zelf_aanw_na_controle'] = $rapportVanKoopman['aantalActieveDagvergunningenZelfAanwezigNaControle'];
            $stats['aanwezig.niet_zelf_aanw_na_controle'] = $rapportVanKoopman['aantalActieveDagvergunningenNietZelfAanwezigNaControle'];
        }
        foreach ($dagvergunningen as $dagvergunning) {
            if (false === $dagvergunning['doorgehaald']) {
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
                $stats['meters.aantal_3m'] = $stats['meters.aantal_3m'] + $dagvergunning['aantal3MeterKramen'];
                $stats['meters.aantal_4m'] = $stats['meters.aantal_4m'] + $dagvergunning['aantal4MeterKramen'];
                $stats['meters.aantal_1m'] = $stats['meters.aantal_1m'] + $dagvergunning['extraMeters'];
                $stats['meters.totaal'] = $stats['meters.totaal'] + ($dagvergunning['aantal3MeterKramen'] * 3) + ($dagvergunning['aantal4MeterKramen'] * 4) + ($dagvergunning['extraMeters'] * 1);
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
            } else {
                // doorgehaald
                ++$stats['doorgehaald'];
            }

            if (isset($dagvergunning['controles'])) {
                foreach ($dagvergunning['controles'] as &$controle) {
                    $controle['registratieDatumtijd'] = new \DateTime($controle['registratieDatumtijd']);
                }
            }
        }

        $lastQuarter = new \DateTime();
        $lastQuarter->modify('-3 months');
        list($startDate, $endDate) = $this->getQuarter($lastQuarter);

        $laatsteMaanden = [];
        $eersteDagVanDeMaand = new \DateTime();
        $eersteDagVanDeMaand->setDate((int) $eersteDagVanDeMaand->format('Y'), (int) $eersteDagVanDeMaand->format('m'), 1);
        $laatsteDagVanDeMaand = clone $eersteDagVanDeMaand;
        $laatsteDagVanDeMaand->setDate((int) $laatsteDagVanDeMaand->format('Y'), (int) $laatsteDagVanDeMaand->format('m'), cal_days_in_month(CAL_GREGORIAN, (int) $laatsteDagVanDeMaand->format('m'), (int) $laatsteDagVanDeMaand->format('Y')));
        $laatsteMaanden[] = ['label' => $eersteDagVanDeMaand->format('m-Y'), 'start' => clone $eersteDagVanDeMaand, 'eind' => clone $laatsteDagVanDeMaand];
        $eersteDagVanDeMaand->sub(new \DateInterval('P1M'));
        $laatsteDagVanDeMaand = clone $eersteDagVanDeMaand;
        $laatsteDagVanDeMaand->setDate((int) $laatsteDagVanDeMaand->format('Y'), (int) $laatsteDagVanDeMaand->format('m'), cal_days_in_month(CAL_GREGORIAN, (int) $laatsteDagVanDeMaand->format('m'), (int) $laatsteDagVanDeMaand->format('Y')));
        $laatsteMaanden[] = ['label' => $eersteDagVanDeMaand->format('m-Y'), 'start' => clone $eersteDagVanDeMaand, 'eind' => clone $laatsteDagVanDeMaand];
        $eersteDagVanDeMaand->sub(new \DateInterval('P1M'));
        $laatsteDagVanDeMaand = clone $eersteDagVanDeMaand;
        $laatsteDagVanDeMaand->setDate((int) $laatsteDagVanDeMaand->format('Y'), (int) $laatsteDagVanDeMaand->format('m'), cal_days_in_month(CAL_GREGORIAN, (int) $laatsteDagVanDeMaand->format('m'), (int) $laatsteDagVanDeMaand->format('Y')));
        $laatsteMaanden[] = ['label' => $eersteDagVanDeMaand->format('m-Y'), 'start' => clone $eersteDagVanDeMaand, 'eind' => clone $laatsteDagVanDeMaand];
        $eersteDagVanDeMaand->sub(new \DateInterval('P1M'));
        $laatsteDagVanDeMaand = clone $eersteDagVanDeMaand;
        $laatsteDagVanDeMaand->setDate((int) $laatsteDagVanDeMaand->format('Y'), (int) $laatsteDagVanDeMaand->format('m'), cal_days_in_month(CAL_GREGORIAN, (int) $laatsteDagVanDeMaand->format('m'), (int) $laatsteDagVanDeMaand->format('Y')));
        $laatsteMaanden[] = ['label' => $eersteDagVanDeMaand->format('m-Y'), 'start' => clone $eersteDagVanDeMaand, 'eind' => clone $laatsteDagVanDeMaand];

        $vandaag = new \DateTime();
        $vandaag->setTime(0, 0, 0);

        return [
            'form' => $form->createView(),
            'koopman' => $koopman,
            'dagvergunningen' => $dagvergunningen,
            'stats' => $stats,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'dagvergunningenEindDatum' => $dagvergunningenEindDatum,
            'dagvergunningenStartDatum' => $dagvergunningenStartDatum,
            'markten' => $markten,
            'markt' => $markt,
            'laatsteMaanden' => $laatsteMaanden,
            'vandaag' => $vandaag,
        ];
    }

    /**
     * @Route("/koopmannen/controle/{id}")
     *
     * @Template()
     *
     * @Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_SENIOR')")
     */
    public function controleAction(Request $request, int $id, MarktApi $api): array
    {
        $koopman = $api->getKoopman($id);
        $start = $request->query->get('startdatum');
        $eind = $request->query->get('einddatum');
        if (null !== $start && null !== $eind) {
            $startdatum = new \DateTime(implode('-', array_reverse(explode('-', $start))));
            $einddatum = new \DateTime(implode('-', array_reverse(explode('-', $eind))));
        } else {
            $startdatum = new \DateTime();
            $startdatum->modify('-1 month');
            $einddatum = new \DateTime();
        }

        $vergunningen = $api->getDagvergunningenByDate($koopman['id'], $startdatum, $einddatum);
        $vergunningen = $vergunningen;

        foreach ($vergunningen as &$vergunning) {
            $vergunning->dag = new \DateTime($vergunning->dag);
            $vergunning['registratieDatumtijd'] = new \DateTime($vergunning['registratieDatumtijd']);
            if (isset($vergunning->controles)) {
                foreach ($vergunning->controles as &$controle) {
                    $controle['registratieDatumtijd'] = new \DateTime($controle['registratieDatumtijd']);
                }
            }
        }

        $methodes = [
            'handmatig' => 'HND',
            'scan-nfc' => 'NFC',
            'scan-barcode' => 'BAR',
        ];

        $vandaag = new \DateTime();

        return [
            'koopman' => $koopman,
            'startdatum' => $startdatum,
            'einddatum' => $einddatum,
            'vergunningen' => $vergunningen,
            'vandaag' => $vandaag,
            'methodes' => $methodes,
        ];
    }

    /**
     * @Route("/koopmannen/toggle_handhavingsverzoek/{id}")
     *
     * @Method("POST")
     *
     * @Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_SENIOR')")
     */
    public function toggleHandhavingsVerzoek(Request $request, int $id, MarktApi $api): RedirectResponse
    {
        $datum = implode('-', array_reverse(explode('-', $request->request->get('handhavingDatum'))));
        $date = new \DateTime($datum);
        $api->toggleHandhavingsverzoek($id, $date);

        return $this->redirectToRoute('app_koopman_detail', ['id' => $id]);
    }

    /**
     * @Route("/koopmannen/factuur/{id}/{startDate}/{endDate}")
     * @Route("/koopmannen/factuur/", name="factuur_blank")
     *
     * @Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_SENIOR')")
     */
    public function factuurOverzichtAction(MarktApi $api, int $id, string $startDate, string $endDate, PdfFactuurService $pdfFactuur): void
    {
        $sDate = \DateTime::createFromFormat('d-m-Y', $startDate);
        $eDate = \DateTime::createFromFormat('d-m-Y', $endDate);

        $koopman = $api->getKoopman($id);
        $dagvergunningen = $api->getDagvergunningenByDate($id, $sDate, $eDate);

        $pdf = $pdfFactuur->generate($koopman, $dagvergunningen, $sDate, $eDate);
        $pdf->Output('factuur_'.$koopman['erkenningsnummer'].'_'.$sDate->format('d-m-Y').'_'.$eDate->format('d-m-Y').'.pdf', 'I');
        exit;
    }

    /**
     * @return \DateTime[]
     */
    protected function getQuarter(\DateTime $date): array
    {
        $startMonth = 1 + (ceil($date->format('m') / 3) - 1) * 3;
        $startDate = new \DateTime($date->format('Y').'-'.$startMonth.'-01');
        $endDate = clone $startDate;
        $endDate->modify('+2 months');
        $endDate->modify('last day of this month');

        return [$startDate, $endDate];
    }
}
