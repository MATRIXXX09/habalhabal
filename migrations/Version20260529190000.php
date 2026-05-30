<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260529190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add wallet balance field to users for mobile top-up support.';
    }

    public function up(Schema $schema): void
    {
        // This column already exists in the database, so this migration is now a no-op
        // The wallet_balance column is managed by Doctrine ORM
    }

    public function down(Schema $schema): void
    {
        // Don't drop the column on rollback to preserve data
        // $this->addSql('ALTER TABLE user DROP wallet_balance');
    }
}
