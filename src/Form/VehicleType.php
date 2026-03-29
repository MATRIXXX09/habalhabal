<?php

namespace App\Form;

use App\Entity\Vehicle;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VehicleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', ChoiceType::class, [
                'choices' => [
                    'Car' => 'Car',
                    'Truck' => 'Truck',
                    'Van' => 'Van',
                    'Motorcycle' => 'Motorcycle',
                ],
                'attr' => [
                    'class' => 'mt-1 block w-full bg-gray-700/50 border-gray-600 text-gray-300 rounded-md focus:border-indigo-500 focus:ring-indigo-500',
                    'id' => 'vehicle_type'
                ],
                'label_attr' => ['class' => 'block text-sm font-medium text-gray-300']
            ])
            ->add('model', TextType::class, [
                'attr' => [
                    'class' => 'mt-1 block w-full bg-gray-700/50 border-gray-600 text-gray-300 rounded-md focus:border-indigo-500 focus:ring-indigo-500',
                    'id' => 'vehicle_model'
                ],
                'label_attr' => ['class' => 'block text-sm font-medium text-gray-300']
            ])
            ->add('plateNumber', TextType::class, [
                'attr' => [
                    'class' => 'mt-1 block w-full bg-gray-700/50 border-gray-600 text-gray-300 rounded-md focus:border-indigo-500 focus:ring-indigo-500',
                    'id' => 'vehicle_plateNumber'
                ],
                'label_attr' => ['class' => 'block text-sm font-medium text-gray-300']
            ])
            ->add('capacity', ChoiceType::class, [
                'choices' => [
                    '-- Select Capacity --' => '',
                    'Car Capacities' => [
                        '4 Passengers' => '4',
                        '5 Passengers' => '5',
                        '6 Passengers' => '6',
                        '7 Passengers' => '7',
                    ],
                    'Truck Capacities' => [
                        '500 kg' => '500 kg',
                        '1000 kg' => '1000 kg',
                        '2000 kg' => '2000 kg',
                        '5000 kg' => '5000 kg',
                        '10000 kg' => '10000 kg',
                    ],
                    'Van Capacities' => [
                        '8 Passengers' => '8',
                        '12 Passengers' => '12',
                        '15 Passengers' => '15',
                        '20 Passengers' => '20',
                        '3000 kg' => '3000 kg',
                        '5000 kg' => '5000 kg',
                    ],
                    'Motorcycle Capacities' => [
                        '1 Passenger' => '1',
                        '2 Passengers' => '2',
                        '100 kg' => '100 kg',
                        '200 kg' => '200 kg',
                    ],
                ],
                'attr' => [
                    'class' => 'mt-1 block w-full bg-gray-700/50 border-gray-600 text-gray-300 rounded-md focus:border-indigo-500 focus:ring-indigo-500',
                    'id' => 'vehicle_capacity'
                ],
                'label_attr' => ['class' => 'block text-sm font-medium text-gray-300']
            ])
            ->add('currentLocation', TextType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'mt-1 block w-full bg-gray-700/50 border-gray-600 text-gray-300 rounded-md focus:border-indigo-500 focus:ring-indigo-500',
                    'id' => 'vehicle_currentLocation'
                ],
                'label_attr' => ['class' => 'block text-sm font-medium text-gray-300']
            ])
            ->add('isAvailable', CheckboxType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'h-4 w-4 bg-gray-700/50 border-gray-600 text-indigo-500 focus:ring-indigo-500 rounded'
                ],
                'label_attr' => ['class' => 'text-sm font-medium text-gray-300']
            ]);

        // Handle rider field as a hidden field that won't break validation
        $builder->add('riderHidden', TextType::class, [
            'required' => false,
            'mapped' => false,
            'attr' => [
                'style' => 'display: none;',
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Vehicle::class,
            'available_riders' => [],
        ]);
    }
}