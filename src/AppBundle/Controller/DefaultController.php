<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Default controller for the application, handles the home page and a few others.
 */
class DefaultController extends Controller {

    const PERMISSION_STMT = 'LOCKSS system has permission to collect, preserve, and serve this Archival Unit.';

    /**
     * Home page.
     * 
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
     * @Route("/fetch/{journalUuid}/{depositUuid}.zip", name="fetch")
     * @param Request $request
     */
    public function fetchAction(Request $request, $journalUuid, $depositUuid) {
        $em = $this->container->get('doctrine');
        $journal = $em->getRepository('AppBundle:Journal')->findOneBy(array('uuid' => $journalUuid));
        $deposit = $em->getRepository('AppBundle:Deposit')->findOneBy(array('depositUuid' => $depositUuid));
        if($deposit->getJournal()->getId() !== $journal->getId()) {
            throw new BadRequestHttpException("The requested Journal ID does not match the deposit's journal ID.");
        }
        $path = $this->get('filepaths')->getStagingBagPath($deposit);
        $fs = new Filesystem();
        if(! $fs->exists($path)) {
            throw new NotFoundHttpException("{$journalUuid}/{$depositUuid}.zip does not exist.");
        }
        return new BinaryFileResponse($path);
    }
    
    /**
     * The ONIX-PH was hosted at /onix.xml which was a dumb thing. Redirect to
     * the proper URL at /feeds/onix.xml
     * 
     * This URI must be public in security.yml
     * 
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
     * and nasty. It isn't generated on the fly - there must be a cron tab to
     * generate the file once in a while. 
     * 
     * This URI must be public in security.yml
     * 
     * @see http://www.editeur.org/127/ONIX-PH/
     * 
     * @param Request $request
     * @Route("/feeds/onix.{_format}", name="onix", requirements={"_format":"xml"})
     */
    public function onyxFeedAction() {
        $path = $this->container->get('filepaths')->getOnixPath();
        $fs = new Filesystem();
        if(! $fs->exists($path)) {
            $this->container->get('logger')->critical("The ONIX-PH file could not be found at {$path}");
            throw new NotFoundHttpException("The ONIX-PH file could not be found.");
        }
        return new BinaryFileResponse($path, 200, array(
            'Content-Type' => 'text/xml'
        ));
    }

    /**
     * Someone requested an RSS feed of the Terms of Use. This route generates 
     * the feed in a RSS, Atom, and a custom JSON format as requested. It might
     * not be used anywhere.
     * 
     * This URI must be public in security.yml
     * 
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
