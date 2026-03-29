<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251211190310 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $riderTable = $this->connection->createSchemaManager()->introspectTable('rider');
        // Add is_available if it doesn't exist
        if (!$riderTable->hasColumn('is_available')) {
            $this->addSql('ALTER TABLE rider ADD is_available TINYINT(1) NOT NULL');
        }
        // Remove columns if they exist
        if ($riderTable->hasColumn('last_status_change')) {
            $this->addSql('ALTER TABLE rider DROP last_status_change');
        }
        if ($riderTable->hasColumn('status')) {
            $this->addSql('ALTER TABLE rider DROP status');
        }
        if ($riderTable->hasColumn('name')) {
            $this->addSql('ALTER TABLE rider DROP name');
        }
    }

    public function down(Schema $schema): void
    {
        $riderTable = $this->connection->createSchemaManager()->introspectTable('rider');
        // Add columns if they don't exist
        if (!$riderTable->hasColumn('last_status_change')) {
            $this->addSql('ALTER TABLE rider ADD last_status_change DATETIME DEFAULT NULL');
        }
        if (!$riderTable->hasColumn('status')) {
            $this->addSql('ALTER TABLE rider ADD status VARCHAR(50) NOT NULL');
        }
        if (!$riderTable->hasColumn('name')) {
            $this->addSql('ALTER TABLE rider ADD name VARCHAR(255) DEFAULT NULL');
        }
        if ($riderTable->hasColumn('is_available')) {
            $this->addSql('ALTER TABLE rider DROP is_available');
        }
    }
}
