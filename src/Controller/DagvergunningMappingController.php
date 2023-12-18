<?php

namespace App\Controller;

use App\Constants\Translations;
use App\Form\DagvergunningMappingType;
use App\Service\MarktApi;
use App\Service\TranslationService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class DagvergunningMappingController extends AbstractController
{
    /**
     * @Route("/dagvergunning_mapping", name="app_dagvergunningmapping_index", methods={"GET"})
     *
     * @Template()
     *
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function indexAction(MarktApi $api)
    {
        $dagvergunningMapping = $api->getDagvergunningMapping();

        return [
            'dagvergunningMapping' => $dagvergunningMapping,
        ];
    }

    /**
     * @Route("/dagvergunning_mapping/{id}/edit", name="app_dagvergunningmapping_edit", methods={"GET", "POST"})
     *
     * @Template()
     *
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function editAction(MarktApi $api, Request $request, int $id)
    {
        $dagvergunningMapping = $api->getDagvergunningMappingById($id);

        $formModel = [
            'mapping' => $dagvergunningMapping,
            'isUpdate' => true,
        ];

        $form = $this->createForm(DagvergunningMappingType::class, $formModel);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $api->updateDagvergunningMapping($dagvergunningMapping['id'], $form->getData());
                $this->addFlash('success', 'Wijziging opgeslagen');

                return $this->redirectToRoute('app_dagvergunningmapping_index');
            }

            $this->addFlash('error', 'De aanpassing kon niet opgeslagen worden');
        }

        return [
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/dagvergunning_mapping/create", name="app_dagvergunningmapping_create", methods={"GET", "POST"})
     *
     * @Template()
     *
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function createAction(MarktApi $api, Request $request)
    {
        $formModel = [
            'mapping' => [],
            'tariefSoorten' => $api->getTariefSoorten(),
            'isUpdate' => false,
        ];

        $form = $this->createForm(DagvergunningMappingType::class, $formModel);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $data = $form->getData();

                $data['unit'] = TranslationService::translateWord($data['unit'], Translations::UNITS, true);

                $api->createDagvergunningMapping($data);
                $this->addFlash('success', 'Wijziging opgeslagen');

                return $this->redirectToRoute('app_dagvergunningmapping_index');
            }

            $this->addFlash('error', 'De aanpassing kon niet opgeslagen worden');
        }

        return [
            'form' => $form->createView(),
        ];
    }
}
