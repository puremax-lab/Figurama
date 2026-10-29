<?php

namespace App\Form;

use App\Entity\Order;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ShippingForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('shippingName', TextType::class, ['label' => 'Nom complet', 'attr' => ['autocomplete' => 'name']])
            ->add('shippingAddress', TextType::class, ['label' => 'Adresse', 'attr' => ['autocomplete' => 'street-address', 'placeholder' => 'Rue et numéro']])
            ->add('shippingPostalCode', TextType::class, ['label' => 'Code postal', 'attr' => ['autocomplete' => 'postal-code']])
            ->add('shippingCity', TextType::class, ['label' => 'Ville', 'attr' => ['autocomplete' => 'address-level2']])
            ->add('shippingCountry', ChoiceType::class, [
                'label' => 'Pays',
                'choices' => ['Belgique' => 'BE', 'France' => 'FR', 'Luxembourg' => 'LU', 'Pays-Bas' => 'NL'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Order::class]);
    }
}
