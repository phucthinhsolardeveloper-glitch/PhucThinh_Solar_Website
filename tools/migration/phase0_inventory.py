#!/usr/bin/env python3
"""Phase 0 inventory for the Laravel 13 migration (read-only).

Writes Markdown and JSON artefacts to docs/16-laravel-migration/phase-0/.
Reads: this checkout, the local MariaDB container (schema and counts only, never row data),
and the media folder of the main checkout. Never connects to production.

Run from the repository root of the migration worktree:
    python3 tools/migration/phase0_inventory.py
"""
import datetime, hashlib, json, os, re, subprocess, sys

ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), '..', '..'))
OUT = os.path.join(ROOT, 'docs', '16-laravel-migration', 'phase-0')
PUBLIC = os.path.join(ROOT, 'domains', 'phucthinhsolar.com', 'public_html')
MEDIA_ROOT = os.environ.get('PTS_MEDIA_ROOT', '/home/admin01/src/Web_PTS_PHP/domains/phucthinhsolar.com/public_html/upload')
DOCKER = os.environ.get('PTS_DOCKER', '/mnt/c/Users/ADMIN/AppData/Local/Programs/DockerDesktop/resources/bin/docker.exe')
DB_CONTAINER = os.environ.get('PTS_DB_CONTAINER', 'pts-mariadb-local')
DB_NAME = os.environ.get('PTS_DB_NAME', 'phucthinhs_local')
NOW = datetime.datetime.now().astimezone().isoformat(timespec='seconds')


def run(args, cwd=ROOT):
    return subprocess.run(args, cwd=cwd, capture_output=True, text=True, check=True).stdout.strip()


def sql(query):
    # The root password is read inside the container; it never reaches this process or the output.
    # Queries go through sh inside double quotes, so they must not contain backticks or double quotes.
    assert '`' not in query and '"' not in query, query
    command = f'mariadb -u root -p"$MARIADB_ROOT_PASSWORD" --default-character-set=utf8mb4 -N -B {DB_NAME} -e "{query}"'
    out = subprocess.run([DOCKER, 'exec', DB_CONTAINER, 'sh', '-c', command], capture_output=True, text=True, check=True).stdout
    return [line.split('\t') for line in out.rstrip('\n').split('\n') if line.strip()]


def write(name, text):
    with open(os.path.join(OUT, name), 'w', encoding='utf8') as handle:
        handle.write(text.rstrip() + '\n')


def baseline():
    head = run(['git', 'rev-parse', 'HEAD'])
    master = run(['git', 'rev-parse', 'origin/master'])
    branch = run(['git', 'rev-parse', '--abbrev-ref', 'HEAD'])
    behind = run(['git', 'rev-list', '--count', f'HEAD..origin/master'])
    status = run(['git', 'status', '--porcelain'])
    worktrees = run(['git', 'worktree', 'list'])
    return {'generated': NOW, 'branch': branch, 'head': head, 'origin_master': master,
            'commits_behind_master': int(behind), 'dirty_paths': len(status.splitlines()) if status else 0,
            'worktrees': worktrees.splitlines()}


def schema():
    tables = sql("select table_name, engine, table_rows, round((data_length+index_length)/1024/1024,2) from information_schema.tables where table_schema=database() order by table_name")
    columns = sql("select table_name, column_name, column_type, is_nullable, column_key from information_schema.columns where table_schema=database() order by table_name, ordinal_position")
    indexes = sql("select table_name, index_name, group_concat(column_name order by seq_in_index) from information_schema.statistics where table_schema=database() group by table_name, index_name order by table_name, index_name")
    fks = sql("select table_name, column_name, referenced_table_name, referenced_column_name from information_schema.key_column_usage where table_schema=database() and referenced_table_name is not null")
    counts = {}
    for name, *_ in tables:
        counts[name] = int(sql(f"select count(*) from {name}")[0][0])
    by_table = {}
    for table, column, ctype, nullable, key in columns:
        by_table.setdefault(table, []).append(f'{column} {ctype}{"" if nullable == "YES" else " NOT NULL"}{" " + key if key else ""}')
    fingerprint = hashlib.sha256(json.dumps({'columns': columns, 'indexes': indexes}, sort_keys=True).encode()).hexdigest()
    return tables, by_table, indexes, fks, counts, fingerprint


def type_breakdown(table, column='type'):
    try:
        return sql(f"select {column}, count(*) from {table} group by {column} order by count(*) desc")
    except subprocess.CalledProcessError:
        return []


def api_inventory():
    rows = []
    for name in sorted(os.listdir(os.path.join(PUBLIC, 'api'))):
        if not name.endswith('.php'):
            continue
        text = open(os.path.join(PUBLIC, 'api', name), encoding='utf8', errors='replace').read()
        method = 'POST' if re.search(r"REQUEST_METHOD'\]\s*!==?\s*'POST'|\$_POST\[", text) else 'GET'
        params = sorted(set(re.findall(r"\$_(?:GET|POST|REQUEST)\[['\"]([a-zA-Z0-9_]+)['\"]\]", text)))
        writes = bool(re.search(r'\b(insert|update|delete)\b', text, re.I)) and bool(re.search(r'->(?:insert|update|delete|rawQuery)\(|\b(?:INSERT|UPDATE|DELETE)\s', text))
        rows.append((name, method, ', '.join(params[:10]) + (' …' if len(params) > 10 else ''), 'có' if writes else ''))
    bridges = sorted(f[:-3] for f in os.listdir(os.path.join(ROOT, 'frontend', 'server', 'routes', '_migration')) if f.endswith('.ts'))
    contracts = sorted(f for f in os.listdir(os.path.join(ROOT, 'frontend', 'shared', 'contracts')) if f.endswith('.ts'))
    return rows, bridges, contracts


def admin_inventory():
    rows = []
    for name in sorted(os.listdir(os.path.join(PUBLIC, 'administrator', 'sources'))):
        if not name.endswith('.php'):
            continue
        text = open(os.path.join(PUBLIC, 'administrator', 'sources', name), encoding='utf8', errors='replace').read()
        acts = sorted(set(re.findall(r"case\s+['\"]([a-zA-Z0-9_]+)['\"]\s*:", text)))
        rows.append((name[:-4], ', '.join(acts)))
    types = {}
    for name in sorted(os.listdir(os.path.join(PUBLIC, 'libraries', 'type'))):
        text = open(os.path.join(PUBLIC, 'libraries', 'type', name), encoding='utf8', errors='replace').read()
        types[name] = sorted(set(re.findall(r"\$nametype\s*=\s*['\"]([a-z0-9-]+)['\"]", text)))
    return rows, types


def domain_map():
    groups = {
        'Catalog': r'product|brand|catalog|search|campaign|fillter|tags',
        'Content/CMS': r'news|static|photo|content_section|seopage|about|home|solution|policy|video|gallery|project',
        'Leads/Contact/Dealer': r'contact|lead|dealer|newsletter|comment|review',
        'Commerce': r'cart|checkout|order|payment|shipping|ward|places|vnpay',
        'Identity/RBAC/Admin core': r'user|login|permission|requick|admin_security|setting|lang|cache',
        'Import/Export': r'excel|import|export|word|bulk',
    }
    files = []
    for folder in ('api', 'sources', 'administrator/sources', 'libraries'):
        base = os.path.join(PUBLIC, folder)
        for name in sorted(os.listdir(base)):
            if name.endswith('.php'):
                files.append(f'{folder}/{name}')
    mapped = {key: [] for key in groups}
    mapped['Chưa phân loại'] = []
    for path in files:
        for key, pattern in groups.items():
            if re.search(pattern, path.split('/')[-1], re.I):
                mapped[key].append(path)
                break
        else:
            mapped['Chưa phân loại'].append(path)
    return mapped


def media():
    if not os.path.isdir(MEDIA_ROOT):
        return []
    rows = []
    for name in sorted(os.listdir(MEDIA_ROOT)):
        path = os.path.join(MEDIA_ROOT, name)
        if not os.path.isdir(path):
            continue
        count = size = 0
        for folder, _, names in os.walk(path):
            for file in names:
                count += 1
                size += os.path.getsize(os.path.join(folder, file))
        rows.append((name, count, round(size / 1024 / 1024, 1)))
    return rows


def main():
    os.makedirs(OUT, exist_ok=True)
    base = baseline()
    tables, by_table, indexes, fks, counts, fingerprint = schema()
    engines = sorted({row[1] for row in tables})
    write('baseline.json', json.dumps({**base, 'schema_fingerprint': fingerprint, 'tables': len(tables)}, indent=2, ensure_ascii=False))

    lines = [f'# Schema local — kiểm kê Phase 0', '', f'Tạo lúc {NOW} từ container `{DB_CONTAINER}`, database `{DB_NAME}`. Chỉ đọc cấu trúc và số dòng, không đọc dữ liệu.', '',
             f'- Số bảng: {len(tables)}; engine: {", ".join(engines)}',
             f'- Dấu vân tay schema (cột + index, SHA-256): `{fingerprint}`',
             f'- Khóa ngoại thật: {len(fks)}' + (''.join(f'\n  - `{t}.{c}` → `{rt}.{rc}`' for t, c, rt, rc in fks)), '',
             '| Bảng | Engine | Số dòng | MB |', '|---|---|---:|---:|']
    lines += [f'| `{name}` | {engine} | {counts[name]} | {mb} |' for name, engine, _rows, mb in tables]
    lines += ['', '## Phân bố theo `type`', '']
    for table in ('table_product', 'table_news', 'table_photo', 'table_static', 'table_content_section', 'table_product_brand'):
        breakdown = type_breakdown(table, 'scope' if table == 'table_content_section' else 'type')
        if breakdown:
            lines.append(f'- `{table}`: ' + ', '.join(f'{k or "(trống)"} {v}' for k, v in breakdown))
    lines += ['', '## Cột theo bảng', '']
    for table in sorted(by_table):
        lines.append(f'<details><summary><code>{table}</code> ({len(by_table[table])} cột)</summary>\n\n' + '\n'.join(f'- `{c}`' for c in by_table[table]) + '\n\n</details>')
    lines += ['', '## Index', ''] + [f'- `{t}`.`{i}` ({c})' for t, i, c in indexes]
    write('schema.md', '\n'.join(lines))

    rows, bridges, contracts = api_inventory()
    lines = ['# API và contract — kiểm kê Phase 0', '', f'Tạo lúc {NOW}. Tham số đọc từ `$_GET/$_POST/$_REQUEST`; cột "Ghi" là dấu hiệu có câu lệnh ghi DB, cần xác minh tay.', '',
             '| File PHP API | Phương thức đoán | Tham số | Ghi |', '|---|---|---|---|']
    lines += [f'| `api/{n}` | {m} | {p} | {w} |' for n, m, p, w in rows]
    lines += ['', f'## Nitro bridge `/_migration/*` ({len(bridges)})', '', ', '.join(f'`{b}`' for b in bridges),
              '', f'## Contract TypeScript ({len(contracts)})', '', ', '.join(f'`{c}`' for c in contracts)]
    write('api-inventory.md', '\n'.join(lines))

    admin, types = admin_inventory()
    lines = ['# Admin legacy — ma trận chức năng Phase 0', '', f'Tạo lúc {NOW}. `act` lấy từ `case` trong `administrator/sources/*.php`.', '', '| Module (`com`) | Hành động (`act`) |', '|---|---|']
    lines += [f'| `{n}` | {a or "—"} |' for n, a in admin]
    lines += ['', '## Loại nội dung khai báo trong `libraries/type`', '']
    lines += [f'- `{f}`: {", ".join(t) if t else "(không tìm thấy $nametype)"}' for f, t in types.items()]
    write('admin-capabilities.md', '\n'.join(lines))

    mapped = domain_map()
    lines = ['# Bản đồ file → domain Phase 0', '', f'Tạo lúc {NOW}. Phân loại theo tên file, cần chủ lane xác nhận.', '']
    for key, files in mapped.items():
        lines += [f'## {key} ({len(files)})', ''] + [f'- `{f}`' for f in files] + ['']
    write('domain-map.md', '\n'.join(lines))

    lines = ['# Media — kiểm kê Phase 0', '', f'Tạo lúc {NOW} từ `{MEDIA_ROOT}` (bản local, không phải production).', '', '| Thư mục | Số file | MB |', '|---|---:|---:|']
    lines += [f'| `upload/{n}` | {c} | {s} |' for n, c, s in media()]
    write('media.md', '\n'.join(lines))
    print(f'OK: {len(tables)} bảng, {len(rows)} API PHP, {len(bridges)} bridge, {len(admin)} module Admin -> {OUT}')


if __name__ == '__main__':
    try:
        main()
    except subprocess.CalledProcessError as error:
        sys.exit(f'Lỗi khi chạy: {error.cmd}\n{error.stderr}')
