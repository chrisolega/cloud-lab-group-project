CREATE DATABASE IF NOT EXISTS campus_navigator CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE campus_navigator;

CREATE TABLE schools (
id INT AUTO_INCREMENT PRIMARY KEY,
name VARCHAR(150) NOT NULL,
short_name VARCHAR(20) NOT NULL
);

CREATE TABLE admins (
id INT AUTO_INCREMENT PRIMARY KEY,
username VARCHAR(50) NOT NULL UNIQUE,
password VARCHAR(255) NOT NULL
);

CREATE TABLE managers (
id INT AUTO_INCREMENT PRIMARY KEY,
username VARCHAR(50) NOT NULL,
password VARCHAR(255) NOT NULL,
school_id INT NOT NULL,
UNIQUE KEY uniq_manager (username, school_id),
FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE
);

CREATE TABLE students (
id INT AUTO_INCREMENT PRIMARY KEY,
index_number VARCHAR(50) NOT NULL,
school_id INT NOT NULL,
UNIQUE KEY uniq_student (index_number, school_id),
FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE
);

CREATE TABLE events (
id INT AUTO_INCREMENT PRIMARY KEY,
school_id INT NOT NULL,
building_name VARCHAR(150) NOT NULL,
building_location VARCHAR(255) NOT NULL,
building_image VARCHAR(500) NOT NULL,
event_info TEXT NOT NULL,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE
);

INSERT INTO schools (name, short_name) VALUES
('Ghana Communication Technology University', 'GCTU'),
('University of Ghana', 'UG'),
('Kwame Nkrumah University of Science and Technology', 'KNUST'),
('University of Mines and Technology', 'UMaT'),
('Accra Technical University', 'ATU'),
('Koforidua Technical University', 'KTU');

INSERT INTO admins (username, password) VALUES
('admin', '$2y$10$HZ.IUsckCYeNNX5qqV.gCuxT789R.6NSLwcdAOvu5ARtHajcp8ufK');

INSERT INTO managers (username, password, school_id) VALUES
('gctu_manager', '$2y$10$O1J8ITcHF0aMhCOieFHEdu2P.BLs0c7cl9RNqlMrRmsSjNCeBYdIC', 1),
('ug_manager', '$2y$10$O1J8ITcHF0aMhCOieFHEdu2P.BLs0c7cl9RNqlMrRmsSjNCeBYdIC', 2),
('knust_manager', '$2y$10$O1J8ITcHF0aMhCOieFHEdu2P.BLs0c7cl9RNqlMrRmsSjNCeBYdIC', 3),
('umat_manager', '$2y$10$O1J8ITcHF0aMhCOieFHEdu2P.BLs0c7cl9RNqlMrRmsSjNCeBYdIC', 4),
('atu_manager', '$2y$10$O1J8ITcHF0aMhCOieFHEdu2P.BLs0c7cl9RNqlMrRmsSjNCeBYdIC', 5),
('ktu_manager', '$2y$10$O1J8ITcHF0aMhCOieFHEdu2P.BLs0c7cl9RNqlMrRmsSjNCeBYdIC', 6);

INSERT INTO students (index_number, school_id) VALUES
('2425402594', 1),
('2425402001', 1),
('10945671', 2),
('10945672', 2),
('20867501', 3),
('20867502', 3),
('UM1002201', 4),
('UM1002202', 4),
('ATU223301', 5),
('ATU223302', 5),
('KTU556601', 6),
('KTU556602', 6);
