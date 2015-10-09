<?php

namespace AppBundle\DataFixtures\ORM\prod;

use AppBundle\Entity\TermOfUse;
use AppBundle\Utility\AbstractDataFixture;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Load the terms of use for a production environment.
 */
class LoadTermsOfUse extends AbstractDataFixture {

    /**
     * @var ObjectManager
     */
    private $manager;

    private $terms = array(
        [6, 'en-US', 'plugins.generic.pln.terms_of_use.jm_has_authority', 'I have the legal and contractual authority to include this journal\'s content in a secure preservation network and, if and when necessary, to make the content available in the PKP PLN.'],
        [3, 'en-US', 'plugins.generic.pln.terms_of_use.pkp_can_use_cc_by', 'I agree to allow the PKP-PLN to make post-trigger event content available under the CC-BY (or current equivalent) license.'],
        [2, 'en-US', 'plugins.generic.pln.terms_of_use.pkp_can_use_address', 'I agree to allow the PKP-PLN to include this journal\'s title and ISSN, and the email address of the Primary Contact, with the preserved journal content.'],
        [5, 'en-US', 'plugins.generic.pln.terms_of_use.licensing_is_current', 'I confirm that licensing information pertaining to articles in this journal is accurate at the time of publication.'],
        [4, 'en-US', 'plugins.generic.pln.terms_of_use.terms_may_be_revised', 'I acknowledge these terms may be revised from time to time and I will be required to review and agree to them each time this occurs.'],
        [0, 'en-US', 'plugins.generic.pln.terms_of_use.jm_will_not_violate', 'I agree not to violate any laws and regulations that may be applicable to this network and the content.'],
        [1, 'en-US', 'plugins.generic.pln.terms_of_use.pkp_may_not_preserve', 'I agree that the PKP-PLN reserves the right, for whatever reason, not to preserve or make content available.'],
    );

    /**
     * Create and persist a term.
     *
     * @param string $weight
     * @param string $langCode
     * @param string $key
     * @param string $content
     */
    private function createTerm($weight, $langCode, $key, $content) {
        $term = new TermOfUse();
        $term->setWeight($weight);
        $term->setLangCode($langCode);
        $term->setKeyCode($key);
        $term->setContent($content);
        $this->manager->persist($term);
    }

    /**
     * {@inheritDoc}
     */
    public function doLoad(ObjectManager $manager) {
        $this->manager = $manager;
        foreach($this->terms as $data) {
            $this->createTerm($data[0], $data[1], $data[2], $data[3]);
        }
        $manager->flush();
    }

    /**
     * {@inheritDoc}
     */
    protected function getEnvironments() {
        return array('prod');
    }

}
