<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251211163947 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove rider_id and last_maintenance_date from vehicle table';
    }

    public function up(Schema $schema): void
    {
        $vehicleTable = $this->connection->createSchemaManager()->introspectTable('vehicle');
        if ($vehicleTable->hasColumn('rider_id')) {
            $this->addSql('ALTER TABLE vehicle DROP FOREIGN KEY FK_1B80E486FF881F6');
            $this->addSql('ALTER TABLE vehicle DROP rider_id');
        }
        if ($vehicleTable->hasColumn('last_maintenance_date')) {
            $this->addSql('ALTER TABLE vehicle DROP last_maintenance_date');
        }
    }

    public function down(Schema $schema): void
    {
        $vehicleTable = $this->connection->createSchemaManager()->introspectTable('vehicle');
        if (!$vehicleTable->hasColumn('rider_id')) {
            $this->addSql('ALTER TABLE vehicle ADD rider_id INT DEFAULT NULL');
            $this->addSql('ALTER TABLE vehicle ADD CONSTRAINT FK_1B80E486FF881F6 FOREIGN KEY (rider_id) REFERENCES user (id)');
        }
    }
}
