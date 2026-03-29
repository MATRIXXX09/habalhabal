<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251212055846 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Check if table already exists
        $tables = $this->connection->createSchemaManager()->listTableNames();
        if (!in_array('messages', $tables)) {
            $this->addSql('CREATE TABLE messages (id INT AUTO_INCREMENT NOT NULL, sender_id INT DEFAULT NULL, admin_id INT NOT NULL, sender_email VARCHAR(180) NOT NULL, subject VARCHAR(255) NOT NULL, message LONGTEXT NOT NULL, created_at DATETIME NOT NULL, read_at DATETIME DEFAULT NULL, INDEX IDX_DB021E96F624B39D (sender_id), INDEX IDX_DB021E96642B8210 (admin_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
            $this->addSql('ALTER TABLE messages ADD CONSTRAINT FK_DB021E96F624B39D FOREIGN KEY (sender_id) REFERENCES user (id) ON DELETE SET NULL');
            $this->addSql('ALTER TABLE messages ADD CONSTRAINT FK_DB021E96642B8210 FOREIGN KEY (admin_id) REFERENCES user (id)');
        }
    }

    public function down(Schema $schema): void
    {
        $tables = $this->connection->createSchemaManager()->listTableNames();
        if (in_array('messages', $tables)) {
            $this->addSql('ALTER TABLE messages DROP FOREIGN KEY FK_DB021E96F624B39D');
            $this->addSql('ALTER TABLE messages DROP FOREIGN KEY FK_DB021E96642B8210');
            $this->addSql('DROP TABLE messages');
        }
    }
}
