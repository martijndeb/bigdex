#!/usr/bin/env php
<?php

require_once 'vendor/autoload.php';

use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statements\CreateStatement;
use PhpMyAdmin\SqlParser\Statements\SelectStatement;
use PhpMyAdmin\SqlParser\Components\Expression;
use PhpMyAdmin\SqlParser\Components\Condition;

if ($argc < 3) {
	die("Usage: php analyzer.php <query_log.sql> <database_dump.sql> [prefix_length]\n");
}

$queryLogFile = $argv[1];
$dbDumpFile = $argv[2];
$prefixLength = $argv[3] ?? 10;

function parseFile($filename)
{
	$content = file_get_contents($filename);
	$parser = new Parser($content);
	return $parser->statements;
}

function extractColumnNames($expr, $tableColumns)
{
	$columns = [];
	if ($expr instanceof Expression && $expr->column !== null) {
		if (in_array($expr->column, $tableColumns)) {
			$columns[] = $expr->column;
		}
	} elseif ($expr instanceof Condition && isset($expr->identifiers)) {
		foreach ($expr->identifiers as $identifier) {
			$column = strpos($identifier, '.') !== false ? explode('.', $identifier)[1] : $identifier;
			if (in_array($column, $tableColumns)) {
				$columns[] = $column;
			}
		}
	} elseif (is_array($expr)) {
		foreach ($expr as $subExpr) {
			$columns = array_merge($columns, extractColumnNames($subExpr, $tableColumns));
		}
	}
	return array_unique(array_filter($columns));
}

function analyzeIndexes($queryLogStatements, $dbDumpStatements)
{
	$tables = [];
	$indexUsage = [];
	$queryComplexity = [];

	foreach ($dbDumpStatements as $statement) {
		if ($statement instanceof CreateStatement) {
			$tableName = $statement->name->table ?? null;
			if ($tableName === null) continue;

			$tables[$tableName] = ['columns' => [], 'indexes' => []];
			foreach ($statement->fields as $field) {
				$type = $field->type->name ?? 'unknown';
				$tables[$tableName]['columns'][$field->name] = $type;
				if ($field->key) {
					$tables[$tableName]['indexes'][] = $field->name;
				}
			}
		}
	}

	foreach ($queryLogStatements as $statement) {
		if ($statement instanceof SelectStatement) {
			$tableName = $statement->from[0]->table ?? null;
			if ($tableName === null || !isset($tables[$tableName])) continue;

			$tableColumns = array_keys($tables[$tableName]['columns']);
			$complexityScore = 0;

			// Check for JOINs
			$complexityScore += isset($statement->join) ? count($statement->join) : 0;

			// Check for WHERE conditions
			$whereColumns = extractColumnNames($statement->where, $tableColumns);
			$complexityScore += count($whereColumns);

			// Safely check for ORDER BY
			$orderByColumns = isset($statement->orderBy) && is_countable($statement->orderBy) ? extractColumnNames($statement->orderBy, $tableColumns) : [];
			$complexityScore += count($orderByColumns);

			// Safely check for GROUP BY
			$groupByColumns = isset($statement->groupBy) && is_countable($statement->groupBy) ? extractColumnNames($statement->groupBy, $tableColumns) : [];
			$complexityScore += count($groupByColumns);

			// Safely check for subqueries
			$subqueryCount = isset($statement->subquery) && is_countable($statement->subquery) ? count($statement->subquery) : 0;
			$complexityScore += $subqueryCount > 0 ? 5 : 0;

			$queryComplexity[$tableName] = ($queryComplexity[$tableName] ?? 0) + $complexityScore;

			$usageType = [
				'filter' => array_merge($whereColumns, extractColumnNames($statement->join, $tableColumns)),
				'sorting' => array_merge($orderByColumns, $groupByColumns),
			];

			foreach ($usageType as $type => $columns) {
				foreach ($columns as $column) {
					if (!in_array($column, $tables[$tableName]['indexes'])) {
						$indexUsage[$tableName][$type][$column] = ($indexUsage[$tableName][$type][$column] ?? 0) + 1;
					}
				}
			}
		}
	}

	return compact('indexUsage', 'queryComplexity', 'tables');
}

function generateCreateIndexQuery($table, $columns, $tables, $prefixLength)
{
	$uniqueColumns = array_unique($columns);
	$indexName = "idx_" . $table . "_" . substr(md5(implode('_', $uniqueColumns)), 0, 8);
	$columnList = implode(", ", array_map(function ($col) use ($tables, $table, $prefixLength) {
		$type = strtolower($tables[$table]['columns'][$col] ?? 'unknown');
		if (in_array($type, ['char', 'varchar', 'text'])) {
			return "$col(" . min($prefixLength, 20) . ")";
		}
		return $col;
	}, $uniqueColumns));
	return "ALTER TABLE $table ADD INDEX $indexName ($columnList);";
}

$queryLogStatements = parseFile($queryLogFile);
$dbDumpStatements = parseFile($dbDumpFile);
$analysis = analyzeIndexes($queryLogStatements, $dbDumpStatements);

echo "Query Complexity Scores:\n";
foreach ($analysis['queryComplexity'] as $table => $score) {
	echo "  Table: $table, Complexity Score: $score\n";
}

echo "\nIndex Suggestions:\n";
$totalSuggestions = 0;
foreach ($analysis['indexUsage'] as $table => $types) {
	foreach ($types as $type => $columns) {
		echo "  [$type indexes for $table]:\n";
		foreach ($columns as $column => $count) {
			echo "    - Column: $column (Usage: $count)\n";
			echo "      " . generateCreateIndexQuery($table, [$column], $analysis['tables'], $prefixLength) . "\n";
			$totalSuggestions++;
		}
	}
}

echo "\n🔍 Summary Report:\n";
echo "Total Tables Analyzed: " . count($analysis['tables']) . "\n";
echo "Total Index Suggestions: $totalSuggestions\n";

$mostComplexTable = array_keys($analysis['queryComplexity'], max($analysis['queryComplexity']))[0];
echo "Most Complex Query Table: $mostComplexTable (Score: " . max($analysis['queryComplexity']) . ")\n";
