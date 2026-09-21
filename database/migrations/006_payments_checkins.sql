-- ============================================================
-- Payments + check-in / check-out audit trails
--
-- payments   : every transaction against a reservation. Refunds are
--              separate records with a NEGATIVE amount, so the
--              running balance is always SUM(amount). Payments link
--              to the staff member who recorded them and to the
--              guest's reservation (RESTRICT: no orphans).
--
-- check_ins / check_outs : immutable audit rows. Exactly one per
--              reservation (UNIQUE reservation_id). They record the
--              REAL arrival/departure times and the responsible user.
-- ============================================================

CREATE TABLE IF NOT EXISTS `payments` (
    `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `reservation_id`   BIGINT UNSIGNED NOT NULL,
    `amount`           DECIMAL(10,2)   NOT NULL,
    `method`           ENUM('cash','bank_transfer','credit_card','debit_card','other')
                                      NOT NULL DEFAULT 'cash',
    `status`           ENUM('completed','refunded') NOT NULL DEFAULT 'completed',
    `reference_number` VARCHAR(100)    NULL,
    `payment_date`     DATE            NOT NULL,
    `recorded_by`      BIGINT UNSIGNED NULL,
    `notes`            TEXT            NULL,
    `created_at`       TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_payments_reservation` (`reservation_id`),
    KEY `idx_payments_date` (`payment_date`),
    KEY `idx_payments_user` (`recorded_by`),
    CONSTRAINT `fk_payments_reservation`
        FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_payments_user`
        FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT `chk_payments_amount` CHECK (`amount` <> 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `check_ins` (
    `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `reservation_id`  BIGINT UNSIGNED NOT NULL,
    `room_id`         BIGINT UNSIGNED NOT NULL,
    `guest_id`        BIGINT UNSIGNED NOT NULL,
    `checked_in_by`   BIGINT UNSIGNED NULL,
    `actual_check_in` DATETIME        NOT NULL,
    `created_at`      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_check_ins_reservation` (`reservation_id`),
    KEY `idx_check_ins_room` (`room_id`),
    KEY `idx_check_ins_guest` (`guest_id`),
    KEY `idx_check_ins_user` (`checked_in_by`),
    CONSTRAINT `fk_check_ins_reservation`
        FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_check_ins_room`
        FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_check_ins_guest`
        FOREIGN KEY (`guest_id`) REFERENCES `guests` (`id`)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_check_ins_user`
        FOREIGN KEY (`checked_in_by`) REFERENCES `users` (`id`)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `check_outs` (
    `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `reservation_id`   BIGINT UNSIGNED NOT NULL,
    `room_id`          BIGINT UNSIGNED NOT NULL,
    `guest_id`         BIGINT UNSIGNED NOT NULL,
    `checked_out_by`   BIGINT UNSIGNED NULL,
    `actual_check_out` DATETIME        NOT NULL,
    `notes`            TEXT            NULL,
    `created_at`       TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_check_outs_reservation` (`reservation_id`),
    KEY `idx_check_outs_room` (`room_id`),
    KEY `idx_check_outs_guest` (`guest_id`),
    KEY `idx_check_outs_user` (`checked_out_by`),
    CONSTRAINT `fk_check_outs_reservation`
        FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_check_outs_room`
        FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_check_outs_guest`
        FOREIGN KEY (`guest_id`) REFERENCES `guests` (`id`)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_check_outs_user`
        FOREIGN KEY (`checked_out_by`) REFERENCES `users` (`id`)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;