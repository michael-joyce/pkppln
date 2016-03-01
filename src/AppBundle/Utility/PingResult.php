<?php

namespace AppBundle\Utility;

use Exception;
use GuzzleHttp\Exception\XmlParseException;
use GuzzleHttp\Message\Response;
use SimpleXMLElement;

class PingResult {
	
	/**
	 * @var SimpleXMLElement 
	 */
	private $xml;
	
	/**
	 * HTTP Status Code
	 * @var int
	 */
	private $status;
	
	/**
	 * @var string
	 */
	private $error;
	
	public function __construct(Response $response) {
		$this->status = $response->getStatusCode();
		$this->error = null;
		$this->xml = null;
		try {
			$this->xml = $response->xml();
		} catch (Exception $ex) {
			$this->error = $ex->getMessage();
		} catch (XmlParseException $ex) {
			$this->error = $ex->getMessage();
		}
	}
	
	private function simpleQuery($q) {
		if($this->xml === null) {
			return null;
		}
		$element = $this->xml->xpath($q);
		if($element) {
			return (string)$element[0];
		}
		return null;
	}
	
	public function hasXml() {
		return $this->xml !== null;
	}
	
	public function hasError() {
		return $this->error !== null;
	}
	
	public function getHttpStatus() {
		return $this->status;
	}
	
	public function getError() {
		return $this->error;
	}
	
	public function getOjsRelease() {
		return $this->simpleQuery('/plnplugin/ojsInfo/release');
	}
	
	public function getPluginReleaseVersion() {
		return $this->simpleQuery('/plnplugin/pluginInfo/release');
	}
	
	public function getPluginReleaseDate() {
		return $this->simpleQuery('/plnplugin/pluginInfo/releaseDate');
	}
	
	public function isPluginCurrent() {
		if($this->simpleQuery('/plnplugin/pluginInfo/current')) {
			return 'Yes';
		}
		return 'No';
	}
	
	public function areTermsAccepted() {
		return $this->simpleQuery('/plnplugin/pluginInfo/terms/@termsAccepted');
	}
	
	public function getJournalTitle() {
		return $this->simpleQuery('/plnplugin/journalInfo/title');
	}
	
	public function getArticleCount() {
		return $this->simpleQuery('/plnplugin/journalInfo/articles/@count');
	}
	
	public function getArticleTitles() {
		$articles = array();
		if($this->xml === null) {
			return $articles;
		}
		foreach ($this->xml->xpath('/plnplugin/journalInfo/articles/article') as $element) {
			$articles[] = array(
				'date' => $element['pubDate'],
				'title' => (string) $element,
			);
		}
		return $articles;
	}
}