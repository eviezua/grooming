<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260530201524 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE bookings ADD total_price INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE districts ADD status VARCHAR(255) DEFAULT NULL');

        $this->addSql("UPDATE districts SET status = 'Approved' WHERE status IS NULL");

        $this->addSql('ALTER TABLE districts ALTER status SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE districts DROP status');
        $this->addSql('ALTER TABLE bookings DROP total_price');
    }
}
