<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Journal;
use DateTime;
use Doctrine\ORM\EntityManager;
use Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;

/**
 * Journal controller. Journals can be deleted, and it's possible to update
 * the journal health status.
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
     * 
     * @param Request $request
     * @return array
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
     * @return array
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
     * 
     * @param string $id
     * @return array
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
     * Build and return a form to delete a journal.
     * 
     * @param Journal $journal
     * @return Form
     */
    private function createDeleteForm(Journal $journal) {
        $formBuilder = $this->createFormBuilder($journal);
        $formBuilder->setAction($this->generateUrl('journal_delete', array('id' => $journal->getId())));
        $formBuilder->setMethod('DELETE');
        $formBuilder->add('confirm', 'checkbox', array(
            'label' => 'Yes, delete this journal', 
            'mapped' => false,
            'value' => 'yes',
            'required' => false,
        ));
        $formBuilder->add('delete', 'submit', array('label' => 'Delete'));
        $form = $formBuilder->getForm();
        return $form;
    }
    
    /**
     * Finds and displays a Journal entity.
     *
     * @Route("/{id}/delete", name="journal_delete")
     * @Method({"GET","DELETE"})
     * @Template()
     * 
     * @param Request $request
     * @param string $id
     * 
     * @return array
     */
    public function deleteAction(Request $request, $id) {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('AppBundle:Journal')->find($id);
        
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Journal entity.');
        }
        
        if($entity->countDeposits() > 0) {
            $this->addFlash('warning', 'Journals which have made deposits cannot be deleted.');
            return $this->redirect($this->generateUrl('journal_show', array('id' => $entity->getId())));
        }
        
        $form = $this->createDeleteForm($entity);
        $form->handleRequest($request);
        
        if($form->isSubmitted() && $form->isValid() && $form->get('confirm')->getData()) {
//            Once JournalUrls are a thing, uncomment these lines.            
//            foreach($entity->getUrls() as $url) {
//                $em->remove($url);
//            }
            
            $whitelist = $em->getRepository('AppBundle:Whitelist')->findOneBy(array('uuid' => $entity->getUuid()));
            if($whitelist) {
                $em->remove($whitelist);
            }
            $blacklist = $em->getRepository('AppBundle:Whitelist')->findOneBy(array('uuid' => $entity->getUuid()));
            if($blacklist) {
                $em->remove($blacklist);
            }
            $em->remove($entity);
            $em->flush();
            
            $this->addFlash('success', 'Journal deleted.');
            return $this->redirect($this->generateUrl('journal'));
        }

        return array(
            'entity' => $entity,
            'form' => $form->createView(),
        );
    }

    /**
     * Update a journal status.
     * 
     * @Route("/{id}/status", name="journal_status")
     * 
     * @param Request $request
     * @param string $id
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
     * Ping a journal and display the result.
     *
     * @Route("/ping/{id}", name="journal_ping")
     * @Method("GET")
     * @Template()
     * 
     * @param string $id
     * @return array
     */
    public function pingAction($id) {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('AppBundle:Journal')->find($id);
		
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Journal entity.');
        }
		
		try {
			$result = $this->container->get('ping')->ping($entity);
            if(! $result->hasXml() || $result->hasError() || ($result->getHttpStatus() !== 200)) {
				$this->addFlash('warning', "The ping did not complete. HTTP {$result->getHttpStatus()} {$result->getError()}");
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
     * Show the deposits for a journal.
     *
     * @Route("/{id}/deposits", name="journal_deposits")
     * @Method("GET")
     * @Template()
     * 
     * @param Request $request
     * @param string $id
     * 
     * @return array
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
