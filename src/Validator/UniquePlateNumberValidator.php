<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use App\Repository\RiderRepository;

class UniquePlateNumberValidator extends ConstraintValidator
{
    private $riderRepository;

    public function __construct(RiderRepository $riderRepository)
    {
        $this->riderRepository = $riderRepository;
    }

    public function validate($value, Constraint $constraint)
    {
        if (null === $value || '' === $value) {
            return;
        }

        $existingRider = $this->riderRepository->findOneBy(['plateNumber' => $value]);

        if ($existingRider) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}
