<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Creates a new admin user'
)]
class AdminCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Create admin user
        $adminEmail = 'admin@gmail.com';
        $adminPassword = 'admin123';

        try {
            $admin = new User();
            $admin->setEmail($adminEmail);
            $admin->setRoles(['ROLE_ADMIN']);
            $admin->setIsVerified(true);
            
            $hashedPassword = $this->passwordHasher->hashPassword($admin, $adminPassword);
            $admin->setPassword($hashedPassword);
            
            $this->entityManager->persist($admin);
            $this->entityManager->flush();
            
            $io->success('Admin user created successfully!');
            $io->table(
                ['Field', 'Value'],
                [
                    ['Email', $adminEmail],
                    ['Roles', 'ROLE_ADMIN'],
                    ['Verified', 'Yes']
                ]
            );

            // Create staff user
            $staffEmail = 'staff@gmail.com';
            $staffPassword = 'staff123';
            
            $staff = new User();
            $staff->setEmail($staffEmail);
            $staff->setRoles(['ROLE_STAFF']);
            $staff->setIsVerified(true);
            
            $hashedPassword = $this->passwordHasher->hashPassword($staff, $staffPassword);
            $staff->setPassword($hashedPassword);
            
            $this->entityManager->persist($staff);
            $this->entityManager->flush();
            
            $io->success('Staff user created successfully!');
            $io->table(
                ['Field', 'Value'],
                [
                    ['Email', $staffEmail],
                    ['Roles', 'ROLE_STAFF'],
                    ['Verified', 'Yes']
                ]
            );  
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Failed to create users: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
