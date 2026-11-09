// Local stand-ins for VNPay and Nhất Tín, used only by the rehearsal stack (deploy/laravel13/docker-compose.sim.yml).
// They sign and answer like the real services so the site's own code paths run end to end without
// touching a real gateway, a real waybill or real money. Never point a server environment at this.
//
// VNPay:     GET /paymentv2/vpcpay.html  checks the merchant signature, shows Pay / Fail / Cancel;
//            the choice calls the IPN URL server to server (twice with ?dup=1) and sends the browser to vnp_ReturnUrl.
// Nhất Tín:  /ntl/v1/auth/sign-in, /ntl/v3/bill/{calc-fee,create,tracking,destroy}, Bearer token required.
//            POST /_sim/ntl/next {"fail":"timeout"|"401"|"500"} makes the next call misbehave once;
//            POST /_sim/ntl/push {"bill_code","status_id","status_name"} sends a webhook to the site.
// Log:       GET /_sim/log  the last 100 calls.
import { createHmac, randomBytes } from 'node:crypto'
import { createServer } from 'node:http'

const env = (key, fallback = '') => process.env[key] ?? fallback
const VNP_SECRET = env('SIM_VNPAY_HASH_SECRET')
const VNP_TMN = env('SIM_VNPAY_TMN_CODE')
const VNP_IPN = env('SIM_VNPAY_IPN_URL')
const NTL_USER = env('SIM_NTL_USERNAME')
const NTL_PASS = env('SIM_NTL_PASSWORD')
const NTL_HOOK = env('SIM_NTL_WEBHOOK_URL')
const NTL_HOOK_TOKEN = env('SIM_NTL_WEBHOOK_TOKEN')
if (!VNP_SECRET || !VNP_TMN || !NTL_USER || !NTL_PASS) throw new Error('SIM_* settings are missing')

const log = []
const note = (entry) => { log.push({ at: new Date().toISOString(), ...entry }); if (log.length > 100) log.shift() }
const tokens = new Set()
const bills = new Map()
let nextFailure = ''

// VNPay signs the sorted query string as PHP's urlencode() writes it (spaces as +).
const phpEncode = (value) => encodeURIComponent(value).replace(/%20/g, '+').replace(/[!'()*~]/g, (c) => '%' + c.charCodeAt(0).toString(16).toUpperCase())
const vnpQuery = (params) => Object.keys(params).sort().map((key) => `${phpEncode(key)}=${phpEncode(String(params[key]))}`).join('&')
const vnpSign = (params) => createHmac('sha512', VNP_SECRET).update(vnpQuery(params)).digest('hex')

function vnpVerify(query) {
  const params = {}
  for (const [key, value] of query) if (key.startsWith('vnp_') && !['vnp_SecureHash', 'vnp_SecureHashType'].includes(key) && value !== '') params[key] = value
  return { ok: (query.get('vnp_SecureHash') || '').toLowerCase() === vnpSign(params), params }
}

const send = (res, status, body, type = 'application/json; charset=utf-8') => {
  res.writeHead(status, { 'Content-Type': type, 'Cache-Control': 'no-store' })
  res.end(typeof body === 'string' ? body : JSON.stringify(body))
}
const readJson = (req) => new Promise((resolve) => {
  let raw = ''
  req.on('data', (chunk) => { raw += chunk; if (raw.length > 65536) req.destroy() })
  req.on('end', () => { try { resolve(raw ? JSON.parse(raw) : {}) } catch { resolve({}) } })
})
const escape = (value) => String(value).replace(/[&<>"']/g, (c) => `&#${c.charCodeAt(0)};`)

async function vnpay(req, res, url) {
  const { ok, params } = vnpVerify(url.searchParams)
  note({ service: 'vnpay', path: url.pathname, ok, ref: params.vnp_TxnRef, amount: params.vnp_Amount })
  if (!ok || params.vnp_TmnCode !== VNP_TMN) return send(res, 400, '<h1>Sai chữ ký hoặc mã website (mô phỏng VNPay)</h1>', 'text/html; charset=utf-8')
  const choice = url.searchParams.get('sim_result')
  if (!choice) {
    const base = url.pathname + url.search
    const button = (result, label, dup = '') => `<a style="display:block;margin:12px 0;padding:14px;border-radius:8px;background:#16365f;color:#fff;text-decoration:none;text-align:center" href="${escape(base)}&sim_result=${result}${dup}">${label}</a>`
    return send(res, 200, `<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width"><title>VNPay mô phỏng</title>
<body style="font-family:system-ui;max-width:420px;margin:40px auto;padding:0 16px"><h1 style="font-size:20px">VNPay mô phỏng (local)</h1>
<p>Đơn <b>${escape(params.vnp_TxnRef)}</b>: <b>${(Number(params.vnp_Amount) / 100).toLocaleString('vi-VN')}đ</b></p>
${button('00', 'Thanh toán thành công')}${button('00', 'Thành công, VNPay báo về 2 lần', '&dup=1')}${button('51', 'Thất bại: tài khoản không đủ số dư')}${button('24', 'Khách hủy giao dịch')}</body>`, 'text/html; charset=utf-8')
  }
  const reply = {
    vnp_Amount: params.vnp_Amount, vnp_BankCode: 'NCB', vnp_BankTranNo: 'SIM' + Date.now(), vnp_CardType: 'ATM',
    vnp_OrderInfo: params.vnp_OrderInfo, vnp_PayDate: new Date().toISOString().replace(/\D/g, '').slice(0, 14),
    vnp_ResponseCode: choice, vnp_TmnCode: VNP_TMN, vnp_TransactionNo: String(Math.floor(1e7 + Math.random() * 9e7)),
    vnp_TransactionStatus: choice === '00' ? '00' : '02', vnp_TxnRef: params.vnp_TxnRef,
  }
  const signed = `${vnpQuery(reply)}&vnp_SecureHashType=HmacSHA512&vnp_SecureHash=${vnpSign(reply)}`
  if (VNP_IPN) {
    for (let i = 0; i < (url.searchParams.get('dup') ? 2 : 1); i++) {
      const answer = await fetch(`${VNP_IPN}${VNP_IPN.includes('?') ? '&' : '?'}${signed}`).then(async (r) => `${r.status} ${await r.text()}`).catch((e) => `ERR ${e.message}`)
      note({ service: 'vnpay', path: 'ipn', ref: reply.vnp_TxnRef, code: choice, answer: answer.slice(0, 200) })
    }
  }
  res.writeHead(302, { Location: `${params.vnp_ReturnUrl}${params.vnp_ReturnUrl.includes('?') ? '&' : '?'}${signed}` })
  res.end()
}

function fee(body, service) {
  const weight = Math.max(0.5, Number(body.weight || 1))
  return Math.round(({ 91: 31000, 90: 44000, 81: 88000 }[service] + Math.max(0, weight - 1) * 6500) / 1000) * 1000
}

async function ntl(req, res, url) {
  const path = url.pathname.slice('/ntl'.length)
  const body = req.method === 'GET' ? Object.fromEntries(url.searchParams) : await readJson(req)
  const failure = nextFailure
  if (path !== '/v1/auth/sign-in') nextFailure = ''
  note({ service: 'ntl', method: req.method, path, failure: failure || undefined, ref: body.ref_code, bill: body.bill_code })
  if (path === '/v1/auth/sign-in') {
    if (body.username !== NTL_USER || body.password !== NTL_PASS) return send(res, 401, { success: false, message: 'Sai tài khoản' })
    const token = randomBytes(16).toString('hex')
    tokens.add(token)
    return send(res, 200, { success: true, data: { jwt_token: token, token_expires_in: '30m' } })
  }
  const auth = (req.headers.authorization || '').replace(/^Bearer\s+/i, '')
  if (!tokens.has(auth) || failure === '401') { tokens.delete(auth); return send(res, 401, { success: false, message: 'Token hết hạn' }) }
  if (failure === 'timeout') { setTimeout(() => res.destroy(), 15000); return }
  if (failure === '500') return send(res, 500, { success: false, message: 'Lỗi máy chủ (mô phỏng)' })
  if (path === '/v3/bill/calc-fee') {
    if (!body.r_ward_id || !body.s_ward_id) return send(res, 400, { success: false, message: 'Thiếu mã phường/xã' })
    return send(res, 200, { success: true, data: [91, 90, 81].map((id) => ({ service_id: id, service_name: { 91: 'Tiết kiệm', 90: 'Nhanh', 81: 'Hỏa tốc' }[id], total_fee: fee(body, id), lead_time: { 91: '3-5 ngày', 90: '2-3 ngày', 81: '1 ngày' }[id] })) })
  }
  if (path === '/v3/bill/create') {
    const existing = [...bills.values()].find((bill) => bill.ref_code === body.ref_code)
    if (existing) return send(res, 400, { success: false, message: `ref_code ${body.ref_code} đã có vận đơn ${existing.bill_code}` })
    const bill = { bill_code: 'SIM' + randomBytes(5).toString('hex').toUpperCase(), ref_code: body.ref_code, status_id: body.is_draft ? 12 : 2, total_fee: fee(body, body.service_id), histories: [] }
    bill.histories.push({ status_id: bill.status_id, status_name: bill.status_id === 12 ? 'Đơn nháp' : 'Chờ lấy hàng', time: Math.floor(Date.now() / 1000) })
    bills.set(bill.bill_code, bill)
    return send(res, 200, { success: true, data: { bill_code: bill.bill_code, ref_code: bill.ref_code, status_id: bill.status_id, total_fee: bill.total_fee } })
  }
  if (path === '/v3/bill/tracking') {
    const bill = bills.get(String(body.bill_code))
    if (!bill) return send(res, 200, { success: true, data: [] })
    return send(res, 200, { success: true, data: [{ bill_code: bill.bill_code, bill_status_id: bill.status_id, histories: bill.histories }] })
  }
  if (path === '/v3/bill/destroy') {
    for (const code of [].concat(body.bill_code || [])) { const bill = bills.get(code); if (bill) { bill.status_id = 6; bill.histories.push({ status_id: 6, status_name: 'Đã hủy', time: Math.floor(Date.now() / 1000) }) } }
    return send(res, 200, { success: true, data: { bill_code: body.bill_code } })
  }
  return send(res, 404, { success: false, message: 'Không có API này (mô phỏng)' })
}

async function control(req, res, url) {
  if (url.pathname === '/_sim/log') return send(res, 200, log)
  if (req.method !== 'POST') return send(res, 405, { error: 'POST' })
  const body = await readJson(req)
  if (url.pathname === '/_sim/ntl/next') { nextFailure = ['timeout', '401', '500'].includes(body.fail) ? body.fail : ''; return send(res, 200, { next: nextFailure }) }
  if (url.pathname === '/_sim/ntl/push') {
    const bill = bills.get(String(body.bill_code))
    const time = Math.floor(Date.now() / 1000)
    if (bill) { bill.status_id = Number(body.status_id); bill.histories.push({ status_id: bill.status_id, status_name: body.status_name || '', time }) }
    const answer = await fetch(NTL_HOOK, { method: 'POST', headers: { 'Content-Type': 'application/json', Authorization: NTL_HOOK_TOKEN },
      body: JSON.stringify({ bill_no: body.bill_code, status_id: Number(body.status_id), status_name: body.status_name || '', status_time: time, shipping_fee: bill?.total_fee }) })
      .then(async (r) => ({ status: r.status, body: await r.text() })).catch((e) => ({ error: e.message }))
    note({ service: 'ntl', path: 'webhook', bill: body.bill_code, answer })
    return send(res, 200, answer)
  }
  return send(res, 404, { error: 'unknown' })
}

createServer((req, res) => {
  const url = new URL(req.url, 'http://sim')
  const route = url.pathname.startsWith('/paymentv2/') ? vnpay : url.pathname.startsWith('/ntl/') ? ntl : url.pathname.startsWith('/_sim/') ? control : null
  if (!route) return send(res, 404, { error: 'not found' })
  route(req, res, url).catch((error) => { note({ error: error.message }); send(res, 500, { error: error.message }) })
}).listen(8080, () => console.log('sandbox-sim listening on 8080'))
