<?php

namespace App\Form;

use App\Entity\Trick;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
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
            ->add('description', TextareaType::class, [
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
                    'min' => 1
                ],
                'label' => 'Difficulté *',
                'constraints' => [
                    new Range([
                        'min' => 1,
                        'max' => 5,
                        'minMessage' => 'La difficultédoit valoir au minimum {{ limit }}',
                        'maxMessage' => 'La difficulté ne doit pas excéder {{ limit }} '
                    ])
                ]
            ])
            ->add('videos', CollectionType::class, [
                'label' => false,
                'entry_type' => VideoType::class,
                'by_reference' => false,
                'entry_options' => ['label' => false],
                'allow_add' => true
            ])
            ->add('images', CollectionType::class, [
                'label' => false,
                'entry_type' => ImageType::class,
                'by_reference' => false,
                'entry_options' => ['label' => false],
                'allow_add' => true
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
