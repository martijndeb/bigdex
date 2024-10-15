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

## Note

Ensure that your query log and database dump files are in a format compatible with the PHPMyAdmin SQL Parser. The query log should contain SELECT statements, and the database dump should include CREATE TABLE and ALTER TABLE statements.