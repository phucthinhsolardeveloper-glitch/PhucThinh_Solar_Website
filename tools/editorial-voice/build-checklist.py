#!/usr/bin/env python3
"""Checklist bài viết có đường dẫn, đánh giá và ô chọn: python3 build-checklist.py  (đọc /tmp/state.json từ máy chủ)"""
import json,html,re,datetime
A='/home/admin01/orca/artifacts/pts-content-voice-20261118/'
rows=json.load(open('/tmp/state.json'))
now=datetime.datetime.now().strftime('%Y-%m-%d %H:%M:%S')
rate={ # (mức, nhận xét thẳng của người rà)
 136:('Đạt','Giải thích nguyên lý đủ ý, hơi giống giáo trình.'),137:('Mạnh','Có số liệu datasheet thật, bảng rõ; dài nhưng đáng.'),138:('Mạnh','Bản tin gọn, bảng 5 thị trường, đúng nguồn IEA.'),
 139:('Đạt','Rõ ràng, có bảng; đã bỏ ví dụ vòi nước.'),140:('Đạt','Bảng ký hiệu rõ; còn hơi nặng thuật ngữ.'),141:('Đạt','Đã viết lại 19/11: có cấu trúc, so hai hộ, bài tập 15 phút.'),
 142:('Mạnh','Bảng so sánh ba loại hệ rất gọn, dễ quyết định.'),143:('Đạt','Đã viết lại 19/11: ba thông tin cần có, lumen/lux có đoạn riêng và liên kết sang bài 152.'),
 144:('Đạt','Viết lại hẳn, đã đăng 19/11. Mở bằng câu đủ nghĩa.'),145:('Đạt','Đúng, nhiều ý kỹ thuật; cần kỹ thuật viên xem lại phần an toàn.'),
 146:('Mạnh','Bạn đã duyệt giọng bài này làm chuẩn.'),147:('Mạnh','Tin ngắn 260 từ, đủ số liệu IEA.'),148:('Đạt','Ba hộ gia đình dễ hình dung; còn hơi dài.'),
 149:('Đạt','Bảng chọn theo nhu cầu tốt; trùng một phần bài 142 và 145.'),150:('Đạt','Bảng so sánh tốt; phần cuối còn loãng.'),151:('Đạt','Đã cắt 875→576 từ; hơi khô.'),
 152:('Đạt','Giải thích watt/lumen/lux; ví dụ 20 m² dễ hiểu, còn dài.'),153:('Đạt','Viết lại hẳn (1.307→658 từ), đã đăng 19/11.'),
 154:('Đạt','Thực tế, rõ ba loại mái.'),155:('Đạt','Bảng công nghệ ổn; phần ký hiệu đã gộp thành bảng.'),156:('Đạt','Gọn hơn nhiều; câu cuối hơi lặp.'),
 100:('Đạt','Thông báo + thông số pin Sharp; còn hai mức hiệu suất cần bạn xác nhận.'),101:('Yếu','Rất ngắn (~100 từ), chỉ là thông báo + ảnh.'),102:('Đạt','Đủ ý, ngắn gọn, bìa ảnh thật.'),
 103:('Đạt','Bản tin chính sách đúng ngày tháng, nhiều ý.'),104:('Yếu','Vẫn mỏng (~150 từ): nguồn gốc ít thông tin, đã thêm liên kết nội bộ.'),105:('Đạt','Lời dặn lắp đặt thực tế.'),
 106:('Đạt','Thông báo đối tác cho dự án; ngắn.'),108:('Đạt','Chính sách đại lý rõ ràng.'),109:('Yếu','Mỏng (~140 từ), chủ yếu ảnh + một đoạn thông báo.'),
 110:('Đạt','Giải thích chip LED, bỏ "TOP 1".'),111:('Đạt','Đủ ý, bìa ảnh đèn thật.'),107:('Ẩn','Trùng bài 102, đã chuyển hướng.')}
preview={144:'lo5.html#b144',153:'lo5.html#b153',155:'lo4.html#b155',156:'lo4.html#b156'}
def state(r):
    if not r['is_visible']: return 'ẨN'
    return 'HẸN GIỜ' if r['published_at']>now else 'ĐANG HIỆN'
cards=[]
for r in rows:
    i=r['id']; st=state(r); lv,note=rate.get(i,('-',''))
    if st=='ĐANG HIỆN': link=f'<a href="https://phucthinhsolar.com/{r["slug"]}/" target=_blank>Mở bài trên web ↗</a>'
    elif i in preview: link=f'<a href="{preview[i]}" target=_blank>Xem bản nháp ↗</a> <small>(chưa hiện trên web)</small>'
    else: link='<small>không còn trên web</small>'
    cards.append(f'''<tr data-id="{i}" class="{lv}"><td>#{i}</td><td><b>{html.escape(r["title"])}</b><br>{link}</td><td>{st}</td><td><span class=lv>{lv}</span><br><small>{html.escape(note)}</small></td>
<td><select data-k="{i}"><option value="">—</option><option>Giữ</option><option>Cần sửa</option><option>Ẩn</option></select><br><input data-n="{i}" placeholder="ghi chú..." size=18></td></tr>''')
page=f'''<!doctype html><html lang=vi><meta charset=utf-8><meta name=viewport content="width=device-width,initial-scale=1"><title>Checklist bài viết</title><style>
:root{{--bg:#f6f4ef;--fg:#1d2733;--mut:#5c6773;--card:#fff;--line:#ddd6c8;--gold:#b8861b}}@media(prefers-color-scheme:dark){{:root{{--bg:#12161c;--fg:#e8e6e1;--mut:#9aa3ad;--card:#1a2028;--line:#2d3641;--gold:#e0a526}}}}
body{{margin:0;background:var(--bg);color:var(--fg);font:15px/1.5 system-ui,sans-serif;padding:16px max(16px,env(safe-area-inset-left))}}main{{max-width:1150px;margin:0 auto}}h1{{font-size:1.3rem}}a{{color:var(--gold)}}
table{{border-collapse:collapse;width:100%;background:var(--card)}}td,th{{border:1px solid var(--line);padding:7px 10px;vertical-align:top;text-align:left}}small{{color:var(--mut)}}.w{{overflow-x:auto}}
tr.Mạnh .lv{{color:#2f7d6d;font-weight:700}}tr.Yếu .lv{{color:#c2562f;font-weight:700}}tr.Ẩn{{opacity:.55}}.bar{{position:sticky;top:0;background:var(--bg);padding:8px 0;display:flex;gap:10px;flex-wrap:wrap;align-items:center}}
button{{padding:8px 14px;border-radius:8px;border:1px solid var(--gold);background:var(--gold);color:#fff;font-weight:700;cursor:pointer}}textarea{{width:100%;height:150px;margin-top:10px;background:var(--card);color:var(--fg);border:1px solid var(--line)}}</style>
<main><h1>Checklist bài viết ({now[:10]})</h1><p>Bấm tên bài để mở trên web. Chọn “Giữ / Cần sửa / Ẩn” rồi bấm <b>Xuất danh sách</b>, copy gửi cho mình. Lựa chọn được lưu trong trình duyệt này.</p>
<div class=bar><button id=exp>Xuất danh sách</button><span id=cnt></span></div><div class=w><table><tr><th>Bài<th>Tiêu đề và đường dẫn<th>Trạng thái<th>Đánh giá của mình<th>Bạn chọn</tr>{''.join(cards)}</table></div>
<textarea id=out placeholder="Danh sách xuất ra hiện ở đây..."></textarea></main>
<script>
const K='pts-checklist-bai';let d={{}};try{{d=JSON.parse(localStorage.getItem(K)||'{{}}')}}catch(e){{}}
document.querySelectorAll('select[data-k]').forEach(s=>{{s.value=(d[s.dataset.k]||{{}}).v||'';s.onchange=save}});
document.querySelectorAll('input[data-n]').forEach(s=>{{s.value=(d[s.dataset.n]||{{}}).n||'';s.oninput=save}});
function save(){{d={{}};document.querySelectorAll('tr[data-id]').forEach(r=>{{const i=r.dataset.id;d[i]={{v:r.querySelector('select').value,n:r.querySelector('input').value}}}});try{{localStorage.setItem(K,JSON.stringify(d))}}catch(e){{}};cnt()}}
function cnt(){{const c={{}};Object.values(d).forEach(x=>{{if(x.v)c[x.v]=(c[x.v]||0)+1}});document.getElementById('cnt').textContent=Object.entries(c).map(([k,v])=>k+': '+v).join(' · ')}}
document.getElementById('exp').onclick=()=>{{const lines=[];document.querySelectorAll('tr[data-id]').forEach(r=>{{const v=r.querySelector('select').value,n=r.querySelector('input').value;if(v||n)lines.push('#'+r.dataset.id+' '+(v||'-')+(n?' — '+n:''))}});const t=lines.join('\\n')||'(chưa chọn bài nào)';const o=document.getElementById('out');o.value=t;o.select();try{{navigator.clipboard.writeText(t)}}catch(e){{}}}};
cnt();
</script></html>'''
open(A+'output/xem-truoc/checklist.html','w').write(page); print(len(rows),'bài; ghi checklist.html')
