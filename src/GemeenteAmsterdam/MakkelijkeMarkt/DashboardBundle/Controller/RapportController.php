<?php

namespace GemeenteAmsterdam\MakkelijkeMarkt\DashboardBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use GemeenteAmsterdam\MakkelijkeMarkt\DashboardBundle\Form\Type\ScanSpeedSelectorType;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class RapportController extends Controller
{
    /**
     * @Route("/rapport/dubbelstaan/{dag}")
     * @Template()
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function dubbelstaanAction(Request $request, $dag = null)
    {
        /* @var $api \GemeenteAmsterdam\MakkelijkeMarkt\DashboardBundle\Service\MarktApi */
        $api = $this->get('markt_api');

        if ($dag === null || $dag === '')
            $dag = date('Y-m-d');

        $today = new \DateTime();
        $day = new \DateTime($dag);
        $tomorrow = clone $day;
        $tomorrow->add(new \DateInterval('P1D'));
        $yesterday = clone $day;
        $yesterday->sub(new \DateInterval('P1D'));

        $rapport = $api->getRapportDubbelstaan($dag);

        return ['rapport' => $rapport, 'dag' => $day, 'gisteren' => $yesterday, 'morgen' => $tomorrow, 'vandaag' => $today];
    }

    /**
     * @Route("/rapport/staanverplichting/")
     * @Template()
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function staanverplichtingAction(Request $request)
    {
        /* @var $api \GemeenteAmsterdam\MakkelijkeMarkt\DashboardBundle\Service\MarktApi */
        $api = $this->get('markt_api');

        $marktId = $request->query->get('marktId');
        $dagStart = $request->query->get('dagStart');
        $dagEind = $request->query->get('dagEind');
        $vergunningType = $request->query->get('vergunningType');

        $markten = $api->getMarkten();
        $markt = null;
        foreach ($markten['results'] as $i) {
            if ($i->id == $marktId) {
                $markt = $i;
            }
        }

        $vergunningTypes = [
            'alle' => 'Alle vergunningen',
            'soll' => 'Sollicitanten',
            'vkk'  => 'VKK',
            'vpl'  => 'Vaste plaats',
            'lot'  => 'Lot'
        ];

        $rapport = null;
        if ($marktId !== null && $dagStart !== null && $dagEind !== null)
            $rapport = $api->getRapportStaanverplichting($marktId, $dagStart, $dagEind, $vergunningType);

        return ['rapport' => $rapport, 'markt' => $markt, 'markten' => $markten, 'marktId' => $marktId, 'dagStart' => $dagStart, 'dagEind' => $dagEind, 'vergunningType' => $vergunningType, 'vergunningTypes' => $vergunningTypes];
    }

    /**
     * @Route("/rapport/facturen/")
     * @Template()
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function facturenAction(Request $request)
    {
        /* @var $api \GemeenteAmsterdam\MakkelijkeMarkt\DashboardBundle\Service\MarktApi */
        $api = $this->get('markt_api');

        $markten = $api->getMarkten();

        $marktId = $request->query->get('markt');
        $vanaf = $request->query->get('vanaf');
        $tot = $request->query->get('tot');

        $report = null;
        if (null !== $marktId && "0" !== $marktId) {
            if ('alle' === $marktId) {
                $report = $api->getFactuurOverzicht($vanaf, $tot);
            } else {
                $report = $api->getFactuurMarktOverzicht($marktId, $vanaf, $tot);

                $obj = $this->get('phpexcel')->createPHPExcelObject();
                $obj->getProperties()->setCreator("Gemeente Amsterdam")
                    ->setLastModifiedBy("Gemeente Amsterdam")
                    ->setTitle("Factuur rapportage")
                    ->setSubject("Factuur rapportage")
                    ->setDescription("Factuur rapportage")
                    ->setKeywords("Factuur rapportage")
                    ->setCategory("Factuur rapportage");

                $obj->setActiveSheetIndex(0);
                $activeSheet = $obj->getActiveSheet();


                $i = 1;
                foreach ($report as $result) {
                    if (1 === $i) {
                        $activeSheet->setCellValueByColumnAndRow(0, 1, 'dagvergunningId');
                        $activeSheet->setCellValueByColumnAndRow(1, 1, 'koopmanErkenningsnummer');
                        $activeSheet->setCellValueByColumnAndRow(2, 1, 'dag');
                        $activeSheet->setCellValueByColumnAndRow(3, 1, 'voorletters');
                        $activeSheet->setCellValueByColumnAndRow(4, 1, 'achternaam');
                        $activeSheet->setCellValueByColumnAndRow(5, 1, 'productNaam');
                        $activeSheet->setCellValueByColumnAndRow(6, 1, 'productAantal');
                        $activeSheet->setCellValueByColumnAndRow(7, 1, 'productBedrag');
                    }

                    $i++;
                    $activeSheet->setCellValueByColumnAndRow(0, $i, $result['dagvergunningId']);
                    $activeSheet->setCellValueByColumnAndRow(1, $i, $result['koopmanErkenningsnummer']);
                    $activeSheet->setCellValueByColumnAndRow(2, $i, $result['dag']['date']);
                    $activeSheet->setCellValueByColumnAndRow(3, $i, $result['voorletters']);
                    $activeSheet->setCellValueByColumnAndRow(4, $i, $result['achternaam']);
                    $activeSheet->setCellValueByColumnAndRow(5, $i, $result['productNaam']);
                    $activeSheet->setCellValueByColumnAndRow(6, $i, $result['productAantal']);
                    $activeSheet->setCellValueByColumnAndRow(7, $i, $result['productBedrag']);
                }

                $activeSheet->setTitle('Rapportage');

                // create the writer
                $writer = $this->get('phpexcel')->createWriter($obj, 'Excel5');
                // create the response
                $response = $this->get('phpexcel')->createStreamedResponse($writer);
                // adding headers
                $dispositionHeader = $response->headers->makeDisposition(
                    ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                    'report.xls'
                );
                $response->headers->set('Content-Type', 'text/vnd.ms-excel; charset=utf-8');
                $response->headers->set('Pragma', 'public');
                $response->headers->set('Cache-Control', 'maxage=1');
                $response->headers->set('Content-Disposition', $dispositionHeader);

                return $response;
            }
        }

        return ['markten' => $markten, 'marktId' => $marktId, 'vanaf' => $vanaf, 'tot' => $tot, 'report' => $report];
    }
}
