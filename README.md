# SQL Index Analyzer

This PHP script analyzes SQL query logs and database dumps to suggest optimal indexes for improving database performance.

## Installation

1. Ensure you have PHP 7.4 or later installed on your system.

2. Clone this repository or download the `advisor.php` script.

3. Install Composer if you haven't already. Visit https://getcomposer.org/ for installation instructions.

4. Navigate to the directory containing the `advisor.php` script and run:

   ```
   composer install
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
Query Complexity Scores:
  Table: characters, Complexity Score: 13
  Table: time_machines, Complexity Score: 4
  Table: time_travels, Complexity Score: 5
  Table: inventions, Complexity Score: 4

Index Suggestions:
  [filter indexes for characters]:
    - Column: last_name (Usage: 3)
      ALTER TABLE characters ADD INDEX idx_characters_7d4553c0 (last_name(10));
    - Column: first_name (Usage: 2)
      ALTER TABLE characters ADD INDEX idx_characters_2a034e9d (first_name(10));
    - Column: birth_year (Usage: 3)
      ALTER TABLE characters ADD INDEX idx_characters_ac95d840 (birth_year);
    - Column: occupation (Usage: 2)
      ALTER TABLE characters ADD INDEX idx_characters_8b7eee43 (occupation(10));
  [filter indexes for time_machines]:
    - Column: name (Usage: 1)
      ALTER TABLE time_machines ADD INDEX idx_time_machines_b068931c (name(10));
    - Column: fuel (Usage: 1)
      ALTER TABLE time_machines ADD INDEX idx_time_machines_7c4e4db5 (fuel(10));
    - Column: max_year (Usage: 1)
      ALTER TABLE time_machines ADD INDEX idx_time_machines_f7612836 (max_year);
    - Column: min_year (Usage: 1)
      ALTER TABLE time_machines ADD INDEX idx_time_machines_34eaefd3 (min_year);
  [filter indexes for time_travels]:
    - Column: character_id (Usage: 1)
      ALTER TABLE time_travels ADD INDEX idx_time_travels_bf21153f (character_id);
    - Column: departure_year (Usage: 1)
      ALTER TABLE time_travels ADD INDEX idx_time_travels_c2bdfa49 (departure_year);
    - Column: arrival_year (Usage: 1)
      ALTER TABLE time_travels ADD INDEX idx_time_travels_76c8ae7a (arrival_year);
    - Column: purpose (Usage: 1)
      ALTER TABLE time_travels ADD INDEX idx_time_travels_4d066bbb (purpose(10));
  [filter indexes for inventions]:
    - Column: inventor_id (Usage: 1)
      ALTER TABLE inventions ADD INDEX idx_inventions_eb70fe4f (inventor_id);
    - Column: year_created (Usage: 1)
      ALTER TABLE inventions ADD INDEX idx_inventions_73ed77d2 (year_created);
    - Column: name (Usage: 1)
      ALTER TABLE inventions ADD INDEX idx_inventions_b068931c (name(10));

🔍 Summary Report:
Total Tables Analyzed: 4
Total Index Suggestions: 15
Most Complex Query Table: characters (Score: 13)
```

## Note

Ensure that your query log and database dump files are in a format compatible with the PHPMyAdmin SQL Parser. The query log should contain SELECT statements, and the database dump should include CREATE TABLE and ALTER TABLE statements.
