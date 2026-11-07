#!/usr/bin/env python3
"""Which parts of the PHP backend are still used, and by whom.

Production routing (deploy/vps/Caddyfile) sends every public page to Nuxt; PHP still answers /api/*,
/administrator, media (/upload, /thumbs, /watermark), sitemap.xml, robots.txt, /account/*, /ngon-ngu and
POST /gio-hang. This script follows references from those entry points and from the Nuxt code, and
writes docs/16-laravel-migration/phase-0/usage-map.md. Static analysis only: a "no reference" row is a
candidate to drop, to confirm before deleting anything.
"""
import os, re

ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), '..', '..'))
PUB = os.path.join(ROOT, 'domains', 'phucthinhsolar.com', 'public_html')
OUT = os.path.join(ROOT, 'docs', '16-laravel-migration', 'phase-0', 'usage-map.md')
REQUIRE = re.compile(r"(?:require|include)(?:_once)?\s*\(?\s*[^;]*?['\"]([A-Za-z0-9_./-]+\.php)['\"]")


def files(folder, exts=('.php',)):
    for base, _, names in os.walk(folder):
        for name in names:
            if name.endswith(exts):
                yield os.path.join(base, name)


def read(path):
    try:
        return open(path, encoding='utf8', errors='replace').read()
    except OSError:
        return ''


def requires(path):
    return {os.path.basename(m) for m in REQUIRE.findall(read(path))}


lib_dir = os.path.join(PUB, 'libraries')
libs = {name: os.path.join(lib_dir, name) for name in os.listdir(lib_dir) if name.endswith('.php')}


def closure(start_paths):
    seen, stack = set(), list(start_paths)
    while stack:
        path = stack.pop()
        for name in requires(path):
            if name in libs and name not in seen:
                seen.add(name)
                stack.append(libs[name])
    return seen


nuxt_text = '\n'.join(read(p) for d in ('frontend/server', 'frontend/app', 'frontend/shared') for p in files(os.path.join(ROOT, d), ('.ts', '.vue')))
admin_paths = list(files(os.path.join(PUB, 'administrator')))
admin_text = '\n'.join(read(p) for p in admin_paths if 'ckfinder' not in p and 'elfinder' not in p)
admin_js = '\n'.join(read(p) for p in files(os.path.join(PUB, 'administrator', 'assets'), ('.js',)))
legacy_fe_text = '\n'.join(read(p) for d in ('sources', 'templates') for p in files(os.path.join(PUB, d))) + '\n'.join(read(p) for p in files(os.path.join(PUB, 'assets', 'js'), ('.js',)))

api_rows, nuxt_api = [], []
for name in sorted(os.listdir(os.path.join(PUB, 'api'))):
    if not name.endswith('.php'):
        continue
    users = []
    if name in nuxt_text:
        users.append('Nuxt')
        nuxt_api.append(os.path.join(PUB, 'api', name))
    if name in admin_text or name in admin_js:
        users.append('Admin')
    if name in legacy_fe_text:
        users.append('giao diện PHP cũ')
    api_rows.append((name, ', '.join(users) or 'không thấy tham chiếu'))

nuxt_libs = closure(nuxt_api)
admin_libs = closure([p for p in admin_paths if 'ckfinder' not in p and 'elfinder' not in p])
entry_libs = closure([os.path.join(PUB, 'index.php')])
lib_rows = []
for name in sorted(libs):
    tags = []
    if name in nuxt_libs:
        tags.append('API cho Nuxt')
    if name in admin_libs:
        tags.append('Admin')
    if name in entry_libs and not tags:
        tags.append('chỉ index.php (sitemap, account, giỏ POST, ảnh, giao diện cũ)')
    lib_rows.append((name, ', '.join(tags) or 'không thấy require (có thể autoload class hoặc thừa)'))

sources = sorted(n for n in os.listdir(os.path.join(PUB, 'sources')) if n.endswith('.php'))
still_routed = {'user.php': '/account/* (đăng nhập thành viên)', 'order.php': 'POST /gio-hang (giỏ hàng, đặt hàng)'}
source_rows = [(n, still_routed.get(n, 'chỉ giao diện PHP cũ; Caddy đã chuyển trang này sang Nuxt')) for n in sources]

lines = ['# Bản đồ sử dụng backend PHP', '',
         'Tạo bởi `tools/migration/usage_map.py` (phân tích tĩnh). Quy tắc chuyển đổi: Laravel chỉ dựng lại phần',
         'Nuxt hoặc Admin dùng; phần "không thấy tham chiếu" và "chỉ giao diện PHP cũ" là ứng viên bỏ, phải xác minh',
         'bằng log truy cập production trước khi xóa.', '',
         f'## API `api/*.php` ({len(api_rows)})', '', '| File | Ai dùng |', '|---|---|']
lines += [f'| `{n}` | {u} |' for n, u in api_rows]
lines += ['', f'## Trang PHP `sources/*.php` ({len(source_rows)})', '', '| File | Còn được gọi từ |', '|---|---|']
lines += [f'| `{n}` | {u} |' for n, u in source_rows]
lines += ['', f'## Thư viện `libraries/*.php` ({len(lib_rows)})', '', '| File | Được nạp bởi |', '|---|---|']
lines += [f'| `{n}` | {u} |' for n, u in lib_rows]
counts = {
    'api Nuxt': sum('Nuxt' in u for _, u in api_rows),
    'api không Nuxt': sum('Nuxt' not in u for _, u in api_rows),
    'lib cho Nuxt': sum('API cho Nuxt' in u for _, u in lib_rows),
    'lib chỉ Admin': sum(u == 'Admin' for _, u in lib_rows),
    'lib chỉ index/giao diện cũ hoặc không thấy': sum('API cho Nuxt' not in u and 'Admin' not in u for _, u in lib_rows),
}
lines[1:1] = ['', 'Tóm tắt: ' + '; '.join(f'{k} {v}' for k, v in counts.items()) + '.']
open(OUT, 'w', encoding='utf8').write('\n'.join(lines) + '\n')
print(counts)
