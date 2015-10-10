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

class DepositBuilder {

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

    public function buildDepositReceiptUrl(Deposit $deposit) {
        return $this->router->getGenerator()->generate(
            "statement", array(
                'journal_uuid' => $deposit->getJournal()->getUuid(),
                'deposit_uuid' => $deposit->getDepositUuid(),
            ), UrlGeneratorInterface::ABSOLUTE_URL
        );
    }

    public function fromXml(Journal $journal, SimpleXMLElement $xml, $action = 'add') {
        $id = $this->getXmlValue($xml, '//atom:id');
        $deposit_uuid = substr($id, 9, 36);

        $deposit = new Deposit();
        $deposit->setAction($action);
        $deposit->setChecksumType($this->getXmlValue($xml, 'pkp:content/@checksumType'));
        $deposit->setChecksumValue($this->getXmlValue($xml, 'pkp:content/@checksumValue'));
        $deposit->setDepositUuid($deposit_uuid);
        $deposit->setFileUuid(Uuid::v4(true));
        $deposit->setFileType('');
        $deposit->setIssue($this->getXmlValue($xml, 'pkp:content/@issue'));
        $deposit->setVolume($this->getXmlValue($xml, 'pkp:content/@volume'));
        $deposit->setPubDate(new DateTime($this->getXmlValue($xml, 'pkp:content/@pubdate')));
        $deposit->setJournal($journal);
        $deposit->setSize($this->getXmlValue($xml, 'pkp:content/@size'));
        $deposit->setUrl($this->getXmlValue($xml, 'pkp:content'));
        $deposit->setDepositReceipt($this->buildDepositReceiptUrl($deposit));
        
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
