<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use AppBundle\Entity\TermOfUse;
use AppBundle\Form\TermOfUseType;

/**
 * TermOfUse controller.
 *
 * @Route("/termofuse")
 */
class TermOfUseController extends Controller
{

    private function fetchHeader(Request $request, $name) {
        if ($request->headers->has($name)) {
            return $request->headers->get($name);
        }
        if ($request->headers->has("X-" . $name)) {
            return $request->headers->has("X-" . $name);
        }
        if ($request->query->has($name)) {
            return $request->query->get($name);
        }
        return null;
    }

    private function getLocale(Request $request) {
        $languageHeader = $this->fetchHeader($request, "Accept-Language");
        $locale = locale_accept_from_http($languageHeader);
        if ($locale !== null) {
            return $locale;
        }
        return $this->container->getParameter("pln_defaultLocale");
    }

    /**
     * Lists all TermOfUse entities.
     *
     * @Route("/", name="termofuse")
     * @Method("GET")
     * @Template()
     */
    public function indexAction(Request $request)
    {
        $locale = $this->getLocale($request);
        $em = $this->getDoctrine()->getManager();
        $entities = $em->getRepository('AppBundle:TermOfUse')->getCurrentTerms($locale);

        return array(
            'entities' => $entities,
        );
    }

    /**
     * Sort the TermOfUse entities.
     *
     * @Route("/sort", name="termofuse_sort")
     * @Template("AppBundle:TermOfUse:sort.html.twig")
     */
    public function sortAction(Request $request) {
        $locale = $this->getLocale($request);
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository('AppBundle:TermOfUse');

        if($request->getMethod() === 'POST') {
            $order = explode(',', $request->request->get('order'));
            for($w = 0; $w < count($order); $w++) {
                $terms = $repo->findBy(array('keyCode' => $order[$w]));
                foreach($terms as $term) {
                    $term->setWeight($w);
                }
            }
            $em->flush();
        }

        $entities = $repo->getCurrentTerms($locale);
        return array(
            'entities' => $entities,
        );
    }

    /**
     * Show the edit history of a term.
     *
     * @param Request $request
     * @Route("/history/{id}", name="termhistory")
     * @Method("GET")
     * @Template()
     */
    public function historyAction(Request $request, $id) {
        $locale = $this->getLocale($request);
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository('AppBundle:TermOfUse');
        $entities = $repo->getTermHistory($id, $locale);
        return array(
            'entities' => $entities,
        );
    }

    /**
     * Creates a new TermOfUse entity.
     *
     * @Route("/", name="termofuse_create")
     * @Method("POST")
     * @Template("AppBundle:TermOfUse:new.html.twig")
     */
    public function createAction(Request $request)
    {
        $entity = new TermOfUse();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('termofuse_show', array('id' => $entity->getId())));
        }

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Creates a form to create a TermOfUse entity.
     *
     * @param TermOfUse $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(TermOfUse $entity)
    {
        $form = $this->createForm(new TermOfUseType(), $entity, array(
            'action' => $this->generateUrl('termofuse_create'),
            'method' => 'POST',
        ));

        $form->add('submit', 'submit', array('label' => 'Create'));

        return $form;
    }

    /**
     * Displays a form to create a new TermOfUse entity.
     *
     * @Route("/new", name="termofuse_new")
     * @Method("GET")
     * @Template()
     */
    public function newAction()
    {
        $entity = new TermOfUse();
        $form   = $this->createCreateForm($entity);

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Finds and displays a TermOfUse entity.
     *
     * @Route("/{id}", name="termofuse_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('AppBundle:TermOfUse')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find TermOfUse entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Displays a form to edit an existing TermOfUse entity.
     *
     * @Route("/{id}/edit", name="termofuse_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('AppBundle:TermOfUse')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find TermOfUse entity.');
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
    * Creates a form to edit a TermOfUse entity.
    *
    * @param TermOfUse $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(TermOfUse $entity)
    {
        $form = $this->createForm(new TermOfUseType(), $entity, array(
            'action' => $this->generateUrl('termofuse_update', array('id' => $entity->getId())),
            'method' => 'PUT',
        ));

        $form->add('submit', 'submit', array('label' => 'Update'));

        return $form;
    }
    /**
     * Edits an existing TermOfUse entity.
     *
     * @Route("/{id}", name="termofuse_update")
     * @Method("PUT")
     * @Template("AppBundle:TermOfUse:edit.html.twig")
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('AppBundle:TermOfUse')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find TermOfUse entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $em->flush();

            return $this->redirect($this->generateUrl('termofuse_edit', array('id' => $id)));
        }

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }
    /**
     * Deletes a TermOfUse entity.
     *
     * @Route("/{id}/delete", name="termofuse_delete")
     */
    public function deleteAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository('AppBundle:TermOfUse')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find TermOfUse entity.');
        }

        $em->remove($entity);
        $em->flush();

        return $this->redirect($this->generateUrl('termofuse'));
    }

    /**
     * Creates a form to delete a TermOfUse entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('termofuse_delete', array('id' => $id)))
            ->setMethod('DELETE')
            ->add('submit', 'submit', array('label' => 'Delete'))
            ->getForm()
        ;
    }
}
