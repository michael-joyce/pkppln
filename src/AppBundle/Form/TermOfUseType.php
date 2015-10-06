<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class TermOfUseType extends AbstractType
{
    protected $defaultLocale;

    public function __construct($defaultLocale) {
        $this->defaultLocale = $defaultLocale;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('weight')
            ->add('keyCode')
            ->add('langCode', 'text', array('data' => $this->defaultLocale))
            ->add('content')
        ;
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\TermOfUse'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'appbundle_termofuse';
    }
}
