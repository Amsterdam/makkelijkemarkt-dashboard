<?php

namespace App\Controller;

use App\Constants\Translations;
use App\Form\TariefSoortType;
use App\Service\MarktApi;
use App\Service\TranslationService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class TariefSoortController extends AbstractController
{
    /**
     * @Route("/tariefsoort", name="app_tariefsoort_index", methods={"GET"})
     *
     * @Template()
     *
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function indexAction(MarktApi $api)
    {
        $tariefSoorten = $api->getTariefSoorten();
        array_walk($tariefSoorten, function (&$soort) {
            $soort['unit'] = TranslationService::translateWord($soort['unit'], Translations::UNITS);
        });

        return [
            'tariefSoorten' => $tariefSoorten,
        ];
    }

    /**
     * @Route("/tariefsoort/create", name="app_tariefsoort_create", methods={"GET", "POST"})
     *
     * @Template()
     *
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function createAction(MarktApi $api, Request $request)
    {
        $tariefSoort = [
            'isUpdate' => false,
        ];

        $form = $this->createForm(TariefSoortType::class, $tariefSoort);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $data = $form->getData();

                $data['unit'] = TranslationService::translateWord($data['unit'], Translations::UNITS, true);

                $api->createTariefSoort($data);
                $this->addFlash('success', 'Nieuwe tariefsoort aangemaakt.');

                return $this->redirectToRoute('app_tariefsoort_index');
            }

            $this->addFlash('error', 'De aanpassing kon niet opgeslagen worden');
        }

        return [
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/tariefsoort/{id}/edit", name="app_tariefsoort_edit", methods={"GET", "POST"})
     *
     * @Template()
     *
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function editAction(MarktApi $api, Request $request, int $id)
    {
        $tariefSoort = $api->getTariefSoortById($id);
        $tariefSoort['isUpdate'] = true;

        $form = $this->createForm(TariefSoortType::class, $tariefSoort);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $data = $form->getData();

                $data['unit'] = TranslationService::translateWord($data['unit'], Translations::UNITS, true);

                $api->updateTariefSoort($id, $data);
                $this->addFlash('success', 'Tariefsoort aangepast.');

                return $this->redirectToRoute('app_tariefsoort_index');
            }

            $this->addFlash('error', 'De aanpassing kon niet opgeslagen worden');
        }

        return [
            'form' => $form->createView(),
        ];
    }
}
