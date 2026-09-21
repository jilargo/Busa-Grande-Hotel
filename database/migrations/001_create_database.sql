-- ============================================================
-- Busa Grande Hotel — database schema
-- Target: MySQL 8.x (utf8mb4)
--
-- Run automatically by:  php scripts/migrate.php
-- ============================================================

-- 001_create_database.sql
-- Safe on repeat runs; privileges come from the configured DB_USERNAME.
CREATE DATABASE IF NOT EXISTS `busa_grande_hotel`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `busa_grande_hotel`;