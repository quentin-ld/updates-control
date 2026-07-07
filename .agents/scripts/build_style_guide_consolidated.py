#!/usr/bin/env python3
"""
Fetch all WordPress Documentation Style Guide URLs listed in the consolidation task
and assemble `.agents/docs/wordpress-documentation-style-guide-consolidated.md`.

Uses upstream `?output_format=md` endpoints (WordPress.org). Stdlib only.
"""
from __future__ import annotations

import re
import ssl
import time
import urllib.error
import urllib.request
from datetime import datetime, timezone
from pathlib import Path

AGENTS_ROOT = Path(__file__).resolve().parent.parent
TASK_FILE = AGENTS_ROOT / "tasks" / "2026-05-05-documentation-wordpress-style-guide-consolidated.md"
OUT_FILE = AGENTS_ROOT / "docs" / "wordpress-documentation-style-guide-consolidated.md"
USER_AGENT = (
    "Updatronix-style-guide-consolidated/1.0 "
    "(local doc mirror; +https://wordpress.org/plugins/updatronix/)"
)
SLEEP_SEC = 0.75


def parse_task_urls() -> list[str]:
    text = TASK_FILE.read_text(encoding="utf-8")
    start = text.find("### URL manifest")
    end = text.find("## Style requirements (for the **wrapper** document only)")
    if start == -1 or end == -1 or end <= start:
        raise SystemExit("Could not find URL manifest region in task file.")
    chunk = text[start:end]
    seen: set[str] = set()
    urls: list[str] = []
    for raw in chunk.splitlines():
        line = raw.strip()
        if not line.startswith("https://"):
            continue
        if "make.wordpress.org/docs/style-guide" not in line and "wordpress.org/documentation/article/wordpress-glossary" not in line:
            continue
        u = line.split()[0].rstrip(").,;")
        if u in seen:
            continue
        seen.add(u)
        urls.append(u)
    return urls


def md_fetch_url(canonical: str) -> str:
    base = canonical.rstrip("/")
    sep = "&" if "?" in base else "?"
    return f"{base}{sep}output_format=md"


def extract_title_and_body(raw: str) -> tuple[str, str]:
    title = "Untitled"
    for line in raw.splitlines():
        if line.startswith("Title:"):
            title = line.split(":", 1)[1].strip()
            break
    if "\n---\n" in raw:
        body = raw.split("\n---\n", 1)[1]
    else:
        body = raw
    for marker in (
        "\n## Was this article helpful?",
        "\nFirst published\n",
        "\nEdit article\n",
    ):
        if marker in body:
            body = body.split(marker)[0]
    return title, body.strip()


def slug_anchor(title: str, url: str, index: int) -> str:
    slug = re.sub(r"[^a-z0-9]+", "-", f"{title}-{url.split('/')[-1] or 'page'}-{index}".lower()).strip("-")
    return slug[:120] or f"section-{index}"


def main() -> int:
    urls = parse_task_urls()
    if len(urls) < 100:
        print(f"ERROR: expected ~104 URLs, got {len(urls)} from {TASK_FILE}")
        return 1

    ctx = ssl.create_default_context()
    sections: list[tuple[str, str, str, str | None]] = []
    # (anchor, display_title, canonical_url, body_md or None if error)

    for i, canonical in enumerate(urls):
        fetch = md_fetch_url(canonical)
        err: str | None = None
        body_out = None
        title = canonical
        try:
            req = urllib.request.Request(fetch, headers={"User-Agent": USER_AGENT})
            with urllib.request.urlopen(req, timeout=120, context=ctx) as resp:
                if resp.status != 200:
                    err = f"HTTP {resp.status}"
                else:
                    raw = resp.read().decode("utf-8", errors="replace")
                    title, body_out = extract_title_and_body(raw)
        except urllib.error.HTTPError as e:
            err = f"HTTPError {e.code}: {e.reason}"
        except urllib.error.URLError as e:
            err = f"URLError: {e.reason}"
        except Exception as e:
            err = f"{type(e).__name__}: {e}"

        anchor = slug_anchor(title, canonical, i)
        if err:
            body_block = (
                f"\n> **FETCH FAILED** ({datetime.now(timezone.utc).date().isoformat()}): `{canonical}`\n>\n"
                f"> {err}\n"
            )
            sections.append((anchor, title, canonical, body_block))
        else:
            sections.append((anchor, title, canonical, body_out or ""))

        print(f"[{i + 1}/{len(urls)}] {title[:60]}… {'OK' if not err else err}")
        time.sleep(SLEEP_SEC)

    gen_at = datetime.now(timezone.utc).strftime("%Y-%m-%dT%H:%M:%SZ")
    lines: list[str] = [
        "---",
        'title: "WordPress Documentation Style Guide (consolidated mirror)"',
        f"generated_at: {gen_at}",
        "source: make.wordpress.org/docs/style-guide + wordpress.org/documentation (WordPress Glossary)",
        "generator_note: .agents/scripts/build_style_guide_consolidated.py — urllib, upstream ?output_format=md",
        "copyright_notice: Convenience mirror for Updatronix contributors; canonical sources are the live URLs cited per section.",
        "---",
        "",
        "# WordPress Documentation Style Guide — consolidated Markdown",
        "",
        "This file aggregates **official** WordPress Documentation Style Guide pages listed in "
        "`.agents/tasks/2026-05-05-documentation-wordpress-style-guide-consolidated.md` (URL manifest). "
        "Each block is fetched as Markdown via WordPress.org (`output_format=md`). **Canonical sources** are the live URLs "
        "on each `**Source:**` line. For **PHPDoc/JSDoc block structure and tags**, see "
        "https://developer.wordpress.org/coding-standards/inline-documentation-standards/ "
        "and `.agents/docs/docs-library.md` (WordPress Coding Standards). Regenerate with:",
        "",
        "```bash",
        "python3 .agents/scripts/build_style_guide_consolidated.py",
        "```",
        "",
        "## Table of contents",
        "",
    ]
    for anchor, title, canonical, body in sections:
        lines.append(f"- [{title}](#{anchor}) — `{canonical}`")
    lines.extend(["", "---", ""])

    for anchor, title, canonical, body in sections:
        lines.append(f'<span id="{anchor}"></span>')
        lines.append(f"## {title}")
        lines.append("")
        lines.append(f"**Source:** {canonical}")
        lines.append("")
        if body:
            lines.append(body)
        lines.extend(["", "---", ""])

    OUT_FILE.parent.mkdir(parents=True, exist_ok=True)
    OUT_FILE.write_text("\n".join(lines), encoding="utf-8")
    print(f"Wrote {OUT_FILE} ({OUT_FILE.stat().st_size // 1024} KiB)")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
