<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251211190832 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $riderTable = $this->connection->createSchemaManager()->introspectTable('rider');
        if (!$riderTable->hasColumn('status')) {
            $this->addSql('ALTER TABLE rider ADD status VARCHAR(50) DEFAULT \'available\' NOT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        $riderTable = $this->connection->createSchemaManager()->introspectTable('rider');
        if ($riderTable->hasColumn('status')) {
            $this->addSql('ALTER TABLE rider DROP status');
        }
    }
}
