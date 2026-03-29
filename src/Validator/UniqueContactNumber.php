<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
class UniqueContactNumber extends Constraint
{
    public $message = 'This contact number is already being used by another rider.';
}
