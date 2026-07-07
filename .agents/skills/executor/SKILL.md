---
name: resume
description: >-
  Continue dev work on worker tier. Use after thread rotation, turn limits,
  crashes, or fixing review/security findings. Reads the task file and resumes
  from the first unchecked task.
---

# Resume

Continue `/architect` work without chat history. **Use a worker-tier model.**

Reply in US English.

## Start

1. Read the task file in full — `## Session checkpoint` first
2. Say: "Resuming from task N. Remaining: [list]. Next: [action]."
3. Execute from the first unchecked task (architect Phase 4–5 rules)

## Reference docs

Grep one section only — never load whole `.agents/docs/` mirrors.

## Plan changes

Update `## Tasks` and note in `## Log` if the remaining plan is wrong.

## Thread rotation

After 3 more tasks or ~20 turns, update checkpoint; suggest fresh **worker** tier thread + `/resume`.

## Hand-off

Follow `review_required` and lint tiers from the task file and `AGENTS.md`.