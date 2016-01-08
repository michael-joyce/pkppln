<?php

namespace AppBundle\Utility;

use SimpleXMLElement;

class PingResult {
	
	/**
	 * @var SimpleXMLElement 
	 */
	private $xml;
	
	public function __construct(SimpleXMLElement $xml) {
		$this->xml = $xml;
	}
	
	private function simpleQuery($q) {
		return (string) $this->xml->xpath($q)[0];
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
		foreach ($this->xml->xpath('/plnplugin/journalInfo/articles/article') as $element) {
			$articles[] = array(
				'date' => $element['pubDate'],
				'title' => (string) $element,
			);
		}
		return $articles;
	}
}