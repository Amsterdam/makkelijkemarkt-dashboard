<?php

declare(strict_types=1);

namespace App\Controller;

use App\Constants\Translations;
use App\Form\FactuurSimulatorType;
use App\Form\TarievenplanType;
use App\Service\FactuurSimulationService;
use App\Service\MarktApi;
use App\Service\TarievenplanService;
use App\Service\TranslationService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class TarievenplanV2Controller extends AbstractController
{
    /**
     * @Route("/tarievenplan", name="app_tarievenplan_index", methods={"GET"})
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
     * @Route("/tarievenplan/{marktId}",
     *      name="app_tarievenplan_marktindex",
     *      methods={"GET"},
     *      requirements={"marktId"="\d+"}
     * )
     * @Template()
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function marktindexAction(MarktApi $api, int $marktId): array
    {
        $markt = $api->getMarkt($marktId);
        $tariefplannen = $api->getTarievenplannenByMarktId($marktId);

        array_walk($tariefplannen, function (&$plan) {
            $plan['variant'] = TranslationService::translateWord($plan['variant'], Translations::VARIANTS);
        });

        return [
            'markt' => $markt,
            'tariefplannen' => $tariefplannen,
        ];
    }

    /**
     * @Route("/tarievenplan/{marktId}/create/{type}/{variant}",
     *      name="app_tarievenplan_create",
     *      methods={"GET", "POST"},
     *      requirements={
     *          "marktId"="\d+",
     *          "type"="concreet|lineair",
     *          "variant"="standard|daysOfWeek|specific"
     *      }
     * )
     * @Template("tarievenplan_v2/update.html.twig")
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function createTarievenplanAction(Request $request, MarktApi $api, int $marktId, string $type, string $variant)
    {
        $markt = $api->getMarkt($marktId);

        $formModel = [
            'tarievenplan' => [
                'type' => $type,
                'marktId' => $marktId,
                'variant' => $variant,
                'marktName' => $markt['naam'],
            ],
            'tariefSoorten' => $api->getActiveTariefSoorten($type),
        ];

        $form = $this->createForm(TarievenplanType::class, $formModel);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $data = TarievenplanService::preparePostData($form->getData());

                try {
                    $api->createTarievenplan($marktId, $type, $data);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Tariefplan kon niet aangemaakt worden. Voorkom dezelfde varianten met een gelijke startdatum.');

                    return $this->redirectToRoute('app_tarievenplan_marktindex', ['marktId' => $marktId]);
                }

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
     * @Route("/tarievenplan/{tarievenplanId}/delete",
     *      name="app_tarievenplan_delete",
     *      methods={"POST"},
     *      requirements={"tarievenplanId"="\d+"}
     * )
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function deleteAction(Request $request, MarktApi $api, int $tarievenplanId): RedirectResponse
    {
        if (false === $this->isCsrfTokenValid('app_tarievenplan_delete', $request->request->get('_csrf'))) {
            throw $this->createAccessDeniedException();
        }

        $response = $api->getTarievenplan($tarievenplanId);
        $tarievenplan = $response['tarievenplan'];
        try {
            $api->deleteTarievenplan($tarievenplan['id']);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Kon tariefplan niet verwijderen. Zorg dat er altijd een actief standaard plan over blijft.');
        }

        return $this->redirectToRoute('app_tarievenplan_marktindex', ['marktId' => $tarievenplan['marktId']]);
    }

    /**
     * @Route("/tarievenplan/update/{tarievenplanId}",
     *      name="app_tarievenplan_update",
     *      methods={"GET", "POST"},
     *      requirements={"tarievenplanId"="\d+"}
     * )
     * @Template
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function updateAction(Request $request, MarktApi $api, int $tarievenplanId)
    {
        $formModel = $api->getTarievenplan($tarievenplanId);
        $form = $this->createForm(TarievenplanType::class, $formModel);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $data = TarievenplanService::preparePostData($form->getData());

                $api->updateTarievenplan($tarievenplanId, $data);
                $this->addFlash('success', 'Tarievenplan aangepast');

                return $this->redirectToRoute('app_tarievenplan_marktindex', ['marktId' => $data['tarievenplan']['marktId']]);
            }

            $this->addFlash('error', 'Het formulier is niet correct ingevuld');
        }

        return [
            'form' => $form->createView(),
            'formModel' => $formModel,
        ];
    }

    /**
     * @Route("/tarievenplan/simulate/{tarievenPlanType}/{marktId}", name="app_tarievenplan_simulate", methods={"GET", "POST"})
     * @Template
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function simulateFactuurAction(Request $request, MarktApi $api, string $tarievenPlanType, int $marktId)
    {
        $markt = $api->getMarktFlex($marktId);
        $formModel = [
            'markt' => $markt,
        ];

        $form = $this->createForm(FactuurSimulatorType::class, $formModel);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $formData = $form->getData();
                $postData = FactuurSimulationService::createPostData($formData, $markt);

                $factuur = $api->simulateFactuur($postData);
                $this->addFlash('success', 'Factuur gesimuleerd');

                return $this->render('tarievenplan_v2/simulate_result.html.twig', [
                    'factuur' => $factuur,
                    'input' => [
                        'paid' => FactuurSimulationService::createProductObjects($formData['paid'], $markt),
                        'unpaid' => FactuurSimulationService::createProductObjects($formData['unpaid'], $markt),
                    ],
                ]);
            }

            $this->addFlash('error', 'Het formulier is niet correct ingevuld');
        }

        return [
            'form' => $form->createView(),
            'formModel' => $formModel,
        ];
    }
}
