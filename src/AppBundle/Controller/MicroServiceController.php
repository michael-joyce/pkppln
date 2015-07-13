<?php

namespace AppBundle\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use AppBundle\Entity\MicroService;

/**
 * MicroService controller.
 *
 * @Route("/microservice")
 */
class MicroServiceController extends Controller
{

    /**
     * Lists all MicroService entities.
     *
     * @Route("/", name="microservice")
     * @Method("GET")
     * @Template()
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $entities = $em->getRepository('AppBundle:MicroService')->findAll();

        return array(
            'entities' => $entities,
        );
    }

    /**
     * Finds and displays a MicroService entity.
     *
     * @Route("/{id}", name="microservice_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('AppBundle:MicroService')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find MicroService entity.');
        }

        return array(
            'entity'      => $entity,
        );
    }
}
