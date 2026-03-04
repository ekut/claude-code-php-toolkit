---
description: Cluster related instincts into higher-order artifacts — skills, commands, or agents
---

# PHP Evolve

Analyze existing instincts and cluster related ones into higher-order artifacts: skills, commands, or specialized agents.

> **Foundation:** See `skills/continuous-learning/SKILL.md` for the evolution path and clustering rules.

## Step 1: Load Instincts

Read all instinct files from:
- `~/.claude/learning/instincts/personal/`
- `~/.claude/learning/instincts/inherited/`

## Step 2: Cluster by Domain

Group instincts by their `domain` tag and look for clusters of 3+ related instincts.

Clustering criteria:
- Same domain tag
- Overlapping `tags` values
- Complementary triggers (e.g., multiple testing-related instincts)

## Step 3: Evaluate Clusters

For each cluster of 3+ instincts:

| Metric | Threshold | Description |
|--------|-----------|-------------|
| Cluster size | >= 3 | Minimum instincts to justify evolution |
| Avg confidence | >= 0.6 | Cluster should be well-established |
| Domain coherence | High | Instincts should address the same concern |

## Step 4: Propose Evolution

Based on the cluster type, propose the appropriate artifact:

- **Workflow instincts** → new **command** (`.md` in `commands/`)
- **Knowledge instincts** → new **skill** (`SKILL.md` in `skills/{name}/`)
- **Domain-specialist instincts** → new **agent** (`.md` in `agents/`)

Draft the artifact but do **not** save it — present to the user for approval.

## Step 5: Save Approved Artifacts

After user approval:
- Write the artifact to `~/.claude/learning/evolved/skills/`, `evolved/commands/`, or `evolved/agents/`
- Mark source instincts with `evolved_into: {artifact-path}` in their frontmatter
- Do not delete source instincts — they retain individual confidence tracking

## Output

```
EVOLUTION REPORT
================
Instincts loaded:   X (personal: Y, inherited: Z)
Clusters found:     X
Clusters eligible:  X (3+ instincts, avg confidence >= 0.6)

Proposed:
- [SKILL] {name} — from {N} instincts (avg confidence: 0.X)
- [COMMAND] {name} — from {N} instincts (avg confidence: 0.X)

Awaiting approval for X artifacts.
```
