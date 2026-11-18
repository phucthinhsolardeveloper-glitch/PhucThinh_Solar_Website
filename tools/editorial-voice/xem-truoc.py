#!/usr/bin/env python3
"""Trang xem trước/sau cho chủ website: python3 xem-truoc.py <lô>  ->  output/xem-truoc/<lô>.html"""
import html
import json
import re
import subprocess
import sys
from pathlib import Path

A = Path(__file__).resolve().parent


def inline(t: str) -> str:
    t = html.escape(t, quote=False)
    t = re.sub(r"!\[([^\]]*)\]\(([^)]*)\)", r'<img alt="\1" src="\2">', t)
    t = re.sub(r"\[([^\]]*)\]\(([^)]*)\)", r'<a href="\2">\1</a>', t)
    t = re.sub(r"\*\*(.+?)\*\*", r"<strong>\1</strong>", t)
    t = re.sub(r"(?<!\*)\*(?!\s)(.+?)(?<!\s)\*(?!\*)", r"<em>\1</em>", t)
    return t


def md(text: str) -> str:
    out, lines, i = [], text.splitlines(), 0
    while i < len(lines):
        line = lines[i]
        if not line.strip():
            i += 1
        elif re.match(r"^#{1,4}\s", line):
            level = min(4, len(line) - len(line.lstrip("#")) + 1)
            out.append(f"<h{level}>{inline(line.lstrip('# ').strip())}</h{level}>")
            i += 1
        elif line.lstrip().startswith("|"):
            rows = []
            while i < len(lines) and lines[i].lstrip().startswith("|"):
                rows.append([c.strip() for c in lines[i].strip().strip("|").split("|")])
                i += 1
            rows = [r for r in rows if not all(re.fullmatch(r":?-{2,}:?", c) for c in r)]
            head, body = rows[0], rows[1:]
            out.append("<div class=tw><table><thead><tr>" + "".join(f"<th>{inline(c)}</th>" for c in head) + "</tr></thead><tbody>"
                       + "".join("<tr>" + "".join(f"<td>{inline(c)}</td>" for c in r) + "</tr>" for r in body) + "</tbody></table></div>")
        elif re.match(r"^\s*[-*]\s", line):
            items = []
            while i < len(lines) and re.match(r"^\s*[-*]\s", lines[i]):
                items.append(re.sub(r"^\s*[-*]\s", "", lines[i]))
                i += 1
            out.append("<ul>" + "".join(f"<li>{inline(x)}</li>" for x in items) + "</ul>")
        else:
            para = []
            while i < len(lines) and lines[i].strip() and not re.match(r"^(#{1,4}\s|\s*[-*]\s|\s*\|)", lines[i]):
                para.append(lines[i].strip())
                i += 1
            out.append(f"<p>{inline(' '.join(para))}</p>")
    return "\n".join(out)


def old_html(raw: str) -> tuple[str, str]:
    title = re.search(r"<h1>(.*?)</h1>", raw, re.S)
    body = re.sub(r"<!--.*?-->|<h1>.*?</h1>|<p class=\"summary\">.*?</p>", "", raw, flags=re.S)
    return (title.group(1) if title else ""), body


def main() -> None:
    lot = sys.argv[1]
    out_dir = A / "output" / lot
    cards = []
    for f in sorted(out_dir.glob("ARTICLE-*.md")):
        aid = f.stem.split("-")[1]
        src = A / "input" / "live" / f"ARTICLE-{aid}.html"
        title, old = old_html(src.read_text(encoding="utf-8"))
        new = md(f.read_text(encoding="utf-8"))
        meta_file = f.with_suffix(".meta.json")
        meta = json.loads(meta_file.read_text(encoding="utf-8")) if meta_file.exists() else {}
        check = subprocess.run(["python3", str(A / "doc-giong.py"), str(src), str(f)], capture_output=True, text=True).stdout.strip().splitlines()
        stats = check[0].lstrip("· ") if check else ""
        verdict = "Đạt công cụ chấm" if any("PASS" in l for l in check) else "Còn lỗi: " + "; ".join(l[2:] for l in check if l.startswith("✗"))[:300]
        cards.append(f"""<section id="b{aid}"><h2>#{aid} · {html.escape(title)}</h2>
<p class=st>{html.escape(stats)} · {html.escape(verdict)}</p>
<div class=cols><article><h3>Hiện tại</h3>{old}</article><article class=new><h3>Bản viết lại</h3>{new}
<p class=meta><b>Tóm tắt:</b> {html.escape(meta.get('summary',''))}<br><b>Mô tả tìm kiếm:</b> {html.escape(meta.get('seo_description',''))}</p></article></div></section>""")
    nav = " · ".join(f'<a href="#b{f.stem.split("-")[1]}">#{f.stem.split("-")[1]}</a>' for f in sorted(out_dir.glob("ARTICLE-*.md")))
    page = f"""<!doctype html><html lang=vi><meta charset=utf-8><meta name=viewport content="width=device-width,initial-scale=1">
<title>Viết lại bài – {lot}</title><style>
:root{{--bg:#f6f4ef;--fg:#1d2733;--mut:#5c6773;--card:#fff;--line:#ddd6c8;--gold:#b8861b}}
@media(prefers-color-scheme:dark){{:root{{--bg:#12161c;--fg:#e8e6e1;--mut:#9aa3ad;--card:#1a2028;--line:#2d3641;--gold:#e0a526}}}}
body{{margin:0;background:var(--bg);color:var(--fg);font:16px/1.65 system-ui,'Segoe UI',sans-serif;padding:16px max(16px,env(safe-area-inset-left))}}
main{{max-width:1280px;margin:0 auto}}h1{{font-size:1.4rem}}nav{{margin:0 0 20px;color:var(--mut)}}a{{color:var(--gold)}}
section{{margin:0 0 40px}}section>h2{{font-size:1.15rem;margin:0 0 4px}}.st{{margin:0 0 12px;color:var(--mut);font-size:.9rem}}
.cols{{display:grid;grid-template-columns:1fr 1fr;gap:16px}}@media(max-width:860px){{.cols{{grid-template-columns:1fr}}}}
article{{background:var(--card);border:1px solid var(--line);border-radius:10px;padding:4px 20px 18px;min-width:0}}article.new{{border-color:var(--gold)}}
article h3{{font-size:.8rem;letter-spacing:.04em;text-transform:uppercase;color:var(--mut)}}article.new h3{{color:var(--gold)}}
article h2,article h4{{font-size:1.05rem}}.tw{{overflow-x:auto}}table{{border-collapse:collapse;width:100%;font-size:.92rem}}td,th{{border:1px solid var(--line);padding:6px 8px;text-align:left}}
img{{max-width:100%}}.meta{{border-top:1px dashed var(--line);margin-top:16px;padding-top:10px;color:var(--mut);font-size:.88rem}}
</style><main><h1>Viết lại bài: bên trái là bài đang đăng, bên phải là bản mới</h1><nav>{nav}</nav>{''.join(cards)}</main></html>"""
    dest = A / "output" / "xem-truoc"
    dest.mkdir(parents=True, exist_ok=True)
    (dest / f"{lot}.html").write_text(page, encoding="utf-8")
    print(dest / f"{lot}.html")


if __name__ == "__main__":
    main()
