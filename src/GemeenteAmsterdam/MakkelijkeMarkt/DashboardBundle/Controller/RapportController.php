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
use GemeenteAmsterdam\MakkelijkeMarkt\DashboardBundle\Form\Type\ScanSpeedSelectorType;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Count;

class RapportController extends Controller
{
    /**
     * @Route("/rapport/dubbelstaan/{dag}")
     * @Template()
     * @Security("has_role('ROLE_ADMIN') or has_role('ROLE_SENIOR')")
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
     * @Security("has_role('ROLE_ADMIN') or has_role('ROLE_SENIOR')")
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
        if ($marktId !== null && $dagStart !== null && $dagEind !== null) {
            $dagStart = \DateTime::createFromFormat('d-m-Y', $dagStart);
            $dagEind = \DateTime::createFromFormat('d-m-Y', $dagEind);
            $rapport = $api->getRapportStaanverplichting($marktId, $dagStart->format('Y-m-d'), $dagEind->format('Y-m-d'), $vergunningType);

            if ($request->query->get('format') === 'excel') {
                /** @var $obj \PHPExcel */
                $obj = $this->get('phpexcel')->createPHPExcelObject();
                $obj->getProperties()->setCreator("Gemeente Amsterdam")
                    ->setLastModifiedBy("Gemeente Amsterdam")
                    ->setTitle("Staanverplichting");
                $obj->setActiveSheetIndex(0);
                $activeSheet = $obj->getActiveSheet();

                $i = 1;
                foreach ($rapport->output as $record) {
                    if (1 === $i) {
                        $activeSheet->setCellValueByColumnAndRow(0, 1, 'Sollicitatienummer');
                        $activeSheet->setCellValueByColumnAndRow(1, 1, 'Status');
                        $activeSheet->setCellValueByColumnAndRow(2, 1, 'Erkenningsnummer');
                        $activeSheet->setCellValueByColumnAndRow(3, 1, 'Voorletters');
                        $activeSheet->setCellValueByColumnAndRow(4, 1, 'Achternaam');
                        $activeSheet->setCellValueByColumnAndRow(5, 1, 'Aantal actieve dagvergunningen in periode');
                        $activeSheet->setCellValueByColumnAndRow(6, 1, 'Waarvan zelf aanwezig');
                        $activeSheet->setCellValueByColumnAndRow(7, 1, 'Waarvan andere aanwezigheid');
                        $activeSheet->setCellValueByColumnAndRow(8, 1, 'Percentage aanwezig');

                        $activeSheet->getCellByColumnAndRow(0, 1)->getStyle()->getFont()->setBold(true);
                        $activeSheet->getCellByColumnAndRow(1, 1)->getStyle()->getFont()->setBold(true);
                        $activeSheet->getCellByColumnAndRow(2, 1)->getStyle()->getFont()->setBold(true);
                        $activeSheet->getCellByColumnAndRow(3, 1)->getStyle()->getFont()->setBold(true);
                        $activeSheet->getCellByColumnAndRow(4, 1)->getStyle()->getFont()->setBold(true);
                        $activeSheet->getCellByColumnAndRow(5, 1)->getStyle()->getFont()->setBold(true);
                        $activeSheet->getCellByColumnAndRow(6, 1)->getStyle()->getFont()->setBold(true);
                        $activeSheet->getCellByColumnAndRow(7, 1)->getStyle()->getFont()->setBold(true);
                        $activeSheet->getCellByColumnAndRow(8, 1)->getStyle()->getFont()->setBold(true);

                        $activeSheet->getColumnDimensionByColumn(0)->setWidth(10);
                        $activeSheet->getColumnDimensionByColumn(1)->setWidth(5);
                        $activeSheet->getColumnDimensionByColumn(2)->setWidth(20);
                        $activeSheet->getColumnDimensionByColumn(3)->setWidth(10);
                        $activeSheet->getColumnDimensionByColumn(4)->setWidth(30);
                    }
                    $i++;
                    $activeSheet->setCellValueByColumnAndRow(0, $i, $record->sollicitatie->sollicitatieNummer);
                    $activeSheet->setCellValueByColumnAndRow(1, $i, $record->sollicitatie->status);
                    $activeSheet->setCellValueByColumnAndRow(2, $i, $record->koopman->erkenningsnummer);
                    $activeSheet->getCellByColumnAndRow(2, $i)->getStyle()->getNumberFormat()->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_TEXT);
                    $activeSheet->setCellValueByColumnAndRow(3, $i, $record->koopman->voorletters);
                    $activeSheet->setCellValueByColumnAndRow(4, $i, $record->koopman->achternaam);
                    $activeSheet->setCellValueByColumnAndRow(5, $i, $record->aantalActieveDagvergunningen);
                    $activeSheet->setCellValueByColumnAndRow(6, $i, $record->aantalActieveDagvergunningenZelfAanwezig);
                    $activeSheet->setCellValueByColumnAndRow(7, $i, $record->aantalActieveDagvergunningenNietZelfAanwezig);
                    $activeSheet->setCellValueByColumnAndRow(8, $i, $record->aantalActieveDagvergunningen > 0 ? $record->percentageAanwezig : '');
                    $activeSheet->getCellByColumnAndRow(8, $i)->getStyle()->getNumberFormat()->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_PERCENTAGE);
                }
                $obj->getActiveSheet()->setAutoFilter($obj->getActiveSheet()->calculateWorksheetDimension());
                $activeSheet->setTitle($markt->naam);
                $activeSheet->freezePaneByColumnAndRow(0,2);

                $writer = $this->get('phpexcel')->createWriter($obj, 'Excel2007');
                $response = $this->get('phpexcel')->createStreamedResponse($writer);
                $dispositionHeader = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, 'staanverplichting_' . $markt->naam . '_' . $dagStart->format('d-m-Y') . '_' . $dagEind->format('d-m-Y') . '.xlsx');
                $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet; charset=utf-8');
                $response->headers->set('Pragma', 'public');
                $response->headers->set('Cache-Control', 'maxage=1');
                $response->headers->set('Content-Disposition', $dispositionHeader);

                return $response;
            }

        } else {
            $dagStart = (new \DateTime())->sub(new \DateInterval('P1M'));
            $dagEind = (new \DateTime());
        }

        return ['rapport' => $rapport, 'markt' => $markt, 'markten' => $markten, 'marktId' => $marktId, 'dagStart' => $dagStart, 'dagEind' => $dagEind, 'vergunningType' => $vergunningType, 'vergunningTypes' => $vergunningTypes];
    }

    /**
     * @Route("/rapport/facturen/")
     * @Template()
     * @Security("has_role('ROLE_ADMIN') or has_role('ROLE_SENIOR')")
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

    /**
     * @Route("/rapport/frequentie/markten")
     * @Template()
     * @Security("has_role('ROLE_ADMIN') or has_role('ROLE_SENIOR')")
     */
    public function frequentieMarktenAction(Request $request)
    {
        /* @var $api \GemeenteAmsterdam\MakkelijkeMarkt\DashboardBundle\Service\MarktApi */
        $api = $this->get('markt_api');

        $markten = $api->getMarkten();

        return ['markten' => $markten];
    }

    protected function frequentieMarktenDag($marktId, $datum = null) {
        /* @var $api \GemeenteAmsterdam\MakkelijkeMarkt\DashboardBundle\Service\MarktApi */
        $api = $this->get('markt_api');

        $markt = $api->getMarkt($marktId);

        $today = new \DateTime();
        $inputDate = null === $datum ? $today : new \DateTime($datum);

        list($startDate, $endDate) = $this->getQuarter($inputDate);

        $endDate = $endDate > $today ? $today : $endDate;

        $requiredAttendancePercentage = 1 / 13 * 10;

        $requiredAttendance = ceil($startDate->diff($endDate)->days/7 * $requiredAttendancePercentage);

        $lastQuarterDate = clone $startDate;
        $lastQuarterDate->modify("-1 day");
        $nextQuarterDate = clone $endDate;
        $nextQuarterDate->modify("+1 day");

        $response = $api->getFrequentieReport($marktId, 'dag', $startDate, $endDate);

        $koopmanId = null;
        $koopmannen = [];

        $emptyWeek = [];
        $s = clone $startDate;
        while ($s->format('W') != $endDate->format('W')) {
            $emptyWeek[(int)$s->format('W')] = 'Geen plaatsbezetting';
            $s->modify('+7 days');
        }
        $emptyWeek[(int)$s->format('W')] = 'Geen plaatsbezetting';

        foreach ($response as $item) {
            $id = $item['id'];
            if (!isset($koopmannen[$id])) {
                $koopmannen[$id]['voorletters'] = $item['voorletters'];
                $koopmannen[$id]['achternaam'] = $item['achternaam'];
                $koopmannen[$id]['id'] = $item['id'];
                $koopmannen[$id]['erkenningsnummer'] = $item['erkenningsnummer'];
                $koopmannen[$id]['weken_aanwezig'] = [];
                $koopmannen[$id]['weken_afwezig'] = [];
                $koopmannen[$id]['aanwezigheid_voldaan'] = false;
            }
            if (null === $item['week_nummer']) {
                $koopmannen[$id]['weken_afwezig'] = $emptyWeek;
                continue;
            }

            if ($item['aantal'] >= 3) {
                $koopmannen[$id]['weken_aanwezig'][(int)$item['week_nummer']] = $this->getDagen($item['dagen']);
            } else {
                $koopmannen[$id]['weken_afwezig'][(int)$item['week_nummer']] = $this->getDagen($item['dagen']);
            }

            if (count($koopmannen[$id]['weken_aanwezig']) >= $requiredAttendance) {
                $koopmannen[$id]['aanwezigheid_voldaan'] = true;
            }
        }

        foreach ($koopmannen as &$koopman) {
            foreach ($emptyWeek as $weeknumber => $text) {
                if (
                    !isset($koopman['weken_aanwezig'][$weeknumber]) &&
                    !isset($koopman['weken_afwezig'][$weeknumber])
                ) {
                    $koopman['weken_afwezig'][$weeknumber] = $text;
                }
            }
        }

        return [
            'markt'              => $markt,
            'startDate'          => $startDate,
            'endDate'            => $endDate,
            'inputDate'          => $inputDate,
            'today'              => $today,
            'lastQuarterDate'    => $lastQuarterDate,
            'nextQuarterDate'    => $nextQuarterDate,
            'requiredAttendance' => $requiredAttendance,
            'koopmannen'         => $koopmannen
        ];
    }

    /**
     * @Route("/rapport/frequentie/markten/dag/{marktId}/{datum}", name="gemeenteamsterdam_makkelijkemarkt_dashboard_rapport_frequentiemarktendag_datum")
     * @Route("/rapport/frequentie/markten/dag/{marktId}", name="gemeenteamsterdam_makkelijkemarkt_dashboard_rapport_frequentiemarktendag")
     * @Template()
     * @Security("has_role('ROLE_ADMIN') or has_role('ROLE_SENIOR')")
     */
    public function frequentieMarktenDagAction($marktId, $datum = null)
    {
        return $this->frequentieMarktenDag($marktId, $datum);
    }

    /**
     * @Route("/rapport/frequentie/markten/excel/dag/{marktId}/{datum}", name="gemeenteamsterdam_makkelijkemarkt_dashboard_rapport_frequentiemarktendag_excel_datum")
     * @Security("has_role('ROLE_ADMIN') or has_role('ROLE_SENIOR')")
     */
    public function frequentieMarktenDagExcelAction($marktId, $datum = null)
    {
        $data = $this->frequentieMarktenDag($marktId, $datum);

        $obj = $this->get('phpexcel')->createPHPExcelObject();
        $obj->getProperties()->setCreator("Gemeente Amsterdam")
            ->setLastModifiedBy("Gemeente Amsterdam")
            ->setTitle("Frequentie rapportage")
            ->setSubject("Frequentie rapportage")
            ->setDescription("Frequentie rapportage")
            ->setKeywords("Frequentie rapportage")
            ->setCategory("Frequentie rapportage");

        $obj->setActiveSheetIndex(0);
        $activeSheet = $obj->getActiveSheet();

        $activeSheet->setCellValueByColumnAndRow(0, 1, 'Frequentie dagmarkt - ' . $data['markt']->naam);
        $activeSheet->getStyleByColumnAndRow(0,1)->getFont()->setSize(18)->setBold(true);

        $activeSheet->setCellValueByColumnAndRow(0, 2, 'Bereik: ' . $data['startDate']->format('d-m-Y') . ' - ' . $data['endDate']->format('d-m-Y'));
        $activeSheet->getStyleByColumnAndRow(0,2)->getFont()->setSize(16)->setBold(false);

        $activeSheet->setCellValueByColumnAndRow(0, 4, 'Totaaloverzicht verplichting niet gehaald');
        $activeSheet->getStyleByColumnAndRow(0,4)->getFont()->setSize(17)->setBold(true);

        $activeSheet->setCellValueByColumnAndRow(0, 6, 'erkenningsnummer');
        $activeSheet->setCellValueByColumnAndRow(1, 6, 'achternaam');
        $activeSheet->setCellValueByColumnAndRow(2, 6, 'voorletters');
        $activeSheet->getStyleByColumnAndRow(0,6,2,6)->getFont()->setBold(true);

        $i = 7;
        foreach ($data['koopmannen'] as $koopman) {
            if (!$koopman['aanwezigheid_voldaan']) {
                $activeSheet->setCellValueByColumnAndRow(0, $i, $koopman['erkenningsnummer']);
                $activeSheet->setCellValueByColumnAndRow(1, $i, $koopman['achternaam']);
                $activeSheet->setCellValueByColumnAndRow(2, $i, $koopman['voorletters']);
                $i++;
            }
        }

        $activeSheet->getColumnDimension('B')->setAutoSize(true);
        $activeSheet->getColumnDimension('C')->setAutoSize(true);

        $i++;

        $activeSheet->setCellValueByColumnAndRow(0, $i, 'Rapportage per koopman');
        $activeSheet->getStyleByColumnAndRow(0,$i)->getFont()->setSize(17)->setBold(true);

        $i++;;

        foreach ($data['koopmannen'] as $koopman) {
            if (!$koopman['aanwezigheid_voldaan']) {
                $i++;
                $activeSheet->setCellValueByColumnAndRow(0, $i, $koopman['erkenningsnummer'] . '. ' . $koopman['achternaam'] . ', ' . $koopman['voorletters']);
                $activeSheet->getStyleByColumnAndRow(0,$i)->getFont()->setSize(15)->setBold(true);

                $i++;

                $activeSheet->setCellValueByColumnAndRow(0, $i, 'Week nummer');
                $activeSheet->setCellValueByColumnAndRow(1, $i, 'Status');
                $activeSheet->setCellValueByColumnAndRow(2, $i, 'dagen aanwezig');

                foreach ($koopman['weken_afwezig'] as $week_nummer => $aanwezig) {
                    $activeSheet->setCellValueByColumnAndRow(0, $i, $week_nummer);
                    $activeSheet->setCellValueByColumnAndRow(1, $i, 'Afwezig');
                    $activeSheet->setCellValueByColumnAndRow(2, $i, $aanwezig);
                    $activeSheet->getStyleByColumnAndRow(0,$i,2,$i)->getFill()
                                ->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)
                                ->getStartColor()
                                ->setRGB('ebcccc');
                    $i++;
                }

                foreach ($koopman['weken_aanwezig'] as $week_nummer => $aanwezig) {
                    $activeSheet->setCellValueByColumnAndRow(0, $i, $week_nummer);
                    $activeSheet->setCellValueByColumnAndRow(1, $i, 'Aanwezig');
                    $activeSheet->setCellValueByColumnAndRow(2, $i, $aanwezig);
                    $activeSheet->getStyleByColumnAndRow(0,$i,2,$i)->getFill()
                                ->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)
                                ->getStartColor()
                                ->setRGB('d0e9c6');
                    $i++;
                }
            }
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

    /**
     * @Route("/rapport/frequentie/markten/excel/soll/{marktId}/{datum}", name="gemeenteamsterdam_makkelijkemarkt_dashboard_rapport_frequentiemarktensoll_excel_datum")
     * @Security("has_role('ROLE_ADMIN') or has_role('ROLE_SENIOR')")
     */
    public function frequentieMarktenSollExcelAction($marktId, $datum = null)
    {
        $data = $this->frequentieMarktenSoll($marktId, $datum);

        $obj = $this->get('phpexcel')->createPHPExcelObject();
        $obj->getProperties()->setCreator("Gemeente Amsterdam")
            ->setLastModifiedBy("Gemeente Amsterdam")
            ->setTitle("Frequentie rapportage")
            ->setSubject("Frequentie rapportage")
            ->setDescription("Frequentie rapportage")
            ->setKeywords("Frequentie rapportage")
            ->setCategory("Frequentie rapportage");

        $obj->setActiveSheetIndex(0);
        $activeSheet = $obj->getActiveSheet();

        $activeSheet->setCellValueByColumnAndRow(0, 1, 'Frequentie dagmarkt - ' . $data['markt']->naam);
        $activeSheet->getStyleByColumnAndRow(0,1)->getFont()->setSize(18)->setBold(true);

        $activeSheet->setCellValueByColumnAndRow(0, 2, 'Bereik: ' . $data['startDate']->format('d-m-Y') . ' - ' . $data['endDate']->format('d-m-Y'));
        $activeSheet->getStyleByColumnAndRow(0,2)->getFont()->setSize(16)->setBold(false);

        $activeSheet->setCellValueByColumnAndRow(0, 4, 'Totaaloverzicht verplichting niet gehaald');
        $activeSheet->getStyleByColumnAndRow(0,4)->getFont()->setSize(17)->setBold(true);

        $activeSheet->setCellValueByColumnAndRow(0, 6, 'erkenningsnummer');
        $activeSheet->setCellValueByColumnAndRow(1, 6, 'achternaam');
        $activeSheet->setCellValueByColumnAndRow(2, 6, 'voorletters');
        $activeSheet->getStyleByColumnAndRow(0,6,2,6)->getFont()->setBold(true);

        $i = 7;
        foreach ($data['koopmannen'] as $koopman) {
            if (!$koopman['aanwezigheid_voldaan']) {
                $activeSheet->setCellValueByColumnAndRow(0, $i, $koopman['erkenningsnummer']);
                $activeSheet->setCellValueByColumnAndRow(1, $i, $koopman['achternaam']);
                $activeSheet->setCellValueByColumnAndRow(2, $i, $koopman['voorletters']);
                $i++;
            }
        }

        $activeSheet->getColumnDimension('B')->setAutoSize(true);
        $activeSheet->getColumnDimension('C')->setAutoSize(true);

        $i++;

        $activeSheet->setCellValueByColumnAndRow(0, $i, 'Rapportage per koopman');
        $activeSheet->getStyleByColumnAndRow(0,$i)->getFont()->setSize(17)->setBold(true);

        $i++;;

        foreach ($data['koopmannen'] as $koopman) {
            if (!$koopman['aanwezigheid_voldaan']) {
                $i++;
                $activeSheet->setCellValueByColumnAndRow(0, $i, $koopman['erkenningsnummer'] . '. ' . $koopman['achternaam'] . ', ' . $koopman['voorletters']);
                $activeSheet->getStyleByColumnAndRow(0,$i)->getFont()->setSize(15)->setBold(true);

                $i++;

                $activeSheet->setCellValueByColumnAndRow(0, $i, 'Week nummer');
                $activeSheet->setCellValueByColumnAndRow(1, $i, 'Status');
                $activeSheet->setCellValueByColumnAndRow(2, $i, 'dagen aanwezig');

                foreach ($koopman['weken_afwezig'] as $week_nummer => $aanwezig) {
                    $activeSheet->setCellValueByColumnAndRow(0, $i, $week_nummer);
                    $activeSheet->setCellValueByColumnAndRow(1, $i, 'Afwezig');
                    $activeSheet->setCellValueByColumnAndRow(2, $i, $aanwezig);
                    $activeSheet->getStyleByColumnAndRow(0,$i,2,$i)->getFill()
                        ->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)
                        ->getStartColor()
                        ->setRGB('ebcccc');
                    $i++;
                }

                foreach ($koopman['weken_aanwezig'] as $week_nummer => $aanwezig) {
                    $activeSheet->setCellValueByColumnAndRow(0, $i, $week_nummer);
                    $activeSheet->setCellValueByColumnAndRow(1, $i, 'Aanwezig');
                    $activeSheet->setCellValueByColumnAndRow(2, $i, $aanwezig);
                    $activeSheet->getStyleByColumnAndRow(0,$i,2,$i)->getFill()
                        ->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)
                        ->getStartColor()
                        ->setRGB('d0e9c6');
                    $i++;
                }
            }
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

    /**
     * @Route("/rapport/frequentie/markten/excel/week/{marktId}/{datum}", name="gemeenteamsterdam_makkelijkemarkt_dashboard_rapport_frequentiemarktenweek_excel_datum")
     * @Security("has_role('ROLE_ADMIN') or has_role('ROLE_SENIOR')")
     */
    public function frequentieMarktenWeekExcelAction($marktId, $datum = null)
    {
        $data = $this->frequentieMarktenWeek($marktId, $datum);

        $obj = $this->get('phpexcel')->createPHPExcelObject();
        $obj->getProperties()->setCreator("Gemeente Amsterdam")
            ->setLastModifiedBy("Gemeente Amsterdam")
            ->setTitle("Frequentie rapportage")
            ->setSubject("Frequentie rapportage")
            ->setDescription("Frequentie rapportage")
            ->setKeywords("Frequentie rapportage")
            ->setCategory("Frequentie rapportage");

        $obj->setActiveSheetIndex(0);
        $activeSheet = $obj->getActiveSheet();

        $activeSheet->setCellValueByColumnAndRow(0, 1, 'Frequentie dagmarkt - ' . $data['markt']->naam);
        $activeSheet->getStyleByColumnAndRow(0,1)->getFont()->setSize(18)->setBold(true);

        $activeSheet->setCellValueByColumnAndRow(0, 2, 'Bereik: ' . $data['startDate']->format('d-m-Y') . ' - ' . $data['endDate']->format('d-m-Y'));
        $activeSheet->getStyleByColumnAndRow(0,2)->getFont()->setSize(16)->setBold(false);

        $activeSheet->setCellValueByColumnAndRow(0, 4, 'Totaaloverzicht verplichting niet gehaald');
        $activeSheet->getStyleByColumnAndRow(0,4)->getFont()->setSize(17)->setBold(true);

        $activeSheet->setCellValueByColumnAndRow(0, 6, 'erkenningsnummer');
        $activeSheet->setCellValueByColumnAndRow(1, 6, 'achternaam');
        $activeSheet->setCellValueByColumnAndRow(2, 6, 'voorletters');
        $activeSheet->getStyleByColumnAndRow(0,6,2,6)->getFont()->setBold(true);

        $i = 7;
        foreach ($data['koopmannen'] as $koopman) {
            if (!$koopman['aanwezigheid_voldaan']) {
                $activeSheet->setCellValueByColumnAndRow(0, $i, $koopman['erkenningsnummer']);
                $activeSheet->setCellValueByColumnAndRow(1, $i, $koopman['achternaam']);
                $activeSheet->setCellValueByColumnAndRow(2, $i, $koopman['voorletters']);
                $i++;
            }
        }

        $activeSheet->getColumnDimension('B')->setAutoSize(true);
        $activeSheet->getColumnDimension('C')->setAutoSize(true);

        $i++;

        $activeSheet->setCellValueByColumnAndRow(0, $i, 'Rapportage per koopman');
        $activeSheet->getStyleByColumnAndRow(0,$i)->getFont()->setSize(17)->setBold(true);

        $i++;;

        foreach ($data['koopmannen'] as $koopman) {
            if (!$koopman['aanwezigheid_voldaan']) {
                $i++;
                $activeSheet->setCellValueByColumnAndRow(0, $i, $koopman['erkenningsnummer'] . '. ' . $koopman['achternaam'] . ', ' . $koopman['voorletters']);
                $activeSheet->getStyleByColumnAndRow(0,$i)->getFont()->setSize(15)->setBold(true);

                $i++;

                $activeSheet->setCellValueByColumnAndRow(0, $i, 'Week nummer');
                $activeSheet->setCellValueByColumnAndRow(1, $i, 'Status');
                $activeSheet->setCellValueByColumnAndRow(2, $i, 'dagen aanwezig');

                foreach ($koopman['weken_afwezig'] as $week_nummer => $aanwezig) {
                    $activeSheet->setCellValueByColumnAndRow(0, $i, $week_nummer);
                    $activeSheet->setCellValueByColumnAndRow(1, $i, 'Afwezig');
                    $activeSheet->setCellValueByColumnAndRow(2, $i, $aanwezig);
                    $activeSheet->getStyleByColumnAndRow(0,$i,2,$i)->getFill()
                        ->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)
                        ->getStartColor()
                        ->setRGB('ebcccc');
                    $i++;
                }

                foreach ($koopman['weken_aanwezig'] as $week_nummer => $aanwezig) {
                    $activeSheet->setCellValueByColumnAndRow(0, $i, $week_nummer);
                    $activeSheet->setCellValueByColumnAndRow(1, $i, 'Aanwezig');
                    $activeSheet->setCellValueByColumnAndRow(2, $i, $aanwezig);
                    $activeSheet->getStyleByColumnAndRow(0,$i,2,$i)->getFill()
                        ->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)
                        ->getStartColor()
                        ->setRGB('d0e9c6');
                    $i++;
                }
            }
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

    protected function frequentieMarktenSoll($marktId, $datum = null) {

        /* @var $api \GemeenteAmsterdam\MakkelijkeMarkt\DashboardBundle\Service\MarktApi */
        $api = $this->get('markt_api');

        $markt = $api->getMarkt($marktId);

        $today = new \DateTime();
        $inputDate = null === $datum ? $today : new \DateTime($datum);

        list($startDate, $endDate) = $this->getYear($inputDate);

        $endDate = $endDate > $today ? $today : $endDate;

        $requiredAttendance = 4;

        $lastQuarterDate = clone $startDate;
        $lastQuarterDate->modify("-1 day");
        $nextQuarterDate = clone $endDate;
        $nextQuarterDate->modify("+1 day");

        $response = $api->getFrequentieReport($marktId, 'soll', $startDate, $endDate);

        $koopmanId = null;
        $koopmannen = [];

        foreach ($response as $item) {
            $id = $item['id'];
            if (!isset($koopmannen[$id])) {
                $koopmannen[$id]['voorletters'] = $item['voorletters'];
                $koopmannen[$id]['achternaam'] = $item['achternaam'];
                $koopmannen[$id]['id'] = $item['id'];
                $koopmannen[$id]['erkenningsnummer'] = $item['erkenningsnummer'];
                $koopmannen[$id]['weken_aanwezig'] = [];
                $koopmannen[$id]['weken_afwezig'] = [];
                $koopmannen[$id]['aanwezigheid_voldaan'] = false;
            }
            if (null === $item['week_nummer']) {
                $koopmannen[$id]['weken_aanwezig'] = [];
                continue;
            }


            $koopmannen[$id]['weken_aanwezig'][(int)$item['week_nummer']] = $this->getDagen($item['dagen']);

            $koopmannen[$id]['totaal_aanwezig'] = isset($koopmannen[$id]['totaal_aanwezig']) ? $koopmannen[$id]['totaal_aanwezig'] + $item['aantal'] : $item['aantal'];

            if ($koopmannen[$id]['totaal_aanwezig'] >= $requiredAttendance) {
                $koopmannen[$id]['aanwezigheid_voldaan'] = true;
            }
        }

        return [
            'markt'              => $markt,
            'startDate'          => $startDate,
            'endDate'            => $endDate,
            'inputDate'          => $inputDate,
            'today'              => $today,
            'lastQuarterDate'    => $lastQuarterDate,
            'nextQuarterDate'    => $nextQuarterDate,
            'requiredAttendance' => $requiredAttendance,
            'koopmannen'         => $koopmannen
        ];
    }

    /**
     * @Route("/rapport/frequentie/markten/soll/{marktId}/{datum}", name="gemeenteamsterdam_makkelijkemarkt_dashboard_rapport_frequentiemarktensoll_datum")
     * @Route("/rapport/frequentie/markten/soll/{marktId}", name="gemeenteamsterdam_makkelijkemarkt_dashboard_rapport_frequentiemarktensoll")
     * @Template()
     * @Security("has_role('ROLE_ADMIN') or has_role('ROLE_SENIOR')")
     */
    public function frequentieMarktenSollAction($marktId, $datum = null)
    {
        return $this->frequentieMarktenSoll($marktId, $datum);
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

    /**
     * @param \DateTime $date
     * @return \DateTime[]
     */
    protected function getYear(\DateTime $date) {
        if ($date->format('m') >= 10) {
            $startDate = new \DateTime($date->format('Y') . '-10-01');
        } else {
            $startDate = new \DateTime(($date->format('Y') - 1) . '-10-01');
        }
        $endDate = clone $startDate;
        $endDate->modify('september 30');
        $endDate->modify('+1 year');
        return [$startDate, $endDate];
    }

    protected function getDagen($string) {

        $dagen = explode('|', $string);
        $output = '';
        foreach ($dagen as $dag) {
            $date = new \Datetime($dag);
            if (strlen($output) >= 1) {
                $output .= ', ';
            }
            switch ($date->format('N')) {
                case "1":
                    $output .= 'maandag';
                    break;
                case "2":
                    $output .= 'dinsdag';
                    break;
                case "3":
                    $output .= 'woensdag';
                    break;
                case "4":
                    $output .= 'donderdag';
                    break;
                case "5":
                    $output .= 'vrijdag';
                    break;
                case "6":
                    $output .= 'zaterdag';
                    break;
                case "7":
                    $output .= 'zondag';
                    break;

            }
            $output .= ' ' . $date->format('d-m');
        }
        return $output;
    }

    protected function frequentieMarktenWeek($marktId, $datum = null) {
        /* @var $api \GemeenteAmsterdam\MakkelijkeMarkt\DashboardBundle\Service\MarktApi */
        $api = $this->get('markt_api');

        $markt = $api->getMarkt($marktId);

        $today = new \DateTime();
        $inputDate = null === $datum ? $today : new \DateTime($datum);

        list($startDate, $endDate) = $this->getQuarter($inputDate);

        $endDate = $endDate > $today ? $today : $endDate;

        $requiredAttendancePercentage = 1 / 13 * 10;

        $requiredAttendance = ceil($startDate->diff($endDate)->days/7 * $requiredAttendancePercentage);

        $lastQuarterDate = clone $startDate;
        $lastQuarterDate->modify("-1 day");
        $nextQuarterDate = clone $endDate;
        $nextQuarterDate->modify("+1 day");

        $response = $api->getFrequentieReport($marktId, 'week', $startDate, $endDate);

        $koopmanId = null;
        $koopmannen = [];

        $emptyWeek = [];
        $s = clone $startDate;
        while ($s->format('W') != $endDate->format('W')) {
            $emptyWeek[(int)$s->format('W')] = 'Geen plaatsbezetting';
            $s->modify('+7 days');
        }
        $emptyWeek[(int)$s->format('W')] = 'Geen plaatsbezetting';

        foreach ($response as $item) {
            $id = $item['id'];
            if (!isset($koopmannen[$id])) {
                $koopmannen[$id]['voorletters'] = $item['voorletters'];
                $koopmannen[$id]['achternaam'] = $item['achternaam'];
                $koopmannen[$id]['id'] = $item['id'];
                $koopmannen[$id]['erkenningsnummer'] = $item['erkenningsnummer'];
                $koopmannen[$id]['weken_aanwezig'] = [];
                $koopmannen[$id]['weken_afwezig'] = [];
                $koopmannen[$id]['aanwezigheid_voldaan'] = false;
            }
            if (null === $item['week_nummer']) {
                $koopmannen[$id]['weken_afwezig'] = $emptyWeek;
                continue;
            }

            if ($item['aantal'] >= 1) {
                $koopmannen[$id]['weken_aanwezig'][(int)$item['week_nummer']] = $this->getDagen($item['dagen']);
            } else {        /* @var $api \GemeenteAmsterdam\MakkelijkeMarkt\DashboardBundle\Service\MarktApi */
        $api = $this->get('markt_api');

        $markt = $api->getMarkt($marktId);

        $today = new \DateTime();
        $inputDate = null === $datum ? $today : new \DateTime($datum);

        list($startDate, $endDate) = $this->getQuarter($inputDate);

        $endDate = $endDate > $today ? $today : $endDate;

        $requiredAttendancePercentage = 1 / 13 * 10;

        $requiredAttendance = ceil($startDate->diff($endDate)->days/7 * $requiredAttendancePercentage);

        $lastQuarterDate = clone $startDate;
        $lastQuarterDate->modify("-1 day");
        $nextQuarterDate = clone $endDate;
        $nextQuarterDate->modify("+1 day");

        $response = $api->getFrequentieReport($marktId, 'week', $startDate, $endDate);

        $koopmanId = null;
        $koopmannen = [];

        $emptyWeek = [];
        $s = clone $startDate;
        while ($s->format('W') != $endDate->format('W')) {
            $emptyWeek[(int)$s->format('W')] = 'Geen plaatsbezetting';
            $s->modify('+7 days');
        }
        $emptyWeek[(int)$s->format('W')] = 'Geen plaatsbezetting';

        foreach ($response as $item) {
            $id = $item['id'];
            if (!isset($koopmannen[$id])) {
                $koopmannen[$id]['voorletters'] = $item['voorletters'];
                $koopmannen[$id]['achternaam'] = $item['achternaam'];
                $koopmannen[$id]['id'] = $item['id'];
                $koopmannen[$id]['weken_aanwezig'] = [];
                $koopmannen[$id]['weken_afwezig'] = [];
                $koopmannen[$id]['aanwezigheid_voldaan'] = false;
            }
            if (null === $item['week_nummer']) {
                $koopmannen[$id]['weken_afwezig'] = $emptyWeek;
                continue;
            }

            if ($item['aantal'] >= 1) {
                $koopmannen[$id]['weken_aanwezig'][(int)$item['week_nummer']] = $this->getDagen($item['dagen']);
            } else {
                $koopmannen[$id]['weken_afwezig'][(int)$item['week_nummer']] = $this->getDagen($item['dagen']);
            }

            if (count($koopmannen[$id]['weken_aanwezig']) >= $requiredAttendance) {
                $koopmannen[$id]['aanwezigheid_voldaan'] = true;
            }
        }

        foreach ($koopmannen as &$koopman) {
            foreach ($emptyWeek as $weeknumber => $text) {
                if (
                    !isset($koopman['weken_aanwezig'][$weeknumber]) &&
                    !isset($koopman['weken_afwezig'][$weeknumber])
                ) {
                    $koopman['weken_afwezig'][$weeknumber] = $text;
                }
            }
        }

        return [
            'markt'              => $markt,
            'startDate'          => $startDate,
            'endDate'            => $endDate,
            'inputDate'          => $inputDate,
            'today'              => $today,
            'lastQuarterDate'    => $lastQuarterDate,
            'nextQuarterDate'    => $nextQuarterDate,
            'requiredAttendance' => $requiredAttendance,
            'koopmannen'         => $koopmannen
        ];
                $koopmannen[$id]['weken_afwezig'][(int)$item['week_nummer']] = $this->getDagen($item['dagen']);
            }

            if (count($koopmannen[$id]['weken_aanwezig']) >= $requiredAttendance) {
                $koopmannen[$id]['aanwezigheid_voldaan'] = true;
            }
        }

        foreach ($koopmannen as &$koopman) {
            foreach ($emptyWeek as $weeknumber => $text) {
                if (
                    !isset($koopman['weken_aanwezig'][$weeknumber]) &&
                    !isset($koopman['weken_afwezig'][$weeknumber])
                ) {
                    $koopman['weken_afwezig'][$weeknumber] = $text;
                }
            }
        }

        return [
            'markt'              => $markt,
            'startDate'          => $startDate,
            'endDate'            => $endDate,
            'inputDate'          => $inputDate,
            'today'              => $today,
            'lastQuarterDate'    => $lastQuarterDate,
            'nextQuarterDate'    => $nextQuarterDate,
            'requiredAttendance' => $requiredAttendance,
            'koopmannen'         => $koopmannen
        ];
    }

    /**
     * @Route("/rapport/frequentie/markten/week/{marktId}/{datum}", name="gemeenteamsterdam_makkelijkemarkt_dashboard_rapport_frequentiemarktenweek_datum")
     * @Route("/rapport/frequentie/markten/week/{marktId}", name="gemeenteamsterdam_makkelijkemarkt_dashboard_rapport_frequentiemarktenweek")
     * @Template()
     * @Security("has_role('ROLE_ADMIN') or has_role('ROLE_SENIOR')")
     */
    public function frequentieMarktenWeekAction($marktId, $datum = null)
    {
        return $this->frequentieMarktenWeek($marktId, $datum);
    }

    /**
     * @Route("/rapport/invoer/{marktId}/{datum}", name="gemeenteamsterdam_makkelijkemarkt_dashboard_rapport_invoer_datum")
     * @Route("/rapport/invoer/{marktId}", name="gemeenteamsterdam_makkelijkemarkt_dashboard_rapport_invoer")
     * @Template()
     * @Security("has_role('ROLE_ADMIN') or has_role('ROLE_SENIOR')")
     */
    public function invoerAction($marktId, $datum = null)
    {
        $today = new \DateTime();
        $inputDate = null === $datum ? $today : new \DateTime($datum);

        list($startDate, $endDate) = $this->getQuarter($inputDate);

        $endDate = $endDate > $today ? $today : $endDate;

        $lastQuarterDate = clone $startDate;
        $lastQuarterDate->modify("-1 day");
        $nextQuarterDate = clone $endDate;
        $nextQuarterDate->modify("+1 day");

        /* @var $api \GemeenteAmsterdam\MakkelijkeMarkt\DashboardBundle\Service\MarktApi */
        $api = $this->get('markt_api');

        $markt = $api->getMarkt($marktId);
        $report = $api->getInvoerReport($marktId, $startDate, $endDate);

        $result = [];
        $lastId = null;
        $currentObject = null;
        foreach ($report as $item) {
            if ($item['id'] !== $lastId) {
                $lastId = $item['id'];
                if (null !== $currentObject) {
                    $result[] = $currentObject;
                }
                $currentObject = [];
                $currentObject['id'] = $item['id'];
                $currentObject['erkenningsnummer'] = $item['erkenningsnummer'];
                $currentObject['achternaam'] = $item['achternaam'];
                $currentObject['voorletters'] = $item['voorletters'];
                $currentObject['options'] = [];
            }
            $currentObject['options'][$item['erkenningsnummer_invoer_methode']] = $item['aantal'];
        }
        if (null !== $currentObject) {
            $result[] = $currentObject;
        }

        return [
            'markt'              => $markt,
            'startDate'          => $startDate,
            'endDate'            => $endDate,
            'inputDate'          => $inputDate,
            'today'              => $today,
            'lastQuarterDate'    => $lastQuarterDate,
            'nextQuarterDate'    => $nextQuarterDate,
            'koopmannen'         => $result
        ];

    }

    protected function persoonlijkeAanwezigheid($marktId, $datum = null) {
        /* @var $api \GemeenteAmsterdam\MakkelijkeMarkt\DashboardBundle\Service\MarktApi */
        $api = $this->get('markt_api');

        $markt = $api->getMarkt($marktId);

        $today = new \DateTime();
        $inputDate = null === $datum ? $today : new \DateTime($datum);

        list($startDate, $endDate) = $this->getYear($inputDate);

        $endDate = $endDate > $today ? $today : $endDate;

        $requiredAttendance = 0.5;

        $lastQuarterDate = clone $startDate;
        $lastQuarterDate->modify("-1 day");
        $nextQuarterDate = clone $endDate;
        $nextQuarterDate->modify("+1 day");

        $response = $api->getAanwezigheidReport($marktId, $startDate, $endDate);

        $koopmanId = null;
        $koopmannen = [];
        $jaarGeleden = new \DateTime();
        $jaarGeleden->modify('-1 year');

        foreach ($response as $item) {
            $id = $item['id'];
            if ('niet_aanwezig' === $item['aanwezig']) {
                continue;
            }

            if (!isset($koopmannen[$id])) {
                $koopmannen[$id]['voorletters'] = $item['voorletters'];
                $koopmannen[$id]['achternaam'] = $item['achternaam'];
                $koopmannen[$id]['id'] = $item['id'];
                $koopmannen[$id]['erkenningsnummer'] = $item['erkenningsnummer'];
                $koopmannen[$id]['types'] = [];
                $koopmannen[$id]['totaal'] = 0;

                $inschrijfDatum = new \DateTime($item['inschrijf_datum']);
                $koopmannen[$id]['inschrijf_datum_jaar_geleden'] = $jaarGeleden > $inschrijfDatum ? true : false;
                $koopmannen[$id]['inschrijf_datum'] = $inschrijfDatum;
            }

            $koopmannen[$id]['types'][$item['aanwezig']] = $item['aantal'];
            $koopmannen[$id]['totaal'] = $koopmannen[$id]['totaal'] + $item['aantal'];
        }

        foreach($koopmannen as &$koopman) {
            if (!isset($koopman['types']['zelf'])) {
                $koopman['types']['zelf'] = 0;
            }
            if ($koopman['types']['zelf'] >= $koopman['totaal']/2) {
                unset($koopmannen[$koopman['id']]);
            }
        }

        return [
            'markt'              => $markt,
            'startDate'          => $startDate,
            'endDate'            => $endDate,
            'inputDate'          => $inputDate,
            'today'              => $today,
            'lastQuarterDate'    => $lastQuarterDate,
            'nextQuarterDate'    => $nextQuarterDate,
            'requiredAttendance' => $requiredAttendance,
            'koopmannen'         => $koopmannen
        ];
    }

    /**
     * @Route("/rapport/aanwezigheid/markten/excel/week/{marktId}/{datum}", name="gemeenteamsterdam_makkelijkemarkt_dashboard_rapport_aanwezigheid_excel_datum")
     * @Security("has_role('ROLE_ADMIN') or has_role('ROLE_SENIOR')")
     */
    public function persoonlijkeAanwezigheidExcelAction($marktId, $datum = null)
    {
        $data = $this->persoonlijkeAanwezigheid($marktId, $datum);

        $obj = $this->get('phpexcel')->createPHPExcelObject();
        $obj->getProperties()->setCreator("Gemeente Amsterdam")
            ->setLastModifiedBy("Gemeente Amsterdam")
            ->setTitle("Frequentie rapportage")
            ->setSubject("Frequentie rapportage")
            ->setDescription("Frequentie rapportage")
            ->setKeywords("Frequentie rapportage")
            ->setCategory("Frequentie rapportage");

        $obj->setActiveSheetIndex(0);
        $activeSheet = $obj->getActiveSheet();

        $activeSheet->setCellValueByColumnAndRow(0, 1, 'Frequentie dagmarkt - ' . $data['markt']->naam);
        $activeSheet->getStyleByColumnAndRow(0,1)->getFont()->setSize(18)->setBold(true);

        $activeSheet->setCellValueByColumnAndRow(0, 2, 'Bereik: ' . $data['startDate']->format('d-m-Y') . ' - ' . $data['endDate']->format('d-m-Y'));
        $activeSheet->getStyleByColumnAndRow(0,2)->getFont()->setSize(16)->setBold(false);

        $activeSheet->setCellValueByColumnAndRow(0, 4, 'Totaaloverzicht verplichting niet gehaald');
        $activeSheet->getStyleByColumnAndRow(0,4)->getFont()->setSize(17)->setBold(true);

        $activeSheet->setCellValueByColumnAndRow(0, 6, 'erkenningsnummer');
        $activeSheet->setCellValueByColumnAndRow(1, 6, 'achternaam');
        $activeSheet->setCellValueByColumnAndRow(2, 6, 'voorletters');
        $activeSheet->getStyleByColumnAndRow(0,6,2,6)->getFont()->setBold(true);

        $i = 7;
        foreach ($data['koopmannen'] as $koopman) {
            $activeSheet->setCellValueByColumnAndRow(0, $i, $koopman['erkenningsnummer']);
            $activeSheet->setCellValueByColumnAndRow(1, $i, $koopman['achternaam']);
            $activeSheet->setCellValueByColumnAndRow(2, $i, $koopman['voorletters']);
            $i++;
        }

        $activeSheet->getColumnDimension('B')->setAutoSize(true);
        $activeSheet->getColumnDimension('C')->setAutoSize(true);

        $i++;

        $activeSheet->setCellValueByColumnAndRow(0, $i, 'Rapportage per koopman');
        $activeSheet->getStyleByColumnAndRow(0,$i)->getFont()->setSize(17)->setBold(true);

        $i++;;

        foreach ($data['koopmannen'] as $koopman) {
            $i++;
            $activeSheet->setCellValueByColumnAndRow(0, $i, $koopman['erkenningsnummer'] . '. ' . $koopman['achternaam'] . ', ' . $koopman['voorletters']);
            $activeSheet->getStyleByColumnAndRow(0,$i)->getFont()->setSize(15)->setBold(true);

            $i++;
            $activeSheet->setCellValueByColumnAndRow(0, $i, 'Inschrijfdatum:' . $koopman['inschrijf_datum']->format('d-m-Y'));
            $activeSheet->getStyleByColumnAndRow(0,$i)->getFont()->setSize(14)->setBold(false);

            $i++;

            $activeSheet->setCellValueByColumnAndRow(0, $i, 'Type');
            $activeSheet->setCellValueByColumnAndRow(1, $i, 'Aanwezig');

            foreach ($koopman['types'] as $type => $aanwezig) {
                $activeSheet->setCellValueByColumnAndRow(0, $i, $type);
                $activeSheet->setCellValueByColumnAndRow(1, $i, $aanwezig);
                $color = $koopman['inschrijf_datum_jaar_geleden'] ? 'ebcccc' : 'fcf8e3';
                $activeSheet->getStyleByColumnAndRow(0,$i,1,$i)->getFill()
                    ->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setRGB($color);
                $i++;
            }
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

    /**
     * @Route("/rapport/aanwezigheid/markten/{marktId}/{datum}", name="gemeenteamsterdam_makkelijkemarkt_dashboard_rapport_aanwezigheid_datum")
     * @Route("/rapport/aanwezigheid/markten/{marktId}", name="gemeenteamsterdam_makkelijkemarkt_dashboard_rapport_aanwezigheid")
     * @Template()
     * @Security("has_role('ROLE_ADMIN') or has_role('ROLE_SENIOR')")
     */
    public function persoonlijkeAanwezigheidAction($marktId, $datum = null)
    {
        return $this->persoonlijkeAanwezigheid($marktId, $datum);
    }

    /**
     * @Route("/rapport/factuurdetail/")
     * @Template()
     * @Security("has_role('ROLE_ADMIN') or has_role('ROLE_SENIOR')")
     */
    public function factuurDetailAction(Request $request)
    {
        /* @var $api \GemeenteAmsterdam\MakkelijkeMarkt\DashboardBundle\Service\MarktApi */
        $api = $this->get('markt_api');

        $periode = $request->query->get('periode', 'maand');
        $dag = $request->query->get('dag', date('d'));
        $maand = $request->query->get('maand', date('m'));
        $jaar = $request->query->get('jaar', date('Y'));
        $kwartaal = $request->query->get('kwartaal', floor(date('m') / 4));
        $dagStart = $request->query->get('dagStart', date('d-m-Y', time() - (7*24*60*60)));
        $dagEind = $request->query->get('dagEind', date('d-m-Y'));
        switch ($periode) {
            case 'dag':
                $dagStart = $dag;
                $dagEind = $dag;
                break;
            case 'maand':
                $dagStart = $jaar . '-' . $maand . '-01';
                $dagEind = $jaar . '-' . $maand . '-' . cal_days_in_month(CAL_GREGORIAN, $maand, $jaar);
                break;
            case 'kwartaal':
                switch ($kwartaal) {
                    case 1:
                        $dagStart = $jaar . '-01-01';
                        $dagEind = $jaar . '-03-31';
                        break;
                    case 2:
                        $dagStart = $jaar . '-04-01';
                        $dagEind = $jaar . '-06-30';
                        break;
                    case 3:
                        $dagStart = $jaar . '-07-01';
                        $dagEind = $jaar . '-09-31';
                        break;
                    case 4:
                        $dagStart = $jaar . '-10-01';
                        $dagEind = $jaar . '-12-31';
                        break;
                }
                break;
        }

        $markten = $api->getMarkten();

        $marktIds = $request->query->get('marktIds', []);
        if (count($marktIds) === 0) {
            $marktIds = array_map(function ($o) { return $o->id; }, $markten['results']);
        }

        $rapport = $api->getRapportFactuurDetail($marktIds, $dagStart, $dagEind);

        if ($request->query->get('submit') === 'Download Excel') {
            $selectedMarktNamen = [];
            foreach ($markten['results'] as $markt) {
                if (in_array($markt->id, $marktIds) === true) {
                    $selectedMarktNamen[] = $markt->naam;
                }
            }

            /* @var $phpExcelObject \PHPExcel */
            $phpExcelObject = $this->get('phpexcel')->createPHPExcelObject();
            $phpExcelObject->getProperties()->setCreator("liuggio")
                ->setLastModifiedBy("Makkelijke Markt")
                ->setTitle("Facturen detail export")
                ->setSubject("Facturen detail")
                ->setDescription("Periode: " . $dagStart .  ' - ' . $dagEind)
                ->setKeywords("")
                ->setCategory("");
            $sheet = $phpExcelObject->setActiveSheetIndex(0);
            $sheet->setCellValueByColumnAndRow(0, 1, 'Markten');
            $sheet->setCellValueByColumnAndRow(1, 1, implode(', ', $selectedMarktNamen));

            $sheet->setCellValueByColumnAndRow(0, 2, 'Periode');
            $sheet->setCellValueByColumnAndRow(1, 2, \PHPExcel_Shared_Date::stringToExcel($dagStart));
            $sheet->getCellByColumnAndRow(1, 2)->getStyle()->getNumberFormat()->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_DATE_YYYYMMDD2);
            $sheet->setCellValueByColumnAndRow(2, 2, \PHPExcel_Shared_Date::stringToExcel($dagEind));
            $sheet->getCellByColumnAndRow(2, 2)->getStyle()->getNumberFormat()->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_DATE_YYYYMMDD2);

            $sheet->setCellValueByColumnAndRow(0, 3, 'Voorkomens');
            $sheet->getColumnDimension('A')->setWidth(15);

            $sheet->setCellValueByColumnAndRow(1, 3, 'Product');
            $sheet->getColumnDimension('B')->setWidth(30);

            $sheet->setCellValueByColumnAndRow(2, 3, 'Markt');
            $sheet->getColumnDimension('B')->setWidth(30);

            $sheet->setCellValueByColumnAndRow(3, 3, 'Datum');
            $sheet->getColumnDimension('C')->setWidth(15);

            $sheet->setCellValueByColumnAndRow(4, 3, 'Bedrag');
            $sheet->getColumnDimension('D')->setWidth(15);

            $sheet->setCellValueByColumnAndRow(5, 3, 'Aantal');
            $sheet->getColumnDimension('E')->setWidth(15);

            $sheet->setCellValueByColumnAndRow(6, 3, 'Som');
            $sheet->getColumnDimension('F')->setWidth(15);

            $sheet->setCellValueByColumnAndRow(7, 3, 'Totaal');
            $sheet->getColumnDimension('G')->setWidth(15);

            $sheet->getStyle('A1:A2')->getFont()->setBold(true);
            $sheet->getStyle('A3:H3')->getFont()->setBold(true);

            foreach ($rapport->output as $i => $row) {
                $sheet->setCellValueByColumnAndRow(0, $i + 4, $row->voorkomens);

                $sheet->setCellValueByColumnAndRow(1, $i + 4, $row->product_naam);

                $sheet->setCellValueByColumnAndRow(2, $i + 4, $row->markt_naam);

                $sheet->setCellValueByColumnAndRow(3, $i + 4, \PHPExcel_Shared_Date::stringToExcel($row->dag));
                $sheet->getCellByColumnAndRow(3, $i + 4)->getStyle()->getNumberFormat()->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_DATE_YYYYMMDD2);

                $sheet->setCellValueByColumnAndRow(4, $i + 4, $row->bedrag);
                $sheet->getCellByColumnAndRow(4, $i + 4)->getStyle()->getNumberFormat()->setFormatCode('€ #,##0.00_-');

                $sheet->setCellValueByColumnAndRow(5, $i + 4, $row->aantal);

                $sheet->setCellValueByColumnAndRow(6, $i + 4, $row->som);
                $sheet->getCellByColumnAndRow(6, $i + 4)->getStyle()->getNumberFormat()->setFormatCode('€ #,##0.00_-');

                $sheet->setCellValueByColumnAndRow(7, $i + 4, $row->totaal);
                $sheet->getCellByColumnAndRow(7, $i + 4)->getStyle()->getNumberFormat()->setFormatCode('€ #,##0.00_-');
            }
            $phpExcelObject->getActiveSheet()->setTitle('Overzicht');
            $phpExcelObject->setActiveSheetIndex(0);
            $writer = $this->get('phpexcel')->createWriter($phpExcelObject, 'Excel2007');
            $response = $this->get('phpexcel')->createStreamedResponse($writer);
            $dispositionHeader = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, 'export.xlsx');
            $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet; charset=utf-8');
            $response->headers->set('Pragma', 'public');
            $response->headers->set('Cache-Control', 'maxage=1');
            $response->headers->set('Content-Disposition', $dispositionHeader);

            return $response;
        }

        return [
            'rapport' => $rapport,
            'dag' => $dag,
            'maand' => $maand,
            'jaar' => $jaar,
            'jaartallen' => range(2015,2100),
            'kwartaal' => $kwartaal,
            'periode' => $periode,
            'dagStart' => $dagStart,
            'dagEind' => $dagEind,
            'marktIds' => $marktIds,
            'markten' => $markten
        ];
    }

    /**
     * @Route("/rapport/capaciteit")
     * @Template()
     * @Security("has_role('ROLE_ADMIN') or has_role('ROLE_SENIOR')")
     */
    public function capaciteitAction(Request $request)
    {
        /** @var $client \GemeenteAmsterdam\MakkelijkeMarkt\DashboardBundle\Service\MarktApi */
        $client = $this->get('markt_api');

        $markten = [];
        foreach ($client->getMarkten()['results'] as $markt) {
            $markten[$markt->id] = $markt->naam;
        }

        $formBuilder = $this->createFormBuilder();

        $formBuilder->add('marktId', 'choice', [
            'label' => 'Markten',
            'choices' => $markten,
            'multiple' => true,
            'expanded' => false,
            'constraints' => [
                new NotBlank(),
                new Count(['min' => 1])
            ]
        ]);
        $formBuilder->add('dagStart', 'date', [
            'label' => 'Periode start',
            'widget' => 'single_text',
            'format' => 'yyyy-MM-dd',
            'html5' => false,
            'constraints' => [
                new NotBlank(),
            ]
        ]);
        $formBuilder->add('dagEind', 'date', [
            'label' => 'Periode eind',
            'widget' => 'single_text',
            'format' => 'yyyy-MM-dd',
            'html5' => false,
            'constraints' => [
                new NotBlank(),
            ]
        ]);
        $formBuilder->add('excel', 'submit', [
            'label' => 'Download Excel'
        ]);

        $form = $formBuilder->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $rapport = $client->getRapportCapaciteit(
                $form->get('marktId')->getData(),
                $form->get('dagStart')->getData()->format('Y-m-d'),
                $form->get('dagEind')->getData()->format('Y-m-d')
            );
            if ($form->getClickedButton() !== null && $form->getClickedButton()->getName() === 'excel') {

                /** @var $obj \PHPExcel */
                $obj = $this->get('phpexcel')->createPHPExcelObject();
                $obj->getProperties()->setCreator("Gemeente Amsterdam")
                    ->setLastModifiedBy("Gemeente Amsterdam")
                    ->setTitle("Capaciteit gebruik");
                $obj->setActiveSheetIndex(0);
                $activeSheet = $obj->getActiveSheet();

                $i = 1;
                foreach ($rapport->output as $record) {
                    if (1 === $i) {
                        $activeSheet->setCellValueByColumnAndRow(0, 1, 'Dag');
                        $activeSheet->setCellValueByColumnAndRow(1, 1, 'Datum');
                        $activeSheet->setCellValueByColumnAndRow(2, 1, 'Week');
                        $activeSheet->setCellValueByColumnAndRow(3, 1, 'Maand');
                        $activeSheet->setCellValueByColumnAndRow(4, 1, 'Jaar');
                        $activeSheet->setCellValueByColumnAndRow(5, 1, 'Markt');
                        $activeSheet->setCellValueByColumnAndRow(6, 1, 'Max. kramen');
                        $activeSheet->setCellValueByColumnAndRow(7, 1, 'Max. meters');
                        $activeSheet->setCellValueByColumnAndRow(8, 1, 'VPL. dagv. #');
                        $activeSheet->setCellValueByColumnAndRow(9, 1, 'VPL. dagv. %');
                        $activeSheet->setCellValueByColumnAndRow(10, 1, 'VPL. kramen #');
                        $activeSheet->setCellValueByColumnAndRow(11, 1, 'VPL. kramen %');
                        $activeSheet->setCellValueByColumnAndRow(12, 1, 'VPL. meters #');
                        $activeSheet->setCellValueByColumnAndRow(13, 1, 'VPL. meters %');
                        $activeSheet->setCellValueByColumnAndRow(14, 1, 'VKK. dagv. #');
                        $activeSheet->setCellValueByColumnAndRow(15, 1, 'VKK. dagv. %');
                        $activeSheet->setCellValueByColumnAndRow(16, 1, 'VKK. kramen #');
                        $activeSheet->setCellValueByColumnAndRow(17, 1, 'VKK. kramen %');
                        $activeSheet->setCellValueByColumnAndRow(18, 1, 'VKK. meters #');
                        $activeSheet->setCellValueByColumnAndRow(19, 1, 'VKK. meters %');
                        $activeSheet->setCellValueByColumnAndRow(20, 1, 'SOLL. dagv. #');
                        $activeSheet->setCellValueByColumnAndRow(21, 1, 'SOLL. dagv. %');
                        $activeSheet->setCellValueByColumnAndRow(22, 1, 'SOLL. kramen #');
                        $activeSheet->setCellValueByColumnAndRow(23, 1, 'SOLL. kramen %');
                        $activeSheet->setCellValueByColumnAndRow(24, 1, 'SOLL. meters #');
                        $activeSheet->setCellValueByColumnAndRow(25, 1, 'SOLL. meters %');
                        $activeSheet->setCellValueByColumnAndRow(26, 1, 'LOT. dagv. #');
                        $activeSheet->setCellValueByColumnAndRow(27, 1, 'LOT. dagv. %');
                        $activeSheet->setCellValueByColumnAndRow(28, 1, 'LOT. kramen #');
                        $activeSheet->setCellValueByColumnAndRow(29, 1, 'LOT. kramen %');
                        $activeSheet->setCellValueByColumnAndRow(30, 1, 'LOT. meters #');
                        $activeSheet->setCellValueByColumnAndRow(31, 1, 'LOT. meters %');
                        $activeSheet->setCellValueByColumnAndRow(32, 1, 'Totaal dagvergunningen #');
                        $activeSheet->setCellValueByColumnAndRow(33, 1, 'Totaal kramen #');
                        $activeSheet->setCellValueByColumnAndRow(34, 1, 'Totaal kramen %');
                        $activeSheet->setCellValueByColumnAndRow(35, 1, 'Totaal meters #');
                        $activeSheet->setCellValueByColumnAndRow(36, 1, 'Totaal meters %');

                        for ($j = 0; $j < 37; $j++) {
                            $activeSheet->getCellByColumnAndRow($j, 1)->getStyle()->getFont()->setBold(true);
                            $activeSheet->getCellByColumnAndRow($j, 1)->getStyle()->getAlignment()->setTextRotation(45);
                            $activeSheet->getColumnDimensionByColumn($j)->setWidth(6);
                        }
                    }
                    $activeSheet->getColumnDimensionByColumn(1)->setWidth(14);
                    $activeSheet->getColumnDimensionByColumn(5)->setWidth(28);
                    $i++;

                    $recordArray = get_object_vars($record);
                    $activeSheet->setCellValueByColumnAndRow(0, $i, $recordArray['dag']);
                    $activeSheet->setCellValueByColumnAndRow(1, $i, $recordArray['datum']);
                    $activeSheet->setCellValueByColumnAndRow(2, $i, $recordArray['week']);
                    $activeSheet->setCellValueByColumnAndRow(3, $i, $recordArray['maand']);
                    $activeSheet->setCellValueByColumnAndRow(4, $i, $recordArray['jaar']);
                    $activeSheet->setCellValueByColumnAndRow(5, $i, $recordArray['marktNaam']);
                    $activeSheet->setCellValueByColumnAndRow(6, $i, $recordArray['capaciteitKramen']);
                    $activeSheet->setCellValueByColumnAndRow(7, $i, $recordArray['capaciteitMeter']);
                    $activeSheet->setCellValueByColumnAndRow(8, $i, $recordArray['vplAantalDagvergunningen']);
                    $activeSheet->setCellValueByColumnAndRow(9, $i, $recordArray['vplAantalDagvergunningen%']);
                    $activeSheet->getCellByColumnAndRow(9, $i)->getStyle()->getNumberFormat()->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_PERCENTAGE);
                    $activeSheet->setCellValueByColumnAndRow(10, $i, $recordArray['vplAantalKramen']);
                    $activeSheet->setCellValueByColumnAndRow(11, $i, $recordArray['vplAantalKramen%']);
                    $activeSheet->getCellByColumnAndRow(11, $i)->getStyle()->getNumberFormat()->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_PERCENTAGE);
                    $activeSheet->setCellValueByColumnAndRow(12, $i, $recordArray['vplAantalMeter']);
                    $activeSheet->setCellValueByColumnAndRow(13, $i, $recordArray['vplAantalMeter%']);
                    $activeSheet->getCellByColumnAndRow(13, $i)->getStyle()->getNumberFormat()->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_PERCENTAGE);
                    $activeSheet->setCellValueByColumnAndRow(14, $i, $recordArray['vkkAantalDagvergunningen']);
                    $activeSheet->setCellValueByColumnAndRow(15, $i, $recordArray['vkkAantalDagvergunningen%']);
                    $activeSheet->getCellByColumnAndRow(15, $i)->getStyle()->getNumberFormat()->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_PERCENTAGE);
                    $activeSheet->setCellValueByColumnAndRow(16, $i, $recordArray['vkkAantalKramen']);
                    $activeSheet->setCellValueByColumnAndRow(17, $i, $recordArray['vkkAantalKramen%']);
                    $activeSheet->getCellByColumnAndRow(17, $i)->getStyle()->getNumberFormat()->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_PERCENTAGE);
                    $activeSheet->setCellValueByColumnAndRow(18, $i, $recordArray['vkkAantalMeter']);
                    $activeSheet->setCellValueByColumnAndRow(19, $i, $recordArray['vkkAantalMeter%']);
                    $activeSheet->getCellByColumnAndRow(19, $i)->getStyle()->getNumberFormat()->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_PERCENTAGE);
                    $activeSheet->setCellValueByColumnAndRow(20, $i, $recordArray['sollAantalDagvergunningen']);
                    $activeSheet->setCellValueByColumnAndRow(21, $i, $recordArray['sollAantalDagvergunningen%']);
                    $activeSheet->getCellByColumnAndRow(21, $i)->getStyle()->getNumberFormat()->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_PERCENTAGE);
                    $activeSheet->setCellValueByColumnAndRow(22, $i, $recordArray['sollAantalKramen']);
                    $activeSheet->setCellValueByColumnAndRow(23, $i, $recordArray['sollAantalKramen%']);
                    $activeSheet->getCellByColumnAndRow(23, $i)->getStyle()->getNumberFormat()->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_PERCENTAGE);
                    $activeSheet->setCellValueByColumnAndRow(24, $i, $recordArray['sollAantalMeter']);
                    $activeSheet->setCellValueByColumnAndRow(25, $i, $recordArray['sollAantalMeter%']);
                    $activeSheet->getCellByColumnAndRow(25, $i)->getStyle()->getNumberFormat()->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_PERCENTAGE);
                    $activeSheet->setCellValueByColumnAndRow(26, $i, $recordArray['lotAantalDagvergunningen']);
                    $activeSheet->setCellValueByColumnAndRow(27, $i, $recordArray['lotAantalDagvergunningen%']);
                    $activeSheet->getCellByColumnAndRow(27, $i)->getStyle()->getNumberFormat()->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_PERCENTAGE);
                    $activeSheet->setCellValueByColumnAndRow(28, $i, $recordArray['lotAantalKramen']);
                    $activeSheet->setCellValueByColumnAndRow(29, $i, $recordArray['lotAantalKramen%']);
                    $activeSheet->getCellByColumnAndRow(29, $i)->getStyle()->getNumberFormat()->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_PERCENTAGE);
                    $activeSheet->setCellValueByColumnAndRow(30, $i, $recordArray['lotAantalMeter']);
                    $activeSheet->setCellValueByColumnAndRow(31, $i, $recordArray['lotAantalMeter%']);
                    $activeSheet->getCellByColumnAndRow(31, $i)->getStyle()->getNumberFormat()->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_PERCENTAGE);

                    $activeSheet->setCellValueByColumnAndRow(32, $i, $recordArray['aantalDagvergunningen']);
                    $activeSheet->setCellValueByColumnAndRow(33, $i, $recordArray['totaalAantalKramen']);
                    $activeSheet->setCellValueByColumnAndRow(34, $i, $recordArray['totaalAantalKramen%']);
                    $activeSheet->getCellByColumnAndRow(34, $i)->getStyle()->getNumberFormat()->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_PERCENTAGE);
                    $activeSheet->setCellValueByColumnAndRow(35, $i, $recordArray['totaalAantalMeter']);
                    $activeSheet->setCellValueByColumnAndRow(36, $i, $recordArray['totaalAantalMeter%']);
                    $activeSheet->getCellByColumnAndRow(36, $i)->getStyle()->getNumberFormat()->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_PERCENTAGE);


                }
                $obj->getActiveSheet()->setAutoFilter($obj->getActiveSheet()->calculateWorksheetDimension());
                $activeSheet->freezePaneByColumnAndRow(8,2);

                $writer = $this->get('phpexcel')->createWriter($obj, 'Excel2007');
                $response = $this->get('phpexcel')->createStreamedResponse($writer);
                $dispositionHeader = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, 'capaciteit.xlsx');
                $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet; charset=utf-8');
                $response->headers->set('Pragma', 'public');
                $response->headers->set('Cache-Control', 'maxage=1');
                $response->headers->set('Content-Disposition', $dispositionHeader);

                return $response;

            }
        }

        return [
            'form' => $form->createView(),
            'rapport' => isset($rapport) ? $rapport->output : null
        ];
    }
}
