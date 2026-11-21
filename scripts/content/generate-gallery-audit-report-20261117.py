import json,csv,collections,html,pathlib
from urllib.parse import quote
R=pathlib.Path('/home/admin01/src/Web_PTS_PHP')
D=json.load(open('/tmp/pts-product-media-audit-20261117-after.json'))
L=D['products']
P=R/'docs/10-audits'
P.mkdir(exist_ok=True)
done={811,812,813,814}
def stage(x):
 n=x['image_count']
 return 'DA_BO_SUNG' if x['id'] in done else ('P0_KHONG_ANH' if n==0 else ('P1_MOT_ANH' if n==1 else ('P2_HAI_ANH' if n==2 else 'P3_BA_ANH_TRO_LEN')))
L.sort(key=lambda x:(x['image_count']>0,x['image_count']!=1,x['category'],x['brand'],x['name']))
with (P/'2026-11-17-product-gallery-inventory.csv').open('w',encoding='utf-8-sig',newline='') as f:
 w=csv.writer(f)
 w.writerow(['Product ID','SKU','Tên sản phẩm','Thương hiệu','Danh mục','Số ảnh hợp lệ (không Shopping)','Ảnh đại diện','Ảnh phụ / gallery','Đường dẫn sản phẩm','Trạng thái','Trường thông số phổ thông đã nhập (chưa phân loại)','Additional specs count','Specs HTML length'])
 for p in L:
  a=p['valid_images']
  w.writerow([p['id'],p['code'],p['name'],p['brand'],p['category'],p['image_count'],
  'https://phucthinhsolar.com'+a[0]['url'] if a else '',
  ' | '.join('https://phucthinhsolar.com'+i['url'] for i in a[1:]),
  'https://phucthinhsolar.com/san-pham/'+p['slug'],stage(p),
  p['spec_fields_filled'],p['additional_specs_count'],p['specs_html_length']])
cat=sorted(D['by_category'].items(),key=lambda z:-(z[1]['zero']+z[1]['one']))
M=['# Audit ảnh sản phẩm · Phúc Thịnh Solar','',
 'Snapshot production: 17/11/2026, sau lô 4 gallery SolaX.','',
 '## Kết quả','','- Tổng đang hiển thị: 374','- Không ảnh: 53','- Đúng 1 ảnh: 249','- Đúng 2 ảnh: 20','- Có từ 3 ảnh: 52','- Đã bổ sung 17/11/2026: 4 SKU (#811, #812, #813, #814).','','## Phân bổ theo danh mục','','|Danh mục|Tổng|0 ảnh|1 ảnh|2 ảnh|3+ ảnh|','|---|---:|---:|---:|---:|---:|']
for k,v in cat:
 M.append('|'+k+'|'+str(v['total'])+'|'+str(v['zero'])+'|'+str(v['one'])+'|'+str(v['two'])+'|'+str(v['three_plus'])+'|')
M+=['','## Phương pháp','',
'- Đếm unique URL có file thực: products.image, products.image_secondary, product_images visible; không tính ảnh Shopping.','- Ảnh đại diện hiện hữu của 4 SKU SolaX đã giữ nguyên. Bổ sung 4 infographic kỹ thuật ở bảng product_images.','- Nguồn thông số 4 infographic: https://vn.solaxpower.com/products/x3-neo-lv.html','- Độ đầy đủ thông số kỹ thuật cần đánh giá theo từng ngành hàng riêng. Số trường chung hiện chỉ là số lượng thô, KHÔNG phải tiêu chuẩn hoàn thiện.','- Ảnh cũ từ file báo giá có nhiều ảnh độ phân giải thấp; chưa được phép coi tất cả là ảnh xuất bản.','',
 '## Tệp','',
 '- 2026-11-17-product-gallery-inventory.csv: đầy đủ 374 SKU.','- 2026-11-17-product-gallery-audit.html: xem, tìm, lọc tất cả 374 SKU.','',
 '## Việc tiếp theo','',
 '1. 53 SKU không ảnh: săn ảnh hãng và tài nguyên nội bộ hợp lệ trước.','2. 249 SKU chỉ một ảnh: tiếp tục tìm góc chụp thật, sơ đồ thiết bị và infographic kỹ thuật theo model.','3. Audit field specs theo templates category rồi bổ sung thông số từng SKU có datasheet.','4. Mỗi batch chạy backup, ghi gallery idempotent, kiểm tra API và trang public, không thay đổi avatar/Shopping nếu không cần thiết.']
(P/'2026-11-17-product-gallery-audit.md').write_text('\n'.join(M),encoding='utf8')
tr=[]
for x in L:
 a=x['valid_images'];url='https://phucthinhsolar.com/san-pham/'+quote(x['slug'],safe='')
 img=('<img width="66" height="66" loading="lazy" src="https://phucthinhsolar.com'+html.escape(a[0]['url'])+'"/>' if a else '-')
 vals=[str(x['id']),x['code'] or '(trống)',x['name'],x['brand'] or '(trống)',x['category'],str(x['image_count']),stage(x)]
 td=''.join('<td>'+html.escape(v)+'</td>' for v in vals)
 tr.append('<tr data-text="'+html.escape((x['code']+' '+x['name']+' '+x['brand']).lower(),quote=True)+'" data-state="'+stage(x)+'"><td>'+img+'</td>'+td+'<td><a target="_blank" href="'+html.escape(url,quote=True)+'">Xem</a></td></tr>')
doc='''<!doctype html><html lang="vi"><head><meta charset="UTF-8"><title>PTS · Audit ảnh sản phẩm 17/11/2026</title><meta name="viewport" content="width=device-width,initial-scale=1"><style>body{font:14px system-ui;color:#0A2251;background:#f5f7fc;margin:0}header{padding:25px;background:#0A2251;color:white;border-bottom:4px solid #C9962E}main{max-width:1300px;margin:auto;padding:20px}h1{font-size:25px}.kpis{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin:20px 0}.kpis>div{background:white;padding:20px;border-radius:12px;border:1px solid #dee5f0}.kpis strong{font-size:29px;display:block}.controls{display:flex;gap:12px;margin:20px 0}input,select{font:inherit;padding:12px;border:1px solid #b9c8d8;border-radius:8px}input{flex:2}select{flex:1}table{background:white;border-collapse:collapse;width:100%}th{background:#0A2251;color:white;text-align:left;position:sticky;top:0}th,td{padding:11px;border-bottom:1px solid #e5e7eb}img{object-fit:contain}td:nth-child(7){font-weight:bold;font-size:18px}a{color:#1255a3}p{line-height:1.55}@media(max-width:800px){.kpis{grid-template-columns:1fr 1fr}table{font-size:11px}td,th{padding:6px}td:first-child,th:first-child{display:none}}</style></head><body><header><h1>Phúc Thịnh Solar · Audit ảnh sản phẩm</h1><div>17/11/2026 · Production · Không tính ảnh Shopping · Đã thêm ảnh cho 4 SKU SolaX</div></header><main><div class="kpis"><div><strong>374</strong>Tổng SKU public</div><div><strong>53</strong>Không ảnh</div><div><strong>249</strong>Chỉ 1 ảnh</div><div><strong>4</strong>Đã bổ sung</div></div><p>Đếm ảnh avatar, ảnh phụ và gallery có file tồn tại trên server; loại URL trùng. Tìm SKU bằng ô tìm kiếm bên dưới. Xem tệp CSV để có URL hình ảnh của từng sản phẩm.</p><div class="controls"><input id="q" placeholder="Tìm SKU, tên hoặc hãng..."/><select id="state"><option value="">Mọi trạng thái</option><option value="P0_KHONG_ANH">0 ảnh</option><option value="P1_MOT_ANH">1 ảnh</option><option value="DA_BO_SUNG">Đã bổ sung</option><option value="P2_HAI_ANH">2 ảnh</option><option value="P3_BA_ANH_TRO_LEN">3+ ảnh</option></select></div><p id="count"></p><table><thead><tr><th>Ảnh</th><th>ID</th><th>SKU</th><th>Tên</th><th>Hãng</th><th>Danh mục</th><th>Số ảnh</th><th>Trạng thái</th><th>Link</th></tr></thead><tbody>'''+''.join(tr)+'''</tbody></table></main><script>const rows=[...document.querySelectorAll('tbody tr')],q=document.getElementById('q'),state=document.getElementById('state');function f(){let n=0;const s=q.value.trim().toLowerCase();for(const r of rows){const v=(!s||r.dataset.text.includes(s))&&(!state.value||r.dataset.state===state.value);r.hidden=!v;if(v)n++}document.getElementById('count').textContent='Đang hiển thị '+n+'/374 SKU';}q.addEventListener('input',f);state.addEventListener('input',f);f();</script></body></html>'''
(P/'2026-11-17-product-gallery-audit.html').write_text(doc,encoding='utf8')
for v in ['2026-11-17-product-gallery-inventory.csv','2026-11-17-product-gallery-audit.html','2026-11-17-product-gallery-audit.md']:
 f=P/v;print(str(f),f.stat().st_size)
print('COUNTS',len(L),collections.Counter(stage(p) for p in L))
