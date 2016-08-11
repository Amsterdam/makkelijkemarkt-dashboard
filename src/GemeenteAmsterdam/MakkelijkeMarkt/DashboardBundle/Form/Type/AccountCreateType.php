<?php

namespace GemeenteAmsterdam\MakkelijkeMarkt\DashboardBundle\Form\Type;

use GemeenteAmsterdam\MakkelijkeMarkt\DashboardBundle\Enum\Roles;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

class AccountCreateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('naam', 'text', [])
            ->add('email', 'email', [])
            ->add('username', 'text', [])
            ->add('password', 'password', ['label' => 'PIN code', 'constraints' => [
                new Assert\Type(['type' => 'digit', 'message' => 'De PIN code mag alleen uit cijfers bestaan'])
            ]])
            ->add('role', 'choice', [
                'choices' => Roles::all(),
                'label'   => 'Rol'
            ])
            ->add('save', 'submit', ['label' => 'Aanmaken']);
    }

    public function getName()
    {
        return str_replace('\\', '_', __CLASS__);
    }
}