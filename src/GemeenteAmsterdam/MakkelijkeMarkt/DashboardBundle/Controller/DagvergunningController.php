<?php

namespace GemeenteAmsterdam\MakkelijkeMarkt\DashboardBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use GemeenteAmsterdam\MakkelijkeMarkt\DashboardBundle\Form\Type\AccountEditType;
use Symfony\Component\HttpFoundation\Request;
use GemeenteAmsterdam\MakkelijkeMarkt\DashboardBundle\Form\Type\AccountCreateType;

class DagvergunningController extends Controller
{
    /**
     * @Route("/dagvergunningen")
     * @Template()
     * @Security("is_granted('IS_AUTHENTICATED_FULLY')")
     */
    public function indexAction(Request $request)
    {
        $markten = $this->get('markt_api')->getMarkten();

        $defaultDag = date('Y-m-d');

        return ['markten' => $markten, 'dag' => $defaultDag];
    }

    /**
     * @Route("/dagvergunningen/{marktId}/{dag}")
     * @Template
     * @Security("is_granted('IS_AUTHENTICATED_FULLY')")
     */
    public function dayviewAction(Request $request, $marktId, $dag)
    {
        $markten = $this->get('markt_api')->getMarkten();
        $markt = array_reduce($markten['results'], function ($carry, $markt) use ($marktId) {
            if ($markt->id == $marktId) {
                return $markt;
            }
            return $carry;
        });

        $today = new \DateTime();
        $day = new \DateTime($dag);
        $tomorrow = clone $day;
        $tomorrow->add(new \DateInterval('P1D'));
        $yesterday = clone $day;
        $yesterday->sub(new \DateInterval('P1D'));

        $dagvergunningen = $this->get('markt_api')->getDagvergunningen(['marktId' => $marktId, 'dag' => $dag, 'doorgehaald' => -1], 0, 1000);

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

        $multipleOnSameMarket = [];
        foreach ($dagvergunningen['results'] as $dagvergunning) {
            if ($dagvergunning->doorgehaald === false) {
                if (isset($multipleOnSameMarket[$dagvergunning->erkenningsnummer]) === false)
                    $multipleOnSameMarket[$dagvergunning->erkenningsnummer] = 0;
                $multipleOnSameMarket[$dagvergunning->erkenningsnummer] ++;
            }
        }
        $multipleOnSameMarket = array_filter($multipleOnSameMarket, function ($value) {
            return $value > 1;
        });

        return ['markten' => $markten, 'dag' => $day, 'gisteren' => $yesterday, 'morgen' => $tomorrow, 'vandaag' => $today, 'selectedMarkt' => $markt, 'dagvergunningen' => $dagvergunningen, 'stats' => $stats, 'multipleOnSameMarktet' => $multipleOnSameMarket];
    }
}
