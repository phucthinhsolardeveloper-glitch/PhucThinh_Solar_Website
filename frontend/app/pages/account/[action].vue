<script setup lang="ts">
import { isPublicAccountPayload, type PublicAccountAction, type PublicAccountPayload } from '#shared/contracts/public-account'
import type { PublicSitePayload } from '#shared/contracts/public-site'
import { isPublicSitePayload } from '#shared/contracts/public-site'
import SiteHeader from '~/components/SiteHeader.vue'
import SiteFooter from '~/components/SiteFooter.vue'

definePageMeta({ layout: false })

const route = useRoute()
const action = computed(() => typeof route.params.action === 'string' ? route.params.action : '')
const pageInfo: Record<string, { title: string; dataAction: PublicAccountAction; memberOnly?: boolean }> = {
  'dang-nhap': { title: 'Đăng nhập', dataAction: 'login' },
  'dang-ky': { title: 'Đăng ký tài khoản', dataAction: 'register' },
  'quen-mat-khau': { title: 'Quên mật khẩu', dataAction: 'forgot' },
  'kich-hoat': { title: 'Kích hoạt tài khoản', dataAction: 'activate' },
  'thong-tin': { title: 'Thông tin cá nhân', dataAction: 'profile', memberOnly: true },
  'danh-sach-don-hang': { title: 'Đơn hàng của tôi', dataAction: 'orders', memberOnly: true },
  'don-hang-doi-tra': { title: 'Đơn hàng đổi trả', dataAction: 'returns', memberOnly: true },
  'don-hang-huy': { title: 'Đơn hàng đã hủy', dataAction: 'cancellations', memberOnly: true },
}
if (!pageInfo[action.value]) throw createError({ statusCode: 404, statusMessage: 'Không tìm thấy trang tài khoản' })
const fallbackPage = pageInfo['dang-nhap']!
const current = computed(() => pageInfo[action.value] ?? fallbackPage)

const requestAction = computed<PublicAccountAction>(() => action.value === 'danh-sach-don-hang' && route.query.action === 'edit' ? 'order-detail' : current.value.dataAction)
const orderId = computed(() => typeof route.query.id === 'string' ? route.query.id : '')
if (requestAction.value === 'order-detail' && !/^[1-9][0-9]{0,9}$/.test(orderId.value)) throw createError({ statusCode: 404, statusMessage: 'Không tìm thấy đơn hàng' })

const { data: accountData, error: accountError } = await useFetch<unknown>('/_migration/account', {
  key: computed(() => `account-${requestAction.value}-${orderId.value}`),
  query: computed(() => ({ action: requestAction.value, ...(requestAction.value === 'activate' || requestAction.value === 'order-detail' ? { id: orderId.value } : {}) })),
})
const account = computed<PublicAccountPayload | null>(() => isPublicAccountPayload(accountData.value) ? accountData.value : null)
if (account.value?.authenticated && ['login', 'register', 'forgot', 'activate'].includes(action.value)) await navigateTo('/', { redirectCode: 302 })
if (!account.value?.authenticated && current.value.memberOnly) await navigateTo('/account/dang-nhap', { redirectCode: 302 })
if (requestAction.value === 'order-detail' && account.value?.order === null) setResponseStatus(404)

const fallbackSite: PublicSitePayload = { schemaVersion: 1, brand: { name: 'Phúc Thịnh Solar', hotline: '' }, productCategories: [] }
const { data: siteData } = await useFetch<unknown>('/_migration/site', { default: () => fallbackSite })
const site = computed(() => isPublicSitePayload(siteData.value) ? siteData.value : fallbackSite)
const requestUrl = useRequestURL()
const path = computed(() => `/account/${action.value}`)
const title = computed(() => current.value?.title ?? 'Tài khoản')
useSeoMeta({ title: computed(() => `${title.value} — Phúc Thịnh Solar`), robots: 'noindex, nofollow, noarchive' })
useHead(() => ({ link: [{ rel: 'canonical', href: new URL(path.value + '/', requestUrl.origin).toString() }] }))

function formatMoney(value: number) {
  return `${new Intl.NumberFormat('vi-VN').format(value)} đ`
}

const member = computed(() => account.value?.member)
const fields = computed(() => account.value?.fields ?? {})
const values = computed(() => ({
  fullname: fields.value.fullname ?? member.value?.fullname ?? '',
  username: fields.value.username ?? member.value?.username ?? '',
  gender: fields.value.gender ?? member.value?.gender ?? '',
  birthday: fields.value.birthday ?? member.value?.birthday ?? '',
  email: fields.value.email ?? member.value?.email ?? '',
  phone: fields.value.phone ?? member.value?.phone ?? '',
  address: fields.value.address ?? member.value?.address ?? '',
}))
</script>

<template>
  <div class="min-h-screen text-navy-800">
    <SiteHeader :site="site" />
    <main class="mx-auto min-h-[55vh] w-full max-w-[1180px] px-4 py-10 nav:py-14">
      <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
        <div>
          <p class="mb-2 text-sm font-bold text-slate-500"><NuxtLink to="/" class="text-inherit">Trang chủ</NuxtLink> / Tài khoản</p>
          <h1 class="m-0 text-[clamp(30px,4vw,42px)] font-black tracking-tight">{{ title }}</h1>
        </div>
        <a v-if="account?.authenticated" href="/account/dang-xuat" class="font-bold text-navy-800 underline underline-offset-4">Đăng xuất</a>
      </div>

      <div v-if="accountError || !account" class="rounded-btn border border-red-200 bg-red-50 p-5 text-red-800" role="alert">Không thể tải tài khoản. Vui lòng tải lại trang.</div>
      <div v-else-if="account.messages.length" class="mb-6 rounded-btn border border-amber-200 bg-amber-50 p-4 text-amber-950" role="status">
        <p v-for="message in account.messages" :key="message" class="mb-2 last:mb-0">{{ message }}</p>
      </div>

      <form v-if="action === 'dang-nhap' && account" method="post" action="/account/dang-nhap" class="mx-auto grid max-w-[520px] gap-5">
        <input type="hidden" name="form_csrf" :value="account.csrfToken">
        <label class="grid gap-2 font-bold">Tài khoản<input name="username" autocomplete="username" required class="h-12 rounded-chip border border-gray-300 px-3 font-normal outline-none focus:border-gold-600"></label>
        <label class="grid gap-2 font-bold">Mật khẩu<input name="password" type="password" autocomplete="current-password" required class="h-12 rounded-chip border border-gray-300 px-3 font-normal outline-none focus:border-gold-600"></label>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="remember-user" value="1"> Ghi nhớ đăng nhập</label>
        <button name="login-user" value="1" class="min-h-12 rounded-chip bg-gold-500 px-5 font-extrabold text-navy-950 hover:bg-gold-600">Đăng nhập</button>
        <div class="flex flex-wrap justify-between gap-3 text-sm"><NuxtLink to="/account/dang-ky">Tạo tài khoản</NuxtLink><NuxtLink to="/account/quen-mat-khau">Quên mật khẩu?</NuxtLink></div>
      </form>

      <form v-else-if="action === 'dang-ky' && account" method="post" action="/account/dang-ky" class="mx-auto grid max-w-[720px] grid-cols-1 gap-4 tb:grid-cols-2">
        <input type="hidden" name="form_csrf" :value="account.csrfToken">
        <label class="grid gap-2 font-bold">Họ tên<input name="fullname" :value="values.fullname" autocomplete="name" required maxlength="120" class="h-12 rounded-chip border border-gray-300 px-3 font-normal"></label>
        <label class="grid gap-2 font-bold">Tài khoản<input name="username" :value="values.username" autocomplete="username" required maxlength="64" class="h-12 rounded-chip border border-gray-300 px-3 font-normal"></label>
        <label class="grid gap-2 font-bold">Mật khẩu<input name="password" type="password" autocomplete="new-password" required class="h-12 rounded-chip border border-gray-300 px-3 font-normal"></label>
        <label class="grid gap-2 font-bold">Nhập lại mật khẩu<input name="repassword" type="password" autocomplete="new-password" required class="h-12 rounded-chip border border-gray-300 px-3 font-normal"></label>
        <fieldset class="grid gap-2 border-0 p-0 tb:col-span-2"><legend class="mb-2 font-bold">Giới tính</legend><div class="flex gap-6"><label class="flex items-center gap-2"><input type="radio" name="gender" value="1" :checked="values.gender === '1'" required> Nam</label><label class="flex items-center gap-2"><input type="radio" name="gender" value="2" :checked="values.gender === '2'" required> Nữ</label></div></fieldset>
        <label class="grid gap-2 font-bold">Ngày sinh<input name="birthday" :value="values.birthday" autocomplete="bday" placeholder="dd/mm/yyyy" required class="h-12 rounded-chip border border-gray-300 px-3 font-normal"></label>
        <label class="grid gap-2 font-bold">Email<input name="email" :value="values.email" type="email" autocomplete="email" required maxlength="190" class="h-12 rounded-chip border border-gray-300 px-3 font-normal"></label>
        <label class="grid gap-2 font-bold">Điện thoại<input name="phone" :value="values.phone" type="tel" autocomplete="tel" required maxlength="30" class="h-12 rounded-chip border border-gray-300 px-3 font-normal"></label>
        <label class="grid gap-2 font-bold">Địa chỉ<input name="address" :value="values.address" autocomplete="street-address" required maxlength="255" class="h-12 rounded-chip border border-gray-300 px-3 font-normal"></label>
        <button name="registration-user" value="1" class="min-h-12 rounded-chip bg-gold-500 px-5 font-extrabold text-navy-950 hover:bg-gold-600 tb:col-span-2">Đăng ký</button>
      </form>

      <form v-else-if="action === 'quen-mat-khau' && account" method="post" action="/account/quen-mat-khau" class="mx-auto grid max-w-[520px] gap-5">
        <input type="hidden" name="form_csrf" :value="account.csrfToken">
        <label class="grid gap-2 font-bold">Tài khoản<input name="username" autocomplete="username" required class="h-12 rounded-chip border border-gray-300 px-3 font-normal"></label>
        <label class="grid gap-2 font-bold">Email<input name="email" type="email" autocomplete="email" required class="h-12 rounded-chip border border-gray-300 px-3 font-normal"></label>
        <button name="forgot-password-user" value="1" class="min-h-12 rounded-chip bg-gold-500 px-5 font-extrabold text-navy-950 hover:bg-gold-600">Lấy lại mật khẩu</button>
      </form>

      <form v-else-if="action === 'kich-hoat' && account" method="post" :action="`/account/kich-hoat?id=${encodeURIComponent(String(route.query.id || ''))}`" class="mx-auto grid max-w-[520px] gap-5">
        <input type="hidden" name="form_csrf" :value="account.csrfToken">
        <p v-if="!account.activation?.available" class="text-slate-600">Liên kết kích hoạt không hợp lệ hoặc tài khoản đã được kích hoạt.</p>
        <template v-else>
          <label class="grid gap-2 font-bold">Mã kích hoạt<input name="confirm_code" autocomplete="one-time-code" required class="h-12 rounded-chip border border-gray-300 px-3 font-normal"></label>
          <button name="activation-user" value="1" class="min-h-12 rounded-chip bg-gold-500 px-5 font-extrabold text-navy-950 hover:bg-gold-600">Kích hoạt</button>
        </template>
      </form>

      <nav v-else-if="account?.authenticated && current?.memberOnly" class="mb-8 flex flex-wrap gap-x-5 gap-y-3 border-b border-gray-200 pb-5 text-sm font-bold" aria-label="Quản lý tài khoản">
        <NuxtLink to="/account/thong-tin">Thông tin cá nhân</NuxtLink><NuxtLink to="/account/danh-sach-don-hang">Đơn hàng</NuxtLink><NuxtLink to="/account/don-hang-doi-tra">Đổi trả</NuxtLink><NuxtLink to="/account/don-hang-huy">Đơn hàng đã hủy</NuxtLink>
      </nav>

      <form v-if="action === 'thong-tin' && account?.authenticated && member" method="post" action="/account/thong-tin" class="grid max-w-[760px] grid-cols-1 gap-4 tb:grid-cols-2">
        <input type="hidden" name="form_csrf" :value="account.csrfToken">
        <label class="grid gap-2 font-bold">Họ tên<input name="fullname" :value="values.fullname" required class="h-12 rounded-chip border border-gray-300 px-3 font-normal"></label>
        <label class="grid gap-2 font-bold">Tài khoản<input :value="member.username" readonly class="h-12 rounded-chip border border-gray-200 bg-slate-50 px-3 font-normal"></label>
        <label class="grid gap-2 font-bold">Mật khẩu cũ<input name="old-password" type="password" autocomplete="current-password" class="h-12 rounded-chip border border-gray-300 px-3 font-normal"></label>
        <label class="grid gap-2 font-bold">Mật khẩu mới<input name="new-password" type="password" autocomplete="new-password" class="h-12 rounded-chip border border-gray-300 px-3 font-normal"></label>
        <label class="grid gap-2 font-bold">Nhập lại mật khẩu mới<input name="new-password-confirm" type="password" autocomplete="new-password" class="h-12 rounded-chip border border-gray-300 px-3 font-normal"></label>
        <label class="grid gap-2 font-bold">Giới tính<select name="gender" :value="values.gender" required class="h-12 rounded-chip border border-gray-300 px-3 font-normal"><option value="">Chọn giới tính</option><option value="1">Nam</option><option value="2">Nữ</option></select></label>
        <label class="grid gap-2 font-bold">Ngày sinh<input name="birthday" :value="values.birthday" placeholder="dd/mm/yyyy" required class="h-12 rounded-chip border border-gray-300 px-3 font-normal"></label>
        <label class="grid gap-2 font-bold">Email<input name="email" :value="values.email" type="email" required class="h-12 rounded-chip border border-gray-300 px-3 font-normal"></label>
        <label class="grid gap-2 font-bold">Điện thoại<input name="phone" :value="values.phone" type="tel" required class="h-12 rounded-chip border border-gray-300 px-3 font-normal"></label>
        <label class="grid gap-2 font-bold tb:col-span-2">Địa chỉ<input name="address" :value="values.address" required class="h-12 rounded-chip border border-gray-300 px-3 font-normal"></label>
        <button name="info-user" value="1" class="min-h-12 rounded-chip bg-gold-500 px-5 font-extrabold text-navy-950 hover:bg-gold-600 tb:col-span-2">Cập nhật thông tin</button>
      </form>

      <section v-if="account?.authenticated && (action === 'don-hang-huy' || action === 'danh-sach-don-hang' && requestAction !== 'order-detail')" class="overflow-x-auto">
        <h2 class="mb-4 text-xl font-black">{{ action === 'don-hang-huy' ? 'Đơn hàng đã hủy' : 'Đơn hàng của tôi' }}</h2>
        <table class="w-full min-w-[760px] border-collapse text-left text-sm">
          <thead><tr class="border-b border-gray-300"><th class="p-3">Mã đơn hàng</th><th class="p-3">Ngày đặt</th><th class="p-3">Người nhận</th><th class="p-3">Điện thoại</th><th class="p-3">Tổng tiền</th><th class="p-3">Thanh toán</th><th class="p-3">Thao tác</th></tr></thead>
          <tbody><tr v-for="order in account.orders" :key="order.id" class="border-b border-gray-200"><td class="p-3">{{ order.code }}</td><td class="p-3">{{ order.date }}</td><td class="p-3">{{ order.name }}</td><td class="p-3">{{ order.phone }}</td><td class="p-3">{{ formatMoney(order.total) }}</td><td class="p-3">{{ order.paid ? 'Đã thanh toán' : 'Chưa thanh toán' }}</td><td class="p-3"><NuxtLink :to="`/account/danh-sach-don-hang?action=edit&id=${order.id}`">Xem đơn</NuxtLink><span v-if="action === 'danh-sach-don-hang' && order.returnRequested" class="mt-2 block text-sm text-slate-500">Đã yêu cầu đổi trả</span><form v-else-if="action === 'danh-sach-don-hang' && requestAction !== 'order-detail'" method="post" :action="`/account/danh-sach-don-hang?action=return&id=${order.id}`" class="mt-2"><input type="hidden" name="form_csrf" :value="account.csrfToken"><button class="font-bold text-gold-700 underline underline-offset-2">Yêu cầu đổi trả</button></form></td></tr><tr v-if="!account.orders.length"><td colspan="7" class="p-8 text-center text-slate-500">Chưa có đơn hàng.</td></tr></tbody>
        </table>
      </section>

      <section v-if="requestAction === 'order-detail' && account?.order" class="grid gap-6">
        <h2 class="m-0 text-xl font-black">Đơn hàng {{ account.order.code }}</h2>
        <div class="grid gap-3 sm:grid-cols-2"><p class="m-0">Ngày đặt: {{ account.order.date }}</p><p class="m-0">Trạng thái: {{ account.order.status }}</p><p class="m-0">Người nhận: {{ account.order.name }}</p><p class="m-0">Điện thoại: {{ account.order.phone }}</p></div>
        <div class="divide-y divide-gray-200 border-y border-gray-200"> <article v-for="(item, index) in account.order.items ?? []" :key="item.code + '-' + index" class="flex items-center gap-4 py-4"><img v-if="item.image" :src="item.image" :alt="item.name" class="h-16 w-16 object-contain"><div class="min-w-0 flex-1"><p class="m-0 font-bold">{{ item.name }}</p><p class="mb-0 mt-1 text-sm text-slate-500">Số lượng: {{ item.quantity }}<span v-if="item.color"> · Màu: {{ item.color }}</span><span v-if="item.size"> · Size: {{ item.size }}</span></p></div><strong>{{ formatMoney(item.lineTotal) }}</strong></article><p v-if="!account.order.items?.length" class="py-5 text-slate-500">Không có dòng sản phẩm để hiển thị.</p></div>
        <p class="m-0 text-lg font-black">Tổng đơn hàng: {{ formatMoney(account.order.total) }}</p>
      </section>

      <section v-if="action === 'don-hang-doi-tra' && account?.authenticated" class="overflow-x-auto">
        <h2 class="mb-4 text-xl font-black">Yêu cầu đổi trả</h2>
        <table class="w-full min-w-[560px] border-collapse text-left text-sm"><thead><tr class="border-b border-gray-300"><th class="p-3">Mã đổi trả</th><th class="p-3">Ngày yêu cầu</th><th class="p-3">Giá trị</th><th class="p-3">Trạng thái</th></tr></thead><tbody><tr v-for="item in account.returns" :key="item.id" class="border-b border-gray-200"><td class="p-3">{{ item.code }}</td><td class="p-3">{{ item.date }}</td><td class="p-3">{{ formatMoney(item.total) }}</td><td class="p-3">{{ item.status }}</td></tr><tr v-if="!account.returns.length"><td colspan="4" class="p-8 text-center text-slate-500">Chưa có yêu cầu đổi trả.</td></tr></tbody></table>
      </section>
    </main>
    <SiteFooter :site="site" />
  </div>
</template>
