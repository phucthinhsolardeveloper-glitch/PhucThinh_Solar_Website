export interface PaginationItem { key: string; page: number | null }

/**
 * dùng chung đúng một thuật toán rút gọn trang (giữ 7 trang trở xuống hiện
 * đủ, nhiều hơn thì rút gọn quanh trang hiện tại + đầu/cuối) thay vì chép lại.
 */
export function usePaginationItems(currentPage: Ref<number>, totalPages: Ref<number>) {
  return computed<PaginationItem[]>(() => {
    const total = totalPages.value
    if (total <= 7) return Array.from({ length: total }, (_, index) => ({ key: `page-${index + 1}`, page: index + 1 }))

    const current = Math.min(Math.max(currentPage.value, 1), total)
    const candidates = new Set([1, total, current - 1, current, current + 1])
    if (current <= 4) [2, 3, 4, 5].forEach((pageNumber) => candidates.add(pageNumber))
    if (current >= total - 3) [total - 4, total - 3, total - 2, total - 1].forEach((pageNumber) => candidates.add(pageNumber))
    const pages = [...candidates].filter((pageNumber) => pageNumber >= 1 && pageNumber <= total).sort((a, b) => a - b)
    const items: PaginationItem[] = []
    pages.forEach((pageNumber, index) => {
      const previous = pages[index - 1]
      if (previous && pageNumber - previous > 1) items.push({ key: `ellipsis-${previous}`, page: null })
      items.push({ key: `page-${pageNumber}`, page: pageNumber })
    })
    return items
  })
}
