<?php

namespace App\Controller;

use App\Service\MarktApi;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class TariefSoortController extends AbstractController
{
    /**
     * @Route("/tariefsoort", name="app_tariefsoort_index", methods={"GET"})
     * @Template()
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function indexAction(MarktApi $api)
    {
        $tariefSoorten = $api->getTariefSoorten();

        return [
            'tariefSoorten' => $tariefSoorten,
        ];
    }
}
