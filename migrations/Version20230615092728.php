<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230615092728 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE forum_category (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, uuid VARCHAR(255) NOT NULL, is_deleted TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE forum_comment (id INT AUTO_INCREMENT NOT NULL, id_user_id INT NOT NULL, id_post_id INT NOT NULL, content LONGTEXT NOT NULL, spoilers TINYINT(1) NOT NULL, uuid LONGTEXT NOT NULL, is_deleted TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_65B81F1D79F37AE5 (id_user_id), INDEX IDX_65B81F1D9514AA5C (id_post_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE forum_post (id INT AUTO_INCREMENT NOT NULL, id_forum_category_id INT NOT NULL, id_user_id INT NOT NULL, title LONGTEXT NOT NULL, content LONGTEXT NOT NULL, id_media INT NOT NULL, spoilers TINYINT(1) NOT NULL, uuid LONGTEXT NOT NULL, is_deleted TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_996BCC5AE5457657 (id_forum_category_id), INDEX IDX_996BCC5A79F37AE5 (id_user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\', password VARCHAR(255) NOT NULL, pseudo VARCHAR(120) NOT NULL, spoilers TINYINT(1) NOT NULL, uuid LONGTEXT NOT NULL, is_deleted TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE watchlist (id INT AUTO_INCREMENT NOT NULL, id_user_id INT NOT NULL, status INT NOT NULL, id_media INT NOT NULL, INDEX IDX_340388D379F37AE5 (id_user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE forum_comment ADD CONSTRAINT FK_65B81F1D79F37AE5 FOREIGN KEY (id_user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE forum_comment ADD CONSTRAINT FK_65B81F1D9514AA5C FOREIGN KEY (id_post_id) REFERENCES forum_post (id)');
        $this->addSql('ALTER TABLE forum_post ADD CONSTRAINT FK_996BCC5AE5457657 FOREIGN KEY (id_forum_category_id) REFERENCES forum_category (id)');
        $this->addSql('ALTER TABLE forum_post ADD CONSTRAINT FK_996BCC5A79F37AE5 FOREIGN KEY (id_user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE watchlist ADD CONSTRAINT FK_340388D379F37AE5 FOREIGN KEY (id_user_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE forum_comment DROP FOREIGN KEY FK_65B81F1D79F37AE5');
        $this->addSql('ALTER TABLE forum_comment DROP FOREIGN KEY FK_65B81F1D9514AA5C');
        $this->addSql('ALTER TABLE forum_post DROP FOREIGN KEY FK_996BCC5AE5457657');
        $this->addSql('ALTER TABLE forum_post DROP FOREIGN KEY FK_996BCC5A79F37AE5');
        $this->addSql('ALTER TABLE watchlist DROP FOREIGN KEY FK_340388D379F37AE5');
        $this->addSql('DROP TABLE forum_category');
        $this->addSql('DROP TABLE forum_comment');
        $this->addSql('DROP TABLE forum_post');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE watchlist');
    }
}
