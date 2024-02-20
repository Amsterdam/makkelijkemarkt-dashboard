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

use App\Kernel;
use App\Service\MarktApi;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;

class InfoController extends AbstractController
{
    /**
     * @Route("/info/version")
     *
     * @Template()
     *
     * @Security("is_granted('IS_AUTHENTICATED_FULLY')")
     */
    public function versionAction(MarktApi $api, KernelInterface $kernel, string $marktApi): array
    {
        /* @var Kernel $kernel */

        return [
            'apiVersion' => $api->getVersion(),
            'dashboardVersion' => $kernel->getVersion(),
            'apiUrl' => $marktApi,
        ];
    }
}
