<?php

declare(strict_types=1);

namespace App\Controller;

use App\Form\TarievenplanType;
use App\Service\MarktApi;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class TarievenplanV2Controller extends AbstractController
{
    /**
     * @Route("/tariefplan", name="app_tarievenplan_index", methods={"GET"})
     * @Template()
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function indexAction(MarktApi $api)
    {
        $markten = $api->getNonExpiredMarkten();

        return [
            'markten' => $markten,
        ];
    }

    /**
     * @Route("/tarievenplan/{marktId}", name="app_tarievenplan_marktindex", methods={"GET"}, requirements={"marktId"="\d+"})
     * @Template()
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function marktindexAction(MarktApi $api, int $marktId): array
    {
        $markt = $api->getMarkt($marktId);
        $tariefplannen = $api->getTarievenplannenByMarktId($marktId);

        return [
            'markt' => $markt,
            'tariefplannen' => $tariefplannen,
        ];
    }

    /**
     * @Route("/tarievenplan/{marktId}/create/{type}", name="app_tarievenplan_create", methods={"GET", "POST"}, requirements={"marktId"="\d+", "type"="concreet|lineair"})
     * @Template("tarievenplan_v2/update.html.twig")
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function createTarievenplanAction(Request $request, MarktApi $api, int $marktId, string $type)
    {
        $formModel = [
            'tarievenplan' => [
                'type' => $type, 'marktId' => $marktId,
            ],
            'tariefSoorten' => $api->getActiveTariefSoorten($type),
        ];

        $form = $this->createForm(TarievenplanType::class, $formModel);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $api->createTarievenplan($marktId, $type, $form->getData());
                $this->addFlash('success', 'Tariefplan aangemaakt');

                return $this->redirectToRoute('app_tarievenplan_marktindex', ['marktId' => $marktId]);
            }

            $this->addFlash('error', 'Het formulier is niet correct ingevuld');
        }

        return $this->render('tarievenplan_v2/update.html.twig', [
            'form' => $form->createView(),
            'formModel' => $formModel,
        ]);
    }

    /**
     * @Route("/tarievenplan/{tarievenplanId}/delete", name="app_tarievenplan_delete", methods={"POST"}, requirements={"tarievenplanId"="\d+"})
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function deleteAction(Request $request, MarktApi $api, int $tarievenplanId): RedirectResponse
    {
        if (false === $this->isCsrfTokenValid('app_tarievenplan_delete', $request->request->get('_csrf'))) {
            throw $this->createAccessDeniedException();
        }

        $response = $api->getTarievenplan($tarievenplanId);
        $tarievenplan = $response['tarievenplan'];
        $api->deleteTarievenplan($tarievenplan['id']);

        return $this->redirectToRoute('app_tarievenplan_marktindex', ['marktId' => $tarievenplan['marktId']]);
    }

    /**
     * @Route("/tarievenplan/update/{tarievenplanId}", name="app_tarievenplan_update", methods={"GET", "POST"}, requirements={"tarievenplanId"="\d+"})
     * @Template
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function updateAction(Request $request, MarktApi $api, int $tarievenplanId)
    {
        $tarievenplan = $api->getTarievenplan($tarievenplanId);
        $form = $this->createForm(TarievenplanType::class, $tarievenplan);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $data = $form->getData();

                $api->updateTarievenplan($tarievenplanId, $data);
                $this->addFlash('success', 'Tarievenplan aangepast');

                // TODO: met Marktbureau bespreken of we niet liever naar het formulier willen redirecten
                return $this->redirectToRoute('app_tarievenplan_marktindex', ['marktId' => $data['tarievenplan']['marktId']]);
            }

            $this->addFlash('error', 'Het formulier is niet correct ingevuld');
        }

        return [
            'form' => $form->createView(),
            'formModel' => $tarievenplan,
        ];
    }
}
