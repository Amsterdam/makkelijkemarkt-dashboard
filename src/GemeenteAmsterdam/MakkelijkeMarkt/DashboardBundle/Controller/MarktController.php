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

use GemeenteAmsterdam\MakkelijkeMarkt\DashboardBundle\Enum\Roles;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use GemeenteAmsterdam\MakkelijkeMarkt\DashboardBundle\Form\Type\AccountEditType;
use Symfony\Component\HttpFoundation\Request;
use GemeenteAmsterdam\MakkelijkeMarkt\DashboardBundle\Form\Type\AccountCreateType;
use GemeenteAmsterdam\MakkelijkeMarkt\DashboardBundle\Form\Type\MarktType;

class MarktController extends Controller
{
    /**
     * @Route("/markten")
     * @Template()
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function indexAction(Request $request)
    {
        $markten = $this->get('markt_api')->getMarkten();

        return ['markten' => $markten];
    }

    /**
     * @Route("/markten/edit/{id}")
     * @Template
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function editAction(Request $request, $id)
    {
        $markt = $this->get('markt_api')->getMarkt($id);

        $formModel = clone $markt;
        $form = $this->createForm(new MarktType(), $formModel);

        $form->handleRequest($request);

        if ($form->isSubmitted())
        {
            if ($form->isValid())
            {
                $this->get('markt_api')->postMarkt($markt->id, $formModel);
                $request->getSession()->getFlashBag()->add('success', 'Opgeslagen');
                return $this->redirectToRoute('gemeenteamsterdam_makkelijkemarkt_dashboard_markt_index');
            }

            $request->getSession()->getFlashBag()->add('error', 'Het formulier is niet correct ingevuld');
        }

        return ['markt' => $markt, 'form' => $form->createView(), 'formModel' => $formModel];
    }
}
