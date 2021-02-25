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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Symfony\Component\HttpFoundation\StreamedResponse;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Form;

class RapportController extends AbstractController
{
    private function formatErkenningsNummer(string $in): string
    {
        return substr($in, 0, 8) . '.' . substr($in, 8);
    }

    /**
     * @Route("/rapport/dubbelstaan/{dag}")
     * @Template()
     * @Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_SENIOR')")
     */
    public function dubbelstaanAction(MarktApi $api, string $dag = null): array
    {
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
     * @Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_SENIOR')")
     */
    public function staanverplichtingAction(Request $request, MarktApi $api)
    {
        $marktIds = $request->query->all('marktId');
        $dagStart = $request->query->get('dagStart');
        $dagEind = $request->query->get('dagEind');
        $vergunningType = $request->query->get('vergunningType');

        $marktenResults = $api->getMarkten();
        $markten = [];
        foreach ($marktenResults as $i) {
            if (in_array($i['id'], $marktIds)) {
                $markten[] = $i;
            }
        }

        $vergunningTypes = [
            'alle' => 'Alle vergunningen',
            'soll' => 'Sollicitanten',
            'vkk'  => 'VKK',
            'tvpl'  => 'TVPL',
            'tvplz'  => 'TVPLZ',
            'vpl'  => 'Vaste plaats',
            'lot'  => 'Lot',
            'exp'  => 'Exp. zone',
            'expf'  => 'Exp. zone F'
        ];
        
        $rapport = null;
        if ($dagStart !== null && $dagEind !== null) {
            $dagStart = \DateTime::createFromFormat('d-m-Y', $dagStart);
            $dagEind = \DateTime::createFromFormat('d-m-Y', $dagEind);
            
            $rapport = $api->getRapportStaanverplichting($marktIds, $dagStart->format('Y-m-d'), $dagEind->format('Y-m-d'), $vergunningType);

            if ($request->query->get('format') === 'excel') {
                $spreadsheet = new Spreadsheet();
                $spreadsheet->getProperties()
                    ->setCreator("Gemeente Amsterdam")
                    ->setLastModifiedBy("Gemeente Amsterdam")
                    ->setTitle("Staanverplichting");

                $spreadsheet->setActiveSheetIndex(0);

                $activeSheet = $spreadsheet->getActiveSheet();

                $i = 1;
                foreach ($rapport['output'] as $record) {
                    if (1 === $i) {
                        $activeSheet->setCellValueByColumnAndRow(1, 1, 'Markt');
                        $activeSheet->setCellValueByColumnAndRow(2, 1, 'Sollicitatienummer met markt');
                        $activeSheet->setCellValueByColumnAndRow(3, 1, 'Sollicitatienummer');
                        $activeSheet->setCellValueByColumnAndRow(4, 1, 'Status');
                        $activeSheet->setCellValueByColumnAndRow(5, 1, 'Erkenningsnummer');
                        $activeSheet->setCellValueByColumnAndRow(6, 1, 'Voorletters');
                        $activeSheet->setCellValueByColumnAndRow(7, 1, 'Tussenvoegsels');
                        $activeSheet->setCellValueByColumnAndRow(8, 1, 'Achternaam');
                        $activeSheet->setCellValueByColumnAndRow(9, 1, 'Aantal actieve dagvergunningen in periode');
                        $activeSheet->setCellValueByColumnAndRow(10, 1, 'Waarvan zelf aanwezig');
                        $activeSheet->setCellValueByColumnAndRow(11, 1, 'Waarvan andere aanwezigheid');
                        $activeSheet->setCellValueByColumnAndRow(12, 1, 'Percentage aanwezig');
                        $activeSheet->setCellValueByColumnAndRow(13, 1, 'Waarvan zelf aanwezig (controle)');
                        $activeSheet->setCellValueByColumnAndRow(14, 1, 'Waarvan andere aanwezigheid (controle)');
                        $activeSheet->setCellValueByColumnAndRow(15, 1, 'Percentage aanwezig (controle)');

                        $activeSheet->getCellByColumnAndRow(1, 1)->getStyle()->getFont()->setBold(true);
                        $activeSheet->getCellByColumnAndRow(2, 1)->getStyle()->getFont()->setBold(true);
                        $activeSheet->getCellByColumnAndRow(3, 1)->getStyle()->getFont()->setBold(true);
                        $activeSheet->getCellByColumnAndRow(4, 1)->getStyle()->getFont()->setBold(true);
                        $activeSheet->getCellByColumnAndRow(5, 1)->getStyle()->getFont()->setBold(true);
                        $activeSheet->getCellByColumnAndRow(6, 1)->getStyle()->getFont()->setBold(true);
                        $activeSheet->getCellByColumnAndRow(7, 1)->getStyle()->getFont()->setBold(true);
                        $activeSheet->getCellByColumnAndRow(8, 1)->getStyle()->getFont()->setBold(true);
                        $activeSheet->getCellByColumnAndRow(9, 1)->getStyle()->getFont()->setBold(true);
                        $activeSheet->getCellByColumnAndRow(10, 1)->getStyle()->getFont()->setBold(true);
                        $activeSheet->getCellByColumnAndRow(11, 1)->getStyle()->getFont()->setBold(true);
                        $activeSheet->getCellByColumnAndRow(12, 1)->getStyle()->getFont()->setBold(true);
                        $activeSheet->getCellByColumnAndRow(13, 1)->getStyle()->getFont()->setBold(true);
                        $activeSheet->getCellByColumnAndRow(14, 1)->getStyle()->getFont()->setBold(true);
                        $activeSheet->getCellByColumnAndRow(15, 1)->getStyle()->getFont()->setBold(true);

                        $activeSheet->getColumnDimensionByColumn(3)->setWidth(10);
                        $activeSheet->getColumnDimensionByColumn(4)->setWidth(5);
                        $activeSheet->getColumnDimensionByColumn(5)->setWidth(20);
                        $activeSheet->getColumnDimensionByColumn(6)->setWidth(10);
                        $activeSheet->getColumnDimensionByColumn(7)->setWidth(10);
                        $activeSheet->getColumnDimensionByColumn(8)->setWidth(30);
                    }
                    $i++;
                    $activeSheet->setCellValueByColumnAndRow(1, $i, $record['sollicitatie']['markt']['naam']);
                    $activeSheet->setCellValueByColumnAndRow(2, $i, $record['sollicitatie']['markt']['afkorting'] . '_' . $record['sollicitatie']['sollicitatieNummer']);
                    $activeSheet->setCellValueByColumnAndRow(3, $i, $record['sollicitatie']['sollicitatieNummer']);
                    $activeSheet->setCellValueByColumnAndRow(4, $i, $record['sollicitatie']['status']);
                    $activeSheet->setCellValueExplicitByColumnAndRow(5, $i, $this->formatErkenningsNummer($record['koopman']['erkenningsnummer']), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    $activeSheet->getCellByColumnAndRow(5, $i)->getStyle()->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);
                    $activeSheet->setCellValueByColumnAndRow(6, $i, $record['koopman']['voorletters']);
                    $activeSheet->setCellValueByColumnAndRow(7, $i, $record['koopman']['tussenvoegsels']);
                    $activeSheet->setCellValueByColumnAndRow(8, $i, $record['koopman']['achternaam']);
                    $activeSheet->setCellValueByColumnAndRow(9, $i, $record['aantalActieveDagvergunningen']);
                    $activeSheet->setCellValueByColumnAndRow(10, $i, $record['aantalActieveDagvergunningenZelfAanwezig']);
                    $activeSheet->setCellValueByColumnAndRow(11, $i, $record['aantalActieveDagvergunningenNietZelfAanwezig']);
                    $activeSheet->setCellValueByColumnAndRow(12, $i, $record['aantalActieveDagvergunningen'] > 0 ? $record['percentageAanwezig'] : '');
                    $activeSheet->setCellValueByColumnAndRow(13, $i, $record['aantalActieveDagvergunningenZelfAanwezigNaControle']);
                    $activeSheet->setCellValueByColumnAndRow(14, $i, $record['aantalActieveDagvergunningenNietZelfAanwezigNaControle']);
                    $activeSheet->setCellValueByColumnAndRow(15, $i, $record['aantalActieveDagvergunningen'] > 0 ? $record['percentageAanwezigNaControle'] : '');
                    //$activeSheet->getCellByColumnAndRow(13, $i)->getStyle()->getNumberFormat()->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_PERCENTAGE);
                }
                $spreadsheet->getActiveSheet()->setAutoFilter($spreadsheet->getActiveSheet()->calculateWorksheetDimension());
                // $activeSheet->freezePaneByColumnAndRow(1,3);

                $writer = new Xlsx($spreadsheet);
                $response =  new StreamedResponse(
                    function () use ($writer) {
                        $writer->save('php://output');
                    }
                );

                $dispositionHeader = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, 'staanverplichting_' . $dagStart->format('d-m-Y') . '_' . $dagEind->format('d-m-Y') . '.xlsx');
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

        return ['rapport' => $rapport, 'selectedMarkten' => $markten, 'markten' => $marktenResults, 'marktIds' => $marktIds, 'dagStart' => $dagStart, 'dagEind' => $dagEind, 'vergunningType' => $vergunningType, 'vergunningTypes' => $vergunningTypes];
    }

    /**
     * @Route("/rapport/facturen/")
     * @Template()
     * @Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_SENIOR')")
     */
    public function facturenAction(Request $request, MarktApi $api)
    {
        $markten = $api->getMarkten();

        $marktId = $request->query->get('markt');
        $vanaf = $request->query->get('vanaf');
        $tot = $request->query->get('tot');

        $report = null;
        if (null !== $marktId && "0" !== $marktId) {
            if ('alle' === $marktId) {
                $report = $api->getFactuurOverzicht($vanaf, $tot);
            } else {
                $report = $api->getFactuurMarktOverzicht((int)$marktId, $vanaf, $tot);

                $spreadsheet = new Spreadsheet();
                $spreadsheet->getProperties()->setCreator("Gemeente Amsterdam")
                    ->setLastModifiedBy("Gemeente Amsterdam")
                    ->setTitle("Factuur rapportage")
                    ->setSubject("Factuur rapportage")
                    ->setDescription("Factuur rapportage")
                    ->setKeywords("Factuur rapportage")
                    ->setCategory("Factuur rapportage");

                $spreadsheet->setActiveSheetIndex(0);
                $activeSheet = $spreadsheet->getActiveSheet();


                $i = 1;
                foreach ($report as $result) {
                    if (1 === $i) {
                        $activeSheet->setCellValueByColumnAndRow(1, 1, 'dagvergunningId');
                        $activeSheet->setCellValueByColumnAndRow(2, 1, 'koopmanErkenningsnummer');
                        $activeSheet->setCellValueByColumnAndRow(3, 1, 'dag');
                        $activeSheet->setCellValueByColumnAndRow(4, 1, 'voorletters');
                        $activeSheet->setCellValueByColumnAndRow(5, 1, 'achternaam');
                        $activeSheet->setCellValueByColumnAndRow(6, 1, 'productNaam');
                        $activeSheet->setCellValueByColumnAndRow(7, 1, 'productAantal');
                        $activeSheet->setCellValueByColumnAndRow(8, 1, 'productBedrag');
                    }

                    $i++;
                    $activeSheet->setCellValueByColumnAndRow(1, $i, $result['dagvergunningId']);
                    $activeSheet->setCellValueByColumnAndRow(2, $i, $result['koopmanErkenningsnummer']);
                    $activeSheet->setCellValueByColumnAndRow(3, $i, $result['dag']['date']);
                    $activeSheet->setCellValueByColumnAndRow(4, $i, $result['voorletters']);
                    $activeSheet->setCellValueByColumnAndRow(5, $i, $result['achternaam']);
                    $activeSheet->setCellValueByColumnAndRow(6, $i, $result['productNaam']);
                    $activeSheet->setCellValueByColumnAndRow(7, $i, $result['productAantal']);
                    $activeSheet->setCellValueByColumnAndRow(8, $i, $result['productBedrag']);
                }

                $activeSheet->setTitle('Rapportage');

                $writer = new Xlsx($spreadsheet);
                $response =  new StreamedResponse(
                    function () use ($writer) {
                        $writer->save('php://output');
                    }
                );

                // adding headers
                $dispositionHeader = $response->headers->makeDisposition(
                    ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                    'report.xlsx'
                );
                $response->headers->set('Content-Type', 'text/vnd.openxmlformats-officedocument.spreadsheetml.sheetl; charset=utf-8');
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
     * @Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_SENIOR')")
     */
    public function frequentieMarktenAction(MarktApi $api): array
    {
        $markten = $api->getMarkten();

        return ['markten' => $markten];
    }

    protected function frequentieMarktenDag(int $marktId, MarktApi $api, string $datum = null): array
    {
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
     * @Route("/rapport/frequentie/markten/dag/{marktId}/{datum}")
     * @Route("/rapport/frequentie/markten/dag/{marktId}")
     * @Template()
     * @Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_SENIOR')")
     */
    public function frequentieMarktenDagAction(MarktApi $api, int $marktId, $datum = null): array
    {
        return $this->frequentieMarktenDag($marktId, $api, $datum);
    }

    /**
     * @Route("/rapport/frequentie/markten/excel/dag/{marktId}/{datum}")
     * @Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_SENIOR')")
     */
    public function frequentieMarktenDagExcelAction(int $marktId,  MarktApi $api, string $datum = null): StreamedResponse
    {
        $data = $this->frequentieMarktenDag($marktId, $api, $datum);

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()->setCreator("Gemeente Amsterdam")
            ->setLastModifiedBy("Gemeente Amsterdam")
            ->setTitle("Frequentie rapportage")
            ->setSubject("Frequentie rapportage")
            ->setDescription("Frequentie rapportage")
            ->setKeywords("Frequentie rapportage")
            ->setCategory("Frequentie rapportage");

        $spreadsheet->setActiveSheetIndex(0);
        $activeSheet = $spreadsheet->getActiveSheet();

        $activeSheet->setCellValueByColumnAndRow(1, 1, 'Frequentie dagmarkt - ' . $data['markt']['naam']);
        $activeSheet->getStyleByColumnAndRow(1,1)->getFont()->setSize(18)->setBold(true);

        $activeSheet->setCellValueByColumnAndRow(1, 2, 'Bereik: ' . $data['startDate']->format('d-m-Y') . ' - ' . $data['endDate']->format('d-m-Y'));
        $activeSheet->getStyleByColumnAndRow(1,2)->getFont()->setSize(16)->setBold(false);

        $activeSheet->setCellValueByColumnAndRow(1, 4, 'Totaaloverzicht verplichting niet gehaald');
        $activeSheet->getStyleByColumnAndRow(1,4)->getFont()->setSize(17)->setBold(true);

        $activeSheet->setCellValueByColumnAndRow(1, 6, 'erkenningsnummer');
        $activeSheet->setCellValueByColumnAndRow(2, 6, 'achternaam');
        $activeSheet->setCellValueByColumnAndRow(3, 6, 'voorletters');
        $activeSheet->getStyleByColumnAndRow(1,6,3,6)->getFont()->setBold(true);

        $i = 7;
        foreach ($data['koopmannen'] as $koopman) {
            if (!$koopman['aanwezigheid_voldaan']) {
                $activeSheet->setCellValueByColumnAndRow(1, $i, $koopman['erkenningsnummer']);
                $activeSheet->setCellValueByColumnAndRow(2, $i, $koopman['achternaam']);
                $activeSheet->setCellValueByColumnAndRow(3, $i, $koopman['voorletters']);
                $i++;
            }
        }

        $activeSheet->getColumnDimension('B')->setAutoSize(true);
        $activeSheet->getColumnDimension('C')->setAutoSize(true);

        $i++;

        $activeSheet->setCellValueByColumnAndRow(1, $i, 'Rapportage per koopman');
        $activeSheet->getStyleByColumnAndRow(1, $i)->getFont()->setSize(17)->setBold(true);

        $i++;;

        foreach ($data['koopmannen'] as $koopman) {
            if (!$koopman['aanwezigheid_voldaan']) {
                $i++;
                $activeSheet->setCellValueByColumnAndRow(1, $i, $koopman['erkenningsnummer'] . '. ' . $koopman['achternaam'] . ', ' . $koopman['voorletters']);
                $activeSheet->getStyleByColumnAndRow(1,$i)->getFont()->setSize(15)->setBold(true);

                $i++;

                $activeSheet->setCellValueByColumnAndRow(1, $i, 'Week nummer');
                $activeSheet->setCellValueByColumnAndRow(2, $i, 'Status');
                $activeSheet->setCellValueByColumnAndRow(3, $i, 'dagen aanwezig');

                foreach ($koopman['weken_afwezig'] as $week_nummer => $aanwezig) {
                    $activeSheet->setCellValueByColumnAndRow(1, $i, $week_nummer);
                    $activeSheet->setCellValueByColumnAndRow(2, $i, 'Afwezig');
                    $activeSheet->setCellValueByColumnAndRow(3, $i, $aanwezig);
                    $activeSheet->getStyleByColumnAndRow(1,$i,3,$i)->getFill()
                                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                                ->getStartColor()
                                ->setRGB('ebcccc');
                    $i++;
                }

                foreach ($koopman['weken_aanwezig'] as $week_nummer => $aanwezig) {
                    $activeSheet->setCellValueByColumnAndRow(1, $i, $week_nummer);
                    $activeSheet->setCellValueByColumnAndRow(2, $i, 'Aanwezig');
                    $activeSheet->setCellValueByColumnAndRow(3, $i, $aanwezig);
                    $activeSheet->getStyleByColumnAndRow(1,$i,3,$i)->getFill()
                                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                                ->getStartColor()
                                ->setRGB('d0e9c6');
                    $i++;
                }
            }
        }

        $activeSheet->setTitle('Rapportage');


        $writer = new Xlsx($spreadsheet);
        $response =  new StreamedResponse(
            function () use ($writer) {
                $writer->save('php://output');
            }
        );

        // adding headers
        $dispositionHeader = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'report.xlsx'
        );
        $response->headers->set('Content-Type', 'text/vnd.openxmlformats-officedocument.spreadsheetml.sheet; charset=utf-8');
        $response->headers->set('Pragma', 'public');
        $response->headers->set('Cache-Control', 'maxage=1');
        $response->headers->set('Content-Disposition', $dispositionHeader);

        return $response;
    }

    /**
     * @Route("/rapport/frequentie/markten/excel/soll/{marktId}/{datum}")
     * @Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_SENIOR')")
     */
    public function frequentieMarktenSollExcelAction( MarktApi $api, int $marktId, $datum = null): StreamedResponse
    {
        $data = $this->frequentieMarktenSoll($api, $marktId, $datum);

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()->setCreator("Gemeente Amsterdam")
            ->setLastModifiedBy("Gemeente Amsterdam")
            ->setTitle("Frequentie rapportage")
            ->setSubject("Frequentie rapportage")
            ->setDescription("Frequentie rapportage")
            ->setKeywords("Frequentie rapportage")
            ->setCategory("Frequentie rapportage");

        $spreadsheet->setActiveSheetIndex(0);
        $activeSheet = $spreadsheet->getActiveSheet();

        $activeSheet->setCellValueByColumnAndRow(1, 1, 'Frequentie dagmarkt - ' . $data['markt']['naam']);
        $activeSheet->getStyleByColumnAndRow(1,1)->getFont()->setSize(18)->setBold(true);

        $activeSheet->setCellValueByColumnAndRow(1, 2, 'Bereik: ' . $data['startDate']->format('d-m-Y') . ' - ' . $data['endDate']->format('d-m-Y'));
        $activeSheet->getStyleByColumnAndRow(1,2)->getFont()->setSize(16)->setBold(false);

        $activeSheet->setCellValueByColumnAndRow(1, 4, 'Totaaloverzicht verplichting niet gehaald');
        $activeSheet->getStyleByColumnAndRow(1,4)->getFont()->setSize(17)->setBold(true);

        $activeSheet->setCellValueByColumnAndRow(1, 6, 'erkenningsnummer');
        $activeSheet->setCellValueByColumnAndRow(2, 6, 'achternaam');
        $activeSheet->setCellValueByColumnAndRow(3, 6, 'voorletters');
        $activeSheet->getStyleByColumnAndRow(1,6,3,6)->getFont()->setBold(true);

        $i = 7;
        foreach ($data['koopmannen'] as $koopman) {
            if (!$koopman['aanwezigheid_voldaan']) {
                $activeSheet->setCellValueByColumnAndRow(1, $i, $koopman['erkenningsnummer']);
                $activeSheet->setCellValueByColumnAndRow(2, $i, $koopman['achternaam']);
                $activeSheet->setCellValueByColumnAndRow(3, $i, $koopman['voorletters']);
                $i++;
            }
        }

        $activeSheet->getColumnDimension('B')->setAutoSize(true);
        $activeSheet->getColumnDimension('C')->setAutoSize(true);

        $i++;

        $activeSheet->setCellValueByColumnAndRow(1, $i, 'Rapportage per koopman');
        $activeSheet->getStyleByColumnAndRow(1, $i)->getFont()->setSize(17)->setBold(true);

        $i++;;

        foreach ($data['koopmannen'] as $koopman) {
            if (!$koopman['aanwezigheid_voldaan']) {
                $i++;
                $activeSheet->setCellValueByColumnAndRow(1, $i, $koopman['erkenningsnummer'] . '. ' . $koopman['achternaam'] . ', ' . $koopman['voorletters']);
                $activeSheet->getStyleByColumnAndRow(1, $i)->getFont()->setSize(15)->setBold(true);

                $i++;

                $activeSheet->setCellValueByColumnAndRow(1, $i, 'Week nummer');
                $activeSheet->setCellValueByColumnAndRow(2, $i, 'Status');
                $activeSheet->setCellValueByColumnAndRow(3, $i, 'dagen aanwezig');

                foreach ($koopman['weken_afwezig'] as $week_nummer => $aanwezig) {
                    $activeSheet->setCellValueByColumnAndRow(1, $i, $week_nummer);
                    $activeSheet->setCellValueByColumnAndRow(2, $i, 'Afwezig');
                    $activeSheet->setCellValueByColumnAndRow(3, $i, $aanwezig);
                    $activeSheet->getStyleByColumnAndRow(1,$i,3,$i)->getFill()
                        ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                        ->getStartColor()
                        ->setRGB('ebcccc');
                    $i++;
                }

                foreach ($koopman['weken_aanwezig'] as $week_nummer => $aanwezig) {
                    $activeSheet->setCellValueByColumnAndRow(1, $i, $week_nummer);
                    $activeSheet->setCellValueByColumnAndRow(2, $i, 'Aanwezig');
                    $activeSheet->setCellValueByColumnAndRow(3, $i, $aanwezig);
                    $activeSheet->getStyleByColumnAndRow(1,$i,3,$i)->getFill()
                        ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                        ->getStartColor()
                        ->setRGB('d0e9c6');
                    $i++;
                }
            }
        }

        $activeSheet->setTitle('Rapportage');

        $writer = new Xlsx($spreadsheet);
        $response =  new StreamedResponse(
            function () use ($writer) {
                $writer->save('php://output');
            }
        );

        // adding headers
        $dispositionHeader = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'report.xlsx'
        );
        $response->headers->set('Content-Type', 'text/vnd.openxmlformats-officedocument.spreadsheetml.sheet; charset=utf-8');
        $response->headers->set('Pragma', 'public');
        $response->headers->set('Cache-Control', 'maxage=1');
        $response->headers->set('Content-Disposition', $dispositionHeader);

        return $response;
    }

    /**
     * @Route("/rapport/frequentie/markten/excel/week/{marktId}/{datum}")
     * @Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_SENIOR')")
     */
    public function frequentieMarktenWeekExcelAction($marktId, $datum = null): StreamedResponse
    {
        $data = $this->frequentieMarktenWeek($marktId, $datum);

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()->setCreator("Gemeente Amsterdam")
            ->setLastModifiedBy("Gemeente Amsterdam")
            ->setTitle("Frequentie rapportage")
            ->setSubject("Frequentie rapportage")
            ->setDescription("Frequentie rapportage")
            ->setKeywords("Frequentie rapportage")
            ->setCategory("Frequentie rapportage");

        $spreadsheet->setActiveSheetIndex(0);
        $activeSheet = $spreadsheet->getActiveSheet();

        $activeSheet->setCellValueByColumnAndRow(1, 1, 'Frequentie dagmarkt - ' . $data['markt']['naam']);
        $activeSheet->getStyleByColumnAndRow(1,1)->getFont()->setSize(18)->setBold(true);

        $activeSheet->setCellValueByColumnAndRow(1, 2, 'Bereik: ' . $data['startDate']->format('d-m-Y') . ' - ' . $data['endDate']->format('d-m-Y'));
        $activeSheet->getStyleByColumnAndRow(1,2)->getFont()->setSize(16)->setBold(false);

        $activeSheet->setCellValueByColumnAndRow(1, 4, 'Totaaloverzicht verplichting niet gehaald');
        $activeSheet->getStyleByColumnAndRow(1,4)->getFont()->setSize(17)->setBold(true);

        $activeSheet->setCellValueByColumnAndRow(1, 6, 'erkenningsnummer');
        $activeSheet->setCellValueByColumnAndRow(2, 6, 'achternaam');
        $activeSheet->setCellValueByColumnAndRow(3, 6, 'voorletters');
        $activeSheet->getStyleByColumnAndRow(1,6,3,6)->getFont()->setBold(true);

        $i = 7;
        foreach ($data['koopmannen'] as $koopman) {
            if (!$koopman['aanwezigheid_voldaan']) {
                $activeSheet->setCellValueByColumnAndRow(1, $i, $koopman['erkenningsnummer']);
                $activeSheet->setCellValueByColumnAndRow(2, $i, $koopman['achternaam']);
                $activeSheet->setCellValueByColumnAndRow(3, $i, $koopman['voorletters']);
                $i++;
            }
        }

        $activeSheet->getColumnDimension('B')->setAutoSize(true);
        $activeSheet->getColumnDimension('C')->setAutoSize(true);

        $i++;

        $activeSheet->setCellValueByColumnAndRow(1, $i, 'Rapportage per koopman');
        $activeSheet->getStyleByColumnAndRow(1,$i)->getFont()->setSize(17)->setBold(true);

        $i++;;

        foreach ($data['koopmannen'] as $koopman) {
            if (!$koopman['aanwezigheid_voldaan']) {
                $i++;
                $activeSheet->setCellValueByColumnAndRow(1, $i, $koopman['erkenningsnummer'] . '. ' . $koopman['achternaam'] . ', ' . $koopman['voorletters']);
                $activeSheet->getStyleByColumnAndRow(1,$i)->getFont()->setSize(15)->setBold(true);

                $i++;

                $activeSheet->setCellValueByColumnAndRow(1, $i, 'Week nummer');
                $activeSheet->setCellValueByColumnAndRow(2, $i, 'Status');
                $activeSheet->setCellValueByColumnAndRow(3, $i, 'dagen aanwezig');

                foreach ($koopman['weken_afwezig'] as $week_nummer => $aanwezig) {
                    $activeSheet->setCellValueByColumnAndRow(1, $i, $week_nummer);
                    $activeSheet->setCellValueByColumnAndRow(2, $i, 'Afwezig');
                    $activeSheet->setCellValueByColumnAndRow(3, $i, $aanwezig);
                    $activeSheet->getStyleByColumnAndRow(1,$i,3,$i)->getFill()
                        ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                        ->getStartColor()
                        ->setRGB('ebcccc');
                    $i++;
                }

                foreach ($koopman['weken_aanwezig'] as $week_nummer => $aanwezig) {
                    $activeSheet->setCellValueByColumnAndRow(1, $i, $week_nummer);
                    $activeSheet->setCellValueByColumnAndRow(2, $i, 'Aanwezig');
                    $activeSheet->setCellValueByColumnAndRow(3, $i, $aanwezig);
                    $activeSheet->getStyleByColumnAndRow(1,$i,3,$i)->getFill()
                        ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                        ->getStartColor()
                        ->setRGB('d0e9c6');
                    $i++;
                }
            }
        }

        $activeSheet->setTitle('Rapportage');

        $writer = new Xlsx($spreadsheet);
        $response =  new StreamedResponse(
            function () use ($writer) {
                $writer->save('php://output');
            }
        );

        // adding headers
        $dispositionHeader = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'report.xlsx'
        );
        $response->headers->set('Content-Type', 'text/vnd.openxmlformats-officedocument.spreadsheetml.sheet; charset=utf-8');
        $response->headers->set('Pragma', 'public');
        $response->headers->set('Cache-Control', 'maxage=1');
        $response->headers->set('Content-Disposition', $dispositionHeader);

        return $response;
    }

    protected function frequentieMarktenSoll(MarktApi $api, int $marktId, string $datum = null): array
    {

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
     * @Route("/rapport/frequentie/markten/soll/{marktId}/{datum}")
     * @Route("/rapport/frequentie/markten/soll/{marktId}")
     * @Template()
     * @Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_SENIOR')")
     */
    public function frequentieMarktenSollAction( MarktApi $api, int $marktId, string $datum = null): array
    {
        return $this->frequentieMarktenSoll($api, $marktId, $datum);
    }

    /**
     * @param \DateTime $date
     * @return \DateTime[]
     */
    protected function getQuarter(\DateTime $date): array
    {
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
    protected function getYear(\DateTime $date): array
    {
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

    protected function getDagen(string $string): string
    {
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

    protected function frequentieMarktenWeek(MarktApi $api, int $marktId, string $datum = null): array
    {

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
            } else {

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
     * @Route("/rapport/frequentie/markten/week/{marktId}/{datum}")
     * @Route("/rapport/frequentie/markten/week/{marktId}")
     * @Template()
     * @Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_SENIOR')")
     */
    public function frequentieMarktenWeekAction( MarktApi $api, int $marktId, string $datum = null): array
    {
        return $this->frequentieMarktenWeek($api, $marktId, $datum);
    }

    /**
     * @Route("/rapport/invoer/{marktId}/{datum}")
     * @Route("/rapport/invoer/{marktId}")
     * @Template()
     * @Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_SENIOR')")
     */
    public function invoerAction(MarktApi $api, int $marktId, string $datum = null): array
    {
        $today = new \DateTime();
        $inputDate = null === $datum ? $today : new \DateTime($datum);

        list($startDate, $endDate) = $this->getQuarter($inputDate);

        $endDate = $endDate > $today ? $today : $endDate;

        $lastQuarterDate = clone $startDate;
        $lastQuarterDate->modify("-1 day");
        $nextQuarterDate = clone $endDate;
        $nextQuarterDate->modify("+1 day");

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

    protected function persoonlijkeAanwezigheid(MarktApi $api, int $marktId, string $datum = null): array
    {

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
     * @Route("/rapport/aanwezigheid/markten/excel/week/{marktId}/{datum}")
     * @Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_SENIOR')")
     */
    public function persoonlijkeAanwezigheidExcelAction( MarktApi $api, int $marktId, string $datum = null): StreamedResponse
    {
        $data = $this->persoonlijkeAanwezigheid($api, $marktId, $datum);

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()->setCreator("Gemeente Amsterdam")
            ->setLastModifiedBy("Gemeente Amsterdam")
            ->setTitle("Frequentie rapportage")
            ->setSubject("Frequentie rapportage")
            ->setDescription("Frequentie rapportage")
            ->setKeywords("Frequentie rapportage")
            ->setCategory("Frequentie rapportage");

        $spreadsheet->setActiveSheetIndex(0);
        $activeSheet = $spreadsheet->getActiveSheet();

        $activeSheet->setCellValueByColumnAndRow(1, 1, 'Frequentie dagmarkt - ' . $data['markt']['naam']);
        $activeSheet->getStyleByColumnAndRow(1,1)->getFont()->setSize(18)->setBold(true);

        $activeSheet->setCellValueByColumnAndRow(1, 2, 'Bereik: ' . $data['startDate']->format('d-m-Y') . ' - ' . $data['endDate']->format('d-m-Y'));
        $activeSheet->getStyleByColumnAndRow(1,2)->getFont()->setSize(16)->setBold(false);

        $activeSheet->setCellValueByColumnAndRow(1, 4, 'Totaaloverzicht verplichting niet gehaald');
        $activeSheet->getStyleByColumnAndRow(1,4)->getFont()->setSize(17)->setBold(true);

        $activeSheet->setCellValueByColumnAndRow(1, 6, 'erkenningsnummer');
        $activeSheet->setCellValueByColumnAndRow(2, 6, 'achternaam');
        $activeSheet->setCellValueByColumnAndRow(3, 6, 'voorletters');
        $activeSheet->getStyleByColumnAndRow(1,6,3,6)->getFont()->setBold(true);

        $i = 7;
        foreach ($data['koopmannen'] as $koopman) {
            $activeSheet->setCellValueByColumnAndRow(1, $i, $koopman['erkenningsnummer']);
            $activeSheet->setCellValueByColumnAndRow(2, $i, $koopman['achternaam']);
            $activeSheet->setCellValueByColumnAndRow(3, $i, $koopman['voorletters']);
            $i++;
        }

        $activeSheet->getColumnDimension('B')->setAutoSize(true);
        $activeSheet->getColumnDimension('C')->setAutoSize(true);

        $i++;

        $activeSheet->setCellValueByColumnAndRow(1, $i, 'Rapportage per koopman');
        $activeSheet->getStyleByColumnAndRow(1,$i)->getFont()->setSize(17)->setBold(true);

        $i++;;

        foreach ($data['koopmannen'] as $koopman) {
            $i++;
            $activeSheet->setCellValueByColumnAndRow(1, $i, $koopman['erkenningsnummer'] . '. ' . $koopman['achternaam'] . ', ' . $koopman['voorletters']);
            $activeSheet->getStyleByColumnAndRow(1,$i)->getFont()->setSize(15)->setBold(true);

            $i++;
            $activeSheet->setCellValueByColumnAndRow(1, $i, 'Inschrijfdatum:' . $koopman['inschrijf_datum']->format('d-m-Y'));
            $activeSheet->getStyleByColumnAndRow(1,$i)->getFont()->setSize(14)->setBold(false);

            $i++;

            $activeSheet->setCellValueByColumnAndRow(1, $i, 'Type');
            $activeSheet->setCellValueByColumnAndRow(2, $i, 'Aanwezig');

            foreach ($koopman['types'] as $type => $aanwezig) {
                $activeSheet->setCellValueByColumnAndRow(1, $i, $type);
                $activeSheet->setCellValueByColumnAndRow(2, $i, $aanwezig);
                $color = $koopman['inschrijf_datum_jaar_geleden'] ? 'ebcccc' : 'fcf8e3';
                $activeSheet->getStyleByColumnAndRow(1,$i,2,$i)->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setRGB($color);
                $i++;
            }
        }

        $activeSheet->setTitle('Rapportage');


        $writer = new Xlsx($spreadsheet);
        $response =  new StreamedResponse(
            function () use ($writer) {
                $writer->save('php://output');
            }
        );

        // adding headers
        $dispositionHeader = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'report.xlsx'
        );
        $response->headers->set('Content-Type', 'text/vnd.openxmlformats-officedocument.spreadsheetml.sheet; charset=utf-8');
        $response->headers->set('Pragma', 'public');
        $response->headers->set('Cache-Control', 'maxage=1');
        $response->headers->set('Content-Disposition', $dispositionHeader);

        return $response;
    }

    /**
     * @Route("/rapport/aanwezigheid/markten/{marktId}/{datum}")
     * @Route("/rapport/aanwezigheid/markten/{marktId}")
     * @Template()
     * @Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_SENIOR')")
     */
    public function persoonlijkeAanwezigheidAction( MarktApi $api, int $marktId, string $datum = null): array
    {
        return $this->persoonlijkeAanwezigheid($api, $marktId, $datum);
    }

    /**
     * @Route("/rapport/factuurdetail/")
     * @Template()
     * @Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_SENIOR')")
     */
    public function factuurDetailAction(MarktApi $api, Request $request)
    {
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
                $dagEind = $jaar . '-' . $maand . '-' . cal_days_in_month(CAL_GREGORIAN, (int)$maand, (int)$jaar);
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
            $marktIds = array_map(function ($o) { return $o['id']; }, $markten);
        }

        $rapport = $api->getRapportFactuurDetail($marktIds, $dagStart, $dagEind);

        if ($request->query->get('submit') === 'Download Excel') {
            $selectedMarktNamen = [];
            foreach ($markten as $markt) {
                if (in_array($markt['id'], $marktIds) === true) {
                    $selectedMarktNamen[] = $markt['naam'];
                }
            }

            $spreadsheet = new Spreadsheet();
            $spreadsheet->getProperties()->setCreator("liuggio")
                ->setLastModifiedBy("Makkelijke Markt")
                ->setTitle("Facturen detail export")
                ->setSubject("Facturen detail")
                ->setDescription("Periode: " . $dagStart .  ' - ' . $dagEind)
                ->setKeywords("")
                ->setCategory("");
            $sheet = $spreadsheet->setActiveSheetIndex(0);
            $sheet->setCellValueByColumnAndRow(1, 1, 'Markten');
            $sheet->setCellValueByColumnAndRow(2, 1, implode(', ', $selectedMarktNamen));

            $sheet->setCellValueByColumnAndRow(1, 2, 'Periode');
            $sheet->setCellValueByColumnAndRow(2, 2, \PhpOffice\PhpSpreadsheet\Shared\Date::stringToExcel($dagStart));
            $sheet->getCellByColumnAndRow(2, 2)->getStyle()->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_DATE_YYYYMMDD2);
            $sheet->setCellValueByColumnAndRow(2, 2, \PhpOffice\PhpSpreadsheet\Shared\Date::stringToExcel($dagEind));
            $sheet->getCellByColumnAndRow(3, 2)->getStyle()->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_DATE_YYYYMMDD2);

            $sheet->setCellValueByColumnAndRow(1, 3, 'Voorkomens');
            $sheet->getColumnDimension('A')->setWidth(15);

            $sheet->setCellValueByColumnAndRow(2, 3, 'Product');
            $sheet->getColumnDimension('B')->setWidth(30);

            $sheet->setCellValueByColumnAndRow(3, 3, 'Markt');
            $sheet->getColumnDimension('B')->setWidth(30);

            $sheet->setCellValueByColumnAndRow(4, 3, 'Datum');
            $sheet->getColumnDimension('C')->setWidth(15);

            $sheet->setCellValueByColumnAndRow(5, 3, 'Bedrag');
            $sheet->getColumnDimension('D')->setWidth(15);

            $sheet->setCellValueByColumnAndRow(6, 3, 'Aantal');
            $sheet->getColumnDimension('E')->setWidth(15);

            $sheet->setCellValueByColumnAndRow(7, 3, 'Som');
            $sheet->getColumnDimension('F')->setWidth(15);

            $sheet->setCellValueByColumnAndRow(8, 3, 'Totaal');
            $sheet->getColumnDimension('G')->setWidth(15);

            $sheet->getStyle('A1:A2')->getFont()->setBold(true);
            $sheet->getStyle('A3:H3')->getFont()->setBold(true);

            foreach ($rapport['output'] as $i => $row) {
                $sheet->setCellValueByColumnAndRow(1, $i + 4, $row['voorkomens']);

                $sheet->setCellValueByColumnAndRow(2, $i + 4, $row['product_naam']);

                $sheet->setCellValueByColumnAndRow(3, $i + 4, $row['markt_naam']);

                $sheet->setCellValueByColumnAndRow(4, $i + 4, \PhpOffice\PhpSpreadsheet\Shared\Date::stringToExcel($row['dag']));
                $sheet->getCellByColumnAndRow(4, $i + 4)->getStyle()->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_DATE_YYYYMMDD2);

                $sheet->setCellValueByColumnAndRow(5, $i + 4, $row['bedrag']);
                $sheet->getCellByColumnAndRow(5, $i + 4)->getStyle()->getNumberFormat()->setFormatCode('€ #,##0.00_-');

                $sheet->setCellValueByColumnAndRow(6, $i + 4, $row['aantal']);

                $sheet->setCellValueByColumnAndRow(7, $i + 4, $row['som']);
                $sheet->getCellByColumnAndRow(7, $i + 4)->getStyle()->getNumberFormat()->setFormatCode('€ #,##0.00_-');

                $sheet->setCellValueByColumnAndRow(8, $i + 4, $row['totaal']);
                $sheet->getCellByColumnAndRow(8, $i + 4)->getStyle()->getNumberFormat()->setFormatCode('€ #,##0.00_-');
            }
            $spreadsheet->getActiveSheet()->setTitle('Overzicht');
            $spreadsheet->setActiveSheetIndex(0);

            $writer = new Xlsx($spreadsheet);
            $response =  new StreamedResponse(
                function () use ($writer) {
                    $writer->save('php://output');
                }
            );

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
     * @Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_SENIOR')")
     */
    public function capaciteitAction(Request $request, MarktApi $client)
    {
        $marktId = $request->query->get('status');
        $markten = $client->getMarkten();

        $formBuilder = $this->createFormBuilder();

        $formBuilder->add('marktId', ChoiceType::class, [
            'label' => 'Markten',
            'choices' => $markten,
            'multiple' => true,
            'expanded' => false,
            'constraints' => [
                new NotBlank(),
                new Count(['min' => 1])
            ]
        ]);
        $formBuilder->add('dagStart', DateType::class, [
            'label' => 'Periode start',
            'widget' => 'single_text',
            'format' => 'yyyy-MM-dd',
            'html5' => false,
            'constraints' => [
                new NotBlank(),
            ]
        ]);
        $formBuilder->add('dagEind', DateType::class, [
            'label' => 'Periode eind',
            'widget' => 'single_text',
            'format' => 'yyyy-MM-dd',
            'html5' => false,
            'constraints' => [
                new NotBlank(),
            ]
        ]);
        $formBuilder->add('excel', SubmitType::class, [
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

            /** @var Form $form */
            if ($form->getClickedButton() !== null && $form->getClickedButton()->getName() === 'excel') {

                $spreadsheet = new Spreadsheet();
                $spreadsheet->getProperties()->setCreator("Gemeente Amsterdam")
                    ->setLastModifiedBy("Gemeente Amsterdam")
                    ->setTitle("Capaciteit gebruik");
                $spreadsheet->setActiveSheetIndex(0);
                $activeSheet = $spreadsheet->getActiveSheet();

                $i = 1;
                foreach ($rapport['output'] as $record) {
                    if (1 === $i) {
                        $activeSheet->setCellValueByColumnAndRow(1, 1, 'Dag');
                        $activeSheet->setCellValueByColumnAndRow(2, 1, 'Datum');
                        $activeSheet->setCellValueByColumnAndRow(3, 1, 'Week');
                        $activeSheet->setCellValueByColumnAndRow(4, 1, 'Maand');
                        $activeSheet->setCellValueByColumnAndRow(5, 1, 'Jaar');
                        $activeSheet->setCellValueByColumnAndRow(6, 1, 'Markt');
                        $activeSheet->setCellValueByColumnAndRow(7, 1, 'Max. kramen');
                        $activeSheet->setCellValueByColumnAndRow(8, 1, 'Max. meters');

                        $activeSheet->setCellValueByColumnAndRow(9, 1, 'VPL. dagv. #');
                        $activeSheet->setCellValueByColumnAndRow(10, 1, 'VPL. dagv. %');
                        $activeSheet->setCellValueByColumnAndRow(11, 1, 'VPL. kramen #');
                        $activeSheet->setCellValueByColumnAndRow(12, 1, 'VPL. kramen %');
                        $activeSheet->setCellValueByColumnAndRow(13, 1, 'VPL. meters #');
                        $activeSheet->setCellValueByColumnAndRow(14, 1, 'VPL. meters %');

                        $activeSheet->setCellValueByColumnAndRow(15, 1, 'VKK. dagv. #');
                        $activeSheet->setCellValueByColumnAndRow(16, 1, 'VKK. dagv. %');
                        $activeSheet->setCellValueByColumnAndRow(17, 1, 'VKK. kramen #');
                        $activeSheet->setCellValueByColumnAndRow(18, 1, 'VKK. kramen %');
                        $activeSheet->setCellValueByColumnAndRow(19, 1, 'VKK. meters #');
                        $activeSheet->setCellValueByColumnAndRow(20, 1, 'VKK. meters %');

                        $activeSheet->setCellValueByColumnAndRow(21, 1, 'TVPL. dagv. #');
                        $activeSheet->setCellValueByColumnAndRow(22, 1, 'TVPL. dagv. %');
                        $activeSheet->setCellValueByColumnAndRow(23, 1, 'TVPL. kramen #');
                        $activeSheet->setCellValueByColumnAndRow(24, 1, 'TVPL. kramen %');
                        $activeSheet->setCellValueByColumnAndRow(25, 1, 'TVPL. meters #');
                        $activeSheet->setCellValueByColumnAndRow(26, 1, 'TVPL. meters %');

                        $activeSheet->setCellValueByColumnAndRow(27, 1, 'TVPLZ. dagv. #');
                        $activeSheet->setCellValueByColumnAndRow(28, 1, 'TVPLZ. dagv. %');
                        $activeSheet->setCellValueByColumnAndRow(29, 1, 'TVPLZ. kramen #');
                        $activeSheet->setCellValueByColumnAndRow(30, 1, 'TVPLZ. kramen %');
                        $activeSheet->setCellValueByColumnAndRow(31, 1, 'TVPLZ. meters #');
                        $activeSheet->setCellValueByColumnAndRow(32, 1, 'TVPLZ. meters %');

                        $activeSheet->setCellValueByColumnAndRow(33, 1, 'EXP. dagv. #');
                        $activeSheet->setCellValueByColumnAndRow(34, 1, 'EXP. dagv. %');
                        $activeSheet->setCellValueByColumnAndRow(35, 1, 'EXP. kramen #');
                        $activeSheet->setCellValueByColumnAndRow(36, 1, 'EXP. kramen %');
                        $activeSheet->setCellValueByColumnAndRow(37, 1, 'EXP. meters #');
                        $activeSheet->setCellValueByColumnAndRow(38, 1, 'EXP. meters %');

                        $activeSheet->setCellValueByColumnAndRow(39, 1, 'EXPF. dagv. #');
                        $activeSheet->setCellValueByColumnAndRow(40, 1, 'EXPF. dagv. %');
                        $activeSheet->setCellValueByColumnAndRow(41, 1, 'EXPF. kramen #');
                        $activeSheet->setCellValueByColumnAndRow(42, 1, 'EXPF. kramen %');
                        $activeSheet->setCellValueByColumnAndRow(43, 1, 'EXPF. meters #');
                        $activeSheet->setCellValueByColumnAndRow(44, 1, 'EXPF. meters %');

                        $activeSheet->setCellValueByColumnAndRow(45, 1, 'SOLL. dagv. #');
                        $activeSheet->setCellValueByColumnAndRow(46, 1, 'SOLL. dagv. %');
                        $activeSheet->setCellValueByColumnAndRow(47, 1, 'SOLL. kramen #');
                        $activeSheet->setCellValueByColumnAndRow(48, 1, 'SOLL. kramen %');
                        $activeSheet->setCellValueByColumnAndRow(49, 1, 'SOLL. meters #');
                        $activeSheet->setCellValueByColumnAndRow(50, 1, 'SOLL. meters %');

                        $activeSheet->setCellValueByColumnAndRow(51, 1, 'LOT. dagv. #');
                        $activeSheet->setCellValueByColumnAndRow(52, 1, 'LOT. dagv. %');
                        $activeSheet->setCellValueByColumnAndRow(53, 1, 'LOT. kramen #');
                        $activeSheet->setCellValueByColumnAndRow(54, 1, 'LOT. kramen %');
                        $activeSheet->setCellValueByColumnAndRow(55, 1, 'LOT. meters #');
                        $activeSheet->setCellValueByColumnAndRow(56, 1, 'LOT. meters %');

                        $activeSheet->setCellValueByColumnAndRow(57, 1, 'Totaal dagvergunningen #');
                        $activeSheet->setCellValueByColumnAndRow(58, 1, 'Totaal kramen #');
                        $activeSheet->setCellValueByColumnAndRow(59, 1, 'Totaal kramen %');
                        $activeSheet->setCellValueByColumnAndRow(60, 1, 'Totaal meters #');
                        $activeSheet->setCellValueByColumnAndRow(61, 1, 'Totaal meters %');

                        for ($j = 1; $j < 38; $j++) {
                            $activeSheet->getCellByColumnAndRow($j, 1)->getStyle()->getFont()->setBold(true);
                            $activeSheet->getCellByColumnAndRow($j, 1)->getStyle()->getAlignment()->setTextRotation(45);
                            $activeSheet->getColumnDimensionByColumn($j)->setWidth(6);
                        }
                    }
                    $activeSheet->getColumnDimensionByColumn(2)->setWidth(14);
                    $activeSheet->getColumnDimensionByColumn(6)->setWidth(28);
                    $i++;

                    $recordArray = get_object_vars($record);
                    $activeSheet->setCellValueByColumnAndRow(1, $i, $recordArray['dag']);
                    $activeSheet->setCellValueByColumnAndRow(2, $i, $recordArray['datum']);
                    $activeSheet->setCellValueByColumnAndRow(3, $i, $recordArray['week']);
                    $activeSheet->setCellValueByColumnAndRow(4, $i, $recordArray['maand']);
                    $activeSheet->setCellValueByColumnAndRow(5, $i, $recordArray['jaar']);
                    $activeSheet->setCellValueByColumnAndRow(6, $i, $recordArray['marktNaam']);
                    $activeSheet->setCellValueByColumnAndRow(7, $i, $recordArray['capaciteitKramen']);
                    $activeSheet->setCellValueByColumnAndRow(8, $i, $recordArray['capaciteitMeter']);

                    $activeSheet->setCellValueByColumnAndRow(9, $i, $recordArray['vplAantalDagvergunningen']);
                    $activeSheet->setCellValueByColumnAndRow(10, $i, $recordArray['vplAantalDagvergunningen%']);
                    $activeSheet->getCellByColumnAndRow(10, $i)->getStyle()->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_PERCENTAGE);
                    $activeSheet->setCellValueByColumnAndRow(11, $i, $recordArray['vplAantalKramen']);
                    $activeSheet->setCellValueByColumnAndRow(12, $i, $recordArray['vplAantalKramen%']);
                    $activeSheet->getCellByColumnAndRow(12, $i)->getStyle()->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_PERCENTAGE);
                    $activeSheet->setCellValueByColumnAndRow(13, $i, $recordArray['vplAantalMeter']);
                    $activeSheet->setCellValueByColumnAndRow(14, $i, $recordArray['vplAantalMeter%']);
                    $activeSheet->getCellByColumnAndRow(14, $i)->getStyle()->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_PERCENTAGE);

                    $activeSheet->setCellValueByColumnAndRow(15, $i, $recordArray['vkkAantalDagvergunningen']);
                    $activeSheet->setCellValueByColumnAndRow(16, $i, $recordArray['vkkAantalDagvergunningen%']);
                    $activeSheet->getCellByColumnAndRow(16, $i)->getStyle()->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_PERCENTAGE);
                    $activeSheet->setCellValueByColumnAndRow(17, $i, $recordArray['vkkAantalKramen']);
                    $activeSheet->setCellValueByColumnAndRow(18, $i, $recordArray['vkkAantalKramen%']);
                    $activeSheet->getCellByColumnAndRow(18, $i)->getStyle()->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_PERCENTAGE);
                    $activeSheet->setCellValueByColumnAndRow(19, $i, $recordArray['vkkAantalMeter']);
                    $activeSheet->setCellValueByColumnAndRow(20, $i, $recordArray['vkkAantalMeter%']);
                    $activeSheet->getCellByColumnAndRow(20, $i)->getStyle()->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_PERCENTAGE);

                    $activeSheet->setCellValueByColumnAndRow(21, $i, $recordArray['tvplAantalDagvergunningen']);
                    $activeSheet->setCellValueByColumnAndRow(22, $i, $recordArray['tvplAantalDagvergunningen%']);
                    $activeSheet->getCellByColumnAndRow(22, $i)->getStyle()->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_PERCENTAGE);
                    $activeSheet->setCellValueByColumnAndRow(23, $i, $recordArray['tvplAantalKramen']);
                    $activeSheet->setCellValueByColumnAndRow(24, $i, $recordArray['tvplAantalKramen%']);
                    $activeSheet->getCellByColumnAndRow(24, $i)->getStyle()->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_PERCENTAGE);
                    $activeSheet->setCellValueByColumnAndRow(25, $i, $recordArray['tvplAantalMeter']);
                    $activeSheet->setCellValueByColumnAndRow(26, $i, $recordArray['tvplAantalMeter%']);
                    $activeSheet->getCellByColumnAndRow(26, $i)->getStyle()->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_PERCENTAGE);

                    $activeSheet->setCellValueByColumnAndRow(27, $i, $recordArray['tvplzAantalDagvergunningen']);
                    $activeSheet->setCellValueByColumnAndRow(28, $i, $recordArray['tvplzAantalDagvergunningen%']);
                    $activeSheet->getCellByColumnAndRow(28, $i)->getStyle()->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_PERCENTAGE);
                    $activeSheet->setCellValueByColumnAndRow(29, $i, $recordArray['tvplzAantalKramen']);
                    $activeSheet->setCellValueByColumnAndRow(30, $i, $recordArray['tvplzAantalKramen%']);
                    $activeSheet->getCellByColumnAndRow(30, $i)->getStyle()->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_PERCENTAGE);
                    $activeSheet->setCellValueByColumnAndRow(31, $i, $recordArray['tvplzAantalMeter']);
                    $activeSheet->setCellValueByColumnAndRow(32, $i, $recordArray['tvplzAantalMeter%']);
                    $activeSheet->getCellByColumnAndRow(32, $i)->getStyle()->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_PERCENTAGE);

                    $activeSheet->setCellValueByColumnAndRow(33, $i, $recordArray['expAantalDagvergunningen']);
                    $activeSheet->setCellValueByColumnAndRow(34, $i, $recordArray['expAantalDagvergunningen%']);
                    $activeSheet->getCellByColumnAndRow(34, $i)->getStyle()->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_PERCENTAGE);
                    $activeSheet->setCellValueByColumnAndRow(35, $i, $recordArray['expAantalKramen']);
                    $activeSheet->setCellValueByColumnAndRow(36, $i, $recordArray['expAantalKramen%']);
                    $activeSheet->getCellByColumnAndRow(36, $i)->getStyle()->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_PERCENTAGE);
                    $activeSheet->setCellValueByColumnAndRow(37, $i, $recordArray['expAantalMeter']);
                    $activeSheet->setCellValueByColumnAndRow(38, $i, $recordArray['expAantalMeter%']);
                    $activeSheet->getCellByColumnAndRow(38, $i)->getStyle()->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_PERCENTAGE);

                    $activeSheet->setCellValueByColumnAndRow(39, $i, $recordArray['expfAantalDagvergunningen']);
                    $activeSheet->setCellValueByColumnAndRow(40, $i, $recordArray['expfAantalDagvergunningen%']);
                    $activeSheet->getCellByColumnAndRow(40, $i)->getStyle()->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_PERCENTAGE);
                    $activeSheet->setCellValueByColumnAndRow(41, $i, $recordArray['expfAantalKramen']);
                    $activeSheet->setCellValueByColumnAndRow(42, $i, $recordArray['expfAantalKramen%']);
                    $activeSheet->getCellByColumnAndRow(42, $i)->getStyle()->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_PERCENTAGE);
                    $activeSheet->setCellValueByColumnAndRow(43, $i, $recordArray['expfAantalMeter']);
                    $activeSheet->setCellValueByColumnAndRow(44, $i, $recordArray['expfAantalMeter%']);
                    $activeSheet->getCellByColumnAndRow(44, $i)->getStyle()->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_PERCENTAGE);

                    $activeSheet->setCellValueByColumnAndRow(45, $i, $recordArray['sollAantalDagvergunningen']);
                    $activeSheet->setCellValueByColumnAndRow(46, $i, $recordArray['sollAantalDagvergunningen%']);
                    $activeSheet->getCellByColumnAndRow(46, $i)->getStyle()->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_PERCENTAGE);
                    $activeSheet->setCellValueByColumnAndRow(47, $i, $recordArray['sollAantalKramen']);
                    $activeSheet->setCellValueByColumnAndRow(48, $i, $recordArray['sollAantalKramen%']);
                    $activeSheet->getCellByColumnAndRow(48, $i)->getStyle()->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_PERCENTAGE);
                    $activeSheet->setCellValueByColumnAndRow(49, $i, $recordArray['sollAantalMeter']);
                    $activeSheet->setCellValueByColumnAndRow(50, $i, $recordArray['sollAantalMeter%']);
                    $activeSheet->getCellByColumnAndRow(50, $i)->getStyle()->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_PERCENTAGE);

                    $activeSheet->setCellValueByColumnAndRow(51, $i, $recordArray['lotAantalDagvergunningen']);
                    $activeSheet->setCellValueByColumnAndRow(52, $i, $recordArray['lotAantalDagvergunningen%']);
                    $activeSheet->getCellByColumnAndRow(52, $i)->getStyle()->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_PERCENTAGE);
                    $activeSheet->setCellValueByColumnAndRow(53, $i, $recordArray['lotAantalKramen']);
                    $activeSheet->setCellValueByColumnAndRow(54, $i, $recordArray['lotAantalKramen%']);
                    $activeSheet->getCellByColumnAndRow(54, $i)->getStyle()->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_PERCENTAGE);
                    $activeSheet->setCellValueByColumnAndRow(55, $i, $recordArray['lotAantalMeter']);
                    $activeSheet->setCellValueByColumnAndRow(56, $i, $recordArray['lotAantalMeter%']);
                    $activeSheet->getCellByColumnAndRow(56, $i)->getStyle()->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_PERCENTAGE);

                    $activeSheet->setCellValueByColumnAndRow(57, $i, $recordArray['aantalDagvergunningen']);
                    $activeSheet->setCellValueByColumnAndRow(58, $i, $recordArray['totaalAantalKramen']);
                    $activeSheet->setCellValueByColumnAndRow(59, $i, $recordArray['totaalAantalKramen%']);
                    $activeSheet->getCellByColumnAndRow(59, $i)->getStyle()->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_PERCENTAGE);
                    $activeSheet->setCellValueByColumnAndRow(60, $i, $recordArray['totaalAantalMeter']);
                    $activeSheet->setCellValueByColumnAndRow(61, $i, $recordArray['totaalAantalMeter%']);
                    $activeSheet->getCellByColumnAndRow(61, $i)->getStyle()->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_PERCENTAGE);


                }
                $spreadsheet->getActiveSheet()->setAutoFilter($spreadsheet->getActiveSheet()->calculateWorksheetDimension());
                $activeSheet->freezePaneByColumnAndRow(9,2);

                $writer = new Xlsx($spreadsheet);
                $response =  new StreamedResponse(
                    function () use ($writer) {
                        $writer->save('php://output');
                    }
                );

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
            'markten' => $markten,
            'marktId'  => $marktId,
            'rapport' => isset($rapport) ? $rapport['output'] : null
        ];
    }
}
