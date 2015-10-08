<?php

namespace AppUserBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use FOS\UserBundle\Form\BaseType;

class PasswordType extends AbstractType {

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder->add('submit', 'submit', array('label' => 'Update'));
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'AppUserBundle\Entity\User'
        ));
    }
    
    public function getParent() {
        return 'fos_user_change_password';
    }
    
    /**
     * @return string
     */
    public function getName() {
        return 'appbundle_user_password';
    }

}
