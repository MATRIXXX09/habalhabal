<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251211194511 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $vehicleTable = $this->connection->createSchemaManager()->introspectTable('vehicle');
        
        if (!$vehicleTable->hasColumn('rider_id')) {
            $this->addSql('ALTER TABLE vehicle ADD rider_id INT DEFAULT NULL');
            $this->addSql('ALTER TABLE vehicle ADD CONSTRAINT FK_1B80E486FF881F6 FOREIGN KEY (rider_id) REFERENCES user (id) ON DELETE SET NULL');
            $this->addSql('CREATE INDEX IDX_1B80E486FF881F6 ON vehicle (rider_id)');
        }
        
        if (!$vehicleTable->hasColumn('created_by_id')) {
            $this->addSql('ALTER TABLE vehicle ADD created_by_id INT DEFAULT NULL');
            $this->addSql('ALTER TABLE vehicle ADD CONSTRAINT FK_1B80E486B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id) ON DELETE SET NULL');
            $this->addSql('CREATE INDEX IDX_1B80E486B03A8386 ON vehicle (created_by_id)');
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE vehicle DROP FOREIGN KEY FK_1B80E486FF881F6');
        $this->addSql('ALTER TABLE vehicle DROP FOREIGN KEY FK_1B80E486B03A8386');
        $this->addSql('DROP INDEX IDX_1B80E486FF881F6 ON vehicle');
        $this->addSql('DROP INDEX IDX_1B80E486B03A8386 ON vehicle');
        $this->addSql('ALTER TABLE vehicle DROP rider_id, DROP created_by_id');
    }
}
