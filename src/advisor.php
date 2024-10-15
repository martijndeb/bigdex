#!/usr/bin/env php
<?php

require_once 'vendor/autoload.php';

use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statements\CreateStatement;
use PhpMyAdmin\SqlParser\Statements\AlterStatement;
use PhpMyAdmin\SqlParser\Statements\SelectStatement;
use PhpMyAdmin\SqlParser\Components\Expression;
use PhpMyAdmin\SqlParser\Components\Condition;

if ($argc !== 3) {
	die("Usage: php analyzer.php <query_log.sql> <database_dump.sql>\n");
}

$queryLogFile = $argv[1];
$dbDumpFile = $argv[2];

function parseFile($filename)
{
	$content = file_get_contents($filename);
	$parser = new Parser($content);
	return $parser->statements;
}

function extractColumnNames($expr, $tableColumns)
{
	$columns = [];

	if ($expr instanceof Expression) {
		if ($expr->column !== null && in_array($expr->column, $tableColumns)) {
			$columns[] = $expr->column;
		}
	} elseif ($expr instanceof Condition) {
		if (isset($expr->identifiers)) {
			foreach ($expr->identifiers as $identifier) {
				if (strpos($identifier, '.') !== false) {
					list(, $column) = explode('.', $identifier);
				} else {
					$column = $identifier;
				}
				if (in_array($column, $tableColumns)) {
					$columns[] = $column;
				}
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
	$missingIndexes = [];
	$compoundIndexSuggestions = [];
	$queryStats = [];
	$optimizationSuggestions = [];

	foreach ($dbDumpStatements as $statement) {
		if ($statement instanceof CreateStatement) {
			$tableName = $statement->name->table ?? null;

			if ($tableName === null) {
				continue;
			}

			$tables[$tableName] = [
				'columns' => [],
				'indexes' => []
			];

			foreach ($statement->fields as $field) {
				$tables[$tableName]['columns'][$field->name] = $field->type->name ?? 'unknown';
				if ($field->key) {
					$tables[$tableName]['indexes'][] = $field->name;
				}
			}
		} elseif ($statement instanceof AlterStatement) {
			$tableName = $statement->table->table ?? null;

			if ($tableName === null) {
				continue;
			}

			if (!isset($tables[$tableName])) {
				$tables[$tableName] = ['columns' => [], 'indexes' => []];
			}

			foreach ($statement->altered as $altered) {
				if ($altered->options->has('ADD') && $altered->options->has('INDEX')) {
					$indexColumns = [];

					if ($altered->field !== null && $altered->field->columns !== null) {
						$indexColumns = array_map(function ($col) {
							return $col->name ?? null;
						}, $altered->field->columns);
						$indexColumns = array_filter($indexColumns);
					}

					$tables[$tableName]['indexes'] = array_merge($tables[$tableName]['indexes'], $indexColumns);
				}
			}
		}
	}

	foreach ($queryLogStatements as $statement) {
		if ($statement instanceof SelectStatement) {
			$tableName = $statement->from[0]->table ?? null;

			if ($tableName === null || !isset($tables[$tableName])) {
				continue;
			}

			$tableColumns = array_keys($tables[$tableName]['columns']);
			$whereColumns = extractColumnNames($statement->where, $tableColumns);
			$joinColumns = [];

			if (!empty($statement->join)) {
				foreach ($statement->join as $join) {
					if ($join->on !== null) {
						$joinColumns = array_merge($joinColumns, extractColumnNames($join->on, $tableColumns));
					}
				}
			}

			$allColumns = array_merge($whereColumns, $joinColumns);

			foreach ($allColumns as $column) {
				if (!in_array($column, $tables[$tableName]['indexes'])) {
					$missingIndexes[$tableName][$column] = ($missingIndexes[$tableName][$column] ?? 0) + 1;
				}
			}

			if (count($allColumns) > 1) {
				$compoundKey = implode('_', $allColumns);
				$compoundIndexSuggestions[$tableName][$compoundKey] = ($compoundIndexSuggestions[$tableName][$compoundKey] ?? 0) + 1;
			}

			$queryStats[$tableName] = ($queryStats[$tableName] ?? 0) + 1;
		}
	}

	$optimizedIndexes = [];
	foreach ($missingIndexes as $table => $columns) {
		$optimizedIndexes[$table] = [];
		$singleIndexes = [];
		$compoundIndexes = [];

		foreach ($compoundIndexSuggestions[$table] ?? [] as $compound => $count) {
			$compoundColumns = removeDuplicatesPreserveOrder(explode('_', $compound));
			$compoundIndexes[] = ['columns' => $compoundColumns, 'count' => $count];
		}

		foreach ($columns as $column => $count) {
			$singleIndexes[] = ['columns' => [$column], 'count' => $count];
		}

		usort($compoundIndexes, function ($a, $b) {
			$colDiff = count($b['columns']) - count($a['columns']);
			return $colDiff !== 0 ? $colDiff : $b['count'] - $a['count'];
		});

		foreach ($compoundIndexes as $index) {
			$isOverlapped = false;

			foreach ($optimizedIndexes[$table] as $existingIndex) {
				if (count(array_intersect($index['columns'], $existingIndex['columns'])) === count($index['columns'])) {
					$isOverlapped = true;
					break;
				}
			}

			if (!$isOverlapped) {
				$optimizedIndexes[$table][] = $index;
			}
		}

		foreach ($singleIndexes as $index) {
			$isIncluded = false;
			foreach ($optimizedIndexes[$table] as $existingIndex) {
				if (in_array($index['columns'][0], $existingIndex['columns'])) {
					$isIncluded = true;
					break;
				}
			}
			if (!$isIncluded) {
				$optimizedIndexes[$table][] = $index;
			}
		}
	}

	$impactScores = [];
	foreach ($optimizedIndexes as $table => $indexes) {
		foreach ($indexes as $index) {
			$impactScores[] = [
				'table' => $table,
				'columns' => $index['columns'],
				'type' => count($index['columns']) > 1 ? 'compound' : 'single',
				'count' => $index['count'],
				'impact' => $index['count'] / $queryStats[$table]
			];
		}
	}

	usort($impactScores, function ($a, $b) {
		return $b['impact'] <=> $a['impact'];
	});

	return [
		'impactScores' => $impactScores,
		'optimizationSuggestions' => $optimizationSuggestions,
		'queryStats' => $queryStats,
		'tables' => $tables,
	];
}

function removeDuplicatesPreserveOrder($array)
{
	$result = [];
	$seen = [];
	foreach ($array as $item) {
		if (!isset($seen[$item])) {
			$seen[$item] = true;
			$result[] = $item;
		}
	}
	return $result;
}

function generateCreateIndexQuery($table, $columns, $tables)
{
	$uniqueColumns = removeDuplicatesPreserveOrder($columns);
	$indexName = "idx_" . $table . "_" . implode("_", $uniqueColumns);
	$columnList = implode(", ", array_map(function ($col) use ($tables, $table) {
		$type = $tables[$table]['columns'][$col] ?? 'unknown';

		if (in_array(strtolower($type), ['char', 'varchar', 'text'])) {
			return "$col(10)";
		}
		return $col;
	}, $uniqueColumns));

	return "ALTER TABLE $table ADD INDEX $indexName ($columnList);";
}

$queryLogStatements = parseFile($queryLogFile);
$dbDumpStatements = parseFile($dbDumpFile);

$analysis = analyzeIndexes($queryLogStatements, $dbDumpStatements);

echo "Query Statistics:\n";
foreach ($analysis['queryStats'] as $table => $count) {
	echo "  Table: $table, Query Count: $count\n";
}

echo "\nOptimization Suggestions:\n";
foreach ($analysis['optimizationSuggestions'] as $suggestion) {
	echo "  - $suggestion\n";
}

echo "\nIndex Suggestions (sorted by potential impact):\n";
foreach ($analysis['impactScores'] as $score) {
	$impactPercentage = round($score['impact'] * 100, 2);
	echo "  Table: {$score['table']}\n";
	echo "    " . ($score['type'] === 'single' ? "Column" : "Compound Index") . ": " . implode(", ", $score['columns']) . "\n";
	echo "    Occurrences: {$score['count']}\n";
	echo "    Potential Impact: {$impactPercentage}%\n";
	echo "\n";
}

echo "\nSuggested Queries:\n";
foreach ($analysis['impactScores'] as $score) {
	echo generateCreateIndexQuery($score['table'], $score['columns'], $analysis['tables']) . "\n";
}