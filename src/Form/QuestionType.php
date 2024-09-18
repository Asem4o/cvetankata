<?php

namespace App\Form;

use App\Entity\Question;
use App\Form\AnswerType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class QuestionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('text', TextType::class, ['label' => 'Question Text'])
            ->add('multipleCorrect', CheckboxType::class, [
                'label' => 'Allow Multiple Correct Answers',
                'required' => false,
            ])
            ->add('answers', CollectionType::class, [
                'entry_type' => AnswerType::class,   // Include the AnswerType form here
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'label' => 'Answers',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Question::class,
        ]);
    }
}
