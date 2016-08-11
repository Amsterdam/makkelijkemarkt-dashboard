<?php

namespace GemeenteAmsterdam\MakkelijkeMarkt\DashboardBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class ScanSpeedSelectorType extends AbstractType
{
    protected $markten = [];

    protected $accounts = [];

    public function __construct(array $markten, array $accounts)
    {
        $this->markten = $markten;
        $this->accounts = $accounts;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('marktId', 'choice', ['choices' => $this->markten, 'label' => 'Markt'])
            ->add('dag', 'date', ['format' => 'dd MMMM yyyy', 'label' => 'Dag'])
            ->add('accountId', 'choice', ['choices' => $this->accounts, 'label' => 'Account'])
            ->add('pauseDetect', 'integer', ['label' => 'Pauze detectie in sec.'])
            ->add('save', 'submit', ['label' => 'Selecteer']);
    }

    public function getName()
    {
        return str_replace('\\', '_', __CLASS__);
    }
}