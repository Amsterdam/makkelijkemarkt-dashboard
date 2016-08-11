<?php

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
        $accounts = $this->get('markt_api')->getAccounts();

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
}
