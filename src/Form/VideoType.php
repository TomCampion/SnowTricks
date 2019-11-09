<?php


namespace App\Form;

use App\Entity\Video;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Regex;

class VideoType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('iframe', TextType::class, [
            'label' => false,
            'required' => true,
            'attr' => ['placeholder' => 'Collez ici une balise embed'],
            'constraints' => new Regex([
                'pattern' => "#<iframe(.+)</iframe>#",
                'message' => 'Votre video doit être entre deux balises <iframe>'
            ])
        ]);

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Video::class
        ]);
    }
}