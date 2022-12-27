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

use App\Form\BtwCreateType;
use App\Form\BtwImportType;
use App\Service\MarktApi;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

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
            'hoog' => '',
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
            'formModel' => $formModel,
        ];
    }

    /**
     * @Route("btw/import")
     * @Template("btw/import_form.html.twig")
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function import(Request $request, MarktApi $api)
    {
        $formModel = [
            'file' => null,
            'planType' => null,
        ]
        ;
        $form = $this->createForm(BtwImportType::class, $formModel);

        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            $data = $form->getData();
            $api->importBtw($data);

            $this->addFlash('error', 'Het formulier is niet correct ingevuld');
        }

        return [
            'form' => $form->createView(),
            'formModel' => $formModel,
        ];
    }

    /**
     * @Route("btw/get_plans")
     * @Template("btw/btw_plan_overview.html.twig")
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function getPlans(MarktApi $api)
    {
        return ['plans' => $api->getBtwPlans()];
    }
}
