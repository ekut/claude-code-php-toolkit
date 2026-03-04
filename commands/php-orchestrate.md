---
description: Sequential agent workflow — plan, build, test, review, security — orchestrating all available PHP agents
---

# PHP Orchestrate

Run a full development workflow by orchestrating PHP agents in sequence. Each phase gates the next — failures must be resolved before continuing.

## Phase 1: Plan (php-planner)

Delegate to the **php-planner** agent:

- Explore the codebase structure
- Break the requirement into phased implementation steps
- Identify risks and testing strategy
- **Gate:** User approves the plan before proceeding

## Phase 2: Build (php-build-resolver)

Delegate to the **php-build-resolver** agent:

- Implement the approved plan phase by phase
- Resolve any Composer or autoloading issues during implementation
- Run `composer dump-autoload` after structural changes
- **Gate:** `composer install` and `php -l` pass on all new/modified files

## Phase 3: Test (php-tdd-guide)

Delegate to the **php-tdd-guide** agent:

- Write tests for new code following Red-Green-Refactor
- Run the full test suite with coverage
- **Gate:** All tests pass, coverage >= 80%

## Phase 4: Review (php-reviewer)

Delegate to the **php-reviewer** agent:

- Review all changes for PSR compliance, type safety, architecture
- Check for N+1 queries, missing error handling, code smells
- **Gate:** No critical issues found

## Phase 5: Security (php-security-reviewer)

Delegate to the **php-security-reviewer** agent:

- OWASP Top 10 audit on changed files
- SQL injection, XSS, CSRF checks
- Dependency audit: `composer audit`
- **Gate:** No security vulnerabilities found

## Phase 6: Documentation (php-doc-updater)

Delegate to the **php-doc-updater** agent:

- Add/update PHPDoc for new public APIs
- Update codemap if structure changed
- **Gate:** Documentation is complete

## Output

After all phases complete:

```
ORCHESTRATION REPORT
====================
Plan:          [APPROVED]
Build:         [PASS/FAIL]
Tests:         [PASS/FAIL] (X passed, Y% coverage)
Review:        [PASS/FAIL] (X critical, Y improvements)
Security:      [PASS/FAIL] (X vulnerabilities)
Documentation: [PASS/FAIL]

Overall: [READY / NOT READY] for commit
```

## Notes

- Each phase uses the specialized agent for that domain
- If any phase fails, fix the issues before moving to the next phase
- The user can skip phases by explicitly requesting it
- Available agents: php-planner, php-build-resolver, php-tdd-guide, php-reviewer, php-security-reviewer, php-doc-updater, php-architect, php-database-reviewer, php-refactor-cleaner, php-e2e-runner
