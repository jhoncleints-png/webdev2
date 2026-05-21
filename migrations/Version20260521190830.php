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
        return 'Add isMixedDrink field to Product';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE product ADD is_mixed_drink TINYINT(1) NOT NULL DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE product DROP is_mixed_drink');
    }
}
