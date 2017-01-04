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

class InfoController extends Controller
{
    /**
     * @Route("/info/version")
     * @Template()
     * @Security("is_granted('IS_AUTHENTICATED_FULLY')")
     */
    public function versionAction(Request $request)
    {
        /* @var $api \GemeenteAmsterdam\MakkelijkeMarkt\DashboardBundle\Service\MarktApi */
        $api = $this->get('markt_api');
        /* @var $kernel \AppData */
        $kernel = $this->get('kernel');

        return [
            'apiVersion' => $api->getVersion(),
            'dashboardVersion' => $kernel->getVersion(),
            'apiUrl' => $this->container->getParameter('markt_api.url')
        ];
    }
}
