#!/usr/bin/env python3
"""Rebuilds a read-only per-SKU image-production plan for floodlights from dated audit snapshots.
No network, DB, upload or source image mutations. Output files only in docs/10-audits/.
"""
from __future__ import annotations
import csv, json, re, base64, html
from collections import Counter
from pathlib import Path

REPO = Path("/home/admin01/src/Web_PTS_PHP")
AUDIT = REPO/"docs/10-audits"
VIP = Path("/home/admin01/orca/artifacts/pts-photo-vip1-20261117")
SOURCE = Path("/mnt/c/Users/ADMIN/Downloads/VIP1_PHÚC THỊNH SOLAR_12TR5-20261117T050129Z-1-001/VIP1_PHÚC THỊNH SOLAR_12TR5")
CSV_OUT = AUDIT/"2026-11-17-den-pha-other-37-vip1-implementation.csv"
HTML_OUT = AUDIT/"2026-11-17-den-pha-other-37-vip1-review.html"

def family(sku):
    if sku in {"DB-760","DB-7500","DB-71000"}: return "ORIANA DB7", "02", "Ảnh nguồn EMF nội bộ: đối chiếu LED/nhãn từng mã; không nhân bản vật lý"
    if sku in {"DB-9200","DB-91000"}: return "ORIANA DB9", "03", "Ảnh nhánh DB9 481×321 có nguy cơ lặp giữa các model; tìm nguồn đúng nhãn"
    if sku in {"DB-8150","DB-8200","DB-8300","DB-8300-S","DB-8500","DB-8500-S"}: return "ORIANA DB8", "06", "Đối chiếu cấu trúc mặt đèn và biến thể -S"
    if sku.startswith("BCT-WR"): return "BLUE CARBON WR", "10", "Dùng ngôn ngữ WR5/WR6; chỉ lấy thông số/ảnh riêng WR đúng mã"
    if sku.startswith("BCT-WW"): return "BLUE CARBON WW", "07,08", "Tách MONO và loại cell; không dùng ảnh WW5 làm ảnh thật WW6"
    if sku.startswith("BCT-FLRP"): return "BLUE CARBON FLRP", "07,08", "Chỉ mượn design language; cấu tạo độc lập"
    if sku in {"JD-18200","JD-18300","JD-18500"}: return "JINDIAN JD18", "11,15", "Hình thân đèn và mã theo nguồn thật; JD-18300 có lịch sử nhầm SKU"
    if sku == "JD-6128": return "JINDIAN CHÓA", "11", "Dùng ảnh chóa thật 1620×1080, không dùng mockup đèn vuông"
    if sku.endswith("-VANG"): return "JINDIAN L ÁNH SÁNG VÀNG", "20", "Chỉ dùng ảnh màu sáng vàng khi có bằng chứng đúng biến thể"
    if sku in {"JD-8500L","JD-8500L-NEW","JD-8860L"}: return "JINDIAN L", "20", "Tách đời NEW, panel/LED thực"
    if sku in {"JD-T100","JD-T200","JD-T300","JD-V100"}: return "JINDIAN T/V", "11", "Chỉ mượn template; không dùng hình thân V200/V300 cho T100"
    raise ValueError(f"Missing family: {sku}")

with (AUDIT/"2026-11-17-product-gallery-inventory.csv").open(encoding="utf-8-sig",newline="") as fh:
    allrows = list(csv.DictReader(fh))
with (AUDIT/"2026-11-17-den-pha-26-image-qa.csv").open(encoding="utf-8-sig",newline="") as fh:
    qa = {r["code"]:r for r in csv.DictReader(fh)}
vip = json.loads((VIP/"apply-plan.json").read_text())
flood = [r for r in allrows if r["Danh mục"] == "Đèn Pha"]
remaining = [r for r in flood if r["Product ID"] not in vip]
assert len(flood)==72, len(flood)
assert len(remaining)==37, len(remaining)
assert len(flood)-len(remaining)==35

rows=[]
for r in remaining:
    sku=r["SKU"]
    fam,ref,note=family(sku)
    n=int(r["Số ảnh hợp lệ (không Shopping)"])
    risk=qa.get(sku,{})
    status=risk.get("status","SOURCE_AUDIT_REQUIRED")
    comment=risk.get("reason", "")
    if status=="SOURCE_AUDIT_REQUIRED" and n>=5:
        status="EXISTING_GALLERY_QUALITY_REVIEW"
    if sku=="JD-18300":
        status="BLOCKED_WRONG_SKU_AND_RIGHTS"
    if sku in {"BCT-WR1.0","BCT-WR4.0"}:
        status="RIGHTS_AND_NATIVE_RESOLUTION_REQUIRED"
    if sku.endswith("-VANG"):
        status="YELLOW_VARIANT_EVIDENCE_REQUIRED"
    if sku in {"DB-760","DB-7500","DB-71000","JD-6128"}:
        status="INTERNAL_PHOTO_CANDIDATE_QA"
    priority="P0" if n<=1 else ("P1" if n<=3 else "P2")
    notes="; ".join(x for x in [note,comment] if x)
    rows.append({
      "Priority":priority,"Product ID":r["Product ID"],"SKU":sku,
      "Brand":r["Thương hiệu"],"Family":fam,"VIP1 reference folders":ref,
      "Existing images (snapshot)":str(n),"Missing to 6 (snapshot)":str(max(0,6-n)),
      "Source QA status":status,"Recommended slots":"P01-P06 (conditional), P07 optional",
      "P01 disposition":"KEEP_CURRENT_UNTIL_VERIFIED", "Rights status":"NOT_VERIFIED",
      "Source / issues":notes, "Product URL":r["Đường dẫn sản phẩm"],
      "Approval status":"NOT_APPROVED", "Production status":"NOT_APPLIED",
    })
rows.sort(key=lambda r:(r["Priority"],r["Family"],r["SKU"]))
assert Counter(r["Brand"] for r in rows)==Counter({"JINDIAN":15,"BLUE CARBON":11,"ORIANA":11})
assert sum(int(r["Missing to 6 (snapshot)"]) for r in rows)==124
with CSV_OUT.open("w",encoding="utf-8-sig",newline="") as fh:
    w=csv.DictWriter(fh,fieldnames=list(rows[0]))
    w.writeheader();w.writerows(rows)

summary={}
for p in "P0 P1 P2".split():
    selected=[r for r in rows if r["Priority"]==p]
    summary[p]={"skus":len(selected),"minimum_slots":sum(int(r["Missing to 6 (snapshot)"]) for r in selected)}
imgrefs={"02":"ORIANA DB7","03":"ORIANA DB9","06":"ORIANA DB8","07":"BLUE CARBON WW","08":"BLUE CARBON WW MONO","10":"BLUE CARBON WR","11":"JINDIAN V","15":"JINDIAN JDE","20":"JINDIAN L"}
cards=[]
for ref,label in imgrefs.items():
    pic=VIP/"sheets"/f"{int(ref)}.jpg"
    if not pic.is_file(): continue
    mime="image/jpeg"
    src=f"data:{mime};base64,{base64.b64encode(pic.read_bytes()).decode()}"
    cards.append(f"<figure><img loading='lazy' src='{src}' alt='VIP1 source reference group {html.escape(ref)}'><figcaption>{html.escape(label)} / F{ref}</figcaption></figure>")
table_rows=[]
for r in rows:
    attrs=" ".join(f"data-{k}='{html.escape(str(v),quote=True)}'" for k,v in [("sku",r["SKU"].lower()),("brand",r["Brand"].lower()),("family",r["Family"].lower()),("priority",r["Priority"])])
    table_rows.append(f"<tr {attrs}><td><b>{r['Priority']}</b></td><td><code>{html.escape(r['SKU'])}</code></td><td>{html.escape(r['Brand'])}</td><td>{html.escape(r['Family'])}</td><td><b>{r['Existing images (snapshot)']}</b> / +{r['Missing to 6 (snapshot)']}</td><td>{html.escape(r['Source QA status'])}</td><td>{html.escape(r['Source / issues'])}</td><td><a href='{html.escape(r['Product URL'],quote=True)}'>PDP</a></td></tr>")

HTML_OUT.write_text("""<!DOCTYPE html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>PTS – 37 đèn pha ngoài VIP1, kế hoạch làm bộ ảnh</title>
<style>
:root{color-scheme:light;font-family:Inter,system-ui,Arial,sans-serif;color:#081b34;background:#f5f7fb}
*{box-sizing:border-box}body{margin:0}.wrap{max-width:1400px;margin:auto;padding:28px}
h1,h2{letter-spacing:-.025em}h1{font-size:clamp(25px,3.4vw,44px);margin:0 0 10px}p{line-height:1.65}header{background:#071d44;color:#fff;padding:35px 0 22px}header p{color:#d3ddeb}.tag{font-weight:650;color:#d1ae62;font-size:13px}
.stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin:20px 0}
.stats section{background:#fff;border:1px solid #e3e8ee;border-radius:14px;padding:16px}.stats strong{display:block;font-size:28px}
.info{background:#fff;padding:18px;border-left:4px solid #C9962E;border-radius:6px;line-height:1.6}
h2{margin:32px 0 12px}.refs{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:13px}
figure{margin:0;background:#fff;border:1px solid #e6e9f0;border-radius:12px;overflow:hidden}
figure img{display:block;width:100%;height:300px;object-fit:contain;background:#edf0f6}
figcaption{padding:10px 14px;font-weight:600}.filters{display:flex;gap:10px;flex-wrap:wrap;margin:20px 0}
input,select{padding:11px 13px;border:1px solid #d5dce6;border-radius:9px;background:white;font:inherit}
input{flex:1;min-width:240px}.tablewrap{overflow:auto;border:1px solid #d9e0e8;border-radius:12px;background:white}
table{border-collapse:collapse;width:100%;min-width:1120px}td,th{padding:12px;border-bottom:1px solid #e9eef4;text-align:left;vertical-align:top;font-size:13px}
th{background:#ecf2fa;position:sticky;top:0}code{white-space:nowrap}td:nth-child(7){min-width:300px}tr:hover{background:#f7faff}
small{color:#6b788c}.foot{padding:22px 0;color:#5c6777}a{color:#065f98}
@media(max-width:730px){.stats{grid-template-columns:repeat(2,1fr)}.refs{grid-template-columns:1fr}figure img{height:auto}}
</style></head><body>
<header><div class="wrap"><div class="tag">PHÚC THỊNH SOLAR · CREATIVE PRODUCTION SPEC</div><h1>37 đèn pha ngoài bộ VIP1</h1>
<p>Ảnh VIP1 là tham chiếu phong cách, không phải ảnh được gán lại sang SKU khác. Bản xem trước chỉ đọc, không thay đổi website.</p>
</div></header><div class="wrap">
<div class="stats"><section><small>Đèn pha snapshot</small><strong>72</strong></section><section><small>Có trong VIP1</small><strong>35</strong></section>
<section><small>Ngoài VIP1, cần thiết kế</small><strong>37</strong></section><section><small>Khoảng trống đến 6 ảnh</small><strong>124</strong></section></div>
<div class="info"><b>NGUYÊN TẮC:</b> Giữ nội dung thiết kế đẹp của VIP1 nhưng tất cả hình thân đèn, phụ kiện, số đo và claim phải theo đúng mã. Không dùng poster chứng nhận chung. 1600×1600 là kích thước xuất canvas, không có nghĩa ảnh nguồn native 1600. Số lượng ảnh hiện có là snapshot có thể đã thay đổi; kiểm chứng lại trước khi sản xuất/nhập.</div>
<h2>Concept VIP1 dùng làm định hướng (ảnh reference)</h2>
<div class="refs">"""+ "\n".join(cards) +"""</div>
<h2>Danh sách và mức ưu tiên</h2>
<p>Điểm xuất phát: P0 (1 ảnh) / P1 (2–3 ảnh) / P2 (≥5 ảnh); ưu tiên chất lượng ảnh có ích, không chỉ đếm ảnh.</p>
<div class="filters"><input id="q" placeholder="Tìm mã, hãng, dòng sản phẩm..." aria-label="Tìm kiếm"><select id="p" aria-label="Ưu tiên"><option value="">Tất cả ưu tiên</option><option>P0</option><option>P1</option><option>P2</option></select></div>
<div class="tablewrap"><table><thead><tr><th>Ưu tiên</th><th>SKU</th><th>Hãng</th><th>Dòng/template</th><th>Ảnh hiện có / thiếu đến 6</th><th>QA nguồn</th><th>Cảnh báo và yêu cầu riêng</th><th>Link</th></tr></thead>
<tbody>"""+ "\n".join(table_rows) +"""</tbody></table></div>
<p class="foot">Trang này được sinh từ inventory CSV + QA 26 mã + mapping VIP1 trên máy công ty. Trạng thái mọi SKU: NOT_APPROVED / NOT_APPLIED. Tài liệu triển khai chi tiết: 2026-11-17-den-pha-vip1-reuse-production-brief.md.</p></div>
<script>
const input=document.getElementById('q'), sel=document.getElementById('p'), rows=[...document.querySelectorAll('tbody tr')];
function apply(){let q=input.value.toLowerCase().trim(),p=sel.value;for(let r of rows){r.hidden=!!((q&&!r.textContent.toLowerCase().includes(q))||(p&&r.dataset.priority!==p))}}
input.addEventListener('input',apply);sel.addEventListener('change',apply);
</script></body></html>""",encoding="utf-8")
print(json.dumps({"floodlights":len(flood),"vip1_floodlights":len(flood)-len(remaining),"other_skus":len(remaining),"brands":dict(Counter(r["Brand"] for r in rows)),"priorities":summary,"missing_slots_to_6":124,"output_csv":str(CSV_OUT),"output_html":str(HTML_OUT),"html_bytes":HTML_OUT.stat().st_size},ensure_ascii=False,indent=2))
