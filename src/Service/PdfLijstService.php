<?php

declare(strict_types=1);

namespace App\Service;

use TCPDF;

class PdfLijstService
{
    /**
     * @var \TCPDF
     */
    protected $pdf;
    protected $projectDir;

    public function __construct(string $projectDir)
    {
        $this->projectDir = $projectDir;
    }

    public function generate(string $markt, string $naam, array $parts): TCPDF
    {
        $this->pdf = new TCPDF();

        // set document information
        $this->pdf->SetCreator('Gemeente Amsterdam');
        $this->pdf->SetAuthor('Gemeente Amsterdam');
        $this->pdf->SetTitle($naam);
        $this->pdf->SetSubject($naam);
        $this->pdf->SetKeywords($naam);

        $this->pdf->SetPrintHeader(false);
        $this->pdf->SetPrintFooter(false);
        $this->pdf->SetAutoPageBreak(false, 0);

        $this->pdf->AddPage();

        $fontname = \TCPDF_FONTS::addTTFfont(
            $this->projectDir.'/public/resources/fonts/Avenir-Roman.ttf',
            'TrueTypeUnicode',
            '',
            96
        );

        $this->pdf->Image(
            $this->projectDir.'/public/resources/images/GASD_1.png',
            10,
            10,
            50
        );

        $this->pdf->Ln(30);

        // set font
        $this->pdf->SetFont($fontname, 'b', 20);
        $this->pdf->Cell(180, 6, $markt, 0, 1);
        $this->pdf->Cell(180, 6, $naam, 0, 1);
        $this->pdf->Ln(4);

        $firstPage = true;

        foreach ($parts as $title => $sollicitaties) {
            if (count($sollicitaties)) {
                $i = 0;
                $col = 0;
                $pb = 1;
                $p = 0;
                $page = [];
                $cols = [[], []];
                foreach ($sollicitaties as $sollicitatie) {
                    $cols[$col][] = $sollicitatie;
                    ++$i;
                    if ((0 === $p && 34 === $i) || (0 != $p && 44 === $i)) {
                        $col = 0 === $col ? 1 : 0;
                        $i = 0;
                        $pb = 0 === $pb ? 1 : 0;
                        if ($pb) {
                            $page[$p] = $cols;
                            $cols = [[], []];
                            ++$p;
                        }
                    }
                }
                $page[] = $cols;
            } else {
                $page = [];
            }

            if ($firstPage) {
                $firstPage = false;
            } else {
                $this->pdf->AddPage();
            }
            $this->pdf->SetFont($fontname, 'b', 15);
            $this->pdf->Cell(180, 6, $title, 0, 1);

            $this->pdf->Ln(4);

            $this->pdf->SetFont($fontname, '', 12);

            $even = false;
            foreach ($page as $key => $cols) {
                if (0 !== $key) {
                    $this->pdf->AddPage();
                }
                for ($i = 0; $i < count($cols[0]); ++$i) {
                    if ($even) {
                        $this->pdf->SetFillColor(237, 237, 237);
                        $even = false;
                    } else {
                        $this->pdf->SetFillColor(255, 255, 255);
                        $even = true;
                    }
                    $this->addSollicitatie($cols[0][$i]);
                    if (isset($cols[1][$i])) {
                        $this->addSollicitatie($cols[1][$i], true);
                    } else {
                        $this->pdf->Cell(90, 6, '', 0, 0, '', true);
                        $this->pdf->Ln();
                    }
                }
            }
        }

        return $this->pdf;
    }

    protected function addSollicitatie(array $sollicitatie, bool $break = false): void
    {
        $koopman = $sollicitatie['koopman'];
        $this->pdf->Cell(20, 6, $sollicitatie['sollicitatieNummer'], 0, 0, '', true);
        switch ($sollicitatie['status']) {
            case 'soll':
                $this->pdf->SetTextColor(3, 192, 60);
                break;
            case 'vpl':
                $this->pdf->SetTextColor(255, 179, 71);
                break;
            case 'vkk':
            case 'tvpl':
            case 'tvplz':
            case 'exp':
            case 'expf':
                $this->pdf->SetTextColor(150, 111, 214);
                break;
        }
        $this->pdf->Cell(10, 6, $sollicitatie['status'], 0, 0, '', true);
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->Cell(50, 6, substr($koopman['achternaam'].', '.$koopman['voorletters'], 0, 20), 0, 0, '', true);
        $this->pdf->Cell(10, 6, '', 0, 0, '', true);
        if ($break) {
            $this->pdf->Ln();
        }
    }
}
