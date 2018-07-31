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

use GemeenteAmsterdam\MakkelijkeMarkt\DashboardBundle\Enum\Roles;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use GemeenteAmsterdam\MakkelijkeMarkt\DashboardBundle\Form\Type\AccountEditType;
use Symfony\Component\HttpFoundation\Request;
use GemeenteAmsterdam\MakkelijkeMarkt\DashboardBundle\Form\Type\AccountCreateType;

class AccountController extends Controller
{
    /**
     * @Route("/accounts")
     * @Template()
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function indexAction(Request $request)
    {
        $accounts = $this->get('markt_api')->getAccounts($request->query->get('active', -1), $request->query->get('locked', -1));

        return ['accounts' => $accounts];
    }

    /**
     * @Route("/accounts/edit/{id}")
     * @Template
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function editAction(Request $request, $id)
    {
        $account = $this->get('markt_api')->getAccount($id);
        $account->password = '';
        $account->role = reset($account->roles);
        $formModel = clone $account;
        $form = $this->createForm(new AccountEditType(), $formModel);

        $form->handleRequest($request);

        if ($form->isSubmitted())
        {
            if ($form->isValid())
            {
                $this->get('markt_api')->putAccount($account->id, $formModel);
                $request->getSession()->getFlashBag()->add('success', 'Opgeslagen');
                return $this->redirectToRoute('gemeenteamsterdam_makkelijkemarkt_dashboard_account_index');
            }

            $request->getSession()->getFlashBag()->add('error', 'Het formulier is niet correct ingevuld');
        }

        return ['account' => $account, 'form' => $form->createView(), 'formModel' => $formModel];
    }

    /**
     * @Route("/accounts/create")
     * @Template
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function createAction(Request $request)
    {
        $account = (object) ['naam' => '', 'email' => '', 'username' => '', 'password' => '', 'role' => 'ROLE_USER'];

        $formModel = clone $account;
        $form = $this->createForm(new AccountCreateType(), $formModel);

        $form->handleRequest($request);

        if ($form->isSubmitted())
        {
            if ($form->isValid())
            {
                $this->get('markt_api')->postAccount($formModel);
                $request->getSession()->getFlashBag()->add('success', 'Aangemaakt');
                return $this->redirectToRoute('gemeenteamsterdam_makkelijkemarkt_dashboard_account_index');
            }

            $request->getSession()->getFlashBag()->add('error', 'Het formulier is niet correct ingevuld');
        }

        return ['form' => $form->createView(), 'formModel' => $formModel];
    }

    /**
     * @Route("/accounts/unlock/{id}")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function unlockAction(Request $request, $id)
    {
        $this->get('markt_api')->unlockAccount($id);
        return $this->redirectToRoute('gemeenteamsterdam_makkelijkemarkt_dashboard_account_index');
    }

    /**
     * @Route("/accounts/detail/{id}/tokens")
     * @Template()
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function showTokensAction(Request $request, $id)
    {
        $pageNumber = $request->query->getInt('page', 0);
        $pageSize = 100;

        $account = $this->get('markt_api')->getAccount($id);
        $tokens = $this->get('markt_api')->getTokensByAccount($id, $pageNumber * $pageSize, $pageSize);

        return [
                'pageNumber' => $pageNumber,
                'pageSize' => $pageSize,
                'account' => $account,
                'tokens' => $tokens
            ];
    }
}
