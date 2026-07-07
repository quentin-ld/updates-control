#!/usr/bin/env python3
"""
Rebuild the **generated appendix** inside `.agents/docs/docs-library.md`.

Human-edited content must stay **above** `<!-- updatronix:handbook-mirror:start -->`.
URLs are collected **only from that curated region**, then WordPress.org handbook pages
are fetched via `?output_format=md` and written between the markers.

Non-WordPress URLs get stub sections only (no HTML scrape).

Stdlib only.
"""
from __future__ import annotations

import re
import ssl
import time
import urllib.error
import urllib.parse
import urllib.request
from datetime import datetime, timezone
from pathlib import Path

AGENTS_ROOT = Path(__file__).resolve().parent.parent
DOCS_LIBRARY = AGENTS_ROOT / "docs" / "docs-library.md"
MARKER_START = "<!-- updatronix:handbook-mirror:start -->"
MARKER_END = "<!-- updatronix:handbook-mirror:end -->"
USER_AGENT = (
    "Updatronix-docs-library-consolidated/1.0 "
    "(local doc mirror; +https://wordpress.org/plugins/updatronix/)"
)
SLEEP_SEC = 0.75

_SKIP_NETLOCS = frozenset(
    {
        "github.com",
        "www.w3.org",
        "w3.org",
        "web.dev",
        "php.net",
        "phpunit.de",
        "playwright.dev",
        "docs.google.com",
    }
)

LINK_RE = re.compile(r"\[[^\]]*\]\((https?://[^)\s]+)\)")


def normalize_url(raw: str) -> str:
    s = raw.strip().rstrip(").,;\"'")
    parts = urllib.parse.urlsplit(s)
    if parts.scheme not in ("http", "https"):
        return s
    return urllib.parse.urlunsplit((parts.scheme, parts.netloc, parts.path, parts.query, ""))


def netloc_key(netloc: str) -> str:
    h = netloc.lower()
    if h.startswith("www."):
        return h[4:]
    return h


def fetch_mode(url: str) -> str:
    parts = urllib.parse.urlsplit(url)
    host = netloc_key(parts.netloc)
    if host in _SKIP_NETLOCS:
        return "external_stub"
    if host in ("developer.wordpress.org", "make.wordpress.org", "wordpress.org"):
        return "wordpress_md"
    return "external_stub"


def curated_body_only(full_text: str) -> str:
    """Return human-edited region only.

    The start marker must appear as its **own line** (optional surrounding whitespace).
    This avoids splitting when the marker string appears inside prose or backticks.
    """
    lines = full_text.splitlines()
    for i, line in enumerate(lines):
        if line.strip() == MARKER_START:
            return "\n".join(lines[:i]).rstrip()
    return full_text.rstrip()


def parse_urls(curated_markdown: str) -> list[str]:
    found = LINK_RE.findall(curated_markdown)
    seen: set[str] = set()
    out: list[str] = []
    for raw in found:
        u = normalize_url(raw)
        if not u.startswith("https://"):
            continue
        if u in seen:
            continue
        seen.add(u)
        out.append(u)
    return out


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
    tail = urllib.parse.urlsplit(url).path.rstrip("/").split("/")[-1] or "page"
    slug = re.sub(r"[^a-z0-9]+", "-", f"{title}-{tail}-{index}".lower()).strip("-")
    return slug[:120] or f"section-{index}"


def build_appendix(urls: list[str], ctx: ssl.SSLContext) -> tuple[str, list[str]]:
    """Returns (markdown, log lines)."""
    sections: list[tuple[str, str, str, str, str]] = []
    log: list[str] = []

    for i, canonical in enumerate(urls):
        mode = fetch_mode(canonical)
        err: str | None = None
        body_out = ""
        title = canonical

        if mode == "external_stub":
            title = f"(external) {urllib.parse.urlsplit(canonical).path or canonical}"
            stub = (
                "\n> **External URL (not mirrored here).** Open in browser — this project does not scrape "
                "third-party HTML.\n"
            )
            anchor = slug_anchor("external", canonical, i)
            sections.append((anchor, title, canonical, "external_stub", stub))
            log.append(f"[{i + 1}/{len(urls)}] {canonical[:70]}… EXTERNAL_STUB")
            continue

        fetch = md_fetch_url(canonical)
        try:
            req = urllib.request.Request(fetch, headers={"User-Agent": USER_AGENT})
            with urllib.request.urlopen(req, timeout=120, context=ctx) as resp:
                if resp.status != 200:
                    err = f"HTTP {resp.status}"
                else:
                    raw = resp.read().decode("utf-8", errors="replace")
                    if "Title:" not in raw[:800]:
                        err = "response missing Title: (not MD export?)"
                    else:
                        title, body_out = extract_title_and_body(raw)
        except urllib.error.HTTPError as e:
            err = f"HTTPError {e.code}: {e.reason}"
        except urllib.error.URLError as e:
            err = f"URLError: {e.reason}"
        except Exception as e:
            err = f"{type(e).__name__}: {e}"

        anchor = slug_anchor(title, canonical, i)
        if err:
            body_out = (
                f"\n> **FETCH FAILED** ({datetime.now(timezone.utc).date().isoformat()}): `{canonical}`\n>\n"
                f"> {err}\n"
            )

        sections.append((anchor, title, canonical, "wordpress_md", body_out or ""))
        log.append(f"[{i + 1}/{len(urls)}] {title[:60]}… {'OK' if not err else err}")
        time.sleep(SLEEP_SEC)

    gen_at = datetime.now(timezone.utc).strftime("%Y-%m-%dT%H:%M:%SZ")
    lines: list[str] = [
        f"## Appendix: WordPress handbook sources (generated {gen_at})",
        "",
        "This section is **machine-generated**. Do not edit by hand — run:",
        "",
        "```bash",
        "python3 .agents/scripts/build_docs_library_consolidated.py",
        "```",
        "",
        "- **WordPress.org family** URLs are fetched as Markdown via `?output_format=md`.",
        "- **Other hosts** (GitHub, W3C, `web.dev`, …) appear as **stubs** only.",
        "",
        "**Curated tables, Agent Directives, and project pointers** are in the sections **above** the start marker.",
        "",
        "### Mirror table of contents",
        "",
    ]
    for anchor, display_title, canonical, _mode, body in sections:
        lines.append(f"- [{display_title}](#{anchor}) — `{canonical}`")
    lines.extend(["", "---", ""])

    for anchor, display_title, canonical, mode, body in sections:
        lines.append(f'<span id="{anchor}"></span>')
        lines.append(f"### {display_title}")
        lines.append("")
        lines.append(f"**Source:** {canonical}")
        if mode == "external_stub":
            lines.append("")
            lines.append(f"**Mirror:** stub only (`{mode}`).")
        lines.append("")
        if body:
            lines.append(body)
        lines.extend(["", "---", ""])

    return "\n".join(lines), log


def main() -> int:
    if not DOCS_LIBRARY.is_file():
        print(f"ERROR: missing {DOCS_LIBRARY}")
        return 1

    full = DOCS_LIBRARY.read_text(encoding="utf-8")
    curated = curated_body_only(full)
    urls = parse_urls(curated)
    if len(urls) < 40:
        print(f"ERROR: expected many URLs in curated docs-library region, got {len(urls)}")
        return 1

    ctx = ssl.create_default_context()
    appendix, log_lines = build_appendix(urls, ctx)
    for line in log_lines:
        print(line)

    new_file = curated + "\n\n" + MARKER_START + "\n\n" + appendix.rstrip() + "\n\n" + MARKER_END + "\n"
    DOCS_LIBRARY.write_text(new_file, encoding="utf-8")
    kb = DOCS_LIBRARY.stat().st_size // 1024
    print(f"Updated {DOCS_LIBRARY} ({kb} KiB total)")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
