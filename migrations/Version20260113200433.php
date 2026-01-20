<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260113200433 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE pedido (id SERIAL NOT NULL, usuario_id INT NOT NULL, fecha DATE NOT NULL, coste NUMERIC(10, 2) NOT NULL, cod VARCHAR(4) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_C4EC16CEDB38439E ON pedido (usuario_id)');
        $this->addSql('CREATE TABLE pedido_producto (id SERIAL NOT NULL, pedido_id INT NOT NULL, producto_id INT NOT NULL, unidades INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_DD333C24854653A ON pedido_producto (pedido_id)');
        $this->addSql('CREATE INDEX IDX_DD333C27645698E ON pedido_producto (producto_id)');
        $this->addSql('ALTER TABLE pedido ADD CONSTRAINT FK_C4EC16CEDB38439E FOREIGN KEY (usuario_id) REFERENCES usuario (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE pedido_producto ADD CONSTRAINT FK_DD333C24854653A FOREIGN KEY (pedido_id) REFERENCES pedido (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE pedido_producto ADD CONSTRAINT FK_DD333C27645698E FOREIGN KEY (producto_id) REFERENCES producto (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE pedido DROP CONSTRAINT FK_C4EC16CEDB38439E');
        $this->addSql('ALTER TABLE pedido_producto DROP CONSTRAINT FK_DD333C24854653A');
        $this->addSql('ALTER TABLE pedido_producto DROP CONSTRAINT FK_DD333C27645698E');
        $this->addSql('DROP TABLE pedido');
        $this->addSql('DROP TABLE pedido_producto');
    }
}
