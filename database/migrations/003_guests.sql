-- ============================================================
-- Guests
--
-- One row per customer. `user_id` is NULL for walk-in guests
-- booked by the front desk; it links the profile to an account
-- (and to that account's reservations) when present.
--
-- Limitation worth recording in the README: a user_id FK has no
-- UNIQUE constraint so one person could open a second account.
-- The application only ever creates at most one profile per
-- account today.
--
-- Relationship:
--   users 1 ── 0..1 guests      (account ↔ guest profile)
--   guests 1 ── 0..n reservations
-- ============================================================

CREATE TABLE IF NOT EXISTS `guests` (
    `id`                     BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`                BIGINT UNSIGNED NULL,
    `first_name`             VARCHAR(80)     NOT NULL,
    `last_name`              VARCHAR(80)     NOT NULL DEFAULT '',
    `email`                  VARCHAR(190)    NULL,
    `phone`                  VARCHAR(30)     NULL,
    `address`                VARCHAR(200)    NULL,
    `city`                   VARCHAR(100)    NULL,
    `country`                VARCHAR(100)    NULL,
    `date_of_birth`          DATE            NULL,
    `id_type`                VARCHAR(50)     NULL,
    `id_number`              VARCHAR(100)    NULL,
    `emergency_contact_name` VARCHAR(120)    NULL,
    `emergency_contact_phone` VARCHAR(30)    NULL,
    `notes`                  TEXT            NULL,
    `created_at`             TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`             TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_guests_user` (`user_id`),
    KEY `idx_guests_name` (`last_name`, `first_name`),
    KEY `idx_guests_email` (`email`),
    CONSTRAINT `fk_guests_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;