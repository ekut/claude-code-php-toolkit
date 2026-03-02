<?php

declare(strict_types=1);

/**
 * Continuous Learning - Session Evaluator
 *
 * Runs on Stop hook to signal Claude for pattern extraction from the session.
 *
 * Usage:
 *   php evaluate-session.php
 *
 * Hook config (in ~/.claude/settings.json):
 * {
 *   "hooks": {
 *     "Stop": [{
 *       "matcher": "*",
 *       "hooks": [{ "type": "command", "command": "php path/to/evaluate-session.php" }]
 *     }]
 *   }
 * }
 */

function loadEvalConfig(): array
{
    $defaults = [
        'evaluate' => [
            'enabled' => true,
            'min_session_length' => 10,
            'learned_skills_path' => '~/.claude/learning/skills/learned/',
        ],
        'patterns_to_detect' => [
            'error_resolution',
            'user_corrections',
            'composer_resolution',
            'phpstan_fixes',
            'framework_quirks',
            'testing_patterns',
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
    $home = getenv('HOME') ?: (getenv('USERPROFILE') ?: '');
    $userConfig = $home . '/.claude/learning/config.json';
    if (file_exists($userConfig)) {
        $parsed = json_decode(file_get_contents($userConfig), true);
        if (is_array($parsed)) {
            $defaults = array_replace_recursive($defaults, $parsed);
        }
    }

    return $defaults;
}

function expandTildePath(string $path): string
{
    if (str_starts_with($path, '~/')) {
        $home = getenv('HOME') ?: (getenv('USERPROFILE') ?: '');
        return $home . substr($path, 1);
    }
    return $path;
}

function getTranscriptPath(string $stdinData): string
{
    // Try stdin JSON first
    if ($stdinData !== '') {
        $data = json_decode($stdinData, true);
        if (is_array($data) && isset($data['transcript_path'])) {
            return (string) $data['transcript_path'];
        }
    }

    // Fallback to env var
    return getenv('CLAUDE_TRANSCRIPT_PATH') ?: '';
}

function countUserMessages(string $transcriptPath): int
{
    if (!file_exists($transcriptPath)) {
        return 0;
    }

    $count = 0;
    $handle = fopen($transcriptPath, 'r');
    if ($handle === false) {
        return 0;
    }

    while (($line = fgets($handle)) !== false) {
        if (str_contains($line, '"type":"user"') || str_contains($line, '"type": "user"')) {
            $count++;
        }
    }

    fclose($handle);
    return $count;
}

// --- Entry point ---

if (PHP_SAPI !== 'cli') {
    exit(0);
}

// Check if disabled
$home = getenv('HOME') ?: (getenv('USERPROFILE') ?: '');
$disabledFlag = $home . '/.claude/learning/disabled';
if (file_exists($disabledFlag)) {
    exit(0);
}

// Load config
$config = loadEvalConfig();
if (!($config['evaluate']['enabled'] ?? true)) {
    exit(0);
}

$minSessionLength = (int) ($config['evaluate']['min_session_length'] ?? 10);
$learnedSkillsPath = expandTildePath($config['evaluate']['learned_skills_path'] ?? '~/.claude/learning/skills/learned/');
$patternsToDetect = $config['patterns_to_detect'] ?? [];

// Ensure learned skills directory exists
if (!is_dir($learnedSkillsPath)) {
    mkdir($learnedSkillsPath, 0755, true);
}

// Read stdin and get transcript path
$stdinData = '';
if (!posix_isatty(STDIN)) {
    $stdinData = trim(stream_get_contents(STDIN) ?: '');
}

$transcriptPath = getTranscriptPath($stdinData);

if ($transcriptPath === '' || !file_exists($transcriptPath)) {
    exit(0);
}

// Count user messages
$messageCount = countUserMessages($transcriptPath);

// Skip short sessions
if ($messageCount < $minSessionLength) {
    if ($messageCount > 0) {
        fwrite(STDERR, "[ContinuousLearning] Session too short ({$messageCount} messages), skipping\n");
    }
    exit(0);
}

// Signal to Claude that session should be evaluated
fwrite(STDERR, "[ContinuousLearning] Session has {$messageCount} messages — evaluate for extractable patterns\n");

if ($patternsToDetect !== []) {
    fwrite(STDERR, '[ContinuousLearning] Look for: ' . implode(', ', $patternsToDetect) . "\n");
}

fwrite(STDERR, "[ContinuousLearning] Save learned skills to: {$learnedSkillsPath}\n");

exit(0);
