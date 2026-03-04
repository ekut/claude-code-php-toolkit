#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Markdown table formatter.
 *
 * Aligns columns in markdown tables by padding cells to equal width.
 * Handles emoji/unicode via mb_strwidth(), preserves alignment markers,
 * and skips tables inside fenced code blocks.
 *
 * Usage: php format-md-tables.php <file.md>
 */

if ($argc < 2) {
    fwrite(STDERR, "Usage: php format-md-tables.php <file.md>\n");
    exit(1);
}

$filePath = $argv[1];

if (!is_file($filePath) || !is_readable($filePath)) {
    exit(0);
}

$content = file_get_contents($filePath);
if ($content === false || $content === '') {
    exit(0);
}

$lines = explode("\n", $content);
$result = [];
$i = 0;
$total = count($lines);
$inCodeBlock = false;

while ($i < $total) {
    $line = $lines[$i];

    // Track fenced code blocks (``` or ~~~)
    if (preg_match('/^(`{3,}|~{3,})/', $line)) {
        $inCodeBlock = !$inCodeBlock;
        $result[] = $line;
        $i++;
        continue;
    }

    if ($inCodeBlock) {
        $result[] = $line;
        $i++;
        continue;
    }

    // Detect start of a table block: line contains | and is not just whitespace
    if (!isTableRow($line)) {
        $result[] = $line;
        $i++;
        continue;
    }

    // Collect consecutive table rows
    $tableLines = [];
    while ($i < $total && isTableRow($lines[$i])) {
        $tableLines[] = $lines[$i];
        $i++;
    }

    // A valid table needs at least 2 rows (header + separator)
    if (count($tableLines) < 2 || !isSeparatorRow($tableLines[1])) {
        foreach ($tableLines as $tl) {
            $result[] = $tl;
        }
        continue;
    }

    $formatted = formatTable($tableLines);
    foreach ($formatted as $fl) {
        $result[] = $fl;
    }
}

$output = implode("\n", $result);

if ($output !== $content) {
    file_put_contents($filePath, $output);
}

// --- Functions ---

function isTableRow(string $line): bool
{
    $trimmed = trim($line);
    return str_starts_with($trimmed, '|') && str_ends_with($trimmed, '|');
}

function isSeparatorRow(string $line): bool
{
    $trimmed = trim($line);
    // Separator row: | followed by combinations of -, :, |, and spaces
    return (bool) preg_match('/^\|[\s:-]+(\|[\s:-]+)*\|$/', $trimmed);
}

function parseCells(string $line): array
{
    $trimmed = trim($line);
    // Remove leading and trailing |
    $inner = substr($trimmed, 1, -1);
    return array_map('trim', explode('|', $inner));
}

function cellWidth(string $cell): int
{
    return mb_strwidth($cell, 'UTF-8');
}

function formatTable(array $lines): array
{
    $rows = array_map('parseCells', $lines);
    $colCount = max(array_map('count', $rows));

    // Normalize all rows to same column count
    foreach ($rows as &$row) {
        while (count($row) < $colCount) {
            $row[] = '';
        }
    }
    unset($row);

    // Detect alignment from separator row (index 1)
    $alignments = [];
    $sepCells = $rows[1];
    for ($c = 0; $c < $colCount; $c++) {
        $sep = trim($sepCells[$c] ?? '---');
        $left = str_starts_with($sep, ':');
        $right = str_ends_with($sep, ':');
        if ($left && $right) {
            $alignments[$c] = 'center';
        } elseif ($right) {
            $alignments[$c] = 'right';
        } else {
            $alignments[$c] = 'left';
        }
    }

    // Calculate max visual width per column (excluding separator row)
    $maxWidths = array_fill(0, $colCount, 3); // minimum width of 3 for ---
    foreach ($rows as $ri => $row) {
        if ($ri === 1) {
            continue; // skip separator
        }
        for ($c = 0; $c < $colCount; $c++) {
            $w = cellWidth($row[$c]);
            if ($w > $maxWidths[$c]) {
                $maxWidths[$c] = $w;
            }
        }
    }

    // Rebuild rows
    $result = [];
    foreach ($rows as $ri => $row) {
        if ($ri === 1) {
            // Rebuild separator row
            $parts = [];
            for ($c = 0; $c < $colCount; $c++) {
                $w = $maxWidths[$c];
                $align = $alignments[$c];
                if ($align === 'center') {
                    $parts[] = ':' . str_repeat('-', $w - 2) . ':';
                } elseif ($align === 'right') {
                    $parts[] = str_repeat('-', $w - 1) . ':';
                } else {
                    $parts[] = str_repeat('-', $w);
                }
            }
            $result[] = '| ' . implode(' | ', $parts) . ' |';
        } else {
            // Pad cells
            $parts = [];
            for ($c = 0; $c < $colCount; $c++) {
                $cell = $row[$c];
                $visualWidth = cellWidth($cell);
                $padding = $maxWidths[$c] - $visualWidth;
                $parts[] = $cell . str_repeat(' ', $padding);
            }
            $result[] = '| ' . implode(' | ', $parts) . ' |';
        }
    }

    return $result;
}
