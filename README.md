# SQL Index Analyzer

This PHP script analyzes SQL query logs and database dumps to suggest optimal indexes for improving database performance.

## Installation

1. Ensure you have PHP 7.4 or later installed on your system.

2. Clone this repository or download the `advisor.php` script.

3. Install Composer if you haven't already. Visit https://getcomposer.org/ for installation instructions.

4. Navigate to the directory containing the `advisor.php` script and run:

   ```
   composer require phpmyadmin/sql-parser
   ```

   This will install the required PHPMyAdmin SQL Parser library.

## Usage

Run the script from the command line using the following syntax:

```
php advisor.php <query_log.sql> <database_dump.sql>
```

Where:
- `<query_log.sql>` is the path to your SQL query log file
- `<database_dump.sql>` is the path to your database dump file

For example:

```
php advisor.php ../example/bttf-query-log.sql ../example/bttf-database-dump.sql
```

## Output

The script will analyze your query log and database structure, and output:

1. Query statistics for each table
2. Optimization suggestions (if any)
3. Index suggestions sorted by potential impact
4. SQL queries to create the suggested indexes

### Example result

Using the provided example files, the script outputs the following results.

```
Query Statistics:
  Table: characters, Query Count: 7
  Table: time_machines, Query Count: 3
  Table: time_travels, Query Count: 3
  Table: inventions, Query Count: 3

Optimization Suggestions:

Index Suggestions (sorted by potential impact):
  Table: time_travels
    Column: character_id
    Occurrences: 2
    Potential Impact: 66.67%

  Table: inventions
    Column: inventor_id
    Occurrences: 2
    Potential Impact: 66.67%

  Table: characters
    Column: last_name
    Occurrences: 3
    Potential Impact: 42.86%

  Table: characters
    Column: birth_year
    Occurrences: 3
    Potential Impact: 42.86%

  Table: time_machines
    Compound Index: max, year, min
    Occurrences: 1
    Potential Impact: 33.33%

  Table: time_machines
    Column: name
    Occurrences: 1
    Potential Impact: 33.33%

  Table: time_machines
    Column: fuel
    Occurrences: 1
    Potential Impact: 33.33%

  Table: time_machines
    Column: max_year
    Occurrences: 1
    Potential Impact: 33.33%

  Table: time_machines
    Column: min_year
    Occurrences: 1
    Potential Impact: 33.33%

  Table: time_travels
    Compound Index: departure, year, arrival
    Occurrences: 1
    Potential Impact: 33.33%

  Table: time_travels
    Compound Index: purpose, character, id
    Occurrences: 1
    Potential Impact: 33.33%

  Table: time_travels
    Column: departure_year
    Occurrences: 1
    Potential Impact: 33.33%

  Table: time_travels
    Column: arrival_year
    Occurrences: 1
    Potential Impact: 33.33%

  Table: inventions
    Compound Index: name, inventor, id
    Occurrences: 1
    Potential Impact: 33.33%

  Table: inventions
    Column: year_created
    Occurrences: 1
    Potential Impact: 33.33%

  Table: characters
    Column: first_name
    Occurrences: 2
    Potential Impact: 28.57%

  Table: characters
    Compound Index: first, name, birth, year
    Occurrences: 1
    Potential Impact: 14.29%

  Table: characters
    Compound Index: occupation, birth, year, id
    Occurrences: 1
    Potential Impact: 14.29%

  Table: characters
    Compound Index: first, name, last
    Occurrences: 1
    Potential Impact: 14.29%

  Table: characters
    Compound Index: last, name, id
    Occurrences: 1
    Potential Impact: 14.29%


Suggested Queries:
ALTER TABLE time_travels ADD INDEX idx_time_travels_character_id (character_id);
ALTER TABLE inventions ADD INDEX idx_inventions_inventor_id (inventor_id);
ALTER TABLE characters ADD INDEX idx_characters_last_name (last_name(10));
ALTER TABLE characters ADD INDEX idx_characters_birth_year (birth_year);
ALTER TABLE time_machines ADD INDEX idx_time_machines_max_year_min (max, year, min);
ALTER TABLE time_machines ADD INDEX idx_time_machines_name (name(10));
ALTER TABLE time_machines ADD INDEX idx_time_machines_fuel (fuel(10));
ALTER TABLE time_machines ADD INDEX idx_time_machines_max_year (max_year);
ALTER TABLE time_machines ADD INDEX idx_time_machines_min_year (min_year);
ALTER TABLE time_travels ADD INDEX idx_time_travels_departure_year_arrival (departure, year, arrival);
ALTER TABLE time_travels ADD INDEX idx_time_travels_purpose_character_id (purpose(10), character, id);
ALTER TABLE time_travels ADD INDEX idx_time_travels_departure_year (departure_year);
ALTER TABLE time_travels ADD INDEX idx_time_travels_arrival_year (arrival_year);
ALTER TABLE inventions ADD INDEX idx_inventions_name_inventor_id (name(10), inventor, id);
ALTER TABLE inventions ADD INDEX idx_inventions_year_created (year_created);
ALTER TABLE characters ADD INDEX idx_characters_first_name (first_name(10));
ALTER TABLE characters ADD INDEX idx_characters_first_name_birth_year (first, name, birth, year);
ALTER TABLE characters ADD INDEX idx_characters_occupation_birth_year_id (occupation(10), birth, year, id);
ALTER TABLE characters ADD INDEX idx_characters_first_name_last (first, name, last);
ALTER TABLE characters ADD INDEX idx_characters_last_name_id (last, name, id);
```

## Note

Ensure that your query log and database dump files are in a format compatible with the PHPMyAdmin SQL Parser. The query log should contain SELECT statements, and the database dump should include CREATE TABLE and ALTER TABLE statements.