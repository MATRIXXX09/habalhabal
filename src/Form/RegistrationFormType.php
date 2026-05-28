<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\IsTrue;

class RegistrationFormType extends AbstractType
{
	public function buildForm(FormBuilderInterface $builder, array $options): void
	{
		$builder
			->add('email', EmailType::class, [
				'label' => 'Email address',
			])
			->add('plainPassword', PasswordType::class, [
				'mapped' => false,
				'label' => 'Password',
				'attr' => ['autocomplete' => 'new-password'],
				'constraints' => [
					new NotBlank(message: 'Please enter a password'),
					new Length(min: 6, minMessage: 'Your password should be at least {{ limit }} characters', max: 4096),
				],
			])
			->add('agreeTerms', CheckboxType::class, [
				'mapped' => false,
				'label' => 'I agree to the terms',
				'constraints' => [
					new IsTrue(message: 'You must agree to the terms'),
				],
			])
		;
	}

	public function configureOptions(OptionsResolver $resolver): void
	{
		$resolver->setDefaults([
			'data_class' => User::class,
		]);
	}
}
