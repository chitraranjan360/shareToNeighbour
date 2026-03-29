-- ============================================================
-- Migration: Add admin role to users table
-- Run this in phpMyAdmin AFTER the initial schema.sql
-- ============================================================

USE sharetoneighbour;

-- Add role column to users table
ALTER TABLE users
    ADD COLUMN role ENUM('user', 'admin') NOT NULL DEFAULT 'user'
    AFTER avatar;

-- Make the first user (anna_cph) an admin for testing
UPDATE users SET role = 'admin' WHERE username = 'anna_cph';

-- Also create a dedicated admin account
INSERT INTO users (username, email, password, full_name, address, latitude, longitude, role)
VALUES (
    'admin',
    'admin@sharetoneighbour.dk',
    '$2y$10$xJ8kQZlU9L1GfVh7vNh5F.WjNvEYgFdLqaR5hDdZjKm3hRz0fXmGa',
    'Site Administrator',
    'Copenhagen, Denmark',
    55.6761,
    12.5683,
    'admin'
);
-- Password: password123