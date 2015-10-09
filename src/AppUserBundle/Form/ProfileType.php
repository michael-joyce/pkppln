<?php

namespace AppUserBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use FOS\UserBundle\Form\BaseType;

class ProfileType extends AbstractType {

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
                ->remove('username')
                ->add('email')
                ->add('fullname')
                ->add('institution')
                ->add('enabled', 'checkbox', array(
                    'label' => 'Account Enabled'
                ))
                ->add('roles', 'choice', array(
                    'label' => 'Roles',
                    'choices' => array(
                        'ROLE_ADMIN' => 'Admin',
                    ),
                    'multiple' => true,
                    'expanded' => true,
                    'required' => false,
                ))
                ->add('submit', 'submit', array('label' => 'Update'));
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
        return 'fos_user_profile';
    }
    
    /**
     * @return string
     */
    public function getName() {
        return 'appbundle_user_profile';
    }

}
