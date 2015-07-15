<?php

namespace AppBundle\EventListener;

use AppBundle\Exception\SwordException;
use Symfony\Bundle\TwigBundle\TwigEngine;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

class SwordExceptionListener {
    
    /**
     * @var TwigEngine
     */
    private $templating;
    
    public function __construct(TwigEngine $templating) {
        $this->templating = $templating;
    }
    
    public function onKernelException(GetResponseForExceptionEvent $event) {
        $exception = $event->getException();
        if($exception instanceof SwordException) {
            
            $response = $this->templating->renderResponse(
                    'AppBundle:Sword:error.xml.twig', 
                    array( 'error' => $exception)
            );
            $response->headers->set('Content-Type', 'text/xml');
            $response->setStatusCode($exception->getStatusCode());
            $event->setResponse($response);
        }
    }
    
}