<?php

namespace AppBundle\Services;

use AppBundle\Entity\Journal;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use Exception;
use Monolog\Logger;
use SimpleXMLElement;
use Symfony\Component\Routing\Router;

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

    public function setLogger(Logger $logger) {
        $this->logger = $logger;
    }

    public function setManager(Registry $registry) {
        $this->em = $registry->getManager();
    }

    public function setRouter(Router $router) {
        $this->router = $router;
    }

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
