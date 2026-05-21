<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260521190830 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add StockActivity table and isMixedDrink field to Product';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE stock_activity (id INT AUTO_INCREMENT NOT NULL, product_id INT NOT NULL, performed_by_id INT NOT NULL, quantity_change INT NOT NULL, previous_quantity INT NOT NULL, new_quantity INT NOT NULL, action_type VARCHAR(50) NOT NULL, notes LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, INDEX IDX_STOCK_ACTIVITY_PRODUCT (product_id), INDEX IDX_STOCK_ACTIVITY_PERFORMED_BY (performed_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE stock_activity ADD CONSTRAINT FK_STOCK_ACTIVITY_PRODUCT FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('ALTER TABLE stock_activity ADD CONSTRAINT FK_STOCK_ACTIVITY_PERFORMED_BY FOREIGN KEY (performed_by_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE product ADD is_mixed_drink TINYINT(1) NOT NULL DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE stock_activity DROP FOREIGN KEY FK_STOCK_ACTIVITY_PRODUCT');
        $this->addSql('ALTER TABLE stock_activity DROP FOREIGN KEY FK_STOCK_ACTIVITY_PERFORMED_BY');
        $this->addSql('DROP TABLE stock_activity');
        $this->addSql('ALTER TABLE product DROP is_mixed_drink');
    }
}
