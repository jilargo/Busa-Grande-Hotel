-- ============================================================
-- Room types + rooms
--
--   room_types 1 ── 0..n rooms
--
-- room_types.base_price is the catalogue rate; each room keeps a
-- price_per_night override so one Deluxe can cost more than another.
-- `amenities` is stored as a plain-text list (JSON would need a
-- real JSON column type; newline-separated keeps it readable).
-- ============================================================

CREATE TABLE IF NOT EXISTS `room_types` (
    `id`          INT UNSIGNED   NOT NULL AUTO_INCREMENT,
    `name`        VARCHAR(100)   NOT NULL,
    `slug`        VARCHAR(100)   NOT NULL,
    `description` TEXT           NULL,
    `base_price`  DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
    `max_guests`  INT UNSIGNED   NOT NULL DEFAULT 2,
    `amenities`   TEXT           NULL,
    `image`       VARCHAR(255)   NULL,
    `created_at`  TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_room_types_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rooms` (
    `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `room_number`     VARCHAR(20)     NOT NULL,
    `room_type_id`    INT UNSIGNED    NOT NULL,
    `floor`           TINYINT UNSIGNED NOT NULL DEFAULT 1,
    `capacity`        INT UNSIGNED    NOT NULL DEFAULT 2,
    `price_per_night` DECIMAL(10,2)   NOT NULL,
    `status`          ENUM('available','reserved','occupied','maintenance','cleaning')
                                 NOT NULL DEFAULT 'available',
    `description`     TEXT            NULL,
    `amenities`       TEXT            NULL,
    `image`           VARCHAR(255)    NULL,
    `created_at`      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_rooms_number` (`room_number`),
    KEY `idx_rooms_type` (`room_type_id`),
    KEY `idx_rooms_status` (`status`),
    CONSTRAINT `fk_rooms_type`
        FOREIGN KEY (`room_type_id`) REFERENCES `room_types` (`id`)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;