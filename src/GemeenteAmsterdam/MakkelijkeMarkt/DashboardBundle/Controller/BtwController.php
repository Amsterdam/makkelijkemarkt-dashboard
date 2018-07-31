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

use GemeenteAmsterdam\MakkelijkeMarkt\DashboardBundle\Form\Type\BtwCreateType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;

class BtwController extends Controller
{
    /**
     * @Route("/btw", name="gemeenteamsterdam_makkelijkemarkt_dashboard_btw_index")
     * @Template()
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function indexAction(Request $request)
    {
        $btw = $this->get('markt_api')->getBtw();

        return ['btw' => $btw];
    }

    /**
     * @Route("/btw/create_update")
     * @Route("/btw/create_update/{jaar}", name="gemeenteamsterdam_makkelijkemarkt_dashboard_btw_createorupdate_jaar")
     * @Template("@Dashboard/Btw/create.html.twig")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function createOrUpdateAction($jaar = null, Request $request)
    {
        $btw = (object) ['jaar' => null === $jaar ? '' : $jaar, 'hoog' => ''];

        $formModel = clone $btw;
        $form = $this->createForm(new BtwCreateType(), $formModel);

        $form->handleRequest($request);

        if ($form->isSubmitted())
        {
            if ($form->isValid())
            {
                $this->get('markt_api')->postBtw($formModel);
                $request->getSession()->getFlashBag()->add('success', 'Aangemaakt');
                return $this->redirectToRoute('gemeenteamsterdam_makkelijkemarkt_dashboard_btw_index');
            }

            $request->getSession()->getFlashBag()->add('error', 'Het formulier is niet correct ingevuld');
        }

        return ['form' => $form->createView(), 'formModel' => $formModel];
    }
}
