<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251207112120 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $userTable = $this->connection->createSchemaManager()->introspectTable('user');
        if (!$userTable->hasColumn('status')) {
            $this->addSql('ALTER TABLE user ADD status VARCHAR(20) DEFAULT \'active\' NOT NULL');
        }
        if (!$userTable->hasColumn('disabled_at')) {
            $this->addSql('ALTER TABLE user ADD disabled_at DATETIME DEFAULT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        $userTable = $this->connection->createSchemaManager()->introspectTable('user');
        if ($userTable->hasColumn('status')) {
            $this->addSql('ALTER TABLE user DROP status');
        }
        if ($userTable->hasColumn('disabled_at')) {
            $this->addSql('ALTER TABLE user DROP disabled_at');
        }
    }
}
