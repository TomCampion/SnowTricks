<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20191024123551 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE token (id INT AUTO_INCREMENT NOT NULL, value VARCHAR(255) NOT NULL, creation_date DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('DROP INDEX UNIQ_8D93D649C05FB297 ON user');
        $this->addSql('ALTER TABLE user ADD confirmation_token_id INT NOT NULL, ADD password_token_id INT DEFAULT NULL, DROP confirmation_token');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649B33AEBDA FOREIGN KEY (confirmation_token_id) REFERENCES token (id)');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649D1497579 FOREIGN KEY (password_token_id) REFERENCES token (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649B33AEBDA ON user (confirmation_token_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649D1497579 ON user (password_token_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D649B33AEBDA');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D649D1497579');
        $this->addSql('DROP TABLE token');
        $this->addSql('DROP INDEX UNIQ_8D93D649B33AEBDA ON user');
        $this->addSql('DROP INDEX UNIQ_8D93D649D1497579 ON user');
        $this->addSql('ALTER TABLE user ADD confirmation_token VARCHAR(255) NOT NULL COLLATE utf8mb4_unicode_ci, DROP confirmation_token_id, DROP password_token_id');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649C05FB297 ON user (confirmation_token)');
    }
}
