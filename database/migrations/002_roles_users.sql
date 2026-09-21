-- ============================================================
-- Roles + users
--
-- roles    → a small lookup table so roles are data, not code.
-- users    → application accounts. password_hash is NULL for
--            Google-only accounts. google_id is UNIQUE to prevent
--            duplicate linking.
-- ============================================================

CREATE TABLE IF NOT EXISTS `roles` (
    `id`         INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `name`       VARCHAR(50)     NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_roles_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `users` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`          VARCHAR(120)    NOT NULL,
    `email`         VARCHAR(190)    NOT NULL,
    `password_hash` VARCHAR(255)    NULL,
    `google_id`     VARCHAR(255)    NULL,
    `role_id`       INT UNSIGNED    NOT NULL,
    `is_active`     TINYINT(1)      NOT NULL DEFAULT 1,
    `remember_token` VARCHAR(64)    NULL,
    `created_at`    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_users_email` (`email`),
    UNIQUE KEY `uq_users_google_id` (`google_id`),
    KEY `idx_users_role` (`role_id`),
    CONSTRAINT `fk_users_role`
        FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;