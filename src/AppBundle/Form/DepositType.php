<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class DepositType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('file_uuid')
            ->add('deposit_uuid')
            ->add('received', 'datetime', array('date_widget' => 'single_text', 'time_widget' => 'single_text'))
            ->add('action')
            ->add('volume')
            ->add('issue')
            ->add('pubDate', 'date', array('widget' => 'single_text'))
            ->add('checksumType')
            ->add('checksumValue')
            ->add('url')
            ->add('size')
            ->add('state')
            ->add('outcome')
            ->add('plnState')
            ->add('depositDate')
            ->add('depositReceipt')
            ->add('journal')
        ;
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\Deposit'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'appbundle_deposit';
    }
}
