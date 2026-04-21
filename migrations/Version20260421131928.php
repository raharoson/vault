<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260421131928 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE audit_logs (id UUID NOT NULL, action VARCHAR(255) NOT NULL, target_type VARCHAR(255) DEFAULT NULL, target_id UUID DEFAULT NULL, context JSON DEFAULT NULL, ip_address VARCHAR(45) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, organization_id UUID DEFAULT NULL, actor_id UUID DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_D62F285832C8A3DE ON audit_logs (organization_id)');
        $this->addSql('CREATE INDEX audit_org_date_idx ON audit_logs (organization_id, created_at)');
        $this->addSql('CREATE INDEX audit_actor_idx ON audit_logs (actor_id)');
        $this->addSql('CREATE TABLE memberships (id UUID NOT NULL, role VARCHAR(255) NOT NULL, joined_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, user_id UUID NOT NULL, organization_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_865A4776A76ED395 ON memberships (user_id)');
        $this->addSql('CREATE INDEX IDX_865A477632C8A3DE ON memberships (organization_id)');
        $this->addSql('CREATE UNIQUE INDEX membership_unique ON memberships (user_id, organization_id)');
        $this->addSql('CREATE TABLE organizations (id UUID NOT NULL, name VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_427C1C7F989D9B62 ON organizations (slug)');
        $this->addSql('CREATE TABLE refresh_tokens (id UUID NOT NULL, token_hash VARCHAR(64) NOT NULL, expires_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, revoked BOOLEAN DEFAULT false NOT NULL, ip_address VARCHAR(45) DEFAULT NULL, user_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_9BACE7E1B3BC57DA ON refresh_tokens (token_hash)');
        $this->addSql('CREATE INDEX IDX_9BACE7E1A76ED395 ON refresh_tokens (user_id)');
        $this->addSql('CREATE INDEX refresh_token_hash_idx ON refresh_tokens (token_hash)');
        $this->addSql('CREATE TABLE secret_folders (id UUID NOT NULL, name VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, organization_id UUID NOT NULL, parent_id UUID DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_D837632832C8A3DE ON secret_folders (organization_id)');
        $this->addSql('CREATE INDEX IDX_D8376328727ACA70 ON secret_folders (parent_id)');
        $this->addSql('CREATE TABLE secret_shares (id UUID NOT NULL, permission VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, expires_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, secret_id UUID NOT NULL, shared_with_id UUID NOT NULL, shared_by_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_49DFFA4C67176C07 ON secret_shares (secret_id)');
        $this->addSql('CREATE INDEX IDX_49DFFA4CD14FE63F ON secret_shares (shared_with_id)');
        $this->addSql('CREATE INDEX IDX_49DFFA4C5489CD19 ON secret_shares (shared_by_id)');
        $this->addSql('CREATE UNIQUE INDEX share_unique ON secret_shares (secret_id, shared_with_id)');
        $this->addSql('CREATE TABLE secrets (id UUID NOT NULL, title VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL, encrypted_payload TEXT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, organization_id UUID NOT NULL, owner_id UUID NOT NULL, folder_id UUID DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_CB327B147E3C61F9 ON secrets (owner_id)');
        $this->addSql('CREATE INDEX IDX_CB327B14162CB942 ON secrets (folder_id)');
        $this->addSql('CREATE INDEX secret_org_idx ON secrets (organization_id)');
        $this->addSql('CREATE TABLE users (id UUID NOT NULL, email VARCHAR(180) NOT NULL, password VARCHAR(255) NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, status VARCHAR(255) NOT NULL, mfa_enabled BOOLEAN DEFAULT false NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9E7927C74 ON users (email)');
        $this->addSql('ALTER TABLE audit_logs ADD CONSTRAINT FK_D62F285832C8A3DE FOREIGN KEY (organization_id) REFERENCES organizations (id) ON DELETE SET NULL NOT DEFERRABLE');
        $this->addSql('ALTER TABLE audit_logs ADD CONSTRAINT FK_D62F285810DAF24A FOREIGN KEY (actor_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE');
        $this->addSql('ALTER TABLE memberships ADD CONSTRAINT FK_865A4776A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE memberships ADD CONSTRAINT FK_865A477632C8A3DE FOREIGN KEY (organization_id) REFERENCES organizations (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE refresh_tokens ADD CONSTRAINT FK_9BACE7E1A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE secret_folders ADD CONSTRAINT FK_D837632832C8A3DE FOREIGN KEY (organization_id) REFERENCES organizations (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE secret_folders ADD CONSTRAINT FK_D8376328727ACA70 FOREIGN KEY (parent_id) REFERENCES secret_folders (id) ON DELETE SET NULL NOT DEFERRABLE');
        $this->addSql('ALTER TABLE secret_shares ADD CONSTRAINT FK_49DFFA4C67176C07 FOREIGN KEY (secret_id) REFERENCES secrets (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE secret_shares ADD CONSTRAINT FK_49DFFA4CD14FE63F FOREIGN KEY (shared_with_id) REFERENCES users (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE secret_shares ADD CONSTRAINT FK_49DFFA4C5489CD19 FOREIGN KEY (shared_by_id) REFERENCES users (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE secrets ADD CONSTRAINT FK_CB327B1432C8A3DE FOREIGN KEY (organization_id) REFERENCES organizations (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE secrets ADD CONSTRAINT FK_CB327B147E3C61F9 FOREIGN KEY (owner_id) REFERENCES users (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE secrets ADD CONSTRAINT FK_CB327B14162CB942 FOREIGN KEY (folder_id) REFERENCES secret_folders (id) ON DELETE SET NULL NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE audit_logs DROP CONSTRAINT FK_D62F285832C8A3DE');
        $this->addSql('ALTER TABLE audit_logs DROP CONSTRAINT FK_D62F285810DAF24A');
        $this->addSql('ALTER TABLE memberships DROP CONSTRAINT FK_865A4776A76ED395');
        $this->addSql('ALTER TABLE memberships DROP CONSTRAINT FK_865A477632C8A3DE');
        $this->addSql('ALTER TABLE refresh_tokens DROP CONSTRAINT FK_9BACE7E1A76ED395');
        $this->addSql('ALTER TABLE secret_folders DROP CONSTRAINT FK_D837632832C8A3DE');
        $this->addSql('ALTER TABLE secret_folders DROP CONSTRAINT FK_D8376328727ACA70');
        $this->addSql('ALTER TABLE secret_shares DROP CONSTRAINT FK_49DFFA4C67176C07');
        $this->addSql('ALTER TABLE secret_shares DROP CONSTRAINT FK_49DFFA4CD14FE63F');
        $this->addSql('ALTER TABLE secret_shares DROP CONSTRAINT FK_49DFFA4C5489CD19');
        $this->addSql('ALTER TABLE secrets DROP CONSTRAINT FK_CB327B1432C8A3DE');
        $this->addSql('ALTER TABLE secrets DROP CONSTRAINT FK_CB327B147E3C61F9');
        $this->addSql('ALTER TABLE secrets DROP CONSTRAINT FK_CB327B14162CB942');
        $this->addSql('DROP TABLE audit_logs');
        $this->addSql('DROP TABLE memberships');
        $this->addSql('DROP TABLE organizations');
        $this->addSql('DROP TABLE refresh_tokens');
        $this->addSql('DROP TABLE secret_folders');
        $this->addSql('DROP TABLE secret_shares');
        $this->addSql('DROP TABLE secrets');
        $this->addSql('DROP TABLE users');
    }
}
