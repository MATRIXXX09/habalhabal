<?php

namespace App\Form;

use App\Entity\Rider;
use App\Validator\UniqueContactNumber;
use App\Validator\UniquePlateNumber;
use App\Validator\UniqueEmail;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Email;

class RiderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'First Name',
                'attr' => ['class' => 'block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6'],
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Last Name',
                'attr' => ['class' => 'block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6'],
            ])
            ->add('contactNumber', TextType::class, [
                'label' => 'Contact Number',
                'attr' => ['class' => 'block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6'],
                'constraints' => [
                    new NotBlank(message: 'Contact number is required'),
                    new \Symfony\Component\Validator\Constraints\Regex(
                        pattern: '/^(09\d{9}|63\d{10})$/',
                        message: 'Contact number must start with 09 or 63 and be a valid Philippine mobile number.'
                    ),
                    new UniqueContactNumber(),
                ]
            ])
            ->add('licenseNumber', TextType::class, [
                'label' => 'License Number',
                'attr' => ['class' => 'block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6'],
            ])
            ->add('vehicleType', ChoiceType::class, [
                'label' => 'Vehicle Type',
                'choices' => [
                    'Select a vehicle type' => '',
                    'Motorcycle' => 'Motorcycle',
                    'Car' => 'Car',
                    'Truck' => 'Truck',
                    'Van' => 'Van',
                    'Tricycle' => 'Tricycle',
                ],
                'attr' => ['class' => 'block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6'],
            ])
            ->add('plateNumber', TextType::class, [
                'label' => 'Plate Number',
                'attr' => ['class' => 'block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6'],
                'constraints' => [
                    new NotBlank(message: 'Plate number is required'),
                    new UniquePlateNumber(),
                ]
            ]);

        if (!$options['is_edit']) {
            $builder
                ->add('email', EmailType::class, [
                    'mapped' => false,
                    'constraints' => [
                        new NotBlank(message: 'Please enter an email'),
                        new Email(message: 'Please enter a valid email'),
                        new UniqueEmail(),
                    ],
                    'attr' => ['class' => 'block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6'],
                ])
                ->add('password', PasswordType::class, [
                    'mapped' => false,
                    'constraints' => [
                        new NotBlank(message: 'Please enter a password'),
                    ],
                    'attr' => ['class' => 'block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6'],
                ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Rider::class,
            'is_edit' => false,
        ]);
    }
}