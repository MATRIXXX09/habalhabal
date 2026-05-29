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
        $this->addSql('ALTER TABLE user ADD wallet_balance NUMERIC(10, 2) NOT NULL DEFAULT 0.00');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP wallet_balance');
    }
}
