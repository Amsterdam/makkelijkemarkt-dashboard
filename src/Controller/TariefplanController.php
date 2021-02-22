<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\MarktApi;
use App\Form\LineairplanType;
use App\Form\ConcreetPlanType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class TariefplanController extends AbstractController
{
    /**
     * @Route("/tariefplan")
     * @Template()
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function indexAction(MarktApi $api)
    {
        $markten = $api->getMarkten();

        return [
            'markten'=> $markten
        ];
    }

    /**
     * @Route("/tariefplan/{marktId}")
     * @Template()
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function marktindexAction(MarktApi $api, int $marktId): array
    {
        $markt = $api->getMarkt($marktId);
        $tariefplannen = $api->getTariefplannenByMarktId($marktId);

        foreach ($tariefplannen as &$tariefplan) {
            $tariefplan['geldigVanaf'] = new \DateTime($tariefplan['geldigVanaf']['date']);
            $tariefplan['geldigTot'] =  new \DateTime($tariefplan['geldigTot']['date']);
        }

        return [
            'markt' => $markt,
            'tariefplannen' => $tariefplannen
        ];
    }

    /**
     * @Route("/tariefplan/{marktId}/create/lineair")
     * @Template
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function createLineairAction(Request $request, MarktApi $api, int $marktId)
    {
        $formModel = $this->getLineairPlanObject();
        $formModel['geldigVanaf'] = new \DateTime($formModel['geldigVanaf']['date']);
        $formModel['geldigTot'] =  new \DateTime($formModel['geldigTot']['date']);
        $form = $this->createForm(LineairplanType::class, $formModel);

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $api->postLineairTariefplan($marktId, $form->getData());
                $this->addFlash('success', 'Aangemaakt');
                return $this->redirectToRoute('app_tariefplan_marktindex', ['marktId' => $marktId]);
            }

            $this->addFlash('error', 'Het formulier is niet correct ingevuld');
        }

        return [
            'form' => $form->createView(),
            'formModel' => $formModel
        ];
    }

    /**
     * @Route("/tariefplan/{tariefPlanId}/delete")
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function deleteAction(Request $request, MarktApi $api, int $tariefPlanId): RedirectResponse
    {
        if ($this->isCsrfTokenValid('app_tariefplan_delete', $request->request->get('_csrf')) === false) {
            throw $this->createAccessDeniedException();
        }

        $tariefplan = $api->getTariefPlan($tariefPlanId);
        $api->deleteTariefPlan($tariefplan['id']);

        return $this->redirectToRoute('app_tariefplan_marktindex', ['marktId' => $tariefplan['marktId']]);
    }

    /**
     * @Route("/tariefplan/{tariefPlanId}/update/lineair")
     * @Template
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function updateLineairAction(Request $request, MarktApi $api, int $tariefPlanId)
    {
        $tariefPlanObject = $api->getTariefPlan($tariefPlanId);
        $formModel = $this->getLineairPlanObject($tariefPlanObject);

        $form = $this->createForm(LineairplanType::class, $formModel);

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $api->updateLineairTariefplan($tariefPlanId, $form->getData());
                $this->addFlash('success', 'Aangepast');
                return $this->redirectToRoute('app_tariefplan_marktindex', ['marktId' => $tariefPlanObject['marktId']]);
            }

            $this->addFlash('error', 'Het formulier is niet correct ingevuld');
        }

        return [
            'form' => $form->createView(),
            'formModel' => $formModel
        ];
    }

    protected function getLineairPlanObject( array $tariefPlanObject = null): array
    {
        if (null !== $tariefPlanObject) {
            $geldigVanaf = new \DateTime($tariefPlanObject['geldigVanaf']['date']);
            $geldigTot = new \DateTime($tariefPlanObject['geldigTot']['date']);
            return [
                'naam' => $tariefPlanObject['naam'],
                'geldigVanaf' => $geldigVanaf,
                'geldigTot' => $geldigTot,
                'tariefPerMeter' => $tariefPlanObject['lineairplan']['tariefPerMeter'],
                'reinigingPerMeter' => $tariefPlanObject['lineairplan']['reinigingPerMeter'],
                'toeslagBedrijfsafvalPerMeter' => $tariefPlanObject['lineairplan']['toeslagBedrijfsafvalPerMeter'],
                'toeslagKrachtstroomPerAansluiting' => $tariefPlanObject['lineairplan']['toeslagKrachtstroomPerAansluiting'],
                'promotieGeldenPerMeter' => $tariefPlanObject['lineairplan']['promotieGeldenPerMeter'],
                'promotieGeldenPerKraam' => $tariefPlanObject['lineairplan']['promotieGeldenPerKraam'],
                'afvaleiland' => $tariefPlanObject['lineairplan']['afvaleiland'],
                'eenmaligElektra' => $tariefPlanObject['lineairplan']['eenmaligElektra'],
                'elektra' => $tariefPlanObject['lineairplan']['elektra']
            ];
        } else {
            return [
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
                'eenmaligElektra' => null,
                'elektra' => null,
            ];
        }
    }

    protected function getConcreetPlanObject(array $tariefPlanObject = null) : array
    {
        if (null !== $tariefPlanObject) {
            $geldigVanaf = new \DateTime($tariefPlanObject['geldigVanaf']['date']);
            $geldigTot = new \DateTime($tariefPlanObject['geldigTot']['date']);
            return [
                'naam' => $tariefPlanObject['naam'],
                'geldigVanaf' => $geldigVanaf,
                'geldigTot' => $geldigTot,
                'een_meter' => $tariefPlanObject['concreetplan']['een_meter'],
                'drie_meter' => $tariefPlanObject['concreetplan']['drie_meter'],
                'vier_meter' => $tariefPlanObject['concreetplan']['vier_meter'],
                'elektra' => $tariefPlanObject['concreetplan']['elektra'],
                'promotieGeldenPerMeter' => $tariefPlanObject['concreetplan']['promotieGeldenPerMeter'],
                'promotieGeldenPerKraam' => $tariefPlanObject['concreetplan']['promotieGeldenPerKraam'],
                'afvaleiland' => $tariefPlanObject['concreetplan']['afvaleiland'],
                'eenmaligElektra' => $tariefPlanObject['concreetplan']['eenmaligElektra']
            ];
        } else {
            return [
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
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function createConcreetAction(Request $request, MarktApi $api, int $marktId)
    {
        $formModel =  $this->getConcreetPlanObject();
        $form = $this->createForm(ConcreetplanType::class, $formModel);

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $api->postConcreetTariefplan($marktId, $form->getData());
                $this->addFlash('success', 'Aangemaakt');
                return $this->redirectToRoute('app_tariefplan_marktindex', ['marktId' => $marktId]);
            }

            $this->addFlash('error', 'Het formulier is niet correct ingevuld');
        }

        return [
            'form' => $form->createView(),
            'formModel' => $formModel
        ];
    }

    /**
     * @Route("/tariefplan/{tariefPlanId}/update/concreet")
     * @Template
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function updateConcreetAction(Request $request, MarktApi $api, int $tariefPlanId)
    {
        $tariefPlanObject = $api->getTariefPlan($tariefPlanId);

        $formModel = $this->getConcreetPlanObject($tariefPlanObject);
        $form = $this->createForm(ConcreetplanType::class, $formModel);

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $api->updateConcreetTariefplan($tariefPlanId, $form->getData());
                $this->addFlash('success', 'Aangepast');
                return $this->redirectToRoute('app_tariefplan_marktindex', ['marktId' => $tariefPlanObject['marktId']]);
            }

            $this->addFlash('error', 'Het formulier is niet correct ingevuld');
        }

        return [
            'form' => $form->createView(),
            'formModel' => $formModel
        ];
    }
}
