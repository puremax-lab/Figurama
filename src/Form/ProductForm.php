<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Product;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ColorType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ProductForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, ['label' => 'Nom'])
            ->add('category', EntityType::class, ['label' => 'Catégorie', 'class' => Category::class, 'placeholder' => 'Choisir…'])
            ->add('description', TextareaType::class, ['label' => 'Description', 'attr' => ['rows' => 5]])
            // Typed in euros, stored in cents.
            ->add('priceCents', MoneyType::class, ['label' => 'Prix', 'currency' => 'EUR', 'divisor' => 100, 'input' => 'integer'])
            ->add('stock', IntegerType::class, ['label' => 'Stock', 'attr' => ['min' => 0]])
            ->add('icon', TextType::class, ['label' => 'Illustration (emoji)', 'help' => 'Un emoji, par exemple 🐉'])
            ->add('color', ColorType::class, ['label' => 'Couleur de fond'])
            ->add('featured', CheckboxType::class, ['label' => 'Mettre en avant sur l\'accueil', 'required' => false])
            ->add('active', CheckboxType::class, ['label' => 'En vente', 'required' => false]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Product::class]);
    }
}
