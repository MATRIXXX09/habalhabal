<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251211195008 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $riderTable = $this->connection->createSchemaManager()->introspectTable('rider');
        if (!$riderTable->hasColumn('name')) {
            $this->addSql('ALTER TABLE rider ADD name VARCHAR(255) DEFAULT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        $riderTable = $this->connection->createSchemaManager()->introspectTable('rider');
        if ($riderTable->hasColumn('name')) {
            $this->addSql('ALTER TABLE rider DROP name');
        }
    }
}
