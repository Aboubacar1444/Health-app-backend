<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260216120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create device_push_token table for mobile push notifications';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE device_push_token (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', user_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', token VARCHAR(255) NOT NULL, platform VARCHAR(20) NOT NULL, provider VARCHAR(20) NOT NULL, device_name VARCHAR(100) DEFAULT NULL, is_active TINYINT(1) NOT NULL, last_used_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX uniq_device_push_token_token (token), INDEX idx_device_push_token_user (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE device_push_token');
    }
}
