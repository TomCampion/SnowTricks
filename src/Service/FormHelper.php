<?php


namespace App\Service;


use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class FormHelper
{

    public function addPasswordFields(Array $fields, FormBuilderInterface $builder){
        foreach ($fields as $key => $value)
        {
            $builder
                ->add($key, PasswordType::class, [
                    'label' => $value,
                    'constraints' => [
                        new NotBlank([
                            'message' => 'Veuillez renseigner un mot de passe.'
                        ]),
                        new Length([
                            'min' => 4,
                            'max' => 50,
                            'minMessage' => 'Votre mot de passe doit faire au moins {{ limit }} caractères',
                            'maxMessage' => 'Votre mot de passe ne doit pas excéder {{ limit }} caractères'
                        ])
                    ]
                ])
            ;
        }
    }

}