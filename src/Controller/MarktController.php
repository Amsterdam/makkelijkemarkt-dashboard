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

use App\Form\MarktType;
use App\Service\MarktApi;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class MarktController extends AbstractController
{
    /**
     * @Route("/markten")
     * @Template()
     * @Security("is_granted('ROLE_USER')")
     */
    public function indexAction(MarktApi $api): array
    {
        $markten = $api->getNonExpiredMarkten();

        return ['markten' => $markten, 'title' => 'Markten', 'index' => true];
    }

    /**
     * @Route("/markten/archief")
     * @Template("markt/index.html.twig")
     * @Security("is_granted('ROLE_USER')")
     */
    public function archiveAction(MarktApi $api): array
    {
        $markten = $api->getExpiredMarkten();

        return ['markten' => $markten, 'title' => 'Markten archief', 'index' => false];
    }

    /**
     * @Route("/markten/edit/{id}")
     * @Template
     * @Security("is_granted('ROLE_USER')")
     */
    public function editAction(Request $request, int $id, MarktApi $api)
    {
        $markt = $api->getMarktFlex($id);
        $formModel = [
            'markt' => $markt,
            'dagvergunningMappings' => $api->getDagvergunningMapping(),
        ];

        $form = $this->createForm(MarktType::class, $formModel);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $api->postMarkt($markt['id'], $form->getData());
                $this->addFlash('success', 'Opgeslagen');

                return $this->redirectToRoute('app_markt_index');
            }

            $this->addFlash('error', 'Het formulier is niet correct ingevuld');
        }

        return ['formModel' => $formModel, 'form' => $form->createView()];
    }
}
