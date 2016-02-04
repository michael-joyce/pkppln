<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * Deposit controller.
 *
 * @Route("/deposit")
 */
class DepositController extends Controller {

    /**
     * Lists all Deposit entities.
     *
     * @Route("/", name="deposit")
     * @Method("GET")
     * @Template()
     */
    public function indexAction(Request $request) {
        $em = $this->getDoctrine()->getManager();
		$repo = $em->getRepository('AppBundle:Deposit');
		$qb = $repo->createQueryBuilder('e');
		$state = $request->query->get('state');
		if($state !== null) {
			$qb->where('e.state = :state');
			$qb->setParameter('state', $state);
		}
		$qb->orderBy('e.id');
		$query = $qb->getQuery();
		
        $paginator = $this->get('knp_paginator');
        $entities = $paginator->paginate(
                $query, $request->query->getInt('page', 1), 25
        );
		$states = $repo->stateSummary();

        return array(
            'entities' => $entities,
			'states' => $states,
        );
    }

	/**
	 * Search for a deposit.
	 * 
	 * @Route("/search", name="deposit_search")
	 * @Method("GET")
	 * @Template()
	 * 
	 * @param Request $request
	 */
	public function searchAction(Request $request) {
		$em = $this->getDoctrine()->getManager();
		$q = $request->query->get('q', '');
		$repo = $em->getRepository('AppBundle:Deposit');
		$entities = array();
		$results = array();
		if($q !== '') {
			$results = $repo->search($q);
	        $paginator = $this->get('knp_paginator');
			$entities = $paginator->paginate(
					$results, 
					$request->query->getInt('page', 1), 
					25
			);
		}
		return array(
			'q' => $q,
			'count' => count($results),
			'entities' => $entities
		);
	}
	
    /**
     * Finds and displays a Deposit entity.
     *
     * @Route("/{id}", name="deposit_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction($id) {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('AppBundle:Deposit')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Deposit entity.');
        }

        return array(
            'entity' => $entity,
        );
    }

}
