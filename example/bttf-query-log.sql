SELECT * FROM characters WHERE last_name = 'McFly';
SELECT * FROM characters WHERE first_name = 'Marty' AND last_name = 'McFly';
SELECT * FROM characters WHERE birth_year > 1950;
SELECT * FROM characters WHERE occupation = 'Scientist';
SELECT * FROM characters WHERE first_name LIKE 'E%' AND birth_year < 1950;

SELECT * FROM time_machines WHERE name = 'DeLorean';
SELECT * FROM time_machines WHERE fuel = 'Plutonium';
SELECT * FROM time_machines WHERE max_year > 2000 AND min_year < 1900;

SELECT * FROM time_travels WHERE character_id = 1;
SELECT * FROM time_travels WHERE departure_year = 1985 AND arrival_year = 1955;
SELECT t.*, c.first_name, c.last_name
FROM time_travels t
JOIN characters c ON t.character_id = c.id
WHERE t.purpose LIKE '%save%';

SELECT * FROM inventions WHERE inventor_id = 2;
SELECT * FROM inventions WHERE year_created = 1955;
SELECT i.*, c.first_name, c.last_name
FROM inventions i
JOIN characters c ON i.inventor_id = c.id
WHERE i.name = 'Flux Capacitor';

SELECT c.first_name, c.last_name, t.departure_year, t.arrival_year, m.name AS time_machine
FROM characters c
JOIN time_travels t ON c.id = t.character_id
JOIN time_machines m ON t.time_machine_id = m.id
WHERE c.last_name = 'McFly' AND t.departure_year = 1985;

SELECT c.first_name, c.last_name, i.name AS invention, i.year_created
FROM characters c
JOIN inventions i ON c.id = i.inventor_id
WHERE c.occupation = 'Scientist' AND i.year_created < c.birth_year;
