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

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    /**
     * @Route("/")
     *
     * @Template()
     *
     * @Security("is_granted('IS_AUTHENTICATED_FULLY')")
     */
    public function indexAction(): RedirectResponse
    {
        if ($this->isGranted('ROLE_ACCOUNTANT')) {
            return $this->redirectToRoute('app_rapport_factuurdetail');
        }

        return $this->redirectToRoute('app_dagvergunning_index');
    }
}
