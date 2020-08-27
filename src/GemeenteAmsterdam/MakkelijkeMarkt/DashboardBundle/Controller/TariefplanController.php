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

use GemeenteAmsterdam\MakkelijkeMarkt\DashboardBundle\Form\Type\ConcreetplanType;
use GemeenteAmsterdam\MakkelijkeMarkt\DashboardBundle\Form\Type\LineairplanType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use GemeenteAmsterdam\MakkelijkeMarkt\DashboardBundle\Form\Type\AccountEditType;
use Symfony\Component\HttpFoundation\Request;
use GemeenteAmsterdam\MakkelijkeMarkt\DashboardBundle\Form\Type\AccountCreateType;

class TariefplanController extends Controller
{
    /**
     * @Route("/tariefplan")
     * @Template()
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function indexAction()
    {
        $markten = $this->get('markt_api')->getMarkten();

        return [
            'markten'         => $markten
        ];
    }

    /**
     * @Route("/tariefplan/{marktId}")
     * @Template()
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function marktindexAction($marktId)
    {
        $markt = $this->get('markt_api')->getMarkt($marktId);
        $tariefplannen = $this->get('markt_api')->getTariefplannenByMarktId($marktId);


        foreach ($tariefplannen['results'] as &$tariefplan) {
            $tariefplan->geldigVanaf = new \DateTime($tariefplan->geldigVanaf->date);
            $tariefplan->geldigTot =  new \DateTime($tariefplan->geldigTot->date);
        }

        return [
            'markt'         => $markt,
            'tariefplannen' => $tariefplannen['results']
        ];
    }

    /**
     * @Route("/tariefplan/{marktId}/create/lineair")
     * @Template
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function createLineairAction(Request $request, $marktId)
    {
        $tariefplan = $this->getLineairPlanObject();

        $formModel = clone $tariefplan;
        $form = $this->createForm(new LineairplanType(), $formModel);

        $form->handleRequest($request);

        if ($form->isSubmitted())
        {
            if ($form->isValid())
            {
                $this->get('markt_api')->postLineairTariefplan($marktId, $formModel);
                $request->getSession()->getFlashBag()->add('success', 'Aangemaakt');
                return $this->redirectToRoute('gemeenteamsterdam_makkelijkemarkt_dashboard_tariefplan_marktindex',array('marktId'=>$marktId));
            }

            $request->getSession()->getFlashBag()->add('error', 'Het formulier is niet correct ingevuld');
        }

        return ['form' => $form->createView(), 'formModel' => $formModel];
    }

    /**
     * @Route("/tariefplan/{tariefPlanId}/delete")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function deleteAction(Request $request, $tariefPlanId)
    {
        $marktApi = $this->get('markt_api');

        $tariefplan = $marktApi->getTariefPlan($tariefPlanId);

        $marktApi->deleteTariefPlan($tariefplan->id);

        return $this->redirectToRoute('gemeenteamsterdam_makkelijkemarkt_dashboard_tariefplan_marktindex',array('marktId'=>$tariefplan->marktId));
    }

    /**
     * @Route("/tariefplan/{tariefPlanId}/update/lineair")
     * @Template
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function updateLineairAction(Request $request, $tariefPlanId)
    {
        $marktApi = $this->get('markt_api');
        $tariefPlanObject = $marktApi->getTariefPlan($tariefPlanId);

        $formModel = $this->getLineairPlanObject($tariefPlanObject);

        $form = $this->createForm(new LineairplanType(), $formModel);

        $form->handleRequest($request);

        if ($form->isSubmitted())
        {
            if ($form->isValid())
            {
                $this->get('markt_api')->updateLineairTariefplan($tariefPlanId, $formModel);
                $request->getSession()->getFlashBag()->add('success', 'Aangepast');
                return $this->redirectToRoute('gemeenteamsterdam_makkelijkemarkt_dashboard_tariefplan_marktindex',array('marktId'=>$tariefPlanObject->marktId));
            }

            $request->getSession()->getFlashBag()->add('error', 'Het formulier is niet correct ingevuld');
        }

        return ['form' => $form->createView(), 'formModel' => $formModel];
    }

    protected function getLineairPlanObject($tariefPlanObject = null) {
        if (null !== $tariefPlanObject) {
            $geldigVanaf = new \DateTime($tariefPlanObject->geldigVanaf->date);
            $geldigTot = new \DateTime($tariefPlanObject->geldigTot->date);
            return $tariefplan = (object)[
                'naam' => $tariefPlanObject->naam,
                'geldigVanaf' => $geldigVanaf,
                'geldigTot' => $geldigTot,
                'tariefPerMeter' => $tariefPlanObject->lineairplan->tariefPerMeter,
                'reinigingPerMeter' => $tariefPlanObject->lineairplan->reinigingPerMeter,
                'toeslagBedrijfsafvalPerMeter' => $tariefPlanObject->lineairplan->toeslagBedrijfsafvalPerMeter,
                'toeslagKrachtstroomPerAansluiting' => $tariefPlanObject->lineairplan->toeslagKrachtstroomPerAansluiting,
                'promotieGeldenPerMeter' => $tariefPlanObject->lineairplan->promotieGeldenPerMeter,
                'promotieGeldenPerKraam' => $tariefPlanObject->lineairplan->promotieGeldenPerKraam,
                'afvaleiland' => $tariefPlanObject->lineairplan->afvaleiland,
                'eenmaligElektra' => $tariefPlanObject->lineairplan->eenmaligElektra
            ];
        } else {
            return $tariefplan = (object)[
                'naam' => '',
                'geldigVanaf' => null,
                'geldigTot' => null,
                'tariefPerMeter' => null,
                'reinigingPerMeter' => null,
                'toeslagBedrijfsafvalPerMeter' => null,
                'toeslagKrachtstroomPerAansluiting' => null,
                'promotieGeldenPerMeter' => null,
                'promotieGeldenPerKraam' => null,
                'afvaleiland' => null,
                'eenmaligElektra' => null
            ];
        }
    }

    protected function getConcreetPlanObject($tariefPlanObject = null) {
        if (null !== $tariefPlanObject) {
            $geldigVanaf = new \DateTime($tariefPlanObject->geldigVanaf->date);
            $geldigTot = new \DateTime($tariefPlanObject->geldigTot->date);
            return $tariefplan = (object)[
                'naam' => $tariefPlanObject->naam,
                'geldigVanaf' => $geldigVanaf,
                'geldigTot' => $geldigTot,
                'een_meter' => $tariefPlanObject->concreetplan->een_meter,
                'drie_meter' => $tariefPlanObject->concreetplan->drie_meter,
                'vier_meter' => $tariefPlanObject->concreetplan->vier_meter,
                'elektra' => $tariefPlanObject->concreetplan->elektra,
                'promotieGeldenPerMeter' => $tariefPlanObject->concreetplan->promotieGeldenPerMeter,
                'promotieGeldenPerKraam' => $tariefPlanObject->concreetplan->promotieGeldenPerKraam,
                'afvaleiland' => $tariefPlanObject->concreetplan->afvaleiland,
                'eenmaligElektra' => $tariefPlanObject->concreetplan->eenmaligElektra
            ];
        } else {
            return $tariefplan = (object)[
                'naam' => '',
                'geldigVanaf' => null,
                'geldigTot' => null,
                'een_meter' => null,
                'drie_meter' => null,
                'vier_meter' => null,
                'elektra' => null,
                'promotieGeldenPerMeter' => null,
                'promotieGeldenPerKraam' => null,
                'afvaleiland' => null,
                'eenmaligElektra' => null
            ];
        }
    }

    /**
     * @Route("/tariefplan/{marktId}/create/concreet")
     * @Template
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function createConcreetAction(Request $request, $marktId)
    {
        $tariefplan = $this->getConcreetPlanObject();

        $formModel = clone $tariefplan;
        $form = $this->createForm(new ConcreetplanType(), $formModel);

        $form->handleRequest($request);

        if ($form->isSubmitted())
        {
            if ($form->isValid())
            {
                $this->get('markt_api')->postConcreetTariefplan($marktId, $formModel);
                $request->getSession()->getFlashBag()->add('success', 'Aangemaakt');
                return $this->redirectToRoute('gemeenteamsterdam_makkelijkemarkt_dashboard_tariefplan_marktindex',array('marktId'=>$marktId));
            }

            $request->getSession()->getFlashBag()->add('error', 'Het formulier is niet correct ingevuld');
        }

        return ['form' => $form->createView(), 'formModel' => $formModel];
    }

    /**
     * @Route("/tariefplan/{tariefPlanId}/update/concreet")
     * @Template
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function updateConcreetAction(Request $request, $tariefPlanId)
    {
        $marktApi = $this->get('markt_api');
        $tariefPlanObject = $marktApi->getTariefPlan($tariefPlanId);

        $formModel = $this->getConcreetPlanObject($tariefPlanObject);

        $form = $this->createForm(new ConcreetplanType(), $formModel);

        $form->handleRequest($request);

        if ($form->isSubmitted())
        {
            if ($form->isValid())
            {
                $this->get('markt_api')->updateConcreetTariefplan($tariefPlanId, $formModel);
                $request->getSession()->getFlashBag()->add('success', 'Aangepast');
                return $this->redirectToRoute('gemeenteamsterdam_makkelijkemarkt_dashboard_tariefplan_marktindex',array('marktId'=>$tariefPlanObject->marktId));
            }

            $request->getSession()->getFlashBag()->add('error', 'Het formulier is niet correct ingevuld');
        }

        return ['form' => $form->createView(), 'formModel' => $formModel];
    }
}
