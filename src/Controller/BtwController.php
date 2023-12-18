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

use App\Form\BtwPlanType;
use App\Form\TariefEnBtwImportType;
use App\Service\BtwPlanEditingService;
use App\Service\MarktApi;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class BtwController extends AbstractController
{
    /**
     * @Route("/btw/create/{planType}", name="app_btw_plan_create", methods={"GET", "POST"})
     *
     * @Template("/btw/create.html.twig")
     *
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function createBtwPlan(
        string $planType,
        Request $request,
        MarktApi $api,
        BtwPlanEditingService $planEditingService
    ) {
        $formModel = $api->getBtwCreate($planType);
        $formModel['planType'] = $planType;
        $form = $this->createForm(BtwPlanType::class, $formModel);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $data = $planEditingService->handleCreateBtwPlanForm($form->getData());
            $api->postBtwPlan($data);
            $this->addFlash('success', 'Nieuw plan aangemaakt.');

            return $this->redirectToRoute('app_btw_overview');
        }

        return [
            'form' => $form->createView(),
            'formModel' => $formModel,
        ];
    }

    /**
     * @Route("/btw/update/{planType}/{btwPlanId?}", name="app_btw_plan_update", methods={"GET", "POST"})
     *
     * @Template("/btw/create.html.twig")
     *
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function updateBtwPlan(
        string $planType,
        ?int $btwPlanId,
        Request $request,
        MarktApi $api,
        BtwPlanEditingService $planEditingService
    ) {
        $formModel = $api->getBtwUpdate($btwPlanId);
        $formModel['planType'] = $planType;

        $form = $this->createForm(BtwPlanType::class, $formModel);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $data = $planEditingService->handleUpdateBtwPlanForm($form->getData());

            if (empty($data)) {
                $this->addFlash('info', 'Geen wijzigingen opgeslagen.');

                return $this->redirectToRoute('app_btw_overview');
            }

            $api->patchBtwPlan($data);
            $this->addFlash('success', 'BTW Plan gewijzigd.');

            return $this->redirectToRoute('app_btw_overview');
        }

        return [
            'form' => $form->createView(),
            'formModel' => $formModel,
        ];
    }

    /**
     * @Route("import/btw", name="app_import_btw", methods={"GET", "POST"})
     *
     * @Template("btw/import_form.html.twig")
     *
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function import(Request $request, MarktApi $api)
    {
        $formModel = [
            'file' => null,
            'planType' => null,
        ]
        ;
        $form = $this->createForm(TariefEnBtwImportType::class, $formModel);

        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $data = $form->getData();
                $api->importBtw($data);
                $this->addFlash('success', 'De import is gelukt.');
            } else {
                $this->addFlash('error', 'Kan niet importeren.');
            }
        }

        return [
            'form' => $form->createView(),
            'formModel' => $formModel,
        ];
    }

    /**
     * @Route("btw/{planType?lineair}", name="app_btw_overview", methods={"GET"})
     *
     * @Template("btw/btw_plan_overview.html.twig")
     *
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function getPlans(string $planType, MarktApi $api, BtwPlanEditingService $planEditingService)
    {
        $plans = $planEditingService->mapActivePlans($api->getBtwPlans($planType));

        return ['plans' => $plans, 'planType' => $planType];
    }

    /**
     * @Route("btw/archive_plan/{id}", name="app_btw_plan_archive", methods={"GET"})
     *
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function archivePlan(int $id, MarktApi $api): RedirectResponse
    {
        $api->archiveBtwPlan($id);
        $this->addFlash('success', 'BTW plan gearchiveerd.');

        return $this->redirectToRoute('app_btw_overview');
    }
}
