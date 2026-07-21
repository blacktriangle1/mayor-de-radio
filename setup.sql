CREATE DATABASE IF NOT EXISTS radio_guardia CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE radio_guardia;

CREATE TABLE IF NOT EXISTS contactos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    indicativo VARCHAR(20) NOT NULL,
    nombre VARCHAR(100) NOT NULL DEFAULT '',
    pais VARCHAR(60) NOT NULL DEFAULT '',
    hora_utc DATETIME NOT NULL,
    frecuencia VARCHAR(20) NOT NULL,
    modalidad VARCHAR(10) NOT NULL,
    senal VARCHAR(20) NOT NULL DEFAULT '',
    observacion TEXT,
    created DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_indicativo (indicativo),
    INDEX idx_pais (pais),
    INDEX idx_hora (hora_utc),
    INDEX idx_modalidad (modalidad)
) ENGINE=InnoDB;
