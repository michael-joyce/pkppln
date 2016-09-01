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

namespace AppBundle\EventListener;

use AppBundle\Controller\SwordController;
use AppBundle\Exception\SwordException;
use Monolog\Logger;
use Symfony\Bundle\TwigBundle\TwigEngine;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

/**
 * Listen for exceptions in the SWORD controller, and produce an error document.
 */
class SwordExceptionListener
{
    /**
     * @var TwigEngine
     */
    private $templating;

    /**
     * Symfony Controller that generated the exception.
     *
     * @var ControllerInterface
     */
    private $controller;

    /**
     * Monolog logger.
     *
     * @var Logger
     */
    private $logger;

    /**
     * The symfony request stack that generated the exception.
     *
     * @var RequestStack
     */
    private $requestStack;

    /**
     * Set the logger for exceptions.
     *
     * @param Logger $logger
     */
    public function setLogger(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Set the Twig Engine for templating and output.
     *
     * @param TwigEngine $templating
     */
    public function setTemplating(TwigEngine $templating)
    {
        $this->templating = $templating;
    }

    /**
     * Set the request stack, so it may be interrogated later.
     *
     * @param RequestStack $requestStack
     */
    public function setRequestStack(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * Respond to an exception with an error document wrapped in a Response.
     *
     * @param GetResponseForExceptionEvent $event
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();

        // only intercept SwordController exceptions.
        if (!$this->controller[0] instanceof SwordController) {
            return;
        }

        if (!$exception instanceof SwordException) {
            return;
        }

        $this->logger->critical($exception->getMessage().' from '.$this->requestStack->getCurrentRequest()->getClientIp());

        $response = $this->templating->renderResponse(
            'AppBundle:Sword:error.xml.twig',
            array('error' => $exception)
        );
        $response->headers->set('Content-Type', 'text/xml');
        $response->setStatusCode($exception->getStatusCode());
        $event->setResponse($response);
    }

    /**
     * Once the controller has been initialized, this event is fired. Grab
     * a reference to the active controller.
     *
     * @param FilterControllerEvent $event
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        $this->controller = $event->getController();
    }
}
