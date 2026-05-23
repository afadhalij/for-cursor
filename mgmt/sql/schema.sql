-- ============================================================================
-- Inganzo Ngari Management System — Members module schema
-- MySQL / MariaDB 10.4+
-- Import into a fresh database, e.g. `inganzo_mgmt`:
--   mysql -u root -p inganzo_mgmt < sql/schema.sql
--   mysql -u root -p inganzo_mgmt < sql/seed.sql
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------------
-- Reference data: sections, performer_types, roles, categories
-- ---------------------------------------------------------------------------

DROP TABLE IF EXISTS sections;
CREATE TABLE sections (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    code        VARCHAR(40)  NOT NULL UNIQUE,
    name        VARCHAR(80)  NOT NULL,
    description VARCHAR(255) DEFAULT NULL,
    sort_order  TINYINT      NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS performer_types;
CREATE TABLE performer_types (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    code        VARCHAR(40)  NOT NULL UNIQUE,
    name        VARCHAR(80)  NOT NULL,
    description VARCHAR(255) DEFAULT NULL,
    sort_order  TINYINT      NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS roles;
CREATE TABLE roles (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(120) NOT NULL,
    section_id  INT UNSIGNED NOT NULL,
    description VARCHAR(255) DEFAULT NULL,
    permissions JSON DEFAULT NULL,
    sort_order  TINYINT      NOT NULL DEFAULT 0,
    CONSTRAINT fk_roles_section FOREIGN KEY (section_id) REFERENCES sections(id) ON DELETE RESTRICT,
    UNIQUE KEY uniq_role_per_section (name, section_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS categories;
CREATE TABLE categories (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(80)   NOT NULL UNIQUE,
    fixed_salary  DECIMAL(12,2) NOT NULL DEFAULT 0,
    description   VARCHAR(255)  DEFAULT NULL,
    status        ENUM('active','archived') NOT NULL DEFAULT 'active',
    sort_order    TINYINT       NOT NULL DEFAULT 0,
    created_at    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Members
-- ---------------------------------------------------------------------------

DROP TABLE IF EXISTS members;
CREATE TABLE members (
    id                INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    full_name         VARCHAR(160) NOT NULL,
    national_id       VARCHAR(20)  NOT NULL UNIQUE,
    gender            ENUM('M','F') NOT NULL,
    date_of_birth     DATE         DEFAULT NULL,
    phone             VARCHAR(20)  NOT NULL,
    email             VARCHAR(160) DEFAULT NULL,

    -- Rwandan address breakdown
    akarere           VARCHAR(80)  NOT NULL,
    umurenge          VARCHAR(80)  NOT NULL,
    akagari           VARCHAR(80)  NOT NULL,
    umudugudu         VARCHAR(80)  DEFAULT NULL,

    -- Emergency contact
    emergency_name    VARCHAR(160) DEFAULT NULL,
    emergency_phone   VARCHAR(20)  DEFAULT NULL,

    -- Org structure
    section_id        INT UNSIGNED NOT NULL,
    performer_type_id INT UNSIGNED DEFAULT NULL,  -- only for Performers section
    role_id           INT UNSIGNED DEFAULT NULL,
    category_id       INT UNSIGNED NOT NULL,

    -- Photo
    photo_path        VARCHAR(255) DEFAULT NULL,

    -- Lifecycle
    status            ENUM('active','retired','suspended') NOT NULL DEFAULT 'active',
    joined_date       DATE         NOT NULL DEFAULT (CURRENT_DATE),
    retirement_date   DATE         DEFAULT NULL,
    retirement_reason ENUM('retired','health','moved_abroad','family','career_change','other') DEFAULT NULL,
    retirement_note   VARCHAR(255) DEFAULT NULL,

    created_at        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_member_section   FOREIGN KEY (section_id)        REFERENCES sections(id)        ON DELETE RESTRICT,
    CONSTRAINT fk_member_perftype  FOREIGN KEY (performer_type_id) REFERENCES performer_types(id) ON DELETE SET NULL,
    CONSTRAINT fk_member_role      FOREIGN KEY (role_id)           REFERENCES roles(id)           ON DELETE SET NULL,
    CONSTRAINT fk_member_category  FOREIGN KEY (category_id)       REFERENCES categories(id)      ON DELETE RESTRICT,

    INDEX idx_member_status      (status),
    INDEX idx_member_section     (section_id),
    INDEX idx_member_perftype    (performer_type_id),
    INDEX idx_member_category    (category_id),
    INDEX idx_member_name        (full_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Health insurance (Mutuelle de Santé or other)
-- ---------------------------------------------------------------------------

DROP TABLE IF EXISTS member_insurance;
CREATE TABLE member_insurance (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    member_id       INT UNSIGNED NOT NULL UNIQUE,
    has_insurance   TINYINT(1)   NOT NULL DEFAULT 0,
    insurance_name  VARCHAR(120) DEFAULT NULL,
    start_date      DATE         DEFAULT NULL,
    expiry_date     DATE         DEFAULT NULL,
    note            VARCHAR(255) DEFAULT NULL,
    updated_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_ins_member FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Lightweight audit trail (writes from the members module)
-- ---------------------------------------------------------------------------

DROP TABLE IF EXISTS audit_log;
CREATE TABLE audit_log (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    actor       VARCHAR(120) NOT NULL,
    entity      VARCHAR(40)  NOT NULL,
    entity_id   INT UNSIGNED DEFAULT NULL,
    action      VARCHAR(40)  NOT NULL,
    diff        JSON         DEFAULT NULL,
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_audit_entity (entity, entity_id),
    INDEX idx_audit_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Admin users (single-table auth for the management system itself)
-- ---------------------------------------------------------------------------

DROP TABLE IF EXISTS users;
CREATE TABLE users (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    username      VARCHAR(40)  NOT NULL UNIQUE,
    full_name     VARCHAR(160) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role          ENUM('boss','admin','viewer') NOT NULL DEFAULT 'admin',
    must_change   TINYINT(1)   NOT NULL DEFAULT 1,
    created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_login    TIMESTAMP    NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Views to make analytics queries trivial
-- ---------------------------------------------------------------------------

DROP VIEW IF EXISTS v_member_full;
CREATE VIEW v_member_full AS
SELECT
    m.id,
    m.full_name, m.national_id, m.gender, m.date_of_birth, m.phone, m.email,
    m.akarere, m.umurenge, m.akagari, m.umudugudu,
    m.emergency_name, m.emergency_phone,
    m.photo_path, m.status, m.joined_date,
    m.retirement_date, m.retirement_reason, m.retirement_note,
    m.section_id,        s.name AS section_name,
    m.performer_type_id, pt.name AS performer_type_name,
    m.role_id,           r.name AS role_name,
    m.category_id,       c.name AS category_name, c.fixed_salary,
    mi.has_insurance, mi.insurance_name, mi.start_date AS insurance_start, mi.expiry_date AS insurance_expiry,
    CASE
        WHEN mi.has_insurance IS NULL OR mi.has_insurance = 0 THEN 'none'
        WHEN mi.expiry_date IS NULL THEN 'unknown'
        WHEN mi.expiry_date < CURRENT_DATE THEN 'expired'
        WHEN DATEDIFF(mi.expiry_date, CURRENT_DATE) <= 30 THEN 'expiring'
        ELSE 'active'
    END AS insurance_status,
    TIMESTAMPDIFF(YEAR, m.date_of_birth, CURRENT_DATE) AS age
FROM members m
LEFT JOIN sections        s  ON s.id  = m.section_id
LEFT JOIN performer_types pt ON pt.id = m.performer_type_id
LEFT JOIN roles           r  ON r.id  = m.role_id
LEFT JOIN categories      c  ON c.id  = m.category_id
LEFT JOIN member_insurance mi ON mi.member_id = m.id;

SET FOREIGN_KEY_CHECKS = 1;
