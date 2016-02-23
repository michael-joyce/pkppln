<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Journal;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Default controller for the application, handles the home page and a few others.
 */
class DefaultController extends Controller {

    const PERMISSION_STMT = 'LOCKSS system has permission to collect, preserve, and serve this Archival Unit.';

    /**
     * @Route("/", name="home")
     */
    public function indexAction() {
        $em = $this->container->get('doctrine');
        $terms = $em->getRepository('AppBundle:TermOfUse')->getTerms();
        return $this->render('AppBundle:Default:index.html.twig', array(
            'terms' => $terms,
        ));
    }

    /**
     * Return the permission statement for LOCKSS.
     * 
     * @Route("/permission", name="lockss_permission")
     */
    public function permissionAction() {
        $response = new Response(self::PERMISSION_STMT, Response::HTTP_OK, array(
            'content-type' => 'text/plain'
        ));
        return $response;
    }

    /**
     * Fetch a processed and packaged deposit.
     * 
     * @Route("/fetch/{depositId}.zip", name="fetch")
     * @param Request $request
     */
    public function fetchAction(Request $request, $depositId) {
    
    /**
     * @Route("/onix.xml")
     */
    public function onyxRedirect() {
        return new RedirectResponse(
            $this->generateUrl('onix', array('_format' => 'xml')), 
            Response::HTTP_MOVED_PERMANENTLY
        );
    }
        
    /**
     * Fetch the current ONYX-PH metadata file and serve it up. The file is big
     * and nasty. It isn't generated on the fly.
     * 
     * @see http://www.editeur.org/127/ONIX-PH/
     * 
     * @param Request $request
     * @Route("/feeds/onix.{_format}", name="onix", requirements={"_format":"xml"})
     */
    public function onyxAction(Request $request) {
        $path = $this->container->get('filepaths')->getOnixPath();
        $fs = new Filesystem();
        if( ! $fs->exists($path)) {
            $this->container->get('logger')->critical("The ONIX-PH file could not be found at {$path}");
            throw new NotFoundHttpException("The ONIX-PH file could not be found.");
        }
        return new BinaryFileResponse($path, 200, array(
            'Content-Type' => 'text/xml'
        ));
    }

    /**
     * @Route("/feeds/terms.{_format}", 
     *      defaults={"_format"="atom"}, 
     *      name="feed_terms", 
     *      requirements={"_format"="json|rss|atom"}
     * )
     * @Template()
     * @param Request $request
     */
    public function termsFeedAction(Request $request) {
        $em = $this->get('doctrine')->getManager();
        $repo = $em->getRepository('AppBundle:TermOfUse');
        $terms = $repo->getTerms();
        return array('terms' => $terms);
    }

}
