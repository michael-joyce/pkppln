<?php

namespace AppBundle\Services;

use AppBundle\Entity\Deposit;
use AppBundle\Entity\Journal;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use Exception;
use J20\Uuid\Uuid;
use Monolog\Logger;
use SimpleXMLElement;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Router;

/**
 * Build a deposit and persist it to the database.
 */
class DepositBuilder {

    /**
     * ORM entity manager.
     * 
     * @var EntityManager
     */
    private $em;

    /**
     * Service logger
     * 
     * @var Logger
     */
    private $logger;

    /**
     * Set the router for the builder.
     * 
     * @var Router
     */
    private $router;

    /**
     * Set the service logger.
     * 
     * @param Logger $logger
     */
    public function setLogger(Logger $logger) {
        $this->logger = $logger;
    }

    /**
     * Set the ORM.
     * 
     * @param Registry $registry
     */
    public function setManager(Registry $registry) {
        $this->em = $registry->getManager();
    }

    /**
     * Set the router
     * 
     * @param Router $router
     */
    public function setRouter(Router $router) {
        $this->router = $router;
    }

    /**
     * Get a single XML value as a string.
     * 
     * @param SimpleXMLElement $xml
     * @param type $xpath
     * @return string
     * @throws Exception
     */
    protected function getXmlValue(SimpleXMLElement $xml, $xpath) {
        $data = $xml->xpath($xpath);
        if (count($data) === 1) {
            return trim((string) $data[0]);
        }
        if (count($data) === 0) {
            return null;
        }
        throw new Exception("Too many elements for '{$xpath}'");
    }

    /**
     * Build the URL for the deposit receipt.
     * 
     * @param Deposit $deposit
     * @return string
     */
    public function buildDepositReceiptUrl(Deposit $deposit) {
        return $this->router->getGenerator()->generate(
            "statement", array(
                'journal_uuid' => $deposit->getJournal()->getUuid(),
                'deposit_uuid' => $deposit->getDepositUuid(),
            ), UrlGeneratorInterface::ABSOLUTE_URL
        );
    }
	
    /**
     * Get the licensing info from the XML and add it to the deposit object.
     * 
     * @todo getLicensingInfo() is a terrible name.
     * 
     * @param Deposit $deposit
     * @param SimpleXMLElement $xml
     */
	public function getLicensingInfo(Deposit $deposit, SimpleXMLElement $xml) {
		$item = $xml->xpath('//pkp:license/node()');
		foreach($item as $child) {
			$deposit->addLicense($child->getName(), (string)$child);
		}
	}

    /**
     * Build a deposit from XML.
     * 
     * @param Journal $journal
     * @param SimpleXMLElement $xml
     * @param string $action
     * @return Deposit
     */
    public function fromXml(Journal $journal, SimpleXMLElement $xml, $action = 'add') {
        $id = $this->getXmlValue($xml, '//atom:id');
        $deposit_uuid = substr($id, 9, 36);

        $deposit = new Deposit();
        $deposit->setAction($action);
        $deposit->setChecksumType($this->getXmlValue($xml, 'pkp:content/@checksumType'));
        $deposit->setChecksumValue($this->getXmlValue($xml, 'pkp:content/@checksumValue'));
        $deposit->setDepositUuid($deposit_uuid);
        $deposit->setFileType('');
        $deposit->setIssue($this->getXmlValue($xml, 'pkp:content/@issue'));
        $deposit->setVolume($this->getXmlValue($xml, 'pkp:content/@volume'));
        $deposit->setPubDate(new DateTime($this->getXmlValue($xml, 'pkp:content/@pubdate')));
        $deposit->setJournal($journal);
        $deposit->setSize($this->getXmlValue($xml, 'pkp:content/@size'));
        $deposit->setUrl($this->getXmlValue($xml, 'pkp:content'));
		$deposit->setOjsVersion($this->getXmlValue($xml, 'pkp:ojsVersion'));
        $deposit->setDepositReceipt($this->buildDepositReceiptUrl($deposit));
        
		$this->getLicensingInfo($deposit, $xml);
		
        if($action === 'add') {
            $deposit->addToProcessingLog("Deposit received.");
        } else {
            $deposit->addToProcessingLog('Deposit edited.');
        }

        $this->em->persist($deposit);
        $this->em->flush();
        return $deposit;
    }

}
