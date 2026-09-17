# Playbook sửa lỗi — đo, đừng đoán

Cập nhật: 2026-09-17. Thay thế cách làm mô tả trong
[debugging-workflow.md](debugging-workflow.md) ở mức chi tiết hơn; file đó vẫn
đúng về nguyên tắc chung.

Tài liệu này viết từ các con bug thật đã sửa, kèm **cả những lần kết luận sai**,
vì kiểu sai đó lặp lại.

---

## 1. Quy tắc gốc

> Chủ project mô tả **triệu chứng**. Việc của bạn là **đo ra nguyên nhân**.
> Đọc code rồi suy luận là cách sai.

Ví dụ thật về khoảng cách giữa triệu chứng và nguyên nhân:

| Chủ project nói | Nguyên nhân đo được |
|---|---|
| "chưa liền mạch, cứ lệch lệch" | 4 biến thể navy × 4 vai trò token, mỗi hub một bảng màu |
| "hơi đơ và lag" | 77/91 ảnh trang chủ tải ngay — 2.190 KB (sau khi sửa: 350 KB) |
| "mấy dòng này link tới đâu đâu" | header sticky 154px, target `scroll-margin-top: 0` |
| "thiếu 2 ô thì thụt về 1 bên" | flex + overflow, không có safe centering |
| "mấy cái nút này nằm ở đây có hợp lí k" | tab ở x=32, nội dung `margin:270px auto` — lệch trục |
| "responsive bị lỗi" | 189 element tràn ngang do `1fr` + `min-width:auto` |

Không câu nào trong cột trái chỉ ra được nguyên nhân ở cột phải. Phải đo.

---

## 2. Vòng 6 bước

### Bước 1 — Tái hiện và ghi lại bằng chứng

URL chính xác, method, input, vai trò người dùng, thiết bị/viewport, thời điểm.
Nếu chỉ lỗi trên mobile thì phải mở đúng viewport mobile, không suy ra.

### Bước 2 — Đo, không đọc

Đây là bước quyết định. Chọn công cụ theo loại lỗi:

| Loại lỗi | Cách đo |
|---|---|
| Layout / khoảng cách | `getComputedStyle(el)`, `el.getBoundingClientRect()` |
| Tràn ngang | `document.documentElement.scrollWidth <= clientWidth + 1`; liệt kê element vi phạm |
| Ảnh không hiện | `img.naturalWidth > 0` (khác hẳn với `src` trả 200) |
| Hiệu năng | đếm ảnh eager vs lazy, tổng KB, số request |
| Tương tác không ăn | **click bằng chuột thật**, không `el.click()` |
| Query sai | chạy đúng query đó trên DB thật, đếm dòng |
| Lỗi 500 | đọc log server, tìm dòng `PHP Fatal error` |
| Deploy không ăn | so nội dung file **trên server** với file local |

### Bước 3 — Cô lập tới nguyên nhân nhỏ nhất

"Trang bị lỗi" không phải nguyên nhân. `1fr` mặc định `min-width:auto` mới là
nguyên nhân. Tiếp tục thu hẹp đến khi còn một dòng giải thích được **toàn bộ**
triệu chứng.

Kiểm tra chéo: nguyên nhân đề xuất có giải thích được *tại sao lỗi xuất hiện ở
chỗ này mà không ở chỗ kia* không? Nếu không, chưa tìm đúng.

### Bước 4 — Sửa đúng nguyên nhân

Không sửa triệu chứng. Không thêm `!important` để đè lên. Không nới lỏng
assertion test.

Ví dụ: khi gom design token làm 3 assertion màu đỏ, cách sai là hardcode màu
mới; cách đúng đã làm là đổi assertion sang **đo tỷ lệ tương phản WCAG ≥ 4.5**
và **đọc `--pts-navy` từ `:root`** — assertion giờ kiểm điều *thật sự* quan
trọng và không vỡ khi đổi màu lần sau.

### Bước 5 — Thêm test chặn tái diễn

Nếu lỗi có thể quay lại, phải có test. Suite ở `tools/visual-tests`.

### Bước 6 — Xác minh trên runtime thật

Chạy lại đúng phép đo ở bước 2 và so số trước/sau. "Chắc là xong" không phải
xác minh. Sau deploy phải verify trên production, không tin "upload thành công".

---

## 3. Bốn lần tôi kết luận sai — và dấu hiệu nhận ra

### 3.1 Bỏ qua một lỗi thật vì tưởng là nhiễu

Đo được `x=-138, w=651` trên trang giải pháp, kết luận "CSS chưa nạp xong". Chủ
project phản hồi "responsive bị lỗi", đo lại: **189 element tràn ngang**. Lỗi
thật.

**Dấu hiệu:** khi phép đo cho số vô lý mà bạn đang tìm cách giải thích cho nó
*hợp lý*, thì bạn đang bào chữa, không phải chẩn đoán. Số vô lý là bug.

### 3.2 Suy ra ý định từ một cờ có nghĩa khác

Sửa lazy-load bằng cách suy từ `isLazy => false` rằng "chỗ này không muốn
lazy". Kết quả: tắt sạch lazy của trang chủ (67 ảnh → 0).

**Dấu hiệu:** dùng một cờ để trả lời câu hỏi mà nó không được tạo ra để trả
lời. Đúng thì phải thêm cờ mới, rõ nghĩa (`$pcInCarousel`).

### 3.3 Thêm bước "cho chắc" và làm treo cả suite

Thêm `image.decode()` vào test cho chắc ảnh đã sẵn sàng. Với ảnh lazy JS chưa
có `src`, `decode()` **không bao giờ settle** → timeout 30s.

**Dấu hiệu:** thêm một `await` mà không biết nó sẽ resolve trong điều kiện nào.

### 3.4 Tin `.click()` của JS

Nút "back to top" không hoạt động. `el.click()` chạy được nên tưởng đã đúng.
Click bằng chuột thật mới lộ ra ba vòng tròn vô hình phủ lên nút.

**Dấu hiệu:** kiểm tra tương tác bằng API thay vì bằng thao tác thật. `.click()`
bỏ qua hit-testing — đúng cái đang lỗi.

---

## 4. Loại trừ nhiễu hạ tầng trước khi nghi code

Trong một phiên đã có **5 lỗi giả** do container WSL tự restart.

```bash
# Trước khi tin bất cứ lỗi kết nối nào
docker ps --format '{{.Names}}\t{{.Status}}'     # "Up 3 seconds" = vừa restart
curl -sS -o /dev/null -w '%{http_code}\n' http://localhost:8080/
```

Triệu chứng của nhiễu hạ tầng, **không phải** lỗi code:

- `ERR_CONNECTION_REFUSED`
- `MySQL server has gone away`
- Nhiều test không liên quan cùng đỏ một lúc
- Chạy lại thì xanh, không sửa gì

Nhiễu bên thứ ba: GTranslate (`cdn.gtranslate.net`) gây lỗi console không phải
của mình. Các spec đã abort request tới host đó.

---

## 5. Test đỏ ngẫu nhiên (flaky) — xử lý tất định

Bốn test flaky trong một phiên đều cùng một gốc: **popup chào mừng bung lên sau
khi `apps.js` chạy**, nên `if (popup.isVisible()) dismiss()` bị race; backdrop
phủ lên rail danh mục làm click treo tới timeout 30s.

Cách sai: thêm `waitForTimeout`, tăng timeout, hoặc retry.

Cách đúng — chặn tất định, đọc khoá chiến dịch **thật** từ DOM (nó là hash động):

```js
await page.addInitScript(() => {
  try {
    const original = window.sessionStorage.getItem.bind(window.sessionStorage);
    window.sessionStorage.getItem = key => {
      if (key === 'pts-popup-campaign') {
        const popup = document.querySelector('#popup');
        return popup ? (popup.getAttribute('data-campaign-key') || 'current') : 'current';
      }
      return original(key);
    };
  } catch (error) {}
});
```

Nguyên tắc: **flaky nghĩa là có một race chưa hiểu.** Tìm ra rồi loại bỏ race,
đừng che nó bằng thời gian chờ.

---

## 6. Ranh giới an toàn

- **Không** bật `display_errors` trên production.
- **Không** copy log chứa PII/secret vào issue, chat hay commit.
- Cần dữ liệu thật để tái hiện → clone tối thiểu, đã ẩn danh.
- Đổi schema → migration SQL riêng, có note rollback, test trên clone trước.
- **Luôn deploy migration trước code.** Query SELECT cột chưa tồn tại sẽ **lỗi
  DB**, không phải trả rỗng — đã gần làm chết màn hình sản phẩm trong Admin.
- Không sửa trực tiếp trên live. Không đổi credential mà chưa hỏi.
