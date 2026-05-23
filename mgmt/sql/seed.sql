-- ============================================================================
-- Inganzo Ngari Management System — seed data
-- Realistic Rwandan names, addresses and IDs for the Members module demo.
-- Import after schema.sql.
-- ============================================================================

SET NAMES utf8mb4;

-- ---------- Sections ----------
INSERT INTO sections (id, code, name, description, sort_order) VALUES
    (1, 'leaders',      'Leaders',       'Governance and finance leadership',     1),
    (2, 'performers',   'Performers',    'Dancers, drummers and singers',         2),
    (3, 'support',      'Support Team',  'Discipline, logistics and social media',3);

-- ---------- Performer types (sub-groups under Performers) ----------
INSERT INTO performer_types (id, code, name, description, sort_order) VALUES
    (1, 'indende',         'Indende',        'Male dancers',                  1),
    (2, 'abaterambabazi',  'Abaterambabazi', 'Female dancers',                2),
    (3, 'inyamamare',      'Inyamamare',     'Mixed — singers and drummers',  3);

-- ---------- Roles (kept verbatim in Kinyarwanda where applicable) ----------
INSERT INTO roles (id, name, section_id, description, sort_order) VALUES
    -- LEADERS
    (1,  "Umuyobozi w'itorero",      1, 'Boss / Chairperson',           1),
    (2,  'Umuyobozi wungirije',      1, 'Vice-chair',                   2),
    (3,  'Ushinzwe umutungo',        1, 'Treasurer',                    3),
    (4,  'Admin',                    1, 'Administration & finance',     4),

    -- PERFORMERS
    (10, 'Umutoza mukuru',           2, 'Overall trainer',              1),
    (11, "Umutoza w'Indende",        2, 'Trainer for Indende',          2),
    (12, 'Umutoza wungirije',        2, 'Assistant trainer',            3),
    (13, "Umutoza w'abaterambabazi", 2, 'Trainer for Abaterambabazi',   4),

    -- SUPPORT
    (20, 'Disciplinaire',            3, 'Discipline officer',           1),
    (21, 'Ishema',                   3, 'Pride / hospitality',          2),
    (22, 'Social',                   3, 'Welfare coordinator',          3),
    (23, 'Social Activities',        3, 'Events & gatherings',          4),
    (24, 'Social Media',             3, 'Communications & marketing',   5);

-- ---------- Categories (payroll-linked) ----------
INSERT INTO categories (id, name, fixed_salary, description, sort_order) VALUES
    (1, 'Category 1', 250000, 'Top tier',                          1),
    (2, 'Category 2', 180000, 'Senior',                            2),
    (3, 'Category 3', 120000, 'Mid',                               3),
    (4, 'Category 4',  80000, 'Junior',                            4),
    (5, 'Stagiaire',       0, 'Probation — unpaid',                5),
    (6, 'Umushya',         0, 'Brand new recruit — unpaid',        6);

-- ---------- Default admin user (password: InganzoN — change on first login) ----------
-- Hash generated offline with PHP: password_hash('InganzoN', PASSWORD_DEFAULT)
INSERT INTO users (username, full_name, password_hash, role, must_change) VALUES
    ('admin', 'Default Admin',
     '$2y$10$djJ1CePXXpiP9ioQLzxKzeJjBmM2DlQS0YC0vfSwYJdtF93FJ4hZy',
     'boss', 1);

-- ---------- Demo members (35 realistic profiles) ----------
-- National ID format: 1 + 4-digit-year + (7=female | 8=male) + 9 sequence digits + 1 check digit
-- (Standard Rwandan NID — total 16 digits)
INSERT INTO members
    (full_name, national_id, gender, date_of_birth, phone, email,
     akarere, umurenge, akagari, umudugudu,
     emergency_name, emergency_phone,
     section_id, performer_type_id, role_id, category_id,
     status, joined_date)
VALUES
    -- ===== LEADERS =====
    ("Jean Bosco Habiyaremye", "1198380123456781", 'M', '1983-04-12', '+250788111201', 'jbosco@inganzongari.rw',
     'Gasabo', 'Remera', 'Nyabisindu', 'Karambo',
     'Marie Uwimana', '+250788111301',
     1, NULL, 1, 1, 'active', '2010-03-01'),

    ("Aline Mukamana", "1198670456789012", 'F', '1986-08-21', '+250788111202', 'aline.muka@inganzongari.rw',
     'Nyarugenge', 'Nyamirambo', 'Cyivugiza', 'Akabuga',
     'Paul Manzi', '+250788111302',
     1, NULL, 2, 1, 'active', '2011-06-15'),

    ("Patrick Niyonsenga", "1198280678901234", 'M', '1982-01-18', '+250788111203', 'patrick.n@inganzongari.rw',
     'Kicukiro', 'Niboye', 'Rukurazo', 'Iterambere',
     'Diane Uwase', '+250788111303',
     1, NULL, 3, 2, 'active', '2012-09-10'),

    ("Eric Twagirayezu", "1199080890123456", 'M', '1990-11-04', '+250788111204', 'eric.t@inganzongari.rw',
     'Gasabo', 'Kacyiru', 'Kamutwa', 'Inkomoko',
     'Sandrine Mukundwa', '+250788111304',
     1, NULL, 4, 2, 'active', '2015-02-01'),

    -- ===== PERFORMERS — Indende (men) =====
    ("Olivier Manzi", "1199580234567812", 'M', '1995-03-10', '+250788111205', NULL,
     'Gasabo', 'Kimironko', 'Bibare', 'Kibagabaga',
     'Jeanne Iradukunda', '+250788111305',
     2, 1, 10, 1, 'active', '2014-07-22'),

    ("Patrick Iradukunda", "1199680345678123", 'M', '1996-05-25', '+250788111206', NULL,
     'Nyarugenge', 'Gitega', 'Kigarama', 'Nyakabanda',
     'Marie Nyiramana', '+250788111306',
     2, 1, 11, 1, 'active', '2015-09-01'),

    ("Pacifique Habineza", "1199780456789234", 'M', '1997-07-30', '+250788111207', NULL,
     'Kicukiro', 'Gatenga', 'Karembure', 'Akamashya',
     'Beatrice Cyusa', '+250788111307',
     2, 1, 12, 2, 'active', '2016-01-20'),

    ("Innocent Bizimana", "1199880567890345", 'M', '1998-09-14', '+250788111208', NULL,
     'Gasabo', 'Bumbogo', 'Ngara', 'Akarere',
     'Esther Mukandayisenga', '+250788111308',
     2, 1, NULL, 2, 'active', '2017-04-11'),

    ("Emmanuel Hakizimana", "1199980678901456", 'M', '1999-12-02', '+250788111209', NULL,
     'Kicukiro', 'Kanombe', 'Karama', 'Akamuhana',
     'Christine Ingabire', '+250788111309',
     2, 1, NULL, 3, 'active', '2018-06-15'),

    ("Daniel Munyaneza", "1200080789012567", 'M', '2000-06-19', '+250788111210', NULL,
     'Gasabo', 'Ndera', 'Cyaruzinge', 'Akabuga',
     'Vestine Uwineza', '+250788111310',
     2, 1, NULL, 3, 'active', '2019-08-22'),

    ("Faustin Kamana", "1200180890123678", 'M', '2001-02-28', '+250788111211', NULL,
     'Nyarugenge', 'Rwezamenyo', 'Kabuguru', 'Akabuga',
     'Florence Murenzi', '+250788111311',
     2, 1, NULL, 4, 'active', '2020-03-05'),

    ("Theogene Ndayisaba", "1200380123456890", 'M', '2003-10-15', '+250788111212', NULL,
     'Gasabo', 'Kimironko', 'Bibare', 'Kibagabaga',
     'Donatille Mukabazungu', '+250788111312',
     2, 1, NULL, 4, 'active', '2021-11-12'),

    -- ===== PERFORMERS — Abaterambabazi (women) =====
    ("Aline Uwase", "1199470234567823", 'F', '1994-04-17', '+250788111213', NULL,
     'Kicukiro', 'Niboye', 'Rukurazo', 'Iterambere',
     'Jean de Dieu Mugisha', '+250788111313',
     2, 2, 13, 1, 'active', '2013-11-04'),

    ("Diane Mukamana", "1199570345678934", 'F', '1995-07-08', '+250788111214', NULL,
     'Gasabo', 'Remera', 'Nyabisindu', 'Karambo',
     'Christian Ntakirutimana', '+250788111314',
     2, 2, NULL, 2, 'active', '2014-05-20'),

    ("Claudine Umuhoza", "1199670456789045", 'F', '1996-11-22', '+250788111215', NULL,
     'Gasabo', 'Kacyiru', 'Kamutwa', 'Inkomoko',
     'Patrick Niyibizi', '+250788111315',
     2, 2, NULL, 2, 'active', '2015-03-18'),

    ("Yvette Mutoni", "1199770567890156", 'F', '1997-01-30', '+250788111216', NULL,
     'Nyarugenge', 'Nyamirambo', 'Cyivugiza', 'Akabuga',
     'Pacifique Gasana', '+250788111316',
     2, 2, NULL, 3, 'active', '2016-09-01'),

    ("Sandrine Mukundwa", "1199870678901267", 'F', '1998-08-12', '+250788111217', NULL,
     'Gasabo', 'Kimironko', 'Bibare', 'Kibagabaga',
     'Innocent Kalisa', '+250788111317',
     2, 2, NULL, 3, 'active', '2017-07-25'),

    ("Esperance Mukandayisenga", "1199970789012378", 'F', '1999-05-04', '+250788111218', NULL,
     'Kicukiro', 'Gatenga', 'Karembure', 'Akamashya',
     'Eric Munyemana', '+250788111318',
     2, 2, NULL, 4, 'active', '2018-11-30'),

    ("Beatrice Cyusa", "1200070890123489", 'F', '2000-09-19', '+250788111219', NULL,
     'Gasabo', 'Bumbogo', 'Ngara', 'Akarere',
     'Jean Bosco Niyibizi', '+250788111319',
     2, 2, NULL, 4, 'active', '2019-12-10'),

    ("Vestine Uwineza", "1200170901234590", 'F', '2001-03-27', '+250788111220', NULL,
     'Gasabo', 'Ndera', 'Cyaruzinge', 'Akabuga',
     'Pierre Habimana', '+250788111320',
     2, 2, NULL, 5, 'active', '2022-08-15'),

    ("Donatille Mukabazungu", "1200273012345601", 'F', '2002-06-08', '+250788111221', NULL,
     'Nyarugenge', 'Rwezamenyo', 'Kabuguru', 'Akabuga',
     'Etienne Munyaneza', '+250788111321',
     2, 2, NULL, 5, 'active', '2023-04-22'),

    -- ===== PERFORMERS — Inyamamare (drummers + singers, mixed gender) =====
    ("Marie Nyiramana", "1199470345678901", 'F', '1994-10-05', '+250788111222', NULL,
     'Gasabo', 'Kacyiru', 'Kamutwa', 'Inkomoko',
     'Jean Damascene Ngabo', '+250788111322',
     2, 3, NULL, 1, 'active', '2013-09-12'),

    ("Christian Ntakirutimana", "1199680456789012", 'M', '1996-12-18', '+250788111223', NULL,
     'Kicukiro', 'Kanombe', 'Karama', 'Akamuhana',
     'Sandrine Niyonsenga', '+250788111323',
     2, 3, NULL, 2, 'active', '2014-12-01'),

    ("Esther Ingabire", "1199770567890123", 'F', '1997-03-25', '+250788111224', NULL,
     'Gasabo', 'Kimironko', 'Bibare', 'Kibagabaga',
     'Olivier Hakuzimana', '+250788111324',
     2, 3, NULL, 2, 'active', '2016-04-08'),

    ("Pierre Habimana", "1199880678901234", 'M', '1998-07-14', '+250788111225', NULL,
     'Nyarugenge', 'Gitega', 'Kigarama', 'Nyakabanda',
     'Florence Mukandori', '+250788111325',
     2, 3, NULL, 3, 'active', '2017-10-20'),

    ("Jeanne Iradukunda", "1199970789012345", 'F', '1999-02-09', '+250788111226', NULL,
     'Gasabo', 'Remera', 'Nyabisindu', 'Karambo',
     'Theogene Niyonzima', '+250788111326',
     2, 3, NULL, 3, 'active', '2018-07-15'),

    ("Etienne Munyemana", "1200080890123456", 'M', '2000-08-23', '+250788111227', NULL,
     'Kicukiro', 'Niboye', 'Rukurazo', 'Iterambere',
     'Jeannette Mukamisha', '+250788111327',
     2, 3, NULL, 4, 'active', '2019-05-30'),

    ("Jeannette Mukamisha", "1200170901234567", 'F', '2001-11-11', '+250788111228', NULL,
     'Gasabo', 'Bumbogo', 'Ngara', 'Akarere',
     'Innocent Hakizimana', '+250788111328',
     2, 3, NULL, 5, 'active', '2022-02-18'),

    -- ===== SUPPORT TEAM =====
    ("Florence Mukandori", "1199070123456789", 'F', '1990-04-30', '+250788111229', 'flo.muka@inganzongari.rw',
     'Gasabo', 'Kacyiru', 'Kamutwa', 'Inkomoko',
     'Jean Bosco Habiyaremye', '+250788111329',
     3, NULL, 20, 2, 'active', '2014-08-01'),

    ("Theogene Niyonzima", "1198580234567890", 'M', '1985-09-12', '+250788111230', 'theo.n@inganzongari.rw',
     'Nyarugenge', 'Nyamirambo', 'Cyivugiza', 'Akabuga',
     'Aline Mukamana', '+250788111330',
     3, NULL, 21, 3, 'active', '2015-06-15'),

    ("Sandrine Niyonsenga", "1199370345678901", 'F', '1993-12-05', '+250788111231', 'sandrine.n@inganzongari.rw',
     'Kicukiro', 'Gatenga', 'Karembure', 'Akamashya',
     'Patrick Niyonsenga', '+250788111331',
     3, NULL, 22, 3, 'active', '2017-03-20'),

    ("Christine Murenzi", "1198970456789012", 'F', '1989-06-28', '+250788111232', NULL,
     'Gasabo', 'Ndera', 'Cyaruzinge', 'Akabuga',
     'Eric Twagirayezu', '+250788111332',
     3, NULL, 23, 3, 'active', '2018-09-10'),

    ("Innocent Kalisa", "1199280567890123", 'M', '1992-10-17', '+250788111233', 'innocent.k@inganzongari.rw',
     'Gasabo', 'Kimironko', 'Bibare', 'Kibagabaga',
     'Yvette Mutoni', '+250788111333',
     3, NULL, 24, 2, 'active', '2016-11-28'),

    -- ===== RECRUITS (Stagiaire / Umushya) =====
    ("Aimable Hakuzimana", "1200478890123456", 'F', '2004-03-22', '+250788111234', NULL,
     'Nyarugenge', 'Rwezamenyo', 'Kabuguru', 'Akabuga',
     'Donatille Mukabazungu', '+250788111334',
     2, 3, NULL, 6, 'active', '2025-09-01'),

    ("Bertrand Nshimiyimana", "1200580901234567", 'M', '2005-01-14', '+250788111235', NULL,
     'Kicukiro', 'Kanombe', 'Karama', 'Akamuhana',
     'Esperance Mukandayisenga', '+250788111335',
     2, 1, NULL, 6, 'active', '2025-10-15'),

    -- ===== INGANZO NKURU (retired) =====
    ("Jean Damascene Ngabo", "1196580123456789", 'M', '1965-08-14', '+250788111236', NULL,
     'Gasabo', 'Remera', 'Nyabisindu', 'Karambo',
     'Marie Nyiramana', '+250788111336',
     2, 1, NULL, 1, 'retired', '1995-04-10'),

    ("Concessa Mukasine", "1196870234567890", 'F', '1968-03-09', '+250788111237', NULL,
     'Gasabo', 'Kacyiru', 'Kamutwa', 'Inkomoko',
     'Jean Bosco Habiyaremye', '+250788111337',
     2, 2, NULL, 2, 'retired', '1998-11-20');

-- Backfill retired members with retirement metadata
UPDATE members SET retirement_date = '2020-07-15', retirement_reason = 'retired',
       retirement_note = '25 years of service, lead drummer for a generation'
       WHERE national_id = '1196580123456789';

UPDATE members SET retirement_date = '2022-03-30', retirement_reason = 'family',
       retirement_note = 'Moved to Cyangugu with family'
       WHERE national_id = '1196870234567890';

-- ---------- Insurance records ----------
-- Most active members have Mutuelle de Santé; a few near expiry; couple expired.
INSERT INTO member_insurance (member_id, has_insurance, insurance_name, start_date, expiry_date)
SELECT m.id, 1, 'Mutuelle de Santé',
       DATE_SUB(CURRENT_DATE, INTERVAL FLOOR(60 + RAND() * 300) DAY),
       DATE_ADD(CURRENT_DATE, INTERVAL FLOOR(60 + RAND() * 250) DAY)
FROM members m WHERE m.status = 'active';

-- Mark a few as expiring within the next 30 days (for the dashboard alert)
UPDATE member_insurance mi
JOIN members m ON m.id = mi.member_id
SET mi.expiry_date = DATE_ADD(CURRENT_DATE, INTERVAL 5 DAY)
WHERE m.national_id IN ('1199470234567823', '1199580234567812');

UPDATE member_insurance mi
JOIN members m ON m.id = mi.member_id
SET mi.expiry_date = DATE_ADD(CURRENT_DATE, INTERVAL 18 DAY)
WHERE m.national_id IN ('1199570345678934', '1199680345678123');

-- One expired
UPDATE member_insurance mi
JOIN members m ON m.id = mi.member_id
SET mi.expiry_date = DATE_SUB(CURRENT_DATE, INTERVAL 12 DAY)
WHERE m.national_id = '1200580901234567';

-- Couple of members without insurance (Stagiaire / Umushya)
UPDATE member_insurance mi
JOIN members m ON m.id = mi.member_id
SET mi.has_insurance = 0, mi.insurance_name = NULL, mi.start_date = NULL, mi.expiry_date = NULL
WHERE m.national_id IN ('1200478890123456');
