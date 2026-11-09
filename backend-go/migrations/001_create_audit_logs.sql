-- Tabel audit untuk middleware AuditLogger.
-- Jalankan sekali sebelum deploy pertama:
--   mysql -u spp -p spp < migrations/001_create_audit_logs.sql

CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NULL,
    `username` VARCHAR(100) NOT NULL DEFAULT '',
    `method` VARCHAR(10) NOT NULL,
    `path` VARCHAR(255) NOT NULL,
    `ip_address` VARCHAR(45) NOT NULL DEFAULT '',
    `user_agent` VARCHAR(512) NOT NULL DEFAULT '',
    `latency_ms` BIGINT NOT NULL DEFAULT 0,
    `status_code` INT NOT NULL DEFAULT 0,
    `created_at` DATETIME(3) NOT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_audit_logs_user_id` (`user_id`),
    KEY `idx_audit_logs_path` (`path`),
    KEY `idx_audit_logs_status_code` (`status_code`),
    KEY `idx_audit_logs_created_at` (`created_at`),
    CONSTRAINT `fk_audit_logs_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE SET NULL
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;
