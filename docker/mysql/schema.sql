CREATE DATABASE IF NOT EXISTS `manifest_server`
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `manifest_server`;

-- Current state of every stored manifest (one row per file on disk).
CREATE TABLE IF NOT EXISTS `manifest` (
    `id`            INT          NOT NULL AUTO_INCREMENT,
    `series`        VARCHAR(100) NOT NULL,
    `volume`        VARCHAR(100) NOT NULL,
    `manifest_name` VARCHAR(255) NOT NULL,
    `url`           VARCHAR(2048) NOT NULL,
    `file_size`     INT          NOT NULL DEFAULT 0,
    `published_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_manifest` (`series`, `volume`, `manifest_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Named API endpoints that keys can be scoped to.
CREATE TABLE IF NOT EXISTS `api_endpoint` (
    `id`          INT          NOT NULL AUTO_INCREMENT,
    `name`        VARCHAR(100) NOT NULL,
    `description` VARCHAR(255) NOT NULL DEFAULT '',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_api_endpoint_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `api_endpoint` (`name`, `description`) VALUES
    ('putManifest',    'Store or overwrite a IIIF manifest'),
    ('removeManifest', 'Delete a stored IIIF manifest');

-- API keys (bcrypt-hashed; raw value never stored).
CREATE TABLE IF NOT EXISTS `api_key` (
    `id`         INT          NOT NULL AUTO_INCREMENT,
    `name`       VARCHAR(255) NOT NULL,
    `key_hash`   VARCHAR(255) NOT NULL,
    `key_prefix` VARCHAR(8)   NOT NULL,
    `active`     TINYINT(1)   NOT NULL DEFAULT 1,
    `created_at` DATETIME     NOT NULL,
    `last_used`  DATETIME              DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Join table linking keys to the endpoints they may access.
CREATE TABLE IF NOT EXISTS `api_key_api_endpoint` (
    `api_key_id`      INT NOT NULL,
    `api_endpoint_id` INT NOT NULL,
    PRIMARY KEY (`api_key_id`, `api_endpoint_id`),
    CONSTRAINT `fk_akae_key`      FOREIGN KEY (`api_key_id`)      REFERENCES `api_key`      (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_akae_endpoint` FOREIGN KEY (`api_endpoint_id`) REFERENCES `api_endpoint` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Append-only audit log of every put and remove operation.
CREATE TABLE IF NOT EXISTS `manifest_log` (
    `id`            INT          NOT NULL AUTO_INCREMENT,
    `action`        ENUM('put','remove') NOT NULL,
    `series`        VARCHAR(100) DEFAULT NULL,
    `volume`        VARCHAR(100) DEFAULT NULL,
    `manifest_name` VARCHAR(255) DEFAULT NULL,
    `url`           VARCHAR(2048) DEFAULT NULL,
    `success`       TINYINT(1)   NOT NULL DEFAULT 1,
    `message`       TEXT         DEFAULT NULL,
    `logged_at`     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
