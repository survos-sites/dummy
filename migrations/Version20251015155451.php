<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251015155451 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product ADD brand VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE product ADD tags JSONB DEFAULT NULL');
        $this->addSql('ALTER TABLE product ADD description_backing TEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product DROP brand');
        $this->addSql('ALTER TABLE product DROP tags');
        $this->addSql('ALTER TABLE product DROP description_backing');
    }
}
