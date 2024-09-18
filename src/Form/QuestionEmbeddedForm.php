<?php

namespace App\Form;

use App\Entity\Question;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class QuestionEmbeddedForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('text', TextType::class, ['label' => 'Question Text'])
            ->add('multipleCorrect', CheckboxType::class, [
                'label' => 'Allow multiple correct answers?',
                'required' => false,
            ])

// Collection of answers for this question
            ->add('answers', CollectionType::class, [
                'entry_type' => AnswerEmbeddedForm::class,  // Embedded form for answers
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'label' => 'Answers',
                'prototype' => true,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Question::class,
        ]);
    }
}
