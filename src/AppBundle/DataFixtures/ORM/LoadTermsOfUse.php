<?php

namespace AppBundle\DataFixtures\ORM;

use AppBundle\Entity\TermOfUse;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class LoadTermsOfUse implements FixtureInterface {

    /**
     * @var ObjectManager
     */
    private $manager;
    private $terms = array(
        [6, 'plugins.generic.pln.terms_of_use.jm_has_authority', 'I have the legal and contractual authority to include this journal\'s content in a secure preservation network and, if and when necessary, to make the content available in the PKP PLN.'],
        [3, 'plugins.generic.pln.terms_of_use.pkp_can_use_cc_by', 'I agree to allow the PKP-PLN to make post-trigger event content available under the CC-BY (or current equivalent) license.'],
        [2, 'plugins.generic.pln.terms_of_use.pkp_can_use_address', 'I agree to allow the PKP-PLN to include this journal\'s title and ISSN, and the email address of the Primary Contact, with the preserved journal content.'],
        [5, 'plugins.generic.pln.terms_of_use.licensing_is_current', 'I confirm that licensing information pertaining to articles in this journal is accurate at the time of publication.'],
        [4, 'plugins.generic.pln.terms_of_use.terms_may_be_revised', 'I acknowledge these terms may be revised from time to time and I will be required to review and agree to them each time this occurs.'],
        [0, 'plugins.generic.pln.terms_of_use.jm_will_not_violate', 'I agree not to violate any laws and regulations that may be applicable to this network and the content.'],
        [1, 'plugins.generic.pln.terms_of_use.pkp_may_not_preserve', 'I agree that the PKP-PLN reserves the right, for whatever reason, not to preserve or make content available.'],
    );

    private function createTerm($weight, $key, $content) {
        $term = new TermOfUse();
        $term->setWeight($weight);
        $term->setKeyCode($key);
        $term->setContent($content);
        $this->manager->persist($term);
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager) {
        $this->manager = $manager;
        foreach($this->terms as $data) {
            $this->createTerm($data[0], $data[1], $data[2]);
        }
        $manager->flush();
    }

}