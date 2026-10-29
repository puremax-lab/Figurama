<?php

namespace App\Form;

use App\Entity\Review;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ReviewForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('rating', ChoiceType::class, [
                'label' => 'Note',
                'choices' => ['★★★★★ Parfait' => 5, '★★★★ Très bien' => 4, '★★★ Bien' => 3, '★★ Décevant' => 2, '★ Mauvais' => 1],
                'placeholder' => 'Choisir…',
            ])
            ->add('comment', TextareaType::class, ['label' => 'Votre avis', 'attr' => ['rows' => 4, 'maxlength' => 1000]]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Review::class]);
    }
}
