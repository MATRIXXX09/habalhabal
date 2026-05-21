<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260405161636 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE activity_log (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, username VARCHAR(255) NOT NULL, role VARCHAR(255) NOT NULL, action VARCHAR(255) NOT NULL, target_data LONGTEXT DEFAULT NULL, date_time DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE booking (id INT AUTO_INCREMENT NOT NULL, customer_id INT NOT NULL, assigned_driver_id INT DEFAULT NULL, created_by_id INT DEFAULT NULL, booking_type VARCHAR(50) NOT NULL, pickup_address VARCHAR(255) NOT NULL, delivery_address VARCHAR(255) NOT NULL, customer_name VARCHAR(255) NOT NULL, customer_phone VARCHAR(20) NOT NULL, parcel_description LONGTEXT DEFAULT NULL, parcel_type VARCHAR(100) DEFAULT NULL, parcel_weight DOUBLE PRECISION DEFAULT NULL, status VARCHAR(50) NOT NULL, requested_pickup_time DATETIME DEFAULT NULL, requested_delivery_time DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, assigned_at DATETIME DEFAULT NULL, estimated_distance DOUBLE PRECISION DEFAULT NULL, estimated_duration DOUBLE PRECISION DEFAULT NULL, estimated_fare DOUBLE PRECISION DEFAULT NULL, priority_level VARCHAR(50) DEFAULT NULL, special_instructions LONGTEXT DEFAULT NULL, cancellation_reason LONGTEXT DEFAULT NULL, INDEX IDX_E00CEDDE9395C3F3 (customer_id), INDEX IDX_E00CEDDEBAE38CAB (assigned_driver_id), INDEX IDX_E00CEDDEB03A8386 (created_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE complaint (id INT AUTO_INCREMENT NOT NULL, created_by_id INT DEFAULT NULL, customer_name VARCHAR(255) NOT NULL, message LONGTEXT NOT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME DEFAULT NULL, INDEX IDX_5F2732B5B03A8386 (created_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messages (id INT AUTO_INCREMENT NOT NULL, sender_id INT DEFAULT NULL, admin_id INT DEFAULT NULL, sender_email VARCHAR(180) DEFAULT NULL, subject VARCHAR(255) DEFAULT NULL, message LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, read_at DATETIME DEFAULT NULL, status VARCHAR(20) DEFAULT NULL, reply LONGTEXT DEFAULT NULL, replied_at DATETIME DEFAULT NULL, INDEX IDX_DB021E96F624B39D (sender_id), INDEX IDX_DB021E96642B8210 (admin_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE parcel_request (id INT AUTO_INCREMENT NOT NULL, customer_id INT NOT NULL, accepted_by_id INT DEFAULT NULL, request_type VARCHAR(50) NOT NULL, parcel_description VARCHAR(255) NOT NULL, pickup_address VARCHAR(255) NOT NULL, delivery_address VARCHAR(255) NOT NULL, status VARCHAR(50) NOT NULL, created_at DATETIME NOT NULL, accepted_at DATETIME DEFAULT NULL, notes LONGTEXT DEFAULT NULL, contact_phone VARCHAR(20) DEFAULT NULL, INDEX IDX_4E922A049395C3F3 (customer_id), INDEX IDX_4E922A0420F699D9 (accepted_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE rider (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, created_by_id INT DEFAULT NULL, first_name VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL, name VARCHAR(255) DEFAULT NULL, contact_number VARCHAR(255) NOT NULL, license_number VARCHAR(255) NOT NULL, vehicle_type VARCHAR(255) NOT NULL, plate_number VARCHAR(255) NOT NULL, is_available TINYINT(1) NOT NULL, status VARCHAR(50) DEFAULT \'available\' NOT NULL, created_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_EA411035A76ED395 (user_id), INDEX IDX_EA411035B03A8386 (created_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE shipment (id INT AUTO_INCREMENT NOT NULL, created_by_id INT DEFAULT NULL, tracking_number VARCHAR(255) NOT NULL, origin VARCHAR(255) NOT NULL, destination VARCHAR(255) NOT NULL, status VARCHAR(255) NOT NULL, sender_name VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_2CB20DCB03A8386 (created_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, created_by_id INT DEFAULT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, is_verified TINYINT(1) NOT NULL, created_at DATETIME DEFAULT NULL, status VARCHAR(20) DEFAULT \'active\' NOT NULL, disabled_at DATETIME DEFAULT NULL, username VARCHAR(255) DEFAULT NULL, verification_token VARCHAR(255) DEFAULT NULL, INDEX IDX_8D93D649B03A8386 (created_by_id), UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE vehicle (id INT AUTO_INCREMENT NOT NULL, rider_id INT DEFAULT NULL, created_by_id INT DEFAULT NULL, type VARCHAR(255) NOT NULL, model VARCHAR(255) NOT NULL, plate_number VARCHAR(255) NOT NULL, is_available TINYINT(1) NOT NULL, capacity VARCHAR(255) NOT NULL, current_location VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_1B80E486FF881F6 (rider_id), INDEX IDX_1B80E486B03A8386 (created_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE booking ADD CONSTRAINT FK_E00CEDDE9395C3F3 FOREIGN KEY (customer_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE booking ADD CONSTRAINT FK_E00CEDDEBAE38CAB FOREIGN KEY (assigned_driver_id) REFERENCES rider (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE booking ADD CONSTRAINT FK_E00CEDDEB03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE complaint ADD CONSTRAINT FK_5F2732B5B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE messages ADD CONSTRAINT FK_DB021E96F624B39D FOREIGN KEY (sender_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE messages ADD CONSTRAINT FK_DB021E96642B8210 FOREIGN KEY (admin_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE parcel_request ADD CONSTRAINT FK_4E922A049395C3F3 FOREIGN KEY (customer_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE parcel_request ADD CONSTRAINT FK_4E922A0420F699D9 FOREIGN KEY (accepted_by_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE rider ADD CONSTRAINT FK_EA411035A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE rider ADD CONSTRAINT FK_EA411035B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE shipment ADD CONSTRAINT FK_2CB20DCB03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE vehicle ADD CONSTRAINT FK_1B80E486FF881F6 FOREIGN KEY (rider_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE vehicle ADD CONSTRAINT FK_1B80E486B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE booking DROP FOREIGN KEY FK_E00CEDDE9395C3F3');
        $this->addSql('ALTER TABLE booking DROP FOREIGN KEY FK_E00CEDDEBAE38CAB');
        $this->addSql('ALTER TABLE booking DROP FOREIGN KEY FK_E00CEDDEB03A8386');
        $this->addSql('ALTER TABLE complaint DROP FOREIGN KEY FK_5F2732B5B03A8386');
        $this->addSql('ALTER TABLE messages DROP FOREIGN KEY FK_DB021E96F624B39D');
        $this->addSql('ALTER TABLE messages DROP FOREIGN KEY FK_DB021E96642B8210');
        $this->addSql('ALTER TABLE parcel_request DROP FOREIGN KEY FK_4E922A049395C3F3');
        $this->addSql('ALTER TABLE parcel_request DROP FOREIGN KEY FK_4E922A0420F699D9');
        $this->addSql('ALTER TABLE rider DROP FOREIGN KEY FK_EA411035A76ED395');
        $this->addSql('ALTER TABLE rider DROP FOREIGN KEY FK_EA411035B03A8386');
        $this->addSql('ALTER TABLE shipment DROP FOREIGN KEY FK_2CB20DCB03A8386');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D649B03A8386');
        $this->addSql('ALTER TABLE vehicle DROP FOREIGN KEY FK_1B80E486FF881F6');
        $this->addSql('ALTER TABLE vehicle DROP FOREIGN KEY FK_1B80E486B03A8386');
        $this->addSql('DROP TABLE activity_log');
        $this->addSql('DROP TABLE booking');
        $this->addSql('DROP TABLE complaint');
        $this->addSql('DROP TABLE messages');
        $this->addSql('DROP TABLE parcel_request');
        $this->addSql('DROP TABLE rider');
        $this->addSql('DROP TABLE shipment');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE vehicle');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
