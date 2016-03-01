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
     * List of terms.
     */
    private $terms = array(
        //id,weight,created,key_code,lang_code,content
        [2, 7, "2014-09-21 07:00:00", "plugins.generic.pln.terms_of_use.pkp_can_use_cc_by", "en-US", "I agree to allow the PKP-PLN to make post-trigger event content available under the CC-BY (or current equivalent) license."],
        [3, 1, "2014-09-21 07:00:00", "plugins.generic.pln.terms_of_use.pkp_can_use_address", "en-US", "I agree to allow the PKP-PLN to include this journal's title and ISSN, and the email address of the Primary Contact, with the preserved journal content."],
        [4, 2, "2014-09-21 07:00:00", "plugins.generic.pln.terms_of_use.licensing_is_current", "en-US", "I confirm that licensing information pertaining to articles in this journal is accurate at the time of publication."],
        [5, 3, "2014-09-21 07:00:00", "plugins.generic.pln.terms_of_use.terms_may_be_revised", "en-US", "I acknowledge these terms may be revised from time to time and I will be required to review and agree to them each time this occurs."],
        [8, 5, "2015-06-02 12:32:18", "plugins.generic.pln.terms_of_use.trigger_events", "en-US", "I agree to make every reasonable effort to inform the PKP-PLN in the event my journal ceases publication. I acknowledge that PKP-PLN will also employ automated techniques to detect a potential trigger event and contact the journal to confirm their publication status."],
        [9, 0, "2015-10-19 11:11:51", "plugins.generic.pln.terms_of_use.jm_has_authority", "en-US", "I have the authority to include this journal's content in a secure preservation network and, if and when necessary, to make the content available in the PKP PLN."],
        [10, 4, "2015-10-19 11:12:49", "plugins.generic.pln.terms_of_use.jm_will_not_violate", "en-US", "I agree not to intentionally violate any laws and regulations that may be applicable to the content."],
        [11, 6, "2015-10-19 11:13:41", "plugins.generic.pln.terms_of_use.pkp_may_not_preserve", "en-US", "I agree that the PKP-PLN reserves the right not to preserve or make content available."],
    );

    /**
     * Create and persist a term.
     */
    private function createTerm($data, ObjectManager $manager) {
        $term = new TermOfUse();
        $term->setWeight($data[1]);
        $term->setKeyCode($data[3]);
        $term->setLangCode($data[4]);
        $term->setContent($data[5]);
        $manager->persist($term);
    }

    /**
     * {@inheritDoc}
     */
    public function doLoad(ObjectManager $manager) {
        foreach ($this->terms as $data) {
            $this->createTerm($data, $manager);
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
