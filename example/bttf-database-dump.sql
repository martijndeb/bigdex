CREATE TABLE characters (
    id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    birth_year INT,
    occupation VARCHAR(100)
);

ALTER TABLE characters
ADD INDEX idx_last_name (last_name);

CREATE TABLE time_machines (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    type VARCHAR(50),
    fuel VARCHAR(50),
    max_year INT,
    min_year INT
);

CREATE TABLE time_travels (
    id INT PRIMARY KEY AUTO_INCREMENT,
    character_id INT,
    time_machine_id INT,
    departure_date DATE,
    departure_year INT,
    arrival_date DATE,
    arrival_year INT,
    purpose TEXT,
    FOREIGN KEY (character_id) REFERENCES characters(id),
    FOREIGN KEY (time_machine_id) REFERENCES time_machines(id)
);

CREATE TABLE inventions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    inventor_id INT,
    year_created INT,
    purpose TEXT,
    FOREIGN KEY (inventor_id) REFERENCES characters(id)
);

-- Optional Sample Data if you want to test the database
INSERT INTO characters (first_name, last_name, birth_year, occupation) VALUES
('Marty', 'McFly', 1968, 'Student'),
('Emmett', 'Brown', 1920, 'Scientist'),
('Lorraine', 'Baines', 1938, 'Housewife'),
('Biff', 'Tannen', 1937, 'Auto Detailer');

INSERT INTO time_machines (name, type, fuel, max_year, min_year) VALUES
('DeLorean', 'Car', 'Plutonium', 2015, 1885);

INSERT INTO inventions (name, inventor_id, year_created, purpose) VALUES
('Flux Capacitor', 2, 1955, 'Enable time travel');
