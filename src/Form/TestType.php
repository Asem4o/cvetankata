<?php

namespace App\Form;

use App\Entity\Discipline;
use App\Entity\Test;
use App\Form\QuestionEmbeddedForm;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TestType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', TextType::class, ['label' => 'Test Title'])
            ->add('discipline', EntityType::class, [
                'class' => Discipline::class,  // Make sure Discipline entity is properly defined
                'choice_label' => 'name',
            ])
            ->add('timeLimit', IntegerType::class, ['label' => 'Time Limit (minutes)'])

// Collection of questions
            ->add('questions', CollectionType::class, [
                'entry_type' => QuestionEmbeddedForm::class,  // This will include questions and answers
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'label' => 'Questions',
                'prototype' => true,  // Allows dynamic fields addition
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Test::class,
        ]);
    }
}
