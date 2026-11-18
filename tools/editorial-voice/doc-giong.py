#!/usr/bin/env python3
"""Tự chấm giọng văn: python3 doc-giong.py <bản gốc .html|.md> <bản mới .md>
Báo: câu quá dài, câu ra lệnh, rào chắn, từ cứng, đề mục theo khuôn, chữ in hoa, số liệu lệch.
Chỉ là lớp phụ; người đọc mới là thước đo cuối."""
import html
import re
import sys
from pathlib import Path

STIFF = {  # từ cứng: số lần tối đa cho phép trong cả bài
    "danh định": 1, "tích lũy": 0, "quy mô": 1, "tình huống": 1, "tham chiếu": 0, "bảo đảm": 1, "đối chiếu": 1,
    "nhằm": 1, "đồng thời": 1, "cho phép": 1, "khả dụng": 1, "kiểm chứng": 0, "xác minh": 1, "cấu hình": 1, "thể hiện": 1,
}
HEDGES = ["tùy model", "tùy từng model", "cần xác minh", "không mặc định", "không bảo đảm", "chưa đủ", "cần kiểm chứng",
          "không nên mặc định", "tùy thuộc vào đúng", "theo đúng model", "đúng mã"]
COMMAND_START = ("hãy ", "đề nghị ", "yêu cầu ", "đối chiếu ", "kiểm tra ", "xác nhận ", "lưu ý ", "cần ")
TEMPLATE_HEADINGS = [r"\bchecklist\b", r"tình huống đọc nhanh", r"minh họa, không phải", r"lưu ý quan trọng", r"những điều cần biết",
                     r"\b\d+ (?:ví dụ|lý do|lợi ích|lưu ý) ", r"top \d+", r"^(?:bảy|sáu|bốn) ", r"ba câu hỏi trước khi"]
NUM = re.compile(r"\d[\d.,]*(?:\s?(?:kWp|kWh|kW|Wp|Wh|W|V|A|Ah|%|mm|cm|m|kg|năm|giờ|tháng|ngày|lux|lm|TW|GW|MW)(?![A-Za-zÀ-ỹ]))?", re.I)


def plain(text: str) -> str:
    text = re.sub(r"<(script|style).*?</\1>", " ", text, flags=re.S)
    text = re.sub(r"<!--.*?-->", " ", text, flags=re.S)
    text = re.sub(r"</(p|h[1-6]|li|tr|div)>", "\n", text, flags=re.I)
    text = re.sub(r"<[^>]+>", " ", text)
    text = html.unescape(text)
    text = re.sub(r"!\[[^\]]*\]\([^)]*\)", " ", text)
    text = re.sub(r"\[([^\]]*)\]\([^)]*\)", r"\1", text)
    text = re.sub(r"[*_`|>#-]+", " ", text)
    return re.sub(r"[ \t]+", " ", text)


def sentences(text: str) -> list[str]:
    parts = re.split(r"(?<=[.!?…])\s+|\n+", text)
    return [p.strip() for p in parts if len(p.split()) >= 3]


def numbers(text: str) -> set[str]:
    found = set()
    for m in NUM.finditer(plain(text)):
        token = re.sub(r"\s+", "", m.group(0)).lower().rstrip(".,")
        if re.search(r"\d", token):
            found.add(token)
    return found


def main() -> int:
    if len(sys.argv) != 3:
        print(__doc__)
        return 2
    old_raw = Path(sys.argv[1]).read_text(encoding="utf-8")
    new_raw = Path(sys.argv[2]).read_text(encoding="utf-8")
    new_plain = plain(new_raw)
    words = len(new_plain.split())
    old_words = len(plain(old_raw).split())
    sents = sentences(new_plain)
    problems: list[str] = []
    notes: list[str] = []

    long_sents = [s for s in sents if len(s.split()) > 28]
    if long_sents:
        problems.append(f"{len(long_sents)} câu dài hơn 28 từ, ví dụ: “{long_sents[0][:110]}…”")
    mean = sum(len(s.split()) for s in sents) / max(1, len(sents))
    notes.append(f"{words} từ (gốc {old_words}), {len(sents)} câu, trung bình {mean:.1f} từ/câu")
    if mean > 20:
        problems.append(f"Câu trung bình {mean:.1f} từ, nên dưới 20")
    if words > old_words * 1.15 + 40:
        problems.append(f"Dài hơn gốc quá 15% ({words} so với {old_words} từ)")

    commands = [s for s in sents if s.lower().startswith(COMMAND_START)]
    if len(commands) > max(2, len(sents) // 8):
        problems.append(f"{len(commands)}/{len(sents)} câu mở đầu bằng lệnh (Hãy/Đề nghị/Yêu cầu/Kiểm tra…); tối đa {max(2, len(sents) // 8)}")

    lowered = new_plain.lower()
    hedge_hits = sum(lowered.count(h) for h in HEDGES)
    if hedge_hits > max(1, words // 150):
        problems.append(f"{hedge_hits} cụm rào chắn (tùy model/cần xác minh/chưa đủ…); tối đa {max(1, words // 150)}")

    for term, limit in STIFF.items():
        n = lowered.count(term)
        if n > limit:
            problems.append(f"Từ cứng “{term}” dùng {n} lần (tối đa {limit})")

    for line in new_raw.splitlines():
        if line.lstrip().startswith("#"):
            title = line.lstrip("# ").strip()
            for pattern in TEMPLATE_HEADINGS:
                if re.search(pattern, title, re.I):
                    problems.append(f"Đề mục theo khuôn: “{title}”")
                    break
    headings = [l for l in new_raw.splitlines() if re.match(r"^##\s", l)]
    if len(headings) > 6:
        problems.append(f"{len(headings)} đề mục `##`; bài thường nên 4–5")

    caps = re.findall(r"\b[A-ZÀ-Ỵ]{4,}\b", re.sub(r"\b(?:MPPT|TOPCon|STC|NOCT|IEC|BESS|LiFePO4|GOLD|PTS|IEA|PVPS|SPD|AFCI|HJT|DOE|NREL|PVGIS|JRC|UNSW|CREE|LED|IP\d+)\b", "", new_plain))
    if len(caps) > 2:
        problems.append(f"Chữ in hoa hàng loạt: {', '.join(caps[:5])}")
    if re.search(r"(?i)\b(chúng tôi đã|khách hàng của chúng tôi|theo kinh nghiệm thực tế|nhiều năm kinh nghiệm)\b", new_plain):
        problems.append("Có lời tự nhận trải nghiệm/uy tín không có trong bài gốc")
    if re.search(r"(?i)\b(độc quyền|top 1|số 1|100%)\b", new_plain) and not re.search(r"(?i)\b(độc quyền|top 1|số 1|100%)\b", plain(old_raw)):
        problems.append("Thêm khẳng định (độc quyền/TOP 1/100%) không có trong bài gốc")

    meta_refs = re.findall(r"(?i)\b(bài gốc|bản gốc|bài viết gốc|nguồn gốc của bài|không có căn cứ|chưa có căn cứ|chưa có bằng chứng|theo bài viết cũ|bản nháp|người biên tập)\b", new_plain)
    if meta_refs:
        problems.append(f"Ghi chú nội bộ lọt vào chữ khách đọc: {', '.join(sorted(set(meta_refs)))}. Ghi chú loại này chỉ được nằm trong báo cáo")
    self_doubt = re.findall(r"(?i)(tự công bố|công ty công bố|công ty cũng nêu|công ty nêu|bài viết nêu|bài cũng nêu|bài nêu|thông tin sản phẩm nêu|nội dung công ty công bố|theo nội dung công ty|được bài viết nêu|thông tin trên là nội dung|cần được xác nhận|cần xác nhận|cần kiểm tra lịch sử)", new_plain)
    if len(self_doubt) > 1:
        problems.append(f"Giọng người ngoài nhận xét chính website ({len(self_doubt)} chỗ: {', '.join(sorted(set(x.lower() for x in self_doubt))[:4])}). Đây là website của công ty: nói thẳng thông tin của công ty/sản phẩm; chứng cứ cần xác nhận chỉ ghi trong báo cáo")
    if re.search(r"\]\(http://", new_raw):
        problems.append("Có ảnh/liên kết http:// (không an toàn); dùng https:// hoặc đường dẫn /upload/…")
    neg_pat = re.compile(r"(không phải|không thay|không cho biết|chưa phải|không đồng nghĩa|không có nghĩa|không thể kết luận|chưa đủ)", re.I)
    neg_sents = [x for x in sents if neg_pat.search(x)]
    if len(neg_sents) > max(2, words // 220):
        problems.append(f"{len(neg_sents)} câu kiểu 'đây không phải… / không thay… / không cho biết…' (tối đa {max(2, words // 220)}). Nói điều ĐÚNG thay vì liệt kê điều không phải; ví dụ: “{neg_sents[0][:90]}…”")
    if re.search(r"(?i)(ảnh bìa|ảnh tư liệu|ảnh minh họa[^.]*không|không phải nhân sự|chỉ là ví dụ,? không|chỉ là phép [^.]*không phải)", new_plain):
        problems.append("Câu thanh minh vô ích về ảnh/ví dụ (ảnh bìa, 'chỉ là ví dụ, không phải…'): bỏ")
    big = [m for m in re.findall(r"\b\d[\d.,/]{2,}\b", re.sub(r"^\s*\|.*$", "", new_plain, flags=re.M)) if not re.fullmatch(r"20\d\d", m)]
    repeated = sorted({m for m in big if (("/" in m) or m.endswith("%")) and big.count(m) > 2})
    if repeated:
        problems.append(f"Con số/ngày lặp quá 2 lần trong thân bài: {', '.join(repeated[:6])}. Nêu một lần, đúng chỗ")
    maybe = lowered.count("có thể") + lowered.count("còn tùy") + lowered.count("còn phụ thuộc") + lowered.count("tùy mẫu") + lowered.count("tùy từng")
    if maybe > max(2, words // 120):
        problems.append(f"Rào chắn mềm (có thể/còn tùy/phụ thuộc…) {maybe} lần; tối đa {max(2, words // 120)}. Nói điều chắc chắn thật chắc, điều không chắc thì bỏ khỏi bài")
    for line in new_raw.splitlines():
        if re.match(r"^#{2,3}\s*\d+[).:]", line):
            problems.append(f"Đề mục đánh số kiểu danh sách: “{line.strip()}”")
    for para in re.split(r"\n\s*\n", new_raw):
        tech = re.findall(r"\b(?:Voc|Vmp|Isc|Imp|MPPT|STC|NOCT|EPS|THD|CRI|CCT)\b", para)
        if len(tech) >= 4 and not para.lstrip().startswith("|"):
            problems.append(f"Một đoạn dồn {len(tech)} ký hiệu kỹ thuật ({', '.join(sorted(set(tech)))}); tách thành bảng “Ký hiệu | Nghĩa” hoặc giải thích từng ký hiệu một câu")
            break
    old_nums, new_nums = numbers(re.sub(r"<h1.*?</h1>", " ", old_raw, flags=re.S)), numbers(new_raw)
    missing = sorted(old_nums - new_nums)
    added = sorted(new_nums - old_nums)
    if added:
        problems.append(f"Số liệu MỚI không có trong gốc: {', '.join(added[:12])}")
    if missing:
        notes.append(f"Số liệu gốc không còn trong bản mới (phải giải thích trong báo cáo nếu là số liệu thật): {', '.join(missing[:20])}")
    for link in re.findall(r"\]\((/[^)\s]+)\)", old_raw) + re.findall(r'href="(/[^"\s]+)"', old_raw):
        if link not in new_raw:
            problems.append(f"Mất liên kết nội bộ {link}")

    print("\n".join("· " + n for n in notes))
    if problems:
        print("\nCẦN SỬA:")
        print("\n".join("✗ " + p for p in problems))
        return 1
    print("\nPASS (nhớ đọc lại bằng mắt người)")
    return 0


if __name__ == "__main__":
    sys.exit(main())
