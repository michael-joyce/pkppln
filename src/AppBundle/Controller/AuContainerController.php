<?php

/* 
 * Copyright (C) 2015-2016 Michael Joyce <ubermichael@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * AuContainer controller. AuContainers are read-only.
 *
 * @Route("/aucontainer")
 */
class AuContainerController extends Controller
{
    /**
     * Lists all AuContainer entities.
     *
     * @Route("/", name="aucontainer")
     * @Method("GET")
     * @Template()
     *
     * @param Request $request
     *
     * @return array
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $dql = 'SELECT e FROM AppBundle:AuContainer e ORDER BY e.id';
        $query = $em->createQuery($dql);
        $paginator = $this->get('knp_paginator');
        $entities = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            25
        );

        $openContainer = $em->getRepository('AppBundle:AuContainer')->getOpenContainer();

        return array(
            'entities' => $entities,
            'openContainer' => $openContainer,
        );
    }

    /**
     * Finds and displays a AuContainer entity.
     *
     * @Route("/{id}", name="aucontainer_show")
     * @Method("GET")
     * @Template()
     *
     * @param string $id
     *
     * @return array
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('AppBundle:AuContainer')->find($id);
        $openContainer = $em->getRepository('AppBundle:AuContainer')->getOpenContainer();

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find AuContainer entity.');
        }

        return array(
            'entity' => $entity,
            'openContainer' => $openContainer,
        );
    }
}
