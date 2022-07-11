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
use App\Form\AccountEditType;
use App\Form\AccountCreateType;
use App\Form\AccountPasswordType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;

class AccountController extends AbstractController
{
    /**
     * @Route("/accounts")
     * @Template()
     * @Security("is_granted('ROLE_SENIOR')")
     */
    public function indexAction(Request $request, MarktApi $api): array
    {
        $accounts = $api->getAccounts($request->query->getInt('active', -1), $request->query->getInt('locked', -1));

        return ['accounts' => $accounts];
    }

    /**
     * @Route("/accounts/edit/{id}")
     * @Template
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function editAction(Request $request, int $id, MarktApi $api)
    {
        $account = $api->getAccount($id);
        $account['password'] = '';
        $account['role'] = reset($account['roles']);
        $formModel = $account;
        $form = $this->createForm(AccountEditType::class, $formModel);

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $api->putAccount($account['id'], $form->getData());

                $this->addFlash('success', 'Opgeslagen');
                return $this->redirectToRoute('app_account_index');
            }

            $this->addFlash('error', 'Het formulier is niet correct ingevuld');
        }

        return [
            'account' => $account,
            'form' => $form->createView(),
            'formModel' => $formModel
        ];
    }

    /**
     * @Route("/accounts_password/{id}")
     * @Template()
     * @Security("is_granted('ROLE_SENIOR')")
     */
    public function updatePasswordAction(Request $request, int $id, MarktApi $api)
    {
        $account = $api->getAccount($id);
        $account['password'] = '';
        $account['role'] = reset($account['roles']);
        $formModel = $account;
        $form = $this->createForm(AccountPasswordType::class, $formModel);

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $api->putPassword($account['id'], $form->getData());

                $this->addFlash('success', 'Opgeslagen');
                return $this->redirectToRoute('app_account_index');
            }

            $this->addFlash('error', 'Het formulier is niet correct ingevuld');
        }

        return [
            'account' => $account,
            'form' => $form->createView(),
            'formModel' => $formModel
        ];
    }

    /**
     * @Route("/accounts/create")
     * @Template
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function createAction(Request $request, MarktApi $api)
    {
        $formModel = [
            'naam' => '',
            'email' => '',
            'username' => '',
            'password' => '',
            'role' => 'ROLE_USER'
        ];
        $form = $this->createForm(AccountCreateType::class, $formModel);

        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $api->postAccount($form->getData());

                $this->addFlash('success', 'Aangemaakt');
                return $this->redirectToRoute('app_account_index');
            }

            $this->addFlash('error', 'Het formulier is niet correct ingevuld');
        }

        return [
            'form' => $form->createView(),
            'formModel' => $formModel
        ];
    }

    /**
     * @Route("/accounts/unlock/{id}",  methods={"POST"})
     * @Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_SENIOR')")
     */
    public function unlockAction(MarktApi $api, int $id, Request $client): RedirectResponse
    {
        $api->unlockAccount($id);

        return $this->redirectToRoute('app_account_index');
    }

    /**
     * @Route("/accounts/detail/{id}/tokens")
     * @Template()
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function showTokensAction(Request $request, MarktApi $api, int $id): array
    {
        $pageNumber = $request->query->getInt('page', 0);
        $pageSize = 100;

        $account = $api->getAccount($id);
        $tokens = $api->getTokensByAccount($id, $pageNumber * $pageSize, $pageSize);

        return [
                'pageNumber' => $pageNumber,
                'pageSize' => $pageSize,
                'account' => $account,
                'tokens' => $tokens
            ];
    }
}
