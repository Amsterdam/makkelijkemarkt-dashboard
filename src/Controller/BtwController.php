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
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use App\Form\BtwCreateType;

class BtwController extends AbstractController
{
    /**
     * @Route("/btw", name="app_btw_index")
     * @Template()
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function indexAction(MarktApi $api): array
    {
        $btw = $api->getBtw();

        return ['btw' => $btw];
    }

    /**
     * @Route("/btw/create_update")
     * @Route("/btw/create_update/{jaar}", name="app_btw_createorupdate_jaar")
     * @Template("/btw/create.html.twig")
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function createOrUpdateAction(string $jaar = null, Request $request, MarktApi $api)
    {
        $formModel = [
            'jaar' => null === $jaar ? '' : $jaar,
            'hoog' => ''
        ]
        ;
        $form = $this->createForm(BtwCreateType::class, $formModel);

        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $api->postBtw($form->getData());
                $this->addFlash('success', 'Aangemaakt');
                return $this->redirectToRoute('app_btw_index');
            }

            $this->addFlash('error', 'Het formulier is niet correct ingevuld');
        }

        return [
            'form' => $form->createView(),
            'formModel' => $formModel
        ];
    }
}
