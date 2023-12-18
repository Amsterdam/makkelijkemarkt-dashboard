<?php

declare(strict_types=1);

namespace App\Controller;

use App\Form\FeatureFlagType;
use App\Service\MarktApi;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class FeatureFlagController extends AbstractController
{
    /**
     * @Route("/feature_flags", name="app_feature_flags_index", methods={"GET"})
     *
     * @Template()
     *
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function indexAction(MarktApi $api)
    {
        $featureFlags = $api->getFeatureFlags();

        return [
            'featureFlags' => $featureFlags,
        ];
    }

    /**
     * @Route("/feature_flag/create", name="app_feature_flags_create", methods={"GET", "POST"})
     *
     * @Template()
     *
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function createFeatureFlagAction(Request $request, MarktApi $api)
    {
        $form = $this->createForm(FeatureFlagType::class, []);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                try {
                    $api->createFeatureFlag($form->getData());
                    $this->addFlash('success', 'Feature flag aangemaakt.');
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Er ging iets mis bij het aanmaken');

                    return $this->redirectToRoute('app_feature_flags_index');
                }

                return $this->redirectToRoute('app_feature_flags_index');
            }

            $this->addFlash('error', 'Er ging iets mis bij het aanmaken.');
        }

        return $this->render('feature_flag/create.html.twig', [
            'form' => $form->createView(),
            'formModel' => [],
        ]);
    }

    /**
     * @Route("/feature_flag/{id}", name="app_feature_flag_update", methods={"GET", "POST"})
     *
     * @Template()
     *
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function updateFeatureFlagAction(Request $request, MarktApi $api, $id)
    {
        $featureFlag = $api->getFeatureFlag($id);

        $form = $this->createForm(FeatureFlagType::class, $featureFlag);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $api->updateFeatureFlag($id, $form->getData());
                $this->addFlash('success', 'Feature flag aangepast.');

                return $this->redirectToRoute('app_feature_flags_index');
            }

            $this->addFlash('error', 'Er ging iets mis bij het aanpassen.');
        }

        return $this->render('feature_flag/update.html.twig', [
            'form' => $form->createView(),
            'formModel' => $featureFlag,
        ]);
    }
}
