-- ============================================================
-- Login attempts (simple brute-force protection)
--
-- The application counts failed logins per email + IP inside a
-- rolling 15-minute window before refusing further attempts.
-- ============================================================

CREATE TABLE IF NOT EXISTS `login_attempts` (
    `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `email`        VARCHAR(190)    NOT NULL,
    `ip_address`   VARCHAR(45)     NOT NULL,
    `attempted_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_login_attempts_throttle` (`email`, `ip_address`, `attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;