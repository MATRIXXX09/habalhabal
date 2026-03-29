<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251212072217 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Check if tables already exist
        $tables = $this->connection->createSchemaManager()->listTableNames();
        
        if (!in_array('booking', $tables)) {
            $this->addSql('CREATE TABLE booking (id INT AUTO_INCREMENT NOT NULL, customer_id INT NOT NULL, assigned_driver_id INT DEFAULT NULL, created_by_id INT DEFAULT NULL, booking_type VARCHAR(50) NOT NULL, pickup_address VARCHAR(255) NOT NULL, delivery_address VARCHAR(255) NOT NULL, pickup_coordinates VARCHAR(255) DEFAULT NULL, delivery_coordinates VARCHAR(255) DEFAULT NULL, customer_name VARCHAR(255) NOT NULL, customer_phone VARCHAR(20) NOT NULL, parcel_description LONGTEXT DEFAULT NULL, parcel_type VARCHAR(100) DEFAULT NULL, parcel_weight DOUBLE PRECISION DEFAULT NULL, status VARCHAR(50) NOT NULL, requested_pickup_time DATETIME NOT NULL, requested_delivery_time DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, assigned_at DATETIME DEFAULT NULL, estimated_distance DOUBLE PRECISION DEFAULT NULL, estimated_duration DOUBLE PRECISION DEFAULT NULL, estimated_fare DOUBLE PRECISION DEFAULT NULL, priority_level VARCHAR(50) DEFAULT NULL, special_instructions LONGTEXT DEFAULT NULL, cancellation_reason LONGTEXT DEFAULT NULL, INDEX IDX_E00CEDDE9395C3F3 (customer_id), INDEX IDX_E00CEDDEBAE38CAB (assigned_driver_id), INDEX IDX_E00CEDDEB03A8386 (created_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
            $this->addSql('ALTER TABLE booking ADD CONSTRAINT FK_E00CEDDE9395C3F3 FOREIGN KEY (customer_id) REFERENCES user (id) ON DELETE CASCADE');
            $this->addSql('ALTER TABLE booking ADD CONSTRAINT FK_E00CEDDEBAE38CAB FOREIGN KEY (assigned_driver_id) REFERENCES rider (id) ON DELETE SET NULL');
            $this->addSql('ALTER TABLE booking ADD CONSTRAINT FK_E00CEDDEB03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id) ON DELETE SET NULL');
        }
        
        if (!in_array('parcel_request', $tables)) {
            $this->addSql('CREATE TABLE parcel_request (id INT AUTO_INCREMENT NOT NULL, customer_id INT NOT NULL, accepted_by_id INT DEFAULT NULL, request_type VARCHAR(50) NOT NULL, parcel_description VARCHAR(255) NOT NULL, pickup_address VARCHAR(255) NOT NULL, delivery_address VARCHAR(255) NOT NULL, status VARCHAR(50) NOT NULL, created_at DATETIME NOT NULL, accepted_at DATETIME DEFAULT NULL, notes LONGTEXT DEFAULT NULL, contact_phone VARCHAR(20) DEFAULT NULL, INDEX IDX_4E922A049395C3F3 (customer_id), INDEX IDX_4E922A0420F699D9 (accepted_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
            $this->addSql('ALTER TABLE parcel_request ADD CONSTRAINT FK_4E922A049395C3F3 FOREIGN KEY (customer_id) REFERENCES user (id) ON DELETE CASCADE');
            $this->addSql('ALTER TABLE parcel_request ADD CONSTRAINT FK_4E922A0420F699D9 FOREIGN KEY (accepted_by_id) REFERENCES user (id) ON DELETE SET NULL');
        }
    }

    public function down(Schema $schema): void
    {
        $tables = $this->connection->createSchemaManager()->listTableNames();
        
        if (in_array('booking', $tables)) {
            $this->addSql('ALTER TABLE booking DROP FOREIGN KEY FK_E00CEDDE9395C3F3');
            $this->addSql('ALTER TABLE booking DROP FOREIGN KEY FK_E00CEDDEBAE38CAB');
            $this->addSql('ALTER TABLE booking DROP FOREIGN KEY FK_E00CEDDEB03A8386');
            $this->addSql('DROP TABLE booking');
        }
        
        if (in_array('parcel_request', $tables)) {
            $this->addSql('ALTER TABLE parcel_request DROP FOREIGN KEY FK_4E922A049395C3F3');
            $this->addSql('ALTER TABLE parcel_request DROP FOREIGN KEY FK_4E922A0420F699D9');
            $this->addSql('DROP TABLE parcel_request');
        }
    }
}
