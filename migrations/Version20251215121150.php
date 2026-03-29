<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251215121150 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE booking DROP FOREIGN KEY FK_E00CEDDEBAE38CAB');
        $this->addSql('ALTER TABLE booking ADD CONSTRAINT FK_E00CEDDEBAE38CAB FOREIGN KEY (assigned_driver_id) REFERENCES rider (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE booking DROP FOREIGN KEY FK_E00CEDDEBAE38CAB');
        $this->addSql('ALTER TABLE booking ADD CONSTRAINT FK_E00CEDDEBAE38CAB FOREIGN KEY (assigned_driver_id) REFERENCES rider (id) ON UPDATE NO ACTION ON DELETE SET NULL');
    }
}
