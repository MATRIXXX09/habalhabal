<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251207060204 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        
        // Check and add columns to complaint table
        $complaintTable = $this->connection->createSchemaManager()->introspectTable('complaint');
        if (!$complaintTable->hasColumn('created_by_id')) {
            $this->addSql('ALTER TABLE complaint ADD created_by_id INT DEFAULT NULL');
            $this->addSql('ALTER TABLE complaint ADD CONSTRAINT FK_5F2732B5B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id) ON DELETE SET NULL');
            $this->addSql('CREATE INDEX IDX_5F2732B5B03A8386 ON complaint (created_by_id)');
        }
        if (!$complaintTable->hasColumn('created_at')) {
            $this->addSql('ALTER TABLE complaint ADD created_at DATETIME DEFAULT NULL');
        }
        
        // Check and add columns to rider table
        $riderTable = $this->connection->createSchemaManager()->introspectTable('rider');
        if (!$riderTable->hasColumn('created_by_id')) {
            $this->addSql('ALTER TABLE rider ADD created_by_id INT DEFAULT NULL');
            $this->addSql('ALTER TABLE rider ADD CONSTRAINT FK_EA411035B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id) ON DELETE SET NULL');
            $this->addSql('CREATE INDEX IDX_EA411035B03A8386 ON rider (created_by_id)');
        }
        if (!$riderTable->hasColumn('created_at')) {
            $this->addSql('ALTER TABLE rider ADD created_at DATETIME DEFAULT NULL');
        }
        
        // Check and add columns to shipment table
        $shipmentTable = $this->connection->createSchemaManager()->introspectTable('shipment');
        if (!$shipmentTable->hasColumn('created_by_id')) {
            $this->addSql('ALTER TABLE shipment ADD created_by_id INT DEFAULT NULL');
            $this->addSql('ALTER TABLE shipment ADD CONSTRAINT FK_2CB20DCB03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id) ON DELETE SET NULL');
            $this->addSql('CREATE INDEX IDX_2CB20DCB03A8386 ON shipment (created_by_id)');
        }
        
        // Check and add columns to user table
        $userTable = $this->connection->createSchemaManager()->introspectTable('user');
        if (!$userTable->hasColumn('created_by_id')) {
            $this->addSql('ALTER TABLE user ADD created_by_id INT DEFAULT NULL');
            $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id) ON DELETE SET NULL');
            $this->addSql('CREATE INDEX IDX_8D93D649B03A8386 ON user (created_by_id)');
        }
        if (!$userTable->hasColumn('created_at')) {
            $this->addSql('ALTER TABLE user ADD created_at DATETIME DEFAULT NULL');
        }
        
        // Check and add columns to vehicle table
        $vehicleTable = $this->connection->createSchemaManager()->introspectTable('vehicle');
        if (!$vehicleTable->hasColumn('created_by_id')) {
            $this->addSql('ALTER TABLE vehicle ADD created_by_id INT DEFAULT NULL');
            $this->addSql('ALTER TABLE vehicle ADD CONSTRAINT FK_1B80E486B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id) ON DELETE SET NULL');
            $this->addSql('CREATE INDEX IDX_1B80E486B03A8386 ON vehicle (created_by_id)');
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE vehicle DROP FOREIGN KEY FK_1B80E486B03A8386');
        $this->addSql('DROP INDEX IDX_1B80E486B03A8386 ON vehicle');
        $this->addSql('ALTER TABLE vehicle DROP created_by_id');
        $this->addSql('ALTER TABLE rider DROP FOREIGN KEY FK_EA411035B03A8386');
        $this->addSql('DROP INDEX IDX_EA411035B03A8386 ON rider');
        $this->addSql('ALTER TABLE rider DROP created_by_id, DROP created_at');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D649B03A8386');
        $this->addSql('DROP INDEX IDX_8D93D649B03A8386 ON user');
        $this->addSql('ALTER TABLE user DROP created_by_id, DROP created_at');
        $this->addSql('ALTER TABLE shipment DROP FOREIGN KEY FK_2CB20DCB03A8386');
        $this->addSql('DROP INDEX IDX_2CB20DCB03A8386 ON shipment');
        $this->addSql('ALTER TABLE shipment DROP created_by_id');
        $this->addSql('ALTER TABLE complaint DROP FOREIGN KEY FK_5F2732B5B03A8386');
        $this->addSql('DROP INDEX IDX_5F2732B5B03A8386 ON complaint');
        $this->addSql('ALTER TABLE complaint DROP created_by_id, DROP created_at');
    }
}
