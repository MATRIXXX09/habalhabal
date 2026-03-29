<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
class UniquePlateNumber extends Constraint
{
    public $message = 'This plate number is already being used by another rider.';
}
