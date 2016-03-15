<?php

namespace AppBundle\Controller;

use DateTime;
use Doctrine\ORM\EntityManager;
use Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * Journal controller. Journals are read only, other than journal health status.
 *
 * @Route("/journal")
 */
class JournalController extends Controller {

    /**
     * Lists all Journal entities.
     *
     * @Route("/", name="journal")
     * @Method("GET")
     * @Template()
     */
    public function indexAction(Request $request) {
        /**
         * @var EntityManager
         */
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository('AppBundle:Journal');
        $qb = $repo->createQueryBuilder('e');
        $status = $request->query->get('status');
        if($status !== null) {
            $qb->where('e.status = :status');
            $qb->setParameter('status', $status);
        }
		$qb->orderBy('e.id');
        $query = $qb->getQuery();

        $paginator = $this->get('knp_paginator');
        $entities = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            25
        );
        $statuses = $repo->statusSummary();


        return array(
            'entities' => $entities,
            'statuses' => $statuses,
        );
    }

    /**
     * Search journals. 
     * 
     * In the JournalController, this action must appear before showAction().
     * 
     * @Route("/search", name="journal_search")
     * @Method("GET")
     * @Template()
     * 
     * @param Request $request
     */
    public function searchAction(Request $request) {
        $em = $this->getDoctrine()->getManager();
        $q = $request->query->get('q', '');

        $repo = $em->getRepository("AppBundle:Journal");
        $paginator = $this->get('knp_paginator');

        $entities = array();
        $results = array();
        if ($q !== '') {
            $results = $repo->search($q);

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
     * Finds and displays a Journal entity.
     *
     * @Route("/{id}", name="journal_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction($id) {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('AppBundle:Journal')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Journal entity.');
        }

        return array(
            'entity' => $entity,
        );
    }
    
    /**
     * Update a journal status.
     * 
     * @Route("/{id}/status", name="journal_status")
     * 
     * @param Request $request
     * @param type $id
     */
    public function updateStatus(Request $request, $id) {
        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository('AppBundle:Journal')->find($id);
        $status = $request->query->get('status');
        if(! $status) {
            $this->addFlash("error", "The journal's status has not been changed.");
        } else {
            $entity->setStatus($status);
            if($status === 'healthy') {
                $entity->setContacted(new DateTime());
            }
            $this->addFlash("success", "The journal's status has been updated.");
            $em->flush();
        }
        return $this->redirect($this->generateUrl('journal_show', array('id' => $entity->getId())));
    }

   /**
     * Finds and displays a Journal entity.
     *
     * @Route("/ping/{id}", name="journal_ping")
     * @Method("GET")
     * @Template()
     */
    public function pingAction($id) {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('AppBundle:Journal')->find($id);
		
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Journal entity.');
        }
		
		try {
			$result = $this->container->get('ping')->ping($entity);
            if(! $result->hasXml() || $result->hasError()) {
                $this->addFlash('warning', 'The ping did not complete. ' . $ping->getError());
                return $this->redirect($this->generateUrl('journal_show', array(
                    'id' => $id
                )));
            }
            $entity->setContacted(new DateTime());
            $entity->setTitle($result->getJournalTitle());
            $em->flush($entity);
			return array(
				'entity' => $entity,
				'ping' => $result,
			);
		} catch (Exception $e) {
			$this->addFlash('danger', $e->getMessage());
			return $this->redirect($this->generateUrl('journal_show', array(
				'id' => $id
			)));
		}
    }

	/**
     * Finds and displays a Journal entity.
     *
     * @Route("/{id}/deposits", name="journal_deposits")
     * @Method("GET")
     * @Template()
     */
    public function showDepositsAction(Request $request, $id) {
        /** var ObjectManager $em */
        $em = $this->getDoctrine()->getManager();
        $journal = $em->getRepository('AppBundle:Journal')->find($id);
        if (!$journal) {
            throw $this->createNotFoundException('Unable to find Journal entity.');
        }

        $qb = $em->getRepository('AppBundle:Deposit')->createQueryBuilder('d')
                ->where('d.journal = :journal')
                ->setParameter('journal', $journal);
        $paginator = $this->get('knp_paginator');
        $entities = $paginator->paginate(
            $qb,
            $request->query->getInt('page', 1),
            25
        );


        return array(
            'journal' => $journal,
            'entities' => $entities,
        );
    }
}
