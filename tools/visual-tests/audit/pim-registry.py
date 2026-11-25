#!/usr/bin/env python3
"""Registry of product data problems, read from the public site only (GET). Changes nothing.

Usage: python3 pim-registry.py OUT.csv [--base https://phucthinhsolar.com]
Every row quotes the published values it is based on; a row is a question for the owner or a datasheet,
never a corrected value.
"""
import csv, json, re, sys, time, urllib.parse, urllib.request

BASE = sys.argv[sys.argv.index('--base') + 1] if '--base' in sys.argv else 'https://phucthinhsolar.com'


def get(url):
    request = urllib.request.Request(url, headers={'User-Agent': 'pts-pim-audit'})
    with urllib.request.urlopen(request, timeout=40) as response:
        return response.read().decode('utf-8')


def figures(text, unit):
    """Numbers written with this unit, e.g. {7.5} for '7,5kW'."""
    return {float(m.replace(',', '.')) for m in re.findall(r'(?<![\d.,])(\d+(?:[.,]\d+)?)\s?' + unit + r'(?![a-zA-Z])', text or '', re.I)}


def years(text):
    return {int(m) for m in re.findall(r'bảo hành[^.;<]{0,40}?(\d{1,2})\s*năm', text or '', re.I)}


def compact(text):
    return re.sub(r'[^a-z0-9]', '', (text or '').lower())


def check(data):
    product = data['product']
    name = product['name']
    specs = {s['label']: str(s['value']).strip() for s in product.get('specifications', [])}
    metrics = {m['label']: str(m['value']).strip() for m in product.get('decisionMetrics', [])}
    body = ' '.join(str(b.get('text', '')) + ' ' + json.dumps(b.get('items', ''), ensure_ascii=False) for b in data.get('content', {}).get('blocks', []))
    found = []
    if not (product.get('brand') or '').strip():
        found.append(('thieu_hang', 'Không có hãng', ''))
    if not (product.get('code') or '').strip():
        found.append(('thieu_ma', 'Không có mã sản phẩm', ''))
    if not (product.get('summary') or '').strip():
        found.append(('thieu_mo_ta', 'Không có mô tả ngắn', ''))
    empty = [label for label, value in specs.items() if not value]
    if empty:
        found.append(('thong_so_trong', 'Có dòng thông số để trống', '; '.join(empty)))
    model = specs.get('Model') or specs.get('Mã model')
    code = product.get('code') or ''
    if model and code and compact(code) not in compact(model) and compact(model) not in compact(code):
        found.append(('lech_ma_model', 'Mã sản phẩm khác model trong thông số', f'mã={code} | model={model}'))
    # The rated figure the page states, against the figure in the product name.
    for label, value in specs.items():
        if not re.match(r'công suất (danh định|định mức|ac danh định)', label, re.I):
            continue
        for unit, scale in (('kw', 1000), ('wp?', 1)):
            rated = {v * scale for v in figures(value, unit)}
            if not rated:
                continue
            named = {v * 1000 for v in figures(name, 'kw')} | figures(name, 'wp?')
            if named and not rated & named:
                found.append(('lech_cong_suat', 'Công suất trong tên khác thông số', f'tên={name} | {label}={value}'))
            break
    stated = {}
    for place, text in (('mô tả ngắn', product.get('summary')), ('thẻ bảo hành', ' '.join('bảo hành ' + v for k, v in metrics.items() if k.lower() == 'bảo hành')),
                        ('thông số', ' '.join('bảo hành ' + v for k, v in specs.items() if 'bảo hành' in k.lower())), ('nội dung', body)):
        for year in years(text):
            stated.setdefault(year, []).append(place)
    if len(stated) > 1:
        found.append(('bao_hanh_nhieu_moc', 'Một trang ghi nhiều mốc bảo hành (có thể là bảo hành từng bộ phận, cần đọc)', '; '.join(f'{y} năm: {", ".join(sorted(set(p)))}' for y, p in sorted(stated.items()))))
    pricing = data.get('pricing') or {}
    regular, sale = pricing.get('regularPrice'), pricing.get('salePrice')
    if pricing.get('mode') == 'price' and not regular and not sale:
        found.append(('thieu_gia', 'Hiện giá nhưng không có giá', ''))
    if regular and sale and sale >= regular:
        found.append(('gia_giam_khong_thap_hon', 'Giá khuyến mãi không thấp hơn giá thường', f'{sale} >= {regular}'))
    return found


def main():
    out = sys.argv[1]
    slugs = sorted(set(re.findall(r'<loc>[^<]*/san-pham/([a-z0-9-]+)/?</loc>', get(BASE + '/sitemap.xml'))))
    rows, failed = [], []
    for slug in slugs:
        try:
            data = json.loads(get(f'{BASE}/_api/product-detail?slug={urllib.parse.quote(slug)}'))
            for key, problem, evidence in check(data):
                rows.append({'ma_loi': key, 'van_de': problem, 'ma_sp': data['product'].get('code', ''), 'ten': data['product']['name'],
                             'url': f'{BASE}/san-pham/{slug}', 'bang_chung': evidence})
        except Exception as error:  # keep going; unread pages are reported, never counted as clean
            failed.append((slug, str(error)[:120]))
        time.sleep(0.15)
    with open(out, 'w', encoding='utf-8-sig', newline='') as handle:
        writer = csv.DictWriter(handle, ['ma_loi', 'van_de', 'ma_sp', 'ten', 'url', 'bang_chung'], lineterminator='\n')
        writer.writeheader()
        writer.writerows(sorted(rows, key=lambda r: (r['ma_loi'], r['ten'])))
    counts = {}
    for row in rows:
        counts[row['ma_loi']] = counts.get(row['ma_loi'], 0) + 1
    print(f'pages read {len(slugs) - len(failed)}/{len(slugs)}; rows {len(rows)}', json.dumps(counts, ensure_ascii=False))
    for slug, error in failed[:10]:
        print('UNREAD', slug, error)
    return 1 if failed or not slugs else 0


if __name__ == '__main__':
    sys.exit(main())
