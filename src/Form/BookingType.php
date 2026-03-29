<?php

namespace App\Form;

use App\Entity\Booking;
use App\Entity\Rider;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class BookingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('bookingType', ChoiceType::class, [
                'choices' => [
                    'Delivery' => 'delivery',
                    'Pickup' => 'pickup',
                ],
                'label' => 'Booking Type',
                'attr' => ['class' => 'form-select'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Please select a booking type']),
                ],
            ])
            ->add('customerName', TextType::class, [
                'label' => 'Customer Name',
                'attr' => ['class' => 'form-input', 'placeholder' => 'Enter customer name'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Customer name is required']),
                    new Assert\Length(['min' => 2, 'minMessage' => 'Name must be at least 2 characters']),
                ],
            ])
            ->add('customerPhone', TextType::class, [
                'label' => 'Customer Phone',
                'attr' => ['class' => 'form-input', 'placeholder' => 'Enter phone number'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Phone number is required']),
                    new Assert\Regex([
                        'pattern' => '/^[\d\s\-\+\(\)]+$/',
                        'message' => 'Invalid phone number format',
                    ]),
                ],
            ])
            ->add('pickupAddress', TextType::class, [
                'label' => 'Pickup Address',
                'attr' => ['class' => 'form-input', 'placeholder' => 'Enter pickup location'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Pickup address is required']),
                ],
            ])
            ->add('deliveryAddress', TextType::class, [
                'label' => 'Delivery Address',
                'attr' => ['class' => 'form-input', 'placeholder' => 'Enter delivery location'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Delivery address is required']),
                ],
            ])
            ->add('parcelType', ChoiceType::class, [
                'choices' => [
                    'Document' => 'document',
                    'Small Package' => 'small_package',
                    'Medium Package' => 'medium_package',
                    'Large Package' => 'large_package',
                ],
                'label' => 'Parcel Type',
                'attr' => ['class' => 'form-select'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Please select a parcel type']),
                ],
            ])
            ->add('parcelWeight', NumberType::class, [
                'label' => 'Parcel Weight (kg)',
                'required' => false,
                'attr' => ['class' => 'form-input', 'placeholder' => 'Enter weight in kg'],
                'scale' => 2,
            ])
            ->add('parcelDescription', TextareaType::class, [
                'label' => 'Parcel Description',
                'required' => false,
                'attr' => ['class' => 'form-textarea', 'rows' => 3, 'placeholder' => 'Describe the contents'],
            ])
            ->add('requestedPickupTime', DateTimeType::class, [
                'label' => 'Requested Pickup Time',
                'widget' => 'single_text',
                'required' => true,
                'attr' => ['class' => 'form-input'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Pickup time is required']),
                ],
            ])
            ->add('requestedDeliveryTime', DateTimeType::class, [
                'label' => 'Requested Delivery Time',
                'widget' => 'single_text',
                'required' => false,
                'attr' => ['class' => 'form-input'],
            ])
            ->add('priorityLevel', ChoiceType::class, [
                'choices' => [
                    'Standard' => 'standard',
                    'Urgent' => 'urgent',
                    'Scheduled' => 'scheduled',
                ],
                'label' => 'Priority Level',
                'required' => false,
                'attr' => ['class' => 'form-select'],
            ])
            ->add('specialInstructions', TextareaType::class, [
                'label' => 'Special Instructions',
                'required' => false,
                'attr' => ['class' => 'form-textarea', 'rows' => 3, 'placeholder' => 'Any special handling or delivery instructions'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Booking::class,
        ]);
    }
}
