/**
 * Ánh xạ thương hiệu PTS vào hệ màu ngữ nghĩa của Nuxt UI v4.
 * CODEX 1 — xem docs/14-nuxt-migration/TAILWIND_NUXT_UI_FULL_MIGRATION_20261026.md
 *
 * Nuxt UI nhận tên thang màu (đã khai trong app/assets/css/main.css qua @theme),
 * không nhận mã hex. Vì vậy mọi giá trị thật nằm ở main.css và file này chỉ nói
 * "vai trò nào dùng thang nào".
 */
export default defineAppConfig({
  ui: {
    colors: {
      /**
       * primary = gold, KHÔNG phải navy.
       *
       * Dễ nhầm vì navy là màu thương hiệu dễ nhận ra hơn. Nhưng trong Nuxt UI,
       * `primary` là màu của hành động chính (UButton mặc định, trạng thái
       * active, focus ring), mà hệ thiết kế đã duyệt giao đúng vai trò đó cho
       * gold: "vàng được giữ riêng cho hành động chính và điểm nhấn"
       * (audit 2026-09-16). Đặt primary = navy sẽ làm mọi nút trên site đổi màu
       * và xoá mất thứ bậc thị giác đã chốt.
       */
      primary: 'gold',

      /**
       * secondary = navy: bề mặt thương hiệu, chip đang chọn, header/footer.
       */
      secondary: 'navy',

      /**
       * neutral = slate, không phải gray.
       *
       * Đây là đối chiếu chứ không phải thẩm mỹ: --pts-muted #64748b ĐÚNG BẰNG
       * slate-500 và --pts-surface #f8fafc ĐÚNG BẰNG slate-50. Chọn gray sẽ
       * lệch nhẹ khỏi hai token gốc của thương hiệu — đúng loại lệch 2-5% mà
       * design-tokens.css đã phải dọn một lần.
       */
      neutral: 'slate',
    },

    button: {
      defaultVariants: {
        // Shared CSS applies the brand gold gradient to filled primary actions.
        variant: 'solid',
      },
    },
  },
})
