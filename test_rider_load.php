<?php
require 'vendor/autoload.php';

use App\Kernel;

$kernel = new Kernel('dev', true);
$kernel->boot();
$container = $kernel->getContainer();
$em = $container->get('doctrine.orm.entity_manager');

try {
    $rider = $em->find('App\Entity\Rider', 6);
    echo "Rider found: " . ($rider ? $rider->getFirstName() : 'null') . "\n";
    
    if ($rider) {
        $user = $rider->getUser();
        echo "User: " . ($user ? $user->getEmail() : 'null') . "\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Class: " . get_class($e) . "\n";
}
