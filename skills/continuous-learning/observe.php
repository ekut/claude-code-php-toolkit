<?php

declare(strict_types=1);

/**
 * Continuous Learning - Observation Hook
 *
 * Captures tool use events for pattern analysis.
 * Claude Code passes hook data via stdin as JSON.
 *
 * Usage:
 *   php observe.php post      # PostToolUse hook
 *   php observe.php failure   # PostToolUseFailure hook
 *
 * Hook config (in ~/.claude/settings.json):
 * {
 *   "hooks": {
 *     "PostToolUse": [{
 *       "matcher": "*",
 *       "hooks": [{ "type": "command", "command": "php path/to/observe.php post" }]
 *     }],
 *     "PostToolUseFailure": [{
 *       "matcher": "*",
 *       "hooks": [{ "type": "command", "command": "php path/to/observe.php failure" }]
 *     }]
 *   }
 * }
 */

function loadConfig(): array
{
    $defaults = [
        'observation' => [
            'enabled' => true,
            'store_path' => '~/.claude/learning/observations.jsonl',
            'max_file_size_mb' => 10,
        ],
    ];

    // Load config from skill directory
    $skillConfig = __DIR__ . '/config.json';
    if (file_exists($skillConfig)) {
        $parsed = json_decode(file_get_contents($skillConfig), true);
        if (is_array($parsed)) {
            $defaults = array_replace_recursive($defaults, $parsed);
        }
    }

    // User override
    $userConfig = expandTilde('~/.claude/learning/config.json');
    if (file_exists($userConfig)) {
        $parsed = json_decode(file_get_contents($userConfig), true);
        if (is_array($parsed)) {
            $defaults = array_replace_recursive($defaults, $parsed);
        }
    }

    return $defaults;
}

function expandTilde(string $path): string
{
    if (str_starts_with($path, '~/')) {
        $home = getenv('HOME') ?: (getenv('USERPROFILE') ?: '');
        return $home . substr($path, 1);
    }
    return $path;
}

function readStdin(): string
{
    $input = '';
    if (!posix_isatty(STDIN)) {
        $input = stream_get_contents(STDIN) ?: '';
    }
    return trim($input);
}

function parseHookInput(string $raw): ?array
{
    if ($raw === '') {
        return null;
    }

    $data = json_decode($raw, true);
    if (!is_array($data)) {
        return null;
    }

    return $data;
}

function truncateValue(mixed $value, int $maxLength = 5000): string
{
    if (is_array($value) || is_object($value)) {
        $str = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    } else {
        $str = (string) $value;
    }

    if (strlen($str) > $maxLength) {
        return substr($str, 0, $maxLength) . '...[truncated]';
    }

    return $str;
}

function buildObservation(array $data, string $hookPhase): array
{
    $event = $hookPhase === 'failure' ? 'tool_failure' : 'tool_complete';

    $toolName = $data['tool_name'] ?? $data['tool'] ?? 'unknown';
    $sessionId = $data['session_id'] ?? 'unknown';
    $toolInput = $data['tool_input'] ?? $data['input'] ?? '';
    $toolOutput = $data['tool_output'] ?? $data['output'] ?? '';

    $observation = [
        'timestamp' => date('c'),
        'event' => $event,
        'session_id' => $sessionId,
        'tool' => $toolName,
        'input' => truncateValue($toolInput),
        'output' => truncateValue($toolOutput),
    ];

    $cwd = getcwd();
    if ($cwd !== false) {
        $observation['cwd'] = $cwd;
    }

    return $observation;
}

function ensureDirectory(string $path): void
{
    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

function rotateIfNeeded(string $filePath, int $maxSizeMb): void
{
    if (!file_exists($filePath)) {
        return;
    }

    $sizeBytes = filesize($filePath);
    if ($sizeBytes === false || $sizeBytes < $maxSizeMb * 1024 * 1024) {
        return;
    }

    $archiveDir = dirname($filePath) . '/observations.archive';
    if (!is_dir($archiveDir)) {
        mkdir($archiveDir, 0755, true);
    }

    $archiveName = sprintf(
        '%s/observations-%s.jsonl',
        $archiveDir,
        date('Ymd-His')
    );

    rename($filePath, $archiveName);
}

function writeObservation(array $observation, string $filePath): void
{
    $line = json_encode($observation, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
    file_put_contents($filePath, $line, FILE_APPEND | LOCK_EX);
}

// --- Entry point ---

if (PHP_SAPI !== 'cli') {
    exit(0);
}

// Determine hook phase from CLI argument
$hookPhase = $argv[1] ?? 'post';
if (!in_array($hookPhase, ['post', 'failure'], true)) {
    $hookPhase = 'post';
}

// Check if disabled
$disabledFlag = expandTilde('~/.claude/learning/disabled');
if (file_exists($disabledFlag)) {
    exit(0);
}

// Load config
$config = loadConfig();
if (!($config['observation']['enabled'] ?? true)) {
    exit(0);
}

// Read and parse stdin
$raw = readStdin();
$data = parseHookInput($raw);

if ($data === null) {
    // No input or invalid JSON — silently exit
    if ($raw !== '') {
        // Log parse error for debugging
        $storePath = expandTilde($config['observation']['store_path']);
        ensureDirectory($storePath);
        $errorEntry = json_encode([
            'timestamp' => date('c'),
            'event' => 'parse_error',
            'raw' => substr($raw, 0, 2000),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
        file_put_contents($storePath, $errorEntry, FILE_APPEND | LOCK_EX);
    }
    exit(0);
}

// Build observation
$observation = buildObservation($data, $hookPhase);

// Write to store
$storePath = expandTilde($config['observation']['store_path']);
$maxSizeMb = (int) ($config['observation']['max_file_size_mb'] ?? 10);

ensureDirectory($storePath);
rotateIfNeeded($storePath, $maxSizeMb);
writeObservation($observation, $storePath);

exit(0);
