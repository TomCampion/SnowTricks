<?php


namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\FormBuilderInterface;

class ChangePasswordType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $fields =[
            "currentPassword" => "Mot de passe actuel",
            "newPassword" => "Nouveau mot de passe",
            "confirmPassword" => "Confirmer Mot de passe"
        ];
        $this->addPasswordFields($fields, $builder);
    }

    private function addPasswordFields(Array $fields, FormBuilderInterface $builder){
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
