-- Online Voting System — schema + seed data
-- Import via phpMyAdmin, or: mysql -u root < schema.sql

CREATE DATABASE IF NOT EXISTS online_voting_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE online_voting_system;

-- ---------------------------------------------------------------------------
-- users: both personas (Super Admin, Voter) live in one table, distinguished
-- by `role`. Voters carry `status`/`source` for how the account was created
-- (bulk Excel import / admin-added manually / self-registered + approval).
-- Having an account here does not by itself grant voting rights anywhere —
-- see election_voters below for per-election enrollment.
-- ---------------------------------------------------------------------------
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    voter_code VARCHAR(50) NULL UNIQUE,
    password_hash VARCHAR(255) NULL,
    role ENUM('admin', 'voter') NOT NULL DEFAULT 'voter',
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'approved',
    source ENUM('seed', 'bulk_import', 'self_registered', 'admin_manual') NOT NULL DEFAULT 'self_registered',
    reset_token VARCHAR(64) NULL,
    reset_token_expires_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------------
-- elections
-- ---------------------------------------------------------------------------
CREATE TABLE elections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT NULL,
    format ENUM('fptp', 'select_n') NOT NULL DEFAULT 'fptp',
    select_n INT NULL,
    starts_at DATETIME NOT NULL,
    ends_at DATETIME NOT NULL,
    closed_manually_at DATETIME NULL,
    results_published TINYINT(1) NOT NULL DEFAULT 0,
    created_by INT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_elections_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------------
-- candidates
-- ---------------------------------------------------------------------------
CREATE TABLE candidates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    election_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    bio TEXT NULL,
    photo_path VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_candidates_election FOREIGN KEY (election_id) REFERENCES elections(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------------
-- election_voters: enrollment. Being an approved voter account does not by
-- itself grant voting rights anywhere — a voter must be enrolled in a
-- specific election (via bulk roster upload or an individual admin action)
-- before they can vote in it. Separate from account approval on purpose.
-- ---------------------------------------------------------------------------
CREATE TABLE election_voters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    election_id INT NOT NULL,
    user_id INT NOT NULL,
    enrolled_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_election_voter (election_id, user_id),
    CONSTRAINT fk_ev_election FOREIGN KEY (election_id) REFERENCES elections(id) ON DELETE CASCADE,
    CONSTRAINT fk_ev_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------------
-- ledger_blocks: blockchain-style, tamper-evident vote storage.
-- One global hash chain (id = block_index) across all elections.
-- voter_hash = sha256(voter_id + ':' + election_id + ':' + app secret) —
-- proves *that* a voter voted (enforces one-vote-per-election via the unique
-- key below) without the block itself exposing identity or choice together.
-- choice_data is JSON: a single candidate id (fptp) or an array (select_n).
-- ---------------------------------------------------------------------------
CREATE TABLE ledger_blocks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    voter_hash CHAR(64) NOT NULL,
    election_id INT NOT NULL,
    choice_data TEXT NOT NULL,
    prev_hash CHAR(64) NOT NULL,
    hash CHAR(64) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_voter_election (voter_hash, election_id),
    CONSTRAINT fk_ledger_election FOREIGN KEY (election_id) REFERENCES elections(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------------
-- audit_log
-- ---------------------------------------------------------------------------
CREATE TABLE audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    action VARCHAR(100) NOT NULL,
    details TEXT NULL,
    ip_address VARCHAR(45) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------------
-- Seed data
-- ---------------------------------------------------------------------------

-- Super Admin — login: admin@ovs.test / Admin@123
INSERT INTO users (name, email, password_hash, role, status, source) VALUES
('Election Commissioner', 'admin@ovs.test', '$2y$10$u9NjJlVY7/0Cle/2roVa4u9VoeaBXlI28VDDTN3SsreTUTwc253Iu', 'admin', 'approved', 'seed');

-- Demo voters — all use password: Voter@123
-- Approved (can log in and vote immediately)
INSERT INTO users (name, email, voter_code, password_hash, role, status, source) VALUES
('Aarav Sharma', 'aarav.sharma@ovs.test', 'cbfe5baa-6ca9-4005-9415-e84b04295086', '$2y$10$dRlp0IgQXDYt1bSjf6Vm4uLmVlIPE761gFtEzm5fImHgMhNaczOma', 'voter', 'approved', 'bulk_import'),
('Diya Patel', 'diya.patel@ovs.test', '976b3bd7-2bcd-4301-a937-6f20b5fc9ffd', '$2y$10$dRlp0IgQXDYt1bSjf6Vm4uLmVlIPE761gFtEzm5fImHgMhNaczOma', 'voter', 'approved', 'bulk_import'),
('Vihaan Reddy', 'vihaan.reddy@ovs.test', '897de580-fb4e-4bd4-8e3e-5191b9c57fba', '$2y$10$dRlp0IgQXDYt1bSjf6Vm4uLmVlIPE761gFtEzm5fImHgMhNaczOma', 'voter', 'approved', 'bulk_import'),
('Ananya Iyer', 'ananya.iyer@ovs.test', 'ea55735b-b3fb-483e-9010-6d69060b63aa', '$2y$10$dRlp0IgQXDYt1bSjf6Vm4uLmVlIPE761gFtEzm5fImHgMhNaczOma', 'voter', 'approved', 'bulk_import'),
('Kabir Nair', 'kabir.nair@ovs.test', 'bc74f911-19f4-46fe-a042-2df1df2277c9', '$2y$10$dRlp0IgQXDYt1bSjf6Vm4uLmVlIPE761gFtEzm5fImHgMhNaczOma', 'voter', 'approved', 'bulk_import'),
('Ishita Gupta', 'ishita.gupta@ovs.test', '2b25a611-47e7-4f69-b202-28f960fef796', '$2y$10$dRlp0IgQXDYt1bSjf6Vm4uLmVlIPE761gFtEzm5fImHgMhNaczOma', 'voter', 'approved', 'bulk_import'),
('Reyansh Verma', 'reyansh.verma@ovs.test', '6721e9df-3ec8-44e0-8cc4-65c9d85a0250', '$2y$10$dRlp0IgQXDYt1bSjf6Vm4uLmVlIPE761gFtEzm5fImHgMhNaczOma', 'voter', 'approved', 'bulk_import'),
('Myra Joshi', 'myra.joshi@ovs.test', '2b622e77-75ea-48e4-8a8d-19de61b58d45', '$2y$10$dRlp0IgQXDYt1bSjf6Vm4uLmVlIPE761gFtEzm5fImHgMhNaczOma', 'voter', 'approved', 'bulk_import'),
('Arjun Kumar', 'arjun.kumar@ovs.test', 'fd93e790-dd90-43a6-9ef4-627169722aa7', '$2y$10$dRlp0IgQXDYt1bSjf6Vm4uLmVlIPE761gFtEzm5fImHgMhNaczOma', 'voter', 'approved', 'bulk_import'),
('Saanvi Menon', 'saanvi.menon@ovs.test', '96c1ad73-8323-4087-809c-5b7446b3c7cb', '$2y$10$dRlp0IgQXDYt1bSjf6Vm4uLmVlIPE761gFtEzm5fImHgMhNaczOma', 'voter', 'approved', 'bulk_import'),
('Vivaan Rao', 'vivaan.rao@ovs.test', '1db18d80-b7cb-417f-af5c-3d6591fdac89', '$2y$10$dRlp0IgQXDYt1bSjf6Vm4uLmVlIPE761gFtEzm5fImHgMhNaczOma', 'voter', 'approved', 'bulk_import'),
('Anika Desai', 'anika.desai@ovs.test', '3177e98c-6481-4569-914d-af175fe0ba41', '$2y$10$dRlp0IgQXDYt1bSjf6Vm4uLmVlIPE761gFtEzm5fImHgMhNaczOma', 'voter', 'approved', 'bulk_import'),
('Aditya Pillai', 'aditya.pillai@ovs.test', 'ea07965f-fb64-4a42-87b4-a80e95d09b5b', '$2y$10$dRlp0IgQXDYt1bSjf6Vm4uLmVlIPE761gFtEzm5fImHgMhNaczOma', 'voter', 'approved', 'bulk_import'),
('Kiara Bose', 'kiara.bose@ovs.test', '25a786a0-af58-498d-80c0-75962b90ea46', '$2y$10$dRlp0IgQXDYt1bSjf6Vm4uLmVlIPE761gFtEzm5fImHgMhNaczOma', 'voter', 'approved', 'bulk_import'),
('Ayaan Malhotra', 'ayaan.malhotra@ovs.test', '57ec101f-dc30-47c5-8637-aafa8bd4a61b', '$2y$10$dRlp0IgQXDYt1bSjf6Vm4uLmVlIPE761gFtEzm5fImHgMhNaczOma', 'voter', 'approved', 'bulk_import');

-- Pending self-registrations (awaiting admin approval, not activation — they
-- already chose their own password at registration, same as the real flow;
-- voter_code is still auto-generated for every voter regardless of source)
INSERT INTO users (name, email, voter_code, password_hash, role, status, source) VALUES
('Rohan Chatterjee', 'rohan.chatterjee@ovs.test', 'df277897-e7e8-43c2-80c0-30ef81440b31', '$2y$10$dRlp0IgQXDYt1bSjf6Vm4uLmVlIPE761gFtEzm5fImHgMhNaczOma', 'voter', 'pending', 'self_registered'),
('Priya Bhat', 'priya.bhat@ovs.test', '8d909eb5-8e1c-4ecc-bf06-d729d79a54c5', '$2y$10$dRlp0IgQXDYt1bSjf6Vm4uLmVlIPE761gFtEzm5fImHgMhNaczOma', 'voter', 'pending', 'self_registered'),
('Nikhil Sinha', 'nikhil.sinha@ovs.test', 'fae35da1-764b-4dc2-b411-8179b337e1f6', '$2y$10$dRlp0IgQXDYt1bSjf6Vm4uLmVlIPE761gFtEzm5fImHgMhNaczOma', 'voter', 'pending', 'self_registered');

-- A voter added individually by admin (App\Models\User::createManual in the
-- real flow auto-generates voter_code + password; seeded here with a fixed
-- demo password instead so it's reproducible)
INSERT INTO users (name, email, voter_code, password_hash, role, status, source) VALUES
('Tanvi Rao', 'tanvi.rao@ovs.test', '89f9326e-2416-41e2-a7e7-80921cb323a6', '$2y$10$dRlp0IgQXDYt1bSjf6Vm4uLmVlIPE761gFtEzm5fImHgMhNaczOma', 'voter', 'approved', 'admin_manual');

-- ---------------------------------------------------------------------------
-- Demo elections
-- ---------------------------------------------------------------------------

-- FPTP election, currently open
INSERT INTO elections (title, description, format, select_n, starts_at, ends_at, created_by) VALUES
('Student Council President', 'Vote for a single candidate to lead the student council for the academic year.', 'fptp', NULL, DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_ADD(NOW(), INTERVAL 6 DAY), 1);

INSERT INTO candidates (election_id, name, bio) VALUES
(1, 'Meera Krishnan', 'Third-year Computer Science, campaigning on better campus wifi and extended library hours.'),
(1, 'Sameer Joshi', 'Final-year Mechanical Engineering, focused on sports facility upgrades.'),
(1, 'Fatima Sheikh', 'Second-year Commerce, running on a platform of more industry-linked events.');

-- Enroll the 15 approved bulk-import voters (ids 2-16) in this election
INSERT INTO election_voters (election_id, user_id)
SELECT 1, id FROM users WHERE id BETWEEN 2 AND 16;

-- Select-N election (electing a 3-person committee), currently open
INSERT INTO elections (title, description, format, select_n, starts_at, ends_at, created_by) VALUES
('Cultural Committee (3 seats)', 'Select up to 3 candidates to serve on this year''s cultural committee.', 'select_n', 3, DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_ADD(NOW(), INTERVAL 5 DAY), 1);

INSERT INTO candidates (election_id, name, bio) VALUES
(2, 'Karan Malhotra', 'Organized last year''s annual fest.'),
(2, 'Sneha Pillai', 'Leads the college dance ensemble.'),
(2, 'Aditya Verma', 'Music club coordinator.'),
(2, 'Riya Kapoor', 'Drama society president.'),
(2, 'Yash Agarwal', 'Photography club founder.');

INSERT INTO election_voters (election_id, user_id)
SELECT 2, id FROM users WHERE id BETWEEN 2 AND 16;

-- A closed election with published results, to demonstrate the results view
INSERT INTO elections (title, description, format, select_n, starts_at, ends_at, closed_manually_at, results_published, created_by) VALUES
('Sports Secretary', 'Single-winner vote for the outgoing sports secretary role.', 'fptp', NULL, DATE_SUB(NOW(), INTERVAL 14 DAY), DATE_SUB(NOW(), INTERVAL 7 DAY), DATE_SUB(NOW(), INTERVAL 7 DAY), 1, 1);

INSERT INTO candidates (election_id, name, bio) VALUES
(3, 'Devika Nair', 'Captain of the college basketball team.'),
(3, 'Rahul Chandra', 'Organized the inter-department athletics meet.');

-- Only the 8 voters who actually cast a seeded vote below (ids 2-9) are
-- enrolled here, matching the seeded ledger data exactly.
INSERT INTO election_voters (election_id, user_id)
SELECT 3, id FROM users WHERE id BETWEEN 2 AND 9;

-- Seed ledger blocks for the closed Sports Secretary election (id=3): a
-- genuine, self-consistent hash chain (5 votes for Devika Nair [candidate 9],
-- 3 for Rahul Chandra [candidate 10]) so "Verify Ledger Integrity" has real
-- data to recompute and confirm as untampered.
-- voter_hash = sha256(voter_id . ':' . election_id . ':' . ledger_secret),
-- ledger_secret must match config/app.php's `ledger_secret`.
INSERT INTO ledger_blocks (id, voter_hash, election_id, choice_data, prev_hash, hash, created_at) VALUES
(1, '1c1ea8407d8bb486a1a45e2def720980b7d0255e9d9cd52da5f4b0d467811c99', 3, '9', '0000000000000000000000000000000000000000000000000000000000000000', '590cb13a70551903903b78c5557d4be5eff4298f999232ba8331a9b8927515df', '2026-08-10 14:02:33'),
(2, '36a97f06c899eac02ce3bcdcef0f405e8664aae820fb6febba5428e531e93eb7', 3, '9', '590cb13a70551903903b78c5557d4be5eff4298f999232ba8331a9b8927515df', 'f6ccbd6ffa48fd9dad6d92a65cc197d8634515437eb90dba79d34cd119f27c0a', '2026-08-10 14:03:33'),
(3, '9e83c8ff4fba34b4412e2a08635b95721eb6b5f0e59e31b8ab51d3e1a2af1333', 3, '9', 'f6ccbd6ffa48fd9dad6d92a65cc197d8634515437eb90dba79d34cd119f27c0a', '3302b5f36187a93fca5da22fd71801c7acd780723d3c9671b488c1ebac81b79d', '2026-08-10 14:04:33'),
(4, 'f87869df48fa9f9b4a826a6daf66baea9317b9254c297975415345a7b914dd4a', 3, '9', '3302b5f36187a93fca5da22fd71801c7acd780723d3c9671b488c1ebac81b79d', 'fc45b750ad39c173f302121367bf8d150aec7e5b288d7ab2ebfe8b8560b20690', '2026-08-10 14:05:33'),
(5, 'd1d0540c02a6e81c69c9fe0b8deae9e5363a059c018637a5fa69004970d0c5b2', 3, '9', 'fc45b750ad39c173f302121367bf8d150aec7e5b288d7ab2ebfe8b8560b20690', '0b8e1df192fdf7d87ee69d94fc8e3c8b638874848d3f26eae7359643c42a4f0d', '2026-08-10 14:06:33'),
(6, 'dae0f6b49a8cf2772eec5da36edd4f7bedc66ba46da75ae75fb130ece9fbcbad', 3, '10', '0b8e1df192fdf7d87ee69d94fc8e3c8b638874848d3f26eae7359643c42a4f0d', '95a0cd0a98dd6b98b57f68c89cfced5a12c27073826b7a6363d5da88564ade91', '2026-08-10 14:07:33'),
(7, '06f512d59f5d3d7c5e08d846954301ef4d0633a39edfe9413931393686ad4d30', 3, '10', '95a0cd0a98dd6b98b57f68c89cfced5a12c27073826b7a6363d5da88564ade91', '7663081e45fa581943e714372ab0c53b5a2e43156f044ec9a77a68852116887c', '2026-08-10 14:08:33'),
(8, 'bffd92b8ba93a6cd39cf5ac88d61f051cd0957b557409ef57c14ec1deda164ed', 3, '10', '7663081e45fa581943e714372ab0c53b5a2e43156f044ec9a77a68852116887c', '996086718c6ce2de9b97deaba3b20dbbdb43db4ded26e432429b6a9df25e0402', '2026-08-10 14:09:33');

ALTER TABLE ledger_blocks AUTO_INCREMENT = 9;
