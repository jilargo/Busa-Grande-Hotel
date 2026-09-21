-- ============================================================
-- Reservations
--
-- The heart of the system.
--
--   guests 1 ── 0..n reservations ── n..1 rooms
--   users 0..n ── 0..n reservations      (who booked on behalf of)
--
-- Deliberate design choices:
--   * `nights` and `total_amount` are STORED, not derived per query.
--     They are a billing snapshot: changing a room's rate later must
--     never alter an existing booking.
--   * `room_price_snapshot` records the exact nightly price used.
--   * The UNIQUE constraint on `reference` gives bookkeepers a human
--     key (BRV-XXXXXX) independent of the internal id.
--   * CHECK constraint guarantees check_out > check_in at the DB level.
--
-- OVERLAP RULE: a reservation occupies a room on dates [check_in,
-- check_out). Two reservations overlap when
--     a.check_in < b.check_out AND b.check_in < a.check_out
-- (a guest leaving around midday can have the room re-booked later
--  that same afternoon).
-- ============================================================

CREATE TABLE IF NOT EXISTS `reservations` (
    `id`                   BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `reference`            VARCHAR(20)     NOT NULL,
    `guest_id`             BIGINT UNSIGNED NOT NULL,
    `room_id`              BIGINT UNSIGNED NOT NULL,
    `user_id`              BIGINT UNSIGNED NULL,
    `check_in`             DATE            NOT NULL,
    `check_out`            DATE            NOT NULL,
    `guests_count`         INT UNSIGNED    NOT NULL DEFAULT 1,
    `nights`               INT UNSIGNED    NOT NULL,
    `room_price_snapshot`  DECIMAL(10,2)   NOT NULL,
    `total_amount`         DECIMAL(10,2)   NOT NULL,
    `status`               ENUM('pending','confirmed','checked_in','checked_out','cancelled','no_show')
                                       NOT NULL DEFAULT 'pending',
    `special_requests`     TEXT            NULL,
    `created_at`           TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`           TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_reservations_reference` (`reference`),
    KEY `idx_reservations_status` (`status`),
    KEY `idx_reservations_dates` (`check_in`, `check_out`),
    KEY `idx_reservations_room_dates` (`room_id`, `check_in`, `check_out`),
    KEY `idx_reservations_guest` (`guest_id`),
    KEY `idx_reservations_user` (`user_id`),
    CONSTRAINT `fk_reservations_guest`
        FOREIGN KEY (`guest_id`) REFERENCES `guests` (`id`)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_reservations_room`
        FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_reservations_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT `chk_reservations_dates` CHECK (`check_out` > `check_in`),
    CONSTRAINT `chk_reservations_nights` CHECK (`nights` >= 1),
    CONSTRAINT `chk_reservations_amount` CHECK (`total_amount` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;