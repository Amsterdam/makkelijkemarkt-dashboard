<?php

namespace GemeenteAmsterdam\MakkelijkeMarkt\DashboardBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use GemeenteAmsterdam\MakkelijkeMarkt\DashboardBundle\Form\Type\AccountEditType;
use Symfony\Component\HttpFoundation\Request;
use GemeenteAmsterdam\MakkelijkeMarkt\DashboardBundle\Form\Type\AccountCreateType;

class KoopmanController extends Controller
{
    /**
     * @Route("/koopmannen")
     * @Template()
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function indexAction(Request $request)
    {
        $page = $request->query->get('page', 0);
        $size = 30;

        $q = ['freeSearch' => $request->query->get('q'), 'erkenningsnummer' => $request->query->get('erkenningsnummer'), 'status' => $request->query->get('status', 1)];

        $koopmannen = $this->get('markt_api')->getKoopmannen($q, $page * $size, $size);

        return [
            'koopmannen' => $koopmannen,
            'pageNumber' => $page,
            'pageSize' => $size,
            'q' => $request->query->get('q'),
            'erkenningsnummer' => $request->query->get('erkenningsnummer'),
            'status' => $request->query->get('status', 1)
        ];
    }

    /**
     * @Route("/koopmannen/detail/{id}")
     * @Template()
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function detailAction(Request $request, $id)
    {
        $koopman = $this->get('markt_api')->getKoopman($id);

        $page = $request->query->get('page', 0);
        $size = 30;
        $dagvergunningen = $this->get('markt_api')->getDagvergunningen(['koopmanId' => $koopman->id], $page * $size, $size);

        return [
            'koopman' => $koopman,
            'dagvergunningen' => $dagvergunningen,
            'pageNumber' => $page,
            'pageSize' => $size,
        ];
    }
}
