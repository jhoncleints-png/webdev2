<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251211175821 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add created_by to customer and order tables';
    }

    public function up(Schema $schema): void
    {
        // 1. Add columns as nullable first
        $this->addSql('ALTER TABLE customer ADD created_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE `order` ADD created_by_id INT DEFAULT NULL');
        
        // 2. Set a default user ID for existing records
        // IMPORTANT: Check what your admin user ID is first!
        // Run: SELECT id, email FROM user WHERE email = 'admin@gmail.com';
        $this->addSql('UPDATE customer SET created_by_id = 8 WHERE created_by_id IS NULL'); // CHANGE 1 to actual admin ID
        $this->addSql('UPDATE `order` SET created_by_id = 8 WHERE created_by_id IS NULL'); // CHANGE 1 to actual admin ID
        
        // 3. Make columns NOT NULL
        $this->addSql('ALTER TABLE customer MODIFY created_by_id INT NOT NULL');
        $this->addSql('ALTER TABLE `order` MODIFY created_by_id INT NOT NULL');
        
        // 4. Add foreign key constraints
        $this->addSql('ALTER TABLE customer ADD CONSTRAINT FK_81398E09B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_81398E09B03A8386 ON customer (created_by_id)');
        
        $this->addSql('ALTER TABLE `order` ADD CONSTRAINT FK_F5299398B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_F5299398B03A8386 ON `order` (created_by_id)');
    }

    public function down(Schema $schema): void
    {
        // Remove foreign keys first
        $this->addSql('ALTER TABLE customer DROP FOREIGN KEY FK_81398E09B03A8386');
        $this->addSql('ALTER TABLE `order` DROP FOREIGN KEY FK_F5299398B03A8386');
        
        // Drop indexes
        $this->addSql('DROP INDEX IDX_81398E09B03A8386 ON customer');
        $this->addSql('DROP INDEX IDX_F5299398B03A8386 ON `order`');
        
        // Drop columns
        $this->addSql('ALTER TABLE customer DROP COLUMN created_by_id');
        $this->addSql('ALTER TABLE `order` DROP COLUMN created_by_id');
    }
}