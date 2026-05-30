<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260530032312 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make booking customer_id nullable to support habal-habal bookings without a customer';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE booking CHANGE customer_id customer_id INT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE booking CHANGE customer_id customer_id INT NOT NULL');
    }
}
