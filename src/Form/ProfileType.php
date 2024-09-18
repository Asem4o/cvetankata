<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class ProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Full Name',
                'required' => true,
            ])
            ->add('phoneNumber', TextType::class, [
                'label' => 'Phone Number',
                'required' => true,
            ])
            ->add('password', PasswordType::class, [
                'label' => 'New Password (Leave blank if not changing)',
                'required' => false,  // Make it optional
                'mapped' => false,  // Avoid setting the password directly on the entity
                'attr' => [
                    'placeholder' => 'Leave blank if you don\'t want to change the password',
                ],
            ])
            ->add('profilePicture', FileType::class, [
                'label' => 'Profile Picture (JPEG/PNG)',
                'mapped' => false,  // Not linked directly to the entity field
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '200048k',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid image file (JPEG or PNG).',
                    ])
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
