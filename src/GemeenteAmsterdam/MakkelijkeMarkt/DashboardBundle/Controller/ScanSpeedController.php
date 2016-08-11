<?php

namespace GemeenteAmsterdam\MakkelijkeMarkt\DashboardBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use GemeenteAmsterdam\MakkelijkeMarkt\DashboardBundle\Form\Type\ScanSpeedSelectorType;
use Doctrine\Common\Collections\ArrayCollection;

class ScanSpeedController extends Controller
{
    /**
     * @Route("/scan-speed")
     * @Template()
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function indexAction(Request $request)
    {
        /* @var $api \GemeenteAmsterdam\MakkelijkeMarkt\DashboardBundle\Service\MarktApi */
        $api = $this->get('markt_api');
        $accounts = [];
        foreach ($api->getAccounts()['results'] as $account) {
            $accounts[$account->id] = $account->naam;
        }
        $markten = [];
        foreach ($api->getMarkten()['results'] as $markt) {
            $markten[$markt->id] = $markt->naam;
        }
        $settings = (object) [
            'marktId' => null,
            'dag' => new \DateTime(),
            'accountId' => null,
            'pauseDetect' => 60*5
        ];

        $form = $this->createForm(new ScanSpeedSelectorType($markten, $accounts), $settings, ['method' => 'GET']);

        $form->handleRequest($request);

        if ($form->isValid() && $form->isSubmitted()) {
            $dagvergunningen = $api->getDagvergunningen(['marktId' => $settings->marktId, 'dag' => $settings->dag->format('Y-m-d'), 'accountId' => $settings->accountId, 'doorgehaald' => -1], 0, 10000);

            $periods = new ArrayCollection();
            $currentPeriod = null;
            $prevDagvergunning = null;
            $fnNewPeriod = function ($start) use ($periods) { $new = (object) ['start' => $start, 'end' => 0, 'duration' => 0, 'scans' => 0, 'avgTimePerScan' => 0, 'avgScansPerHour' => 0]; $periods->add($new); return $new; };
            $dagvergunningen['results'] = array_reverse($dagvergunningen['results']);
            foreach ($dagvergunningen['results'] as $dagvergunning) {
                if ($currentPeriod === null || $prevDagvergunning === null) {
                    $currentPeriod = $fnNewPeriod(strtotime($dagvergunning->registratieDatumtijd));
                    $currentPeriod->scans ++;
                    $prevDagvergunning = $dagvergunning;
                } else if ((strtotime($dagvergunning->registratieDatumtijd) - strtotime($prevDagvergunning->registratieDatumtijd)) > $settings->pauseDetect) {
                    $currentPeriod->end = strtotime($prevDagvergunning->registratieDatumtijd);
                    $currentPeriod = $fnNewPeriod(strtotime($dagvergunning->registratieDatumtijd));
                    $currentPeriod->scans ++;
                    $prevDagvergunning = $dagvergunning;
                } else {
                    $currentPeriod->scans ++;
                    $prevDagvergunning = $dagvergunning;
                }
            }
            if ($currentPeriod !== null) {
                $currentPeriod->end = strtotime($prevDagvergunning->registratieDatumtijd);
            }
            foreach ($periods as $period) {
                $period->duration = $period->end - $period->start;
                if ($period->duration === 0) {
                    $period->duration = 1;
                    $period->avgTimePerScan = -1;
                    $period->avgScansPerHour = -1;
                } else {
                    $period->avgTimePerScan = $period->duration / $period->scans;
                    $period->avgScansPerHour = (60 * 60) / $period->avgTimePerScan;
                }

            }

            return ['form' => $form->createView(), 'periods' => $periods];

        }

        return ['form' => $form->createView()];
    }
}
