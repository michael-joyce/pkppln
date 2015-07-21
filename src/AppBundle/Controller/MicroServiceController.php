<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use AppBundle\Entity\MicroService;
use AppBundle\Form\MicroServiceType;

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
     * Creates a new MicroService entity.
     *
     * @Route("/", name="microservice_create")
     * @Method("POST")
     * @Template("AppBundle:MicroService:new.html.twig")
     */
    public function createAction(Request $request)
    {
        $entity = new MicroService();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('microservice_show', array('id' => $entity->getId())));
        }

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Creates a form to create a MicroService entity.
     *
     * @param MicroService $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(MicroService $entity)
    {
        $form = $this->createForm(new MicroServiceType(), $entity, array(
            'action' => $this->generateUrl('microservice_create'),
            'method' => 'POST',
        ));

        $form->add('submit', 'submit', array('label' => 'Create'));

        return $form;
    }

    /**
     * Displays a form to create a new MicroService entity.
     *
     * @Route("/new", name="microservice_new")
     * @Method("GET")
     * @Template()
     */
    public function newAction()
    {
        $entity = new MicroService();
        $form   = $this->createCreateForm($entity);

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
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

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Displays a form to edit an existing MicroService entity.
     *
     * @Route("/{id}/edit", name="microservice_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('AppBundle:MicroService')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find MicroService entity.');
        }

        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
    * Creates a form to edit a MicroService entity.
    *
    * @param MicroService $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(MicroService $entity)
    {
        $form = $this->createForm(new MicroServiceType(), $entity, array(
            'action' => $this->generateUrl('microservice_update', array('id' => $entity->getId())),
            'method' => 'PUT',
        ));

        $form->add('submit', 'submit', array('label' => 'Update'));

        return $form;
    }
    /**
     * Edits an existing MicroService entity.
     *
     * @Route("/{id}", name="microservice_update")
     * @Method("PUT")
     * @Template("AppBundle:MicroService:edit.html.twig")
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('AppBundle:MicroService')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find MicroService entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $em->flush();

            return $this->redirect($this->generateUrl('microservice_edit', array('id' => $id)));
        }

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }
    /**
     * Deletes a MicroService entity.
     *
     * @Route("/{id}/delete", name="microservice_delete")
     */
    public function deleteAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository('AppBundle:MicroService')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find MicroService entity.');
        }

        $em->remove($entity);
        $em->flush();

        return $this->redirect($this->generateUrl('microservice'));
    }

    /**
     * Creates a form to delete a MicroService entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('microservice_delete', array('id' => $id)))
            ->setMethod('DELETE')
            ->add('submit', 'submit', array('label' => 'Delete'))
            ->getForm()
        ;
    }
}
