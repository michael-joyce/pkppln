<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends Controller
{
    const PERMISSION_STMT = 'LOCKSS system has permission to collect, preserve, and serve this Archival Unit.';

    /**
     * @Route("/", name="home")
     */
    public function indexAction()
    {
        return $this->render('default/index.html.twig');
    }

    /**
     * Return the permission statement for LOCKSS.
     * 
     * @Route("/permission", name="lockss_permission")
     */
    public function permissionAction() {
        $response = new Response(self::PERMISSION_STMT, Response::HTTP_OK, array(
            'content-type' => 'text/plain'
        ));
        return $response;
    }

    /**
     * Fetch a processed and packaged deposit.
     * 
     * @Route("/fetch/{depositId}/{fileId}.zip", name="fetch")
     * @param Request $request
     */
    public function fetchAction(Request $request, $depositId, $fileId) {
        
    }
}
