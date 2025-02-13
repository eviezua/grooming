<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250212022853 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE bookings ADD status VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE cities ADD status VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE masters ADD status VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE pets ADD status VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE services ADD status VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE pets DROP status');
        $this->addSql('ALTER TABLE masters DROP status');
        $this->addSql('ALTER TABLE cities DROP status');
        $this->addSql('ALTER TABLE bookings DROP status');
        $this->addSql('ALTER TABLE services DROP status');
    }
}
