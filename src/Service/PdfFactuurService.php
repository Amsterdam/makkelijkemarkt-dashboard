<?php

declare(strict_types=1);

namespace App\Service;

use Qipsius\TCPDFBundle\Controller\TCPDFController;

class PdfFactuurService
{
    /**
     * @var \TCPDF
     */
    protected $pdf;

    protected $fontname;

    protected $fontnameBold;

    protected $projectDir;

    public function __construct(
        string $projectDir,
        private TCPDFController $tcpdfController
    ) {
        $this->projectDir = $projectDir;

        $this->fontname = \TCPDF_FONTS::addTTFfont(
            $this->projectDir.'/public/resources/fonts/AmsterdamSans-Regular.ttf',
            'TrueTypeUnicode',
            '',
            96
        );
        $this->fontnameBold = \TCPDF_FONTS::addTTFfont(
            $this->projectDir.'/public/resources/fonts/AmsterdamSans-Bold.ttf',
            'TrueTypeUnicode',
            '',
            96
        );
    }

    public function generate(array $koopman, array $dagvergunningen, \DateTime $startDate, \DateTime $endDate): \TCPDF
    {
        $this->pdf = $this->tcpdfController->create();

        // set document information
        $this->pdf->SetCreator('Gemeente Amsterdam');
        $this->pdf->SetAuthor('Gemeente Amsterdam');
        $this->pdf->SetTitle('Factuur');
        $this->pdf->SetSubject('Factuur');
        $this->pdf->SetKeywords('Factuur');

        $this->pdf->SetPrintHeader(false);
        $this->pdf->SetPrintFooter(false);
        $this->pdf->SetAutoPageBreak(false, 0);

        $this->pdf->AddPage();

        $this->pdf->Image(
            $this->projectDir.'/public/resources/fonts/GASD_1.png',
            10,
            10,
            50
        );

        $this->pdf->Ln(60);

        // set font
        $this->pdf->SetFont($this->fontname, 'b', 20);
        $this->pdf->Cell(180, 6, 'Factuur overzicht marktbureau', 0, 1);
        $this->pdf->Ln(2);
        $this->pdf->SetFont($this->fontname, '', 14);
        $this->pdf->SetFillColor(220, 220, 220);
        $this->pdf->Cell(50, 6, 'Erkenningsnummer:', 0, 0, '', true);
        $this->pdf->Cell(130, 6, $koopman['erkenningsnummer'], 0, 1, '', true);
        $this->pdf->SetFillColor(255, 255, 255);
        $this->pdf->Cell(50, 6, 'Naam:', 0, 0, '', true);
        $this->pdf->Cell(130, 6, $koopman['voorletters'].' '.$koopman['achternaam'], 0, 1, '', true);
        $this->pdf->SetFillColor(220, 220, 220);
        $this->pdf->Cell(50, 6, 'Periode:', 0, 0, '', true);
        $this->pdf->Cell(130, 6, $startDate->format('d-m-Y').' - '.$endDate->format('d-m-Y'), 0, 1, '', true);

        $totaalIncl = 0;
        $totaalExcl = 0;
        $totaalProducten = 0;

        $vergunningenMetFactuur = [];

        foreach ($dagvergunningen as $dagvergunning) {
            if (null !== $dagvergunning['factuur']) {
                $vergunningenMetFactuur[] = $dagvergunning;
                $totaalIncl += $dagvergunning['factuur']['totaal'];
                $totaalExcl += $dagvergunning['factuur']['exclusief'];
                $totaalProducten += count($dagvergunning['factuur']['producten']);
            }
        }

        $this->pdf->Ln(4);

        $this->pdf->SetFont($this->fontname, '', 16);
        $this->pdf->Cell(180, 6, 'Totalen:', 0, 1);
        $this->pdf->Ln(2);
        $this->pdf->SetFont($this->fontname, '', 14);
        $this->pdf->SetFillColor(220, 220, 220);
        $this->pdf->Cell(50, 6, 'Producten:', 0, 0, '', true);
        $this->pdf->Cell(130, 6, $totaalProducten, 0, 1, '', true);
        $this->pdf->SetFillColor(255, 255, 255);
        $this->pdf->Cell(50, 6, 'Totaal inclusief btw:', 0, 0, '', true);
        $this->pdf->Cell(130, 6, '€ '.$totaalIncl, 0, 1, '', true);
        $this->pdf->SetFillColor(220, 220, 220);
        $this->pdf->Cell(50, 6, 'Totaal exclusief btw:', 0, 0, '', true);
        $this->pdf->Cell(130, 6, '€ '.$totaalExcl, 0, 1, '', true);

        foreach ($vergunningenMetFactuur as $vergunning) {
            $this->addVergunning($koopman, $vergunning);
        }

        return $this->pdf;
    }

    protected function addVergunning(array $koopman, array $vergunning): void
    {
        $this->pdf->AddPage();
        $this->pdf->Image(
            $this->projectDir.'/public/resources/fonts/GASD_1.png',
            10,
            10,
            50
        );

        $this->pdf->Ln(40);

        $this->pdf->SetFont($this->fontname, 'b', 8);
        $this->pdf->Cell(16, 6, '', 0, 0);
        $this->pdf->Cell(164, 6, 'Retouradres: Postbus 2813, 1000 CV Amsterdam', 0, 0);

        $this->pdf->Ln(10);

        $this->pdf->SetFont($this->fontname, 'b', 11);
        $this->pdf->Cell(16, 6, '', 0, 0);
        $this->pdf->Cell(164, 6, $koopman['achternaam'].' '.$koopman['voorletters'], 0, 1);

        $this->pdf->SetY(10);

        $this->pdf->SetFont($this->fontname, 'b', 10);
        $this->pdf->Cell(130, 6, '', 0, 0);
        $this->pdf->Cell(50, 6, 'Stadswerken', 0, 0);
        $this->pdf->Ln(5);
        $this->pdf->Cell(130, 6, '', 0, 0);
        $this->pdf->Cell(50, 6, 'Bezoekadres', 0, 0);
        $this->pdf->Ln(5);
        $this->pdf->Cell(130, 6, '', 0, 0);
        $this->pdf->Cell(50, 6, 'Amstel 1', 0, 0);
        $this->pdf->Ln(5);
        $this->pdf->Cell(130, 6, '', 0, 0);
        $this->pdf->Cell(50, 6, '1011 PN Amsterdam', 0, 0);

        $this->pdf->Ln(10);
        $this->pdf->Cell(130, 6, '', 0, 0);
        $this->pdf->Cell(50, 6, 'Postbus 202', 0, 0);
        $this->pdf->Ln(5);
        $this->pdf->Cell(130, 6, '', 0, 0);
        $this->pdf->Cell(50, 6, '1000 AE Amsterdam', 0, 0);
        $this->pdf->Ln(5);
        $this->pdf->Cell(130, 6, '', 0, 0);
        $this->pdf->Cell(50, 6, 'Telefoon 020 2552912', 0, 0);
        $this->pdf->Ln(5);
        $this->pdf->Cell(130, 6, '', 0, 0);
        $this->pdf->Cell(50, 6, 'Bereikbaar van 8.00-18.00', 0, 0);
        $this->pdf->Ln(5);
        $this->pdf->Cell(130, 6, '', 0, 0);
        $this->pdf->Cell(50, 6, 'Email', 0, 0);
        $this->pdf->Ln(5);
        $this->pdf->Cell(130, 6, '', 0, 0);
        $this->pdf->Cell(50, 6, 'debiteurenadministratie@amsterdam.nl', 0, 0);

        $this->pdf->Ln(10);
        $this->pdf->Cell(130, 6, '', 0, 0);
        $this->pdf->Cell(50, 6, 'BTW nr NL002564440B01', 0, 0);
        $this->pdf->Ln(5);
        $this->pdf->Cell(130, 6, '', 0, 0);
        $this->pdf->Cell(50, 6, 'KvK nr 34366966 0061', 0, 0);

        $this->pdf->Ln(10);

        $this->pdf->Cell(16, 6, '', 0, 0);
        $this->pdf->SetFont($this->fontnameBold, 'b', 9);
        $this->pdf->Cell(26, 6, 'Factuurnummer', 0, 0);
        $this->pdf->SetFont($this->fontname, 'b', 9);
        $this->pdf->Cell(26, 6, 'mm'.$vergunning['factuur']['id'], 0, 0);
        $this->pdf->SetFont($this->fontnameBold, 'b', 9);
        $this->pdf->Cell(26, 6, 'Factuurdatum', 0, 0);
        $this->pdf->SetFont($this->fontname, 'b', 9);
        $dag = implode('-', array_reverse(explode('-', $vergunning['dag'])));
        $this->pdf->Cell(26, 6, $dag, 0, 1);

        $this->pdf->Cell(16, 6, '', 0, 0);
        $this->pdf->SetFont($this->fontnameBold, 'b', 9);
        $this->pdf->Cell(144, 6, 'Omschrijving', 'B', 0);
        $this->pdf->Cell(20, 6, 'Bedrag €', 'B', 1, 'R');

        $this->pdf->SetFont($this->fontname, 'b', 9);

        $this->pdf->Cell(16, 6, '', 0, 0);
        $this->pdf->Cell(164, 6, 'Markt: '.$vergunning['markt']['naam'], '', 1);

        $btwTotaal = [];
        $btwOver = [];

        foreach ($vergunning['factuur']['producten'] as $product) {
            $this->pdf->Cell(16, 6, '', 0, 0);
            $btwText = $product['btw_percentage'] > 0 ? '. excl. '.$product['btw_percentage'].'% BTW' : '';
            $this->pdf->Cell(144, 6, $product['aantal'].' maal '.$product['naam'].$btwText, '', 0);
            $this->pdf->Cell(20, 6, $product['totaal'], 0, 0, 'R');
            if (!isset($btwTotaal[$product['btw_percentage']])) {
                $btwTotaal[$product['btw_percentage']] = 0;
                $btwOver[$product['btw_percentage']] = 0;
            }

            $btwTotaal[$product['btw_percentage']] += $product['btw_totaal'];
            $btwOver[$product['btw_percentage']] += $product['totaal'];

            $this->pdf->Ln(5);
        }

        $this->pdf->Ln(5);

        $this->pdf->Cell(98, 6, '', 0, 0);
        $this->pdf->Cell(41, 6, 'Subtotaal', 'T', 0);
        $this->pdf->Cell(41, 6, $vergunning['factuur']['exclusief'], 'T', 0, 'R');
        $this->pdf->Ln(5);
        foreach ($btwTotaal as $key => $value) {
            $this->pdf->Cell(98, 6, '', 0, 0);
            $this->pdf->Cell(41, 6, 'BTW '.$key.'% over '.number_format($btwOver[$key], 2), 0, 0);
            $this->pdf->Cell(41, 6, number_format($value, 2), 0, 0, 'R');
            $this->pdf->Ln(5);
        }

        $this->pdf->SetFont($this->fontnameBold, 'b', 9);
        $this->pdf->Cell(98, 6, '', 0, 0);
        $this->pdf->Cell(41, 6, 'Totaal', 'T', 0);
        $this->pdf->Cell(41, 6, $vergunning['factuur']['totaal'], 'T', 0, 'R');

        /*
                $this->pdf->Cell(15, 6, $product->totaal_inclusief, $top, 0, '' , $fillColor);
                $this->pdf->Cell(15, 6, $product->btw_totaal, $top, 0, '' , $fillColor);
                $this->pdf->Cell(15, 6, $product->btw_percentage, $top, 0, '' , $fillColor);
                $this->pdf->Cell(15, 6, $product->totaal, $top, 0, '' , $fillColor);
                $this->pdf->Cell(15, 6, $product->aantal, $top, 0, '' , $fillColor);
                $this->pdf->Cell(15, 6, $product->bedrag, $top, 0, '' , $fillColor);
                $this->pdf->Cell(90, 6, $product->naam, $top, 1, '' , $fillColor);
        */

        //        $this->pdf->Cell(90, 6, $vergunning->factuur->totaal, 'T', 0, '' , $fillColor);
        //        $this->pdf->Cell(90, 6, 'Totaal', 'T', 1, '' , $fillColor);
    }
}
