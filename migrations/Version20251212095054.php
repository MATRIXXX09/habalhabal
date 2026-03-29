<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251212095054 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $bookingTable = $this->connection->createSchemaManager()->introspectTable('booking');
        if ($bookingTable->hasColumn('pickup_coordinates')) {
            $this->addSql('ALTER TABLE booking DROP pickup_coordinates');
        }
        if ($bookingTable->hasColumn('delivery_coordinates')) {
            $this->addSql('ALTER TABLE booking DROP delivery_coordinates');
        }
    }

    public function down(Schema $schema): void
    {
        $bookingTable = $this->connection->createSchemaManager()->introspectTable('booking');
        if (!$bookingTable->hasColumn('pickup_coordinates')) {
            $this->addSql('ALTER TABLE booking ADD pickup_coordinates VARCHAR(255) DEFAULT NULL');
        }
        if (!$bookingTable->hasColumn('delivery_coordinates')) {
            $this->addSql('ALTER TABLE booking ADD delivery_coordinates VARCHAR(255) DEFAULT NULL');
        }
    }
}
