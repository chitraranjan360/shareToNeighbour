-- ============================================================
-- ShareToNeighbour — Full Database
-- Import this via phpMyAdmin
-- ============================================================

CREATE DATABASE IF NOT EXISTS sharetoneighbour
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE sharetoneighbour;

-- Drop existing tables (fresh install)
DROP TABLE IF EXISTS requests;
DROP TABLE IF EXISTS messages;
DROP TABLE IF EXISTS furniture_items;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS admins;

-- -----------------------------------------------------------
-- ADMINS TABLE (completely separate from users)
-- -----------------------------------------------------------
CREATE TABLE admins (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    username   VARCHAR(50) NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,
    full_name  VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- -----------------------------------------------------------
-- LOCAL USERS TABLE
-- -----------------------------------------------------------
CREATE TABLE users (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    username   VARCHAR(50)  NOT NULL UNIQUE,
    email      VARCHAR(100) NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,
    full_name  VARCHAR(100) NOT NULL,
    address    VARCHAR(255) DEFAULT NULL,
    latitude   DECIMAL(10,8) DEFAULT 55.6761,
    longitude  DECIMAL(11,8) DEFAULT 12.5683,
    avatar     VARCHAR(255) DEFAULT 'default_avatar.png',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- -----------------------------------------------------------
-- FURNITURE ITEMS TABLE
-- -----------------------------------------------------------
CREATE TABLE furniture_items (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NOT NULL,
    title           VARCHAR(150) NOT NULL,
    description     TEXT NOT NULL,
    category        ENUM('sofa','table','chair','bed','shelf','desk','wardrobe','other')
                        NOT NULL DEFAULT 'other',
    condition_level ENUM('like_new','good','fair','needs_repair')
                        NOT NULL DEFAULT 'good',
    photo           VARCHAR(255) DEFAULT NULL,
    video_link      VARCHAR(500) DEFAULT NULL,
    latitude        DECIMAL(10,8) DEFAULT 55.6761,
    longitude       DECIMAL(11,8) DEFAULT 12.5683,
    status          ENUM('available','requested','taken') NOT NULL DEFAULT 'available',
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_category (category),
    INDEX idx_status   (status),
    INDEX idx_location (latitude, longitude)
) ENGINE=InnoDB;

-- -----------------------------------------------------------
-- MESSAGES TABLE (user-to-user chat)
-- -----------------------------------------------------------
CREATE TABLE messages (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    sender_id   INT NOT NULL,
    receiver_id INT NOT NULL,
    item_id     INT DEFAULT NULL,
    subject     VARCHAR(200) NOT NULL,
    body        TEXT NOT NULL,
    is_read     TINYINT(1) DEFAULT 0,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (sender_id)   REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id)     REFERENCES furniture_items(id) ON DELETE SET NULL,
    INDEX idx_receiver (receiver_id, is_read)
) ENGINE=InnoDB;

-- -----------------------------------------------------------
-- REQUESTS TABLE
-- -----------------------------------------------------------
CREATE TABLE requests (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    item_id      INT NOT NULL,
    requester_id INT NOT NULL,
    owner_id     INT NOT NULL,
    status       ENUM('pending','accepted','declined') NOT NULL DEFAULT 'pending',
    message      TEXT DEFAULT NULL,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (item_id)      REFERENCES furniture_items(id) ON DELETE CASCADE,
    FOREIGN KEY (requester_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (owner_id)     REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_owner (owner_id, status)
) ENGINE=InnoDB;

-- -----------------------------------------------------------
-- DEFAULT ADMIN ACCOUNT
-- Username: admin    Password: admin123
-- -----------------------------------------------------------
INSERT INTO admins (username, password, full_name) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Site Administrator');
-- bcrypt hash of "admin123" (generated via password_hash)

-- -----------------------------------------------------------
-- SAMPLE LOCAL USERS (password for all = "password123")
-- -----------------------------------------------------------
INSERT INTO users (username, email, password, full_name, address, latitude, longitude) VALUES
('anna_cph',     'anna@example.com',   '$2y$10$xJ8kQZlU9L1GfVh7vNh5F.WjNvEYgFdLqaR5hDdZjKm3hRz0fXmGa', 'Anna Jensen',      'Nørrebrogade 12, Copenhagen', 55.6892, 12.5528),
('peter_nv',     'peter@example.com',  '$2y$10$xJ8kQZlU9L1GfVh7vNh5F.WjNvEYgFdLqaR5hDdZjKm3hRz0fXmGa', 'Peter Nielsen',    'Blågårdsgade 5, Copenhagen',  55.6878, 12.5561),
('maria_vest',   'maria@example.com',  '$2y$10$xJ8kQZlU9L1GfVh7vNh5F.WjNvEYgFdLqaR5hDdZjKm3hRz0fXmGa', 'Maria Olsen',      'Griffenfeldsgade 20, CPH',    55.6901, 12.5499),
('lars_frbg',    'lars@example.com',   '$2y$10$xJ8kQZlU9L1GfVh7vNh5F.WjNvEYgFdLqaR5hDdZjKm3hRz0fXmGa', 'Lars Frederiksen', 'Frederiksberg Allé 8, CPH',   55.6722, 12.5349),
('sofie_oester', 'sofie@example.com',  '$2y$10$xJ8kQZlU9L1GfVh7vNh5F.WjNvEYgFdLqaR5hDdZjKm3hRz0fXmGa', 'Sofie Andersen',   'Østerbrogade 45, Copenhagen', 55.6995, 12.5770),
('morten_amager','morten@example.com', '$2y$10$xJ8kQZlU9L1GfVh7vNh5F.WjNvEYgFdLqaR5hDdZjKm3hRz0fXmGa', 'Morten Sørensen',  'Amagerbrogade 100, CPH',      55.6601, 12.6030);

-- -----------------------------------------------------------
-- 20 SAMPLE FURNITURE ITEMS
-- -----------------------------------------------------------
INSERT INTO furniture_items (user_id, title, description, category, condition_level, photo, video_link, latitude, longitude, status) VALUES
(1, 'Blue IKEA Sofa',           'Comfortable 3-seat sofa, minor wear on armrest.',           'sofa',     'good',      'sofa_blue.jpg',     NULL, 55.6892, 12.5528, 'available'),
(1, 'Small Coffee Table',       'Wooden coffee table, 60x40 cm. Oak finish.',                'table',    'like_new',  'coffee_table.jpg',  NULL, 55.6892, 12.5528, 'available'),
(1, 'Bar Stools (pair)',        'Two adjustable bar stools, chrome + black leather.',         'chair',    'like_new',  'bar_stools.jpg',    NULL, 55.6892, 12.5528, 'available'),
(2, 'Dining Chairs (set of 4)','White wooden chairs, good condition, minor scratches.',      'chair',    'good',      'dining_chairs.jpg', NULL, 55.6878, 12.5561, 'available'),
(2, 'Standing Desk',           'Adjustable electric standing desk, 120 cm wide.',            'desk',     'like_new',  'standing_desk.jpg', 'https://youtube.com/watch?v=example1', 55.6878, 12.5561, 'available'),
(2, 'Nightstand Lamp Table',   'Round side table that doubles as a nightstand.',             'table',    'good',      'lamp_table.jpg',    NULL, 55.6878, 12.5561, 'available'),
(3, 'Single Bed Frame',        'IKEA MALM single bed frame, white. No mattress included.',  'bed',      'fair',      'bed_frame.jpg',     NULL, 55.6901, 12.5499, 'available'),
(3, 'BILLY Bookshelf',         'Classic IKEA bookshelf, birch veneer, 80x202 cm.',           'shelf',    'good',      'bookshelf.jpg',     NULL, 55.6901, 12.5499, 'available'),
(3, 'Bedside Table',           'Small wooden bedside table with one drawer.',                'table',    'good',      'bedside_table.jpg', NULL, 55.6901, 12.5499, 'available'),
(4, 'Leather Armchair',        'Dark brown leather armchair, vintage 1970s style.',          'chair',    'fair',      'armchair.jpg',      NULL, 55.6722, 12.5349, 'available'),
(4, 'KALLAX Shelf Unit',       'IKEA KALLAX 4x4, white. Some scratches on top.',            'shelf',    'fair',      'kallax.jpg',        'https://youtube.com/watch?v=example2', 55.6722, 12.5349, 'available'),
(4, 'Kids Study Desk',         'Small wooden desk suitable for children age 5-10.',          'desk',     'good',      'kids_desk.jpg',     NULL, 55.6722, 12.5349, 'available'),
(5, 'Queen Bed + Mattress',    'IKEA queen bed with HOVAG mattress, 3 years old.',           'bed',      'good',      'queen_bed.jpg',     NULL, 55.6995, 12.5770, 'available'),
(5, 'TV Stand',                'Low TV stand with two open shelves, dark walnut.',            'shelf',    'like_new',  'tv_stand.jpg',      NULL, 55.6995, 12.5770, 'available'),
(5, 'Folding Dining Table',    'Space-saving folding table, seats 4 when open.',             'table',    'good',      'folding_table.jpg', NULL, 55.6995, 12.5770, 'available'),
(5, 'Office Chair',            'Ergonomic mesh-back office chair, fully adjustable.',        'chair',    'like_new',  'office_chair.jpg',  NULL, 55.6995, 12.5770, 'available'),
(6, 'Wardrobe PAX',            'IKEA PAX 150x200 cm, white with mirror door.',               'wardrobe', 'good',      'wardrobe_pax.jpg',  NULL, 55.6601, 12.6030, 'available'),
(6, 'Shoe Rack',               'Wooden shoe rack, holds 12 pairs. Compact design.',          'shelf',    'like_new',  'shoe_rack.jpg',     NULL, 55.6601, 12.6030, 'available'),
(6, 'Garden Bench',            'Outdoor wooden bench, weathered but very solid.',             'other',    'fair',      'garden_bench.jpg',  NULL, 55.6601, 12.6030, 'available'),
(6, 'Corner Sofa',             'Large L-shaped sofa, grey fabric, seats 5 comfortably.',     'sofa',     'good',      'corner_sofa.jpg',   'https://youtube.com/watch?v=example3', 55.6601, 12.6030, 'available');