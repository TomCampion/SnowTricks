<?php


namespace App\Form;

use App\Service\FormHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class ResetPasswordType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $fields =[
            "newPassword" => "Nouveau mot de passe",
            "confirmPassword" => "Confirmer Mot de passe"
        ];
        $formHelper = new FormHelper();
        $formHelper->addPasswordFields($fields, $builder);
    }

}

