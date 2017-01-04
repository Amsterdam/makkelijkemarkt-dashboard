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
use GemeenteAmsterdam\MakkelijkeMarkt\DashboardBundle\Form\Type\AccountEditType;
use Symfony\Component\HttpFoundation\Request;
use GemeenteAmsterdam\MakkelijkeMarkt\DashboardBundle\Form\Type\AccountCreateType;

class KoopmanController extends Controller
{
    /**
     * @Route("/koopmannen")
     * @Template()
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function indexAction(Request $request)
    {
        $page = $request->query->get('page', 0);
        $size = 30;

        $q = ['freeSearch' => $request->query->get('q'), 'erkenningsnummer' => $request->query->get('erkenningsnummer'), 'status' => $request->query->get('status', 1)];

        $koopmannen = $this->get('markt_api')->getKoopmannen($q, $page * $size, $size);

        return [
            'koopmannen' => $koopmannen,
            'pageNumber' => $page,
            'pageSize' => $size,
            'q' => $request->query->get('q'),
            'erkenningsnummer' => $request->query->get('erkenningsnummer'),
            'status' => $request->query->get('status', 1)
        ];
    }

    /**
     * @Route("/koopmannen/detail/{id}")
     * @Template()
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function detailAction(Request $request, $id)
    {
        $koopman = $this->get('markt_api')->getKoopman($id);

        $page = $request->query->get('page', 0);
        $size = 30;
        $dagvergunningen = $this->get('markt_api')->getDagvergunningen(['koopmanId' => $koopman->id], $page * $size, $size);

        $lastQuarter = new \DateTime();
        $lastQuarter->modify('-3 months');

        list($startDate , $endDate) = $this->getQuarter($lastQuarter);

        return [
            'koopman'         => $koopman,
            'dagvergunningen' => $dagvergunningen,
            'pageNumber'      => $page,
            'pageSize'        => $size,
            'startDate'       => $startDate,
            'endDate'         => $endDate
        ];
    }

    /**
     * @Route("/koopmannen/factuur/{id}/{startDate}/{endDate}")
     * @Route("/koopmannen/factuur/", name="factuur_blank")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function factuurOverzichtAction(Request $request, $id, $startDate, $endDate)
    {
        $api = $this->get('markt_api');

        $sDate = new \DateTime($startDate);
        $eDate = new \DateTime($endDate);

        $koopman = $api->getKoopman($id);
        $dagvergunningen = $api->getDagvergunningenByDate($id, $sDate, $eDate);

        $pdfService = $this->get('pdf_factuur');


        $pdf = $pdfService->generate($koopman, $dagvergunningen, $sDate, $eDate);
        $pdf->Output('factuur_' . $koopman->erkenningsnummer . '_' . $sDate->format('d-m-Y') . '_'  . $eDate->format('d-m-Y') . '.pdf', 'I');
        die;
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
}
