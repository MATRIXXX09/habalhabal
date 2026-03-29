<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251211160007 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Clean up: remove name column from vehicle table and cleanup migration history';
    }

    public function up(Schema $schema): void
    {
        // Remove the name column from vehicle table if it still exists
        if ($this->connection->createSchemaManager()->introspectTable('vehicle')->hasColumn('name')) {
            $this->addSql('ALTER TABLE vehicle DROP name');
        }
    }

    public function down(Schema $schema): void
    {
        // Restore the name column to vehicle table
        if (!$this->connection->createSchemaManager()->introspectTable('vehicle')->hasColumn('name')) {
            $this->addSql('ALTER TABLE vehicle ADD name VARCHAR(255) NOT NULL');
        }
    }
}
