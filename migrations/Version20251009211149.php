<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251009211149 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE index_info (index_name VARCHAR(255) NOT NULL, pixie_code VARCHAR(255) NOT NULL, locale VARCHAR(255) NOT NULL, last_indexed TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, document_count INT NOT NULL, task_id VARCHAR(255) DEFAULT NULL, batch_id VARCHAR(255) DEFAULT NULL, status VARCHAR(20) DEFAULT NULL, PRIMARY KEY (index_name))');
        $this->addSql('ALTER TABLE product ADD title_backing TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE product DROP title');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE index_info');
        $this->addSql('ALTER TABLE product ADD title VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE product DROP title_backing');
    }
}
