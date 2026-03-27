<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260313143352 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE masters ADD roles JSON NULL');
        $this->addSql("UPDATE masters SET roles = '[]'");
        $this->addSql('ALTER TABLE masters ALTER COLUMN roles SET NOT NULL');
        $this->addSql("COMMENT ON COLUMN masters.roles IS '(DC2Type:json)'");
        $this->addSql('ALTER TABLE masters ALTER address TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE masters ALTER address DROP DEFAULT');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_7493B149E7927C74 ON masters (email)');
        $this->addSql('ALTER TABLE masters_services ALTER price TYPE DOUBLE PRECISION');
        $this->addSql('ALTER TABLE masters_services ALTER price DROP NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_7493B149E7927C74');
        $this->addSql('ALTER TABLE masters DROP roles');
        $this->addSql('ALTER TABLE masters ALTER address TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE masters ALTER address SET DEFAULT \'Unknown\'');
        $this->addSql('ALTER TABLE masters_services ALTER price TYPE DOUBLE PRECISION');
        $this->addSql('ALTER TABLE masters_services ALTER price SET NOT NULL');
    }
}
