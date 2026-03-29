<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251211173435 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove createdBy column from vehicle table';
    }

    public function up(Schema $schema): void
    {
        $vehicleTable = $this->connection->createSchemaManager()->introspectTable('vehicle');
        if ($vehicleTable->hasColumn('created_by_id')) {
            $this->addSql('ALTER TABLE vehicle DROP FOREIGN KEY FK_1B80E486B03A8386');
            $this->addSql('ALTER TABLE vehicle DROP COLUMN created_by_id');
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE vehicle ADD created_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE vehicle ADD CONSTRAINT FK_1B80E486B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
    }
}
