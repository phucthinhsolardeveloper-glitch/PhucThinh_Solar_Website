#!/usr/bin/env python3
"""Build review-only handoff files for 26 one-image floodlights; never modify production."""
from pathlib import Path
from urllib.parse import quote
import json,csv,html,hashlib,zipfile,collections
BASE=Path('/home/admin01/src/Web_PTS_PHP')
Q=BASE/'backend/resources/product-gallery/2026-11-17/den-pha-qa-batch-1'
A=Q/'candidate-alpha'
D=Q/'detail-candidates'
REPORT=BASE/'docs/10-audits';REPORT.mkdir(parents=True,exist_ok=True)
raw=json.loads((A/'manifest.json').read_text())
audit=json.loads(Path('/tmp/pts-den-pha-deep-20261117.json').read_text())['products']
target=['DB-760','DB-7300','DB-7500','DB-71000','DB-9100','DB-9200','DB-9300','DB-9600','DB-9800','DB-91000','DB-A100','DB-A200','DB-A300','DB-A500','BCT-WR1.0','BCT-WR4.0','JD-V200','JD-V300','JD-8860L-VANG','JD-8800L-VANG','JD-8200L-VANG','JD-8300L-VANG','JD-6128','JD-18200','JD-18300','JD-18500']
assert len(target)==26 and len(set(target))==26
prod={p['code']:p for p in audit}
assert len(prod)>=72 and all(x in prod for x in target)
assert all(len(prod[x]['images'])==1 for x in target)

def status(code):
 if code in {'DB-760','DB-7300','DB-7500','DB-71000'}:
  return 'QA_INTERNAL_PREVIEW','Có bản tách nền từ EMF và ảnh cận cảnh LED đúng nhãn SKU; cần duyệt chất lượng và độ khác biệt so với avatar'
 if code=='JD-6128':
  return 'QA_INTERNAL_PREVIEW','Đã tách nền ảnh thật 1620x1080 của chóa đèn; cần QA chi tiết mép và đúng bộ bán thực tế'
 if code in {'BCT-WR1.0','BCT-WR4.0'}:
  return 'QA_NEEDS_IMAGE_RIGHTS','Có nhiều góc chụp nguồn hãng; ảnh gốc chỉ 720px, cần xin nguồn nét/ủy quyền sử dụng, xác minh SKU - thông số'
 if code in {'JD-V200','JD-V300'}:
  return 'REFERENCE_ONLY','Đã tải ảnh đối chiếu 1080px từ reseller, góc nhìn gần giống avatar hiện tại, có logo bên thứ ba; không đăng'
 if code=='JD-18300':
  return 'QA_EXTERNAL_RIGHTS_PENDING','Nguồn nội bộ nhầm JD-18200/JDE-8300 đã loại; tìm được ảnh thật JD-18300 300W từ đại lý khác và đã tách nền; nguồn 750px, cần xác nhận quyền sử dụng'
 if code.endswith('-VANG'):
  return 'NEEDS_YELLOW_LIGHT_PROOF','Cần ảnh đúng biến thể ánh sáng vàng và mã model, không lấy ảnh trắng đổi màu giả'
 if code.startswith('DB-9'):
  return 'BLOCKED_REUSED_SERIES_IMAGE','Nguồn *_2.png ở 6 SKU DB-9xx dùng ảnh bố cục giống nhau; nhãn model cần xác minh từng file, ảnh gốc chỉ 481x321'
 if code.startswith('DB-A'):
  return 'NEEDS_DIFFERENT_ANGLE','Đã có ảnh nguồn bộ đèn+tấm pin, gần giống avatar hiện có; cần ảnh góc nghiêng hoặc bộ phụ kiện thật'
 return 'SOURCE_RESEARCH','Cần ảnh gốc góc mới chính xác SKU và độ nét, không được gán ảnh cùng series nếu chưa xác minh'

items=[]
for code in target:
 p=prod[code];state,note=status(code)
 images=[x for x in raw['items'] if x['sku']==code and x.get('output_file')]
 details=[str(x) for x in sorted(D.glob('pts-'+code.lower()+'-original-led-detail-qareview.jpg'))]
 items.append({'id':p['id'],'code':code,'brand':p['brand'],'slug':p['slug'],'current_image':p['images'][0]['url'],'current_image_px':str(p['images'][0]['width'])+'x'+str(p['images'][0]['height']),
 'current_image_count':len(p['images']),'status':state,'reason':note,
 'candidate_count':len(images)+len(details),
 'candidate_files':[x['output_file'] for x in images]+details})
assert len(items)==26
csvp=REPORT/'2026-11-17-den-pha-26-image-qa.csv'
with csvp.open('w',encoding='utf-8-sig',newline='') as f:
 writer=csv.DictWriter(f,fieldnames=['id','code','brand','slug','current_image_count','current_image_px','status','candidate_count','reason','candidate_files'])
 writer.writeheader()
 for x in items:writer.writerow({k:'; '.join(v)if isinstance(v,list)else v for k,v in x.items()if k in writer.fieldnames})
print('CSV',csvp)

fn=REPORT/'2026-11-17-den-pha-qa-batch-1.html'
imgPrefix='../../backend/resources/product-gallery/2026-11-17/den-pha-qa-batch-1/'
code2new={x['code']:x for x in items}
def local_img(path):
 return '../../'+str(Path(path).relative_to(BASE))
def fmt(s):return html.escape(str(s))
styles="""<style>*{box-sizing:border-box}body{margin:0;background:#eef2f7;color:#0a2251;font-family:system-ui,-apple-system,Segoe UI,sans-serif}.wrap{max-width:1450px;padding:28px;margin:auto}h1{font-size:29px;line-height:1.3;margin:0 0 8px}h2{margin:30px 0 12px}.muted{color:#536277;font-size:14px}.caps{display:flex;gap:14px;flex-wrap:wrap;margin:18px 0}.caps div{padding:14px 20px;background:white;border-radius:10px;border:1px solid #dae2eb}.caps b{display:block;font-size:28px}a{color:#0b5d9b}.sheet{display:flex;align-items:flex-start;gap:15px;flex-wrap:wrap}.product{background:white;padding:18px;border:1px solid #d7e2ed;border-radius:12px;margin:18px 0}.product h3{margin:0 0 12px}.photos{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:12px}.photo{border:1px solid #d5dde6;border-radius:8px;overflow:hidden}.photo .thumb{height:235px;display:flex;align-items:center;justify-content:center;background:white}.thumb.checker{background-color:white;background-image:linear-gradient(45deg,#dce2e9 25%,transparent 25%,transparent 75%,#dce2e9 75%,#dce2e9),linear-gradient(45deg,#dce2e9 25%,transparent 25%,transparent 75%,#dce2e9 75%,#dce2e9);background-size:32px 32px;background-position:0 0,16px 16px}.thumb img{max-width:100%;max-height:100%;object-fit:contain}.photo p{font-size:12px;color:#42516a;margin:9px 10px;overflow-wrap:anywhere}.tag{display:inline-block;padding:4px 9px;border:1px solid #cfd9e4;border-radius:99px;font-size:12px;font-weight:650}.warn{color:#985800}.safegreen{color:#107253}.t{width:100%;border-collapse:collapse;background:white}td,th{padding:8px 12px;text-align:left;border:1px solid #d5dfe9;font-size:14px}th{background:#0a2251;color:white}input{border:1px solid #b8c5d6;padding:10px 14px;min-width:300px;border-radius:7px;font:inherit}footer{font-size:12px;color:#586b82;padding:28px 0}@media(max-width:680px){.wrap{padding:12px}.photos{grid-template-columns:repeat(2,minmax(0,1fr))}.photo .thumb{height:165px}table{display:block;overflow:auto}h1{font-size:22px}}</style>"""
lines=['<!doctype html><html lang="vi"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">','<title>PTS · QA bộ ảnh đèn pha 26 SKU · 17/11/2026</title>',styles,'<div class="wrap"><h1>Audit & QA ảnh đèn pha · 26 SKU</h1><p class="muted">Lô thử nghiệm 17/11/2026 · Dữ liệu sản phẩm production chỉ đọc · Ảnh xuất trong kho local máy công ty, CHƯA đăng website. Nguồn bên thứ ba cần được cho phép sử dụng trước khi nhập sản phẩm.</p>']
counts=collections.Counter(x['status'] for x in items)
lines+=['<div class="caps"><div><b>26</b>Mã 1 ảnh</div><div><b>5</b>Mã có nguồn nội bộ đã cắt nền</div><div><b>2</b>Mã Blue Carbon có tham chiếu hãng</div><div><b>1</b>Trường hợp sai model bị chặn</div></div>']
lines.append('<p><strong>Nguyên tắc:</strong> QA tách nền khác QA độ nét gốc; ảnh xuất 1400px không chứng minh file gốc có 1400px chi tiết. Không dùng ảnh AI dựng phần cứng. Giữ nguyên ảnh avatar và thông số đang live.</p>')
lines.append('<h2>1. Bản xem trước sản phẩm & ảnh ứng viên</h2>')
show=['DB-760','DB-7300','DB-7500','DB-71000','BCT-WR1.0','BCT-WR4.0','JD-6128','JD-V200','JD-V300','JD-18300']
for code in show:
 x=code2new[code]
 lines.append('<section class="product"><h3>'+fmt(x['brand']+' · '+code)+' <small class="tag">'+fmt(x['status'])+'</small></h3>')
 lines.append('<p class="muted">'+fmt(x['reason'])+'</p><div class="photos">')
 lines.append('<div class="photo"><div class="thumb"><img loading="lazy" src="https://phucthinhsolar.com'+fmt(x['current_image'])+'"></div><p>Ảnh production hiện tại · '+fmt(x['current_image_px'])+'</p></div>')
 for candidate in x['candidate_files']:
  pp=Path(candidate)
  # candidate is a verified local path; no sandbox inference
  if not pp.is_file():continue
  m=next((i for i in raw['items'] if i.get('output_file')==candidate),None)
  tag=m['usage'] if m else 'Đèn LED cận cảnh từ ảnh gốc'
  checker=pp.suffix.lower() in ('.png','.webp')
  lines.append('<div class="photo"><div class="thumb'+(' checker'if checker else '')+'"><img loading="lazy" src="'+fmt(local_img(pp))+'"></div><p>'+fmt(tag)+(('<span class="warn"> · Chưa xác nhận quyền sử dụng</span>')if m and m.get('requires_usage_permission')else '')+'</p></div>')
 lines.append('</div></section>')
lines.append('<h2>2. Danh sách xử lý đủ 26 mã</h2>')
lines.append('<input id="find" placeholder="Lọc theo mã, hãng, trạng thái" oninput="document.querySelectorAll(\'tbody tr\').forEach(r=>r.style.display=r.textContent.toLowerCase().includes(this.value.toLowerCase())?\'\':\'none\')">')
lines.append('<table class="t"><thead><tr><th>ID</th><th>Mã sản phẩm</th><th>Hãng</th><th>Ảnh hiện tại</th><th>Ảnh ứng viên</th><th>Trạng thái</th></tr></thead><tbody>')
for x in items:
 lines.append('<tr><td>'+str(x['id'])+'</td><td><a href="https://phucthinhsolar.com/san-pham/'+fmt(x['slug'])+'">'+fmt(x['code'])+'</a></td><td>'+fmt(x['brand'])+'</td><td>1</td><td>'+str(x['candidate_count'])+'</td><td>'+fmt(x['status'])+'</td></tr>')
lines.append('</tbody></table><h2>3. Điều kiện trước khi nhập website</h2>')
lines.append('<p>Ảnh đúng model và nhãn; không nhầm ánh sáng vàng/trắng, không trùng với avatar; kiểm tra đường cắt nền trên sáng và tối, bóng mờ, viền, thiết bị lắp đặt; đủ chi tiết ở màn hình 960px; quyền sử dụng ảnh nhà sản xuất/đại lý được xác nhận; mọi ảnh phải có SHA256, manifest và bản backup.</p>')
lines.append('<footer>QA source folder: <code>backend/resources/product-gallery/2026-11-17/den-pha-qa-batch-1</code> · Đã ghi nhận các trường hợp nhầm JD-18300/JD-18200, JDE-8300, và Blue Carbon LED mismatch. Chỉ kiểm thử trong staging.</footer></div></html>')
fn.write_text('\n'.join(lines),encoding='utf-8');print('HTML',fn,len(lines))

md=REPORT/'2026-11-17-den-pha-26-qa-report.md'
md.write_text('''# QA ảnh đèn pha 26 mã · 17/11/2026

## Kết quả thực tế
- Phạm vi production được đọc: 72 đèn pha, đúng 26 mã chỉ có một ảnh; không kiểm tra ảnh Shopping, không thay đổi database hoặc upload.
- Đã khôi phục từ nguồn EMF nội bộ các mẫu ORIANA DB-760 / DB-7300 / DB-7500 / DB-71000 bằng Windows GDI+; tạo 4 ảnh PNG có alpha (đề xuất nâng cấp ảnh chính) và 4 ảnh **chi tiết LED được cắt từ file EMF**, nhãn model rõ, chưa gắn gallery.
- Đã tách nền ảnh thật đèn chóa JINDIAN JD-6128 từ file nội bộ 1620×1080; đây là ứng viên bổ sung gallery tốt nhất về độ phân giải nguồn.
- Đã lấy 6 ảnh chính hãng BCT-WR1.0 và 6 ảnh chính hãng BCT-WR4.0 để đối chiếu; tạo 5 ứng viên từ hãng gồm nhiều góc nhìn, ảnh mặt sau, bản vẽ kích thước; các ảnh hãng 720×720 nên còn giới hạn độ nét. Quyền tái sử dụng cần được xác minh.
- Đã tìm ảnh JD-V200/JD-V300 1080×1080 ở đại lý Sunny Solar nhưng giống góc ảnh hiện tại và có logo bên thứ ba; giữ reference, không tự đăng.
- **BLOCK:** JD-18300.emf hiện rõ chữ **JD-18200 / 200W**; ảnh JD-18300/10.png và /11.png hiện rõ **JDE-8300**; các ảnh này là ảnh sai SKU nên bị loại. Các ảnh 6, 1, 4 và 9 trong thư mục JD-18300 cũng có ngoại hình không khớp 6 cụm LED của sản phẩm live.
- **SOURCE MỚI (CHỈ THAM KHẢO):** Ảnh chụp thật JD-18300 300W 750x750 của nhà phân phối Hoàng Quốc Bảo có nhãn JD-18300 / 300W, giống mặt đèn 6 cụm trên website; đã tách nền làm ứng viên nhưng **chưa được phép đăng** vì chưa xác minh quyền sử dụng và độ nét gốc chỉ 750px. Nguồn: https://www.hoangquocbao.com/hoanghung/29/images/hinh-thuc-te-jd-18300-2.webp
- **BLOCK:** BCT-WR1.0/4.png và BCT-WR4.0/8.png từ pricebook có số cụm/chip LED khác ảnh live, nên không được dùng tự động.
- Nhóm DB-9xx có ảnh `*_2.png` giống nhau giữa nhiều model, ảnh nguồn chỉ 481×321, phải xác minh nhãn mã trước khi dùng.
- Nguồn catalog công suất/bảo hành ở một số nhóm có mâu thuẫn; batch ảnh **không gắn thông số mới** lên ảnh.

## Tài nguyên
- [Báo cáo xem trước 26 SKU](2026-11-17-den-pha-qa-batch-1.html)
- [Danh sách CSV](2026-11-17-den-pha-26-image-qa.csv)
- Manifest file+SHA256: `backend/resources/product-gallery/2026-11-17/den-pha-qa-batch-1/candidate-alpha/manifest.json`
- Ảnh trong `candidate-alpha` và `detail-candidates`; nguồn `emf-converted` / `external-review-only`.
- Công cụ chạy: `scripts/content/render-emf-product-sources-20261117.ps1`, `scripts/content/build-den-pha-qa-batch-1-20261117.py`.

## Kiểm tra tiếp theo
1. Tách rõ ứng viên nội bộ và ảnh cần quyền hãng/đại lý. Chỉ đưa ảnh đã duyệt vào batch apply với mã SHA256 và backup.
2. Kiểm tra nguồn ảnh thực 1:1 của ORIANA và mã sản phẩm trên mặt đèn; ưu tiên xin ảnh studio gốc >1200px nếu có.
3. Đèn JD-18300: cần ảnh thật chụp JD-18300 300W từ nhà phân phối; không sử dụng tài liệu JD-18200/JDE-8300.
4. JD-V200/300: xin góc mặt sau, giá đỡ, remote thật thay vì nhân bản ảnh chính.
5. Đánh giá 16 SKU còn lại từ CSV; tách nhóm ánh sáng vàng có ảnh bằng chứng thực tế.

**STOP SHIP:** Chưa đăng hoặc chỉnh sửa production. Ảnh hãng và đại lý chỉ dùng đối chiếu nội bộ khi chưa xác minh giấy phép sử dụng.
''',encoding='utf8')
print('MARKDOWN',md)

# Split source-owned and rights-unverified candidate assets into two very distinct archives.
def make_zip(out, paths):
 with zipfile.ZipFile(out,'w',compression=zipfile.ZIP_DEFLATED,compresslevel=6) as z:
  for p in paths:
   if p.is_file():z.write(p,arcname=p.relative_to(Q))
  z.writestr('READ-ME-FIRST.txt','PHUC THINH SOLAR | DRAFT IMAGE QA | 2026-11-17\nNOT PUBLISHED. Do not import without model, visual, rights QA. Source image 1400px render may originate from lower native detail.\n')
 print('ZIP',out,'files',len(paths),round(out.stat().st_size/1024/1024,2),'MB')
internal=[Path(x['output_file']) for x in raw['items'] if x.get('output_file') and x['origin'].startswith('internal')] + [Path(x) for x in sum((i['candidate_files'] for i in items if i['code'].startswith('DB-7')),[]) if 'detail-candidates' in x]
internal=list(dict.fromkeys(internal))
make_zip(Q/'QA-internal-ORIANA-JD6128-NOT-PUBLISHED.zip',internal)
external=[Path(x['output_file']) for x in raw['items'] if x.get('output_file') and x['origin'] in ('blue-carbon-manufacturer-direct','third-party-retailer')]
make_zip(Q/'QA-external-REFERENCES-RIGHTS-REQUIRED.zip',external)
print('STATUS',dict(counts),'ACTIVE_CANDIDATES',sum(x['candidate_count'] for x in items))
