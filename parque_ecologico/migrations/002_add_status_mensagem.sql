-- Migration: adicionar campos de status para mensagens de contato
ALTER TABLE mensagens
    ADD COLUMN lida TINYINT(1) NOT NULL DEFAULT 0,
    ADD COLUMN respondida TINYINT(1) NOT NULL DEFAULT 0;
