<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Build a form to edit a term of use.
 */
class TermOfUseType extends AbstractType
{
    /**
     * @var string
     */
    protected $defaultLocale;

    /**
     * Default locale for the terms of use.
     *
     * @param string $defaultLocale
     */
    public function __construct($defaultLocale) {
        $this->defaultLocale = $defaultLocale;
    }

    /**
     * {@inheritDoc}
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
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\TermOfUse'
        ));
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'appbundle_termofuse';
    }
}
