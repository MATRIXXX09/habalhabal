<?php
namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'attr' => [
                    'class' => 'block w-full rounded-md bg-white/5 px-3 py-1.5 text-white'
                ]
            ])
            ->add('roles', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
                'choices' => [
                    'Staff' => 'ROLE_STAFF',
                    'Admin' => 'ROLE_ADMIN',
                ],
                'expanded' => true,
                'multiple' => false,
                'label' => 'User Type',
                'attr' => [
                    'class' => 'mt-2 space-y-3'
                ],
                'mapped' => false,
                'data' => $options['default_roles'] ? reset($options['default_roles']) : 'ROLE_STAFF'
            ])
            ->add('isVerified', \Symfony\Component\Form\Extension\Core\Type\CheckboxType::class, [
                'label' => 'Verified User',
                'required' => false,
                'attr' => [
                    'class' => 'h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600'
                ],
                'label_attr' => [
                    'class' => 'text-sm font-medium leading-6 text-gray-300'
                ],
            ]);

        // Only add password field if required (for new users) or explicitly requested
        if (!$options['data']->getId() || $options['require_password']) {
            $builder->add('plainPassword', PasswordType::class, [
                'mapped' => false,
                'required' => !$options['data']->getId(), // Required for new users
                'label' => 'Password',
                'constraints' => [
                    new NotBlank(message: 'Please enter a password'),
                    new Length(min: 8, minMessage: 'Your password should be at least {{ limit }} characters'),
                    new Regex(pattern: '/[A-Z]/', message: 'Password must contain at least one uppercase letter'),
                    new Regex(pattern: '/[a-z]/', message: 'Password must contain at least one lowercase letter'),
                    new Regex(pattern: '/[0-9]/', message: 'Password must contain at least one number'),
                ],
                'attr' => [
                    'class' => 'block w-full rounded-md bg-white/5 px-3 py-1.5 text-white',
                    'placeholder' => 'Min. 8 chars, 1 uppercase, 1 lowercase, 1 number'
                ],
                'help' => 'Password must be at least 8 characters long and contain uppercase, lowercase, and numbers',
                'help_attr' => ['class' => 'text-xs text-gray-400 mt-1']
            ]);
        }

        // Add form event listener to transform the single role selection into proper roles array
        $builder->addEventListener(\Symfony\Component\Form\FormEvents::SUBMIT, function (\Symfony\Component\Form\FormEvent $event) {
            $user = $event->getData();
            $form = $event->getForm();
            
            if ($form->has('roles')) {
                $selectedRole = $form->get('roles')->getData();
                $user->setRoles([$selectedRole ?: 'ROLE_USER']);
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'require_password' => false,
            'default_roles' => [], // Used to set initial role selection
        ]);

        $resolver->setAllowedTypes('require_password', 'bool');
        $resolver->setAllowedTypes('default_roles', 'array');
    }

    public function getBlockPrefix(): string
    {
        return 'user_form';
    }
}