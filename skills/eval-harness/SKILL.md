---
name: eval-harness
description: Evaluation framework for Claude Code PHP sessions — eval types, PHP graders, metrics, 4-phase workflow for measuring AI-assisted development quality.
origin: claude-code-php-toolkit
---

# Eval Harness for PHP

A structured evaluation framework for measuring how well Claude Code performs on PHP development tasks. Think of evals as **unit tests for AI** — they define expected behavior, run the AI, and grade the output.

## When to Activate

- Setting up quality benchmarks for Claude Code PHP workflows
- Measuring whether prompt or skill changes improve output quality
- Building regression tests to catch quality degradation
- Comparing different agent configurations or models on PHP tasks

---

## 1. Philosophy — Eval-Driven Development (EDD)

Just as TDD writes tests before code, EDD writes evals before optimizing prompts or skills.

**The EDD loop:**

1. **Define** — Write an eval that captures what "good" looks like
2. **Measure** — Run the eval against current behavior to get a baseline
3. **Improve** — Modify prompts, skills, or agent config
4. **Re-measure** — Run the eval again to verify improvement
5. **Repeat** — Continue until quality targets are met

**Key principle:** If you can't measure it, you can't improve it. Every skill or prompt change should be validated by evals, not vibes.

---

## 2. Eval Types

### Capability Evals

Test whether Claude Code **can** perform a specific PHP task correctly.

| Eval                     | Input                                     | Expected Outcome                                            |
|--------------------------|-------------------------------------------|-------------------------------------------------------------|
| Create Laravel migration | "Add users table with email unique index" | Valid migration file, correct column types, index present   |
| Fix PHPStan error        | PHP file + PHPStan error output           | File passes PHPStan at same or higher level                 |
| Write PHPUnit test       | Service class code                        | Test covers happy path + at least 1 edge case, passes green |
| Refactor to readonly     | PHP 8.1 class with mutable properties     | Properties converted to readonly, tests still pass          |
| API endpoint scaffold    | "CRUD for orders resource"                | Controller, FormRequest, Resource, routes — all valid PHP   |

### Regression Evals

Test that previously working capabilities **still work** after changes.

```
.claude/evals/
├── capability/
│   ├── migration-create.eval.json
│   ├── phpstan-fix.eval.json
│   └── phpunit-write.eval.json
└── regression/
    ├── laravel-route-model-binding.eval.json
    └── doctrine-entity-relationships.eval.json
```

---

## 3. PHP Graders

### Code-Based Graders (Automated)

Use existing PHP tools as objective graders.

| Grader | What It Checks | Pass Criteria | Command |
|--------|----------------|---------------|---------|
| PHPUnit | Functional correctness | All tests pass | `vendor/bin/phpunit --filter={test}` |
| PHPStan | Type safety | Level N with 0 errors | `vendor/bin/phpstan analyse {file} --level={n}` |
| PHP-CS-Fixer | Code style | No fixable issues (dry-run clean) | `vendor/bin/php-cs-fixer fix {file} --dry-run --diff` |
| Composer validate | Package validity | No warnings | `composer validate --strict` |
| PHP lint | Syntax | No parse errors | `php -l {file}` |
| Pest | Test execution | All tests pass | `vendor/bin/pest --filter={test}` |

**Grader wrapper example:**

```php
final readonly class PhpStanGrader
{
    public function __construct(
        private int $level = 8,
    ) {}

    public function grade(string $filePath): GradeResult
    {
        $output = [];
        $exitCode = 0;

        exec(
            sprintf('vendor/bin/phpstan analyse %s --level=%d --error-format=json --no-progress 2>&1',
                escapeshellarg($filePath),
                $this->level,
            ),
            $output,
            $exitCode,
        );

        $json = json_decode(implode("\n", $output), true);
        $errorCount = $json['totals']['errors'] ?? -1;

        return new GradeResult(
            passed: $exitCode === 0 && $errorCount === 0,
            score: $errorCount === 0 ? 1.0 : 0.0,
            details: $json['files'] ?? [],
        );
    }
}

final readonly class GradeResult
{
    public function __construct(
        public bool $passed,
        public float $score,    // 0.0 to 1.0
        public array $details,
    ) {}
}
```

### Model-Based Graders

Use an LLM to evaluate subjective quality (code readability, architecture adherence, naming).

```json
{
  "grader": "model",
  "prompt": "Evaluate this PHP code for: (1) PSR-12 compliance, (2) proper use of readonly properties, (3) clear naming. Score 0-10 for each, explain deductions.",
  "model": "claude-haiku-4-5-20251001",
  "threshold": 7.0
}
```

**When to use model-based graders:**

- Code style judgments beyond what PHP-CS-Fixer checks
- Architecture pattern adherence (is this actually DDD?)
- Documentation quality
- Naming clarity

### Human Graders

For evals that require domain expertise or subjective judgment that AI can't reliably assess.

```json
{
  "grader": "human",
  "rubric": [
    "Does the migration handle rollback correctly?",
    "Are indexes appropriate for expected query patterns?",
    "Is the migration safe for zero-downtime deployment?"
  ],
  "scale": "pass/fail"
}
```

---

## 4. Metrics

### pass@k

Run the eval `k` times. What fraction of attempts pass?

```
pass@1 = probability of passing on a single attempt
pass@3 = probability of at least 1 pass in 3 attempts
```

**Interpretation:**

| pass@1 | Quality Level |
|--------|--------------|
| > 0.9 | Reliable — safe for automated workflows |
| 0.7–0.9 | Good — may need human review |
| 0.5–0.7 | Inconsistent — investigate failure modes |
| < 0.5 | Unreliable — skill/prompt needs work |

### pass^k (Strict)

All `k` attempts must pass. Measures consistency.

```
pass^3 = probability of passing 3 out of 3 attempts
```

High pass@1 but low pass^3 indicates flaky behavior — the AI sometimes produces incorrect results. Investigate variance before trusting in automated pipelines.

---

## 5. 4-Phase Workflow

### Phase 1: Define

Create an eval specification:

```json
{
  "name": "create-laravel-migration",
  "type": "capability",
  "description": "Can Claude create a valid Laravel migration from natural language?",
  "input": {
    "prompt": "Create a Laravel migration for a 'products' table with: name (string, max 255), price (decimal 10,2), sku (string, unique), category_id (foreign key to categories), soft deletes, timestamps.",
    "context_files": []
  },
  "graders": [
    { "type": "php-lint" },
    { "type": "php-cs-fixer", "config": ".php-cs-fixer.dist.php" },
    {
      "type": "custom",
      "script": "eval-graders/migration-structure.php",
      "checks": ["has_columns", "has_foreign_key", "has_unique_index", "has_soft_deletes"]
    }
  ],
  "pass_criteria": {
    "all_graders_pass": true
  }
}
```

### Phase 2: Implement the Grader

```php
// eval-graders/migration-structure.php
$file = $argv[1] ?? '';
$content = file_get_contents($file);

$checks = [
    'has_columns' => str_contains($content, "'name'")
        && str_contains($content, 'decimal')
        && str_contains($content, "'sku'"),
    'has_foreign_key' => str_contains($content, 'foreignId')
        || str_contains($content, 'foreign'),
    'has_unique_index' => str_contains($content, 'unique'),
    'has_soft_deletes' => str_contains($content, 'softDeletes'),
];

$passed = ! in_array(false, $checks, true);

echo json_encode([
    'passed' => $passed,
    'checks' => $checks,
]);

exit($passed ? 0 : 1);
```

### Phase 3: Evaluate

Run the eval and collect results:

```bash
# Run eval 5 times for pass@5
for i in {1..5}; do
  echo "--- Run $i ---"
  claude --prompt "$(cat .claude/evals/capability/create-laravel-migration.eval.json | jq -r .input.prompt)" \
    --output-dir ".claude/evals/results/run-$i/" \
    --no-interactive
  php eval-graders/migration-structure.php ".claude/evals/results/run-$i/migration.php"
done
```

### Phase 4: Report

```
Eval: create-laravel-migration
Runs: 5
Results:
  Run 1: PASS (all checks green)
  Run 2: PASS
  Run 3: FAIL (missing unique index on sku)
  Run 4: PASS
  Run 5: PASS

pass@1: 0.80
pass@5: 1.00 (at least 1 pass in 5 runs)
pass^5: 0.00 (not all 5 passed)

Action: Investigate Run 3 — prompt may need explicit "unique constraint" wording.
```

---

## 6. Storage Structure

```
.claude/evals/
├── capability/                    # Can it do X?
│   ├── create-migration.eval.json
│   ├── phpstan-fix.eval.json
│   └── write-phpunit-test.eval.json
├── regression/                    # Does it still do X?
│   └── route-model-binding.eval.json
├── graders/                       # Custom PHP grading scripts
│   ├── migration-structure.php
│   └── test-coverage-check.php
└── results/                       # Eval run outputs (gitignored)
    ├── 2026-03-02-migration/
    │   ├── run-1/
    │   ├── run-2/
    │   └── summary.json
    └── 2026-03-02-phpstan-fix/
```

**Add to `.gitignore`:**

```
.claude/evals/results/
```

Keep eval definitions and graders in version control. Results are ephemeral.
