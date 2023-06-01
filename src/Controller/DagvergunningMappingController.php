<?php

namespace App\Controller;

use App\Service\MarktApi;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class DagvergunningMappingController extends AbstractController
{
    /**
     * @Route("/dagvergunning_mapping", name="app_dagvergunningmapping_index", methods={"GET"})
     * @Template()
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function indexAction(MarktApi $api)
    {
        $dagvergunningMapping = $api->getDagvergunningMapping();

        return [
            'dagvergunningMapping' => $dagvergunningMapping,
        ];
    }
}
