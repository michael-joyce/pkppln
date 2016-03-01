<?php

namespace AppBundle\Services;

use AppBundle\Entity\Journal;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use Exception;
use Monolog\Logger;
use SimpleXMLElement;
use Symfony\Component\Routing\Router;

/**
 * Construct a journal, and save it to the database.
 */
class JournalBuilder {

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var Router
     */
    private $router;

    /**
     * Set the service logger
     * 
     * @param Logger $logger
     */
    public function setLogger(Logger $logger) {
        $this->logger = $logger;
    }

    /**
     * Set the ORM thing.
     * 
     * @param Registry $registry
     */
    public function setManager(Registry $registry) {
        $this->em = $registry->getManager();
    }

    /**
     * Set the router. 
     * 
     * @todo why does the journal builder need a router?
     * 
     * @param Router $router
     */
    public function setRouter(Router $router) {
        $this->router = $router;
    }

    /**
     * Fetch a single XML value from a SimpleXMLElement.
     * 
     * @param SimpleXMLElement $xml
     * @param string $xpath
     * @return string|null
     * @throws Exception
     */
    protected function getXmlValue(SimpleXMLElement $xml, $xpath) {
        $data = $xml->xpath($xpath);
        if (count($data) === 1) {
            return (string) $data[0];
        }
        if (count($data) === 0) {
            return null;
        }
        throw new Exception("Too many elements for '{$xpath}'");
    }

    /**
     * Build and persist a journal from XML.
     * 
     * @param SimpleXMLElement $xml
     * @param string $journal_uuid
     * @return Journal
     */
    public function fromXml(SimpleXMLElement $xml, $journal_uuid) {
		$journal = $this->em->getRepository('AppBundle:Journal')->findOneBy(array(
			'uuid' => $journal_uuid
		));
		if($journal === null) {
			$journal = new Journal();
		}
        $journal->setUuid($journal_uuid);
        $journal->setTitle($this->getXmlValue($xml, '//atom:title'));
        $journal->setUrl($this->getXmlValue($xml, '//pkp:journal_url'));
        $journal->setEmail($this->getXmlValue($xml, '//atom:email'));
        $journal->setIssn($this->getXmlValue($xml, '//pkp:issn'));
        $journal->setPublisherName($this->getXmlValue($xml, '//pkp:publisherName'));
        $journal->setPublisherUrl($this->getXmlValue($xml, '//pkp:publisherUrl'));
        $this->em->persist($journal);
        $this->em->flush($journal);
        return $journal;
    }
	
}
