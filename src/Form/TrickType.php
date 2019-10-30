<?php

namespace App\Form;

use App\Entity\Trick;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\Range;

class TrickType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, [
                'empty_data' => '',
                'label' => 'Nom du Trick *'
            ])
            ->add('description', TextType::class, [
                'empty_data' => '',
                'label' => 'Description *'
            ])
            ->add('category', ChoiceType::class, [
                'label' => 'Catégorie *',
                'choices' => Trick::GROUP,
                'choice_label' => function($choice, $key, $value) {
                    return $value;
                }
            ])
            ->add('difficulty', IntegerType::class, [
                'attr' => [
                    'max' => 5,
                    'min' => 0
                ],
                'label' => 'Difficulté *',
                'constraints' => [
                    new Range([
                        'min' => 1,
                        'max' => 5,
                        'minMessage' => 'La difficulté doit faire au moins {{ limit }} caractères',
                        'maxMessage' => 'La difficulté ne doit pas excéder {{ limit }} caractères'
                    ])
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Trick::class,
        ]);
    }
}
