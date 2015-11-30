<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * Journal controller.
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
        $em = $this->getDoctrine()->getManager();
        $dql = 'SELECT e FROM AppBundle:Journal e';
        $query = $em->createQuery($dql);
        $paginator = $this->get('knp_paginator');
        $entities = $paginator->paginate(
                $query, $request->query->getInt('page', 1), 25
        );


        return array(
            'entities' => $entities,
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
                    $results, $request->query->getInt('page', 1), 25
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
                $qb, $request->query->getInt('page', 1), 25
        );


        return array(
            'journal' => $journal,
            'entities' => $entities,
        );
    }

}
