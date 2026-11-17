-- ==========================================
-- Banco de Dados MySQL - AcessiIdoso
-- ==========================================

CREATE DATABASE IF NOT EXISTS acessi_idoso_db 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE acessi_idoso_db;

-- 1. Tabela de Usuários
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Tabela de Remédios
CREATE TABLE IF NOT EXISTS remedios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    nome VARCHAR(200) NOT NULL,
    categoria VARCHAR(100) DEFAULT 'Não informada',
    prescricao VARCHAR(50) DEFAULT 'Não necessária',
    indicacao TEXT,
    dosagem VARCHAR(255),
    contraindicacoes TEXT,
    efeitos_colaterais TEXT,
    alimentacao TEXT,
    status ENUM('active', 'completed') DEFAULT 'active',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id) ON DELETE CASCADE
);

-- 3. Tabela de Horários
CREATE TABLE IF NOT EXISTS horarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_remedio INT NOT NULL,
    frequencia_horas INT NOT NULL DEFAULT 8,
    ultima_dose DATETIME NOT NULL,
    data_inicio DATE NOT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_remedio) REFERENCES remedios(id) ON DELETE CASCADE
);

-- 4. Tabela de Sincronização Gov.br (Carteira de Vacinação Simulação)
CREATE TABLE IF NOT EXISTS vacinas_gov (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    nome_vacina VARCHAR(150) NOT NULL,
    lote VARCHAR(50),
    data_aplicacao DATE NOT NULL,
    local_aplicacao VARCHAR(150),
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id) ON DELETE CASCADE
);
