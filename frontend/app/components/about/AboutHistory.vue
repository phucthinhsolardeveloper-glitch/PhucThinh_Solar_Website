<script setup lang="ts">
// The owner's own account of the company, edited for the page on 2026-11-07. It lives here rather than in Admin.
defineProps<{ image: string }>()

const LEAD = 'Năm 2020, khi thế giới chuyển mình mạnh mẽ trước làn sóng năng lượng mới, Phúc Thịnh Solar được hình thành từ một khát vọng giản dị nhưng đủ bền bỉ để theo đuổi đến cùng: mang công nghệ xanh và nguồn năng lượng của tương lai đến gần hơn với mỗi gia đình, mỗi công trình Việt Nam.'

const MILESTONES = [
  {
    period: '2020',
    title: 'Khởi nguồn từ một khát vọng giản dị',
    paragraphs: [
      'Ngày 30 tháng 5 năm 2020, Công ty TNHH Công nghệ Thương mại và Dịch vụ Phúc Thịnh Solar chính thức được thành lập. Đó không chỉ là sự ra đời của một doanh nghiệp, mà là khởi đầu của một tinh thần trẻ: dám nghĩ, dám làm, dám chịu trách nhiệm với từng sản phẩm, từng công trình và từng cam kết.',
      'Từ những bước đi đầu tiên đầy thử thách, tập thể Phúc Thịnh chọn kiên trì, chọn chất lượng và lấy chữ tín làm kim chỉ nam. Mỗi công trình hoàn thành không chỉ tạo ra điện năng mà còn thắp lên niềm tin về một tương lai bền vững.',
    ],
  },
  {
    period: '2021–2022',
    title: 'Chọn bước tiếp giữa đại dịch',
    paragraphs: [
      'Đại dịch Covid-19 bùng phát, nền kinh tế toàn cầu rơi vào giai đoạn khó khăn chưa từng có. Thị trường đóng băng, chuỗi cung ứng đứt gãy, chi phí tăng cao. Áp lực tài chính, nhân sự và đặc biệt là niềm tin của khách hàng trở thành bài toán sống còn với một doanh nghiệp trẻ.',
      'Đứng trước lựa chọn lùi lại hay bước tiếp, Phúc Thịnh Solar chọn bước tiếp: không vội vàng, không mạo hiểm, mà bằng sự kiên trì thích nghi từng ngày.',
    ],
  },
  {
    period: '2023',
    title: 'Kiên trì mang lại trái ngọt',
    paragraphs: [
      'Phúc Thịnh Solar từng bước khẳng định uy tín trên thị trường qua hoạt động nhập khẩu và phân phối thiết bị điện năng lượng mặt trời, đèn năng lượng mặt trời trên toàn quốc. Song hành là hệ thống kho hàng được đầu tư và mở rộng khắp cả nước, tạo nền tảng cho năng lực cung ứng nhanh chóng, ổn định và chủ động.',
      'Từ kho vận đến sản phẩm, mọi quy trình đều được kiểm soát chặt chẽ. Mỗi sản phẩm đến tay khách hàng là một cam kết rõ ràng về chất lượng, trách nhiệm và uy tín.',
    ],
  },
  {
    period: '2024–2025',
    title: 'Ghi dấu ấn qua từng dự án',
    paragraphs: [
      'Dưới sự lãnh đạo quyết đoán và tầm nhìn chiến lược của ban lãnh đạo, cùng tinh thần đồng đội của toàn bộ các phòng ban, Phúc Thịnh Solar ghi dấu ấn với hàng loạt dự án điện mặt trời dân dụng, thương mại và hệ thống bơm tưới năng lượng mặt trời tại Gia Lai và các vùng lân cận.',
      'Bộ máy công ty được tái cấu trúc tinh gọn, linh hoạt, với đội ngũ trẻ trung, chuyên nghiệp, luôn khát khao học hỏi. Đội ngũ logistics làm chủ toàn bộ quy trình từ vận chuyển, giao nhận đến thi công, để mỗi dự án được triển khai chuyên nghiệp, an toàn, đúng tiến độ và mang đến sự yên tâm cho đối tác, khách hàng.',
      'Năm 2025 đánh dấu 5 năm hình thành và phát triển: 5 năm của dấn thân, 5 năm của thử thách, 5 năm của niềm tin được bồi đắp mỗi ngày.',
    ],
  },
  {
    period: '2026',
    title: 'Mở rộng hệ sinh thái sản phẩm',
    paragraphs: [
      'Phúc Thịnh Solar tiếp tục mở rộng hành trình phát triển, từng bước xây dựng hệ sinh thái sản phẩm toàn diện trong lĩnh vực năng lượng mặt trời.',
      'Công nghệ mới, tư duy mới tạo nền tảng cho sự phát triển ổn định, bền vững và dài lâu.',
    ],
  },
] as const

const activeIndex = ref(0)
const active = computed(() => MILESTONES[activeIndex.value]!)
const rows = ref<HTMLElement[]>([])
const root = ref<HTMLElement | null>(null)
const line = ref<HTMLElement | null>(null)
const seen = ref(false)
// Tall enough screens read the story by scrolling: the section stays put while the periods change. Others tap.
const pinned = ref(false)
// The header slides away while reading down, leaving the section rail at the top of the screen.
const STICKY_TOP = 60
const { isHeaderHidden } = useSiteHeader()
let frame = 0
let pinQuery: MediaQueryList | undefined
let seenObserver: IntersectionObserver | undefined

function clamp(value: number) {
  return Math.min(Math.max(value, 0), 1)
}

// Runs once per frame while scrolling and only writes a transform, so the page stays smooth.
function update() {
  frame = 0
  const section = root.value
  const rail = line.value
  if (!section || !rail) return
  let progress: number
  if (pinned.value) {
    const rect = section.getBoundingClientRect()
    progress = clamp((STICKY_TOP - rect.top) / Math.max(rect.height - (window.innerHeight - STICKY_TOP), 1))
    const index = Math.min(Math.floor(progress * MILESTONES.length), MILESTONES.length - 1)
    if (index !== activeIndex.value) activeIndex.value = index
  } else {
    const rect = rail.getBoundingClientRect()
    progress = clamp((window.innerHeight * 0.7 - rect.top) / Math.max(rect.height, 1))
  }
  rail.style.setProperty('--fill', progress.toFixed(4))
}

function schedule() {
  if (!frame) frame = requestAnimationFrame(update)
}

function setPinned() {
  pinned.value = Boolean(pinQuery?.matches)
  nextTick(update)
}

function openMilestone(index: number) {
  const section = root.value
  if (pinned.value && section) {
    const rect = section.getBoundingClientRect()
    const range = rect.height - (window.innerHeight - STICKY_TOP)
    const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches
    window.scrollTo({ top: window.scrollY + rect.top - STICKY_TOP + range * (index + 0.5) / MILESTONES.length, behavior: reduced ? 'auto' : 'smooth' })
    return
  }
  activeIndex.value = index
  // Closing the period above lifts this one, so bring it back into view once the fold settles.
  window.setTimeout(() => rows.value[index]?.scrollIntoView({ behavior: 'smooth', block: 'nearest' }), 360)
}

onMounted(() => {
  const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches
  if (!reduced) {
    pinQuery = window.matchMedia('(min-width: 1000px) and (min-height: 900px)')
    pinQuery.addEventListener('change', setPinned)
    setPinned()
  }
  window.addEventListener('scroll', schedule, { passive: true })
  window.addEventListener('resize', schedule, { passive: true })
  update()
  if ('IntersectionObserver' in window && root.value) {
    seenObserver = new IntersectionObserver(([entry]) => {
      if (entry?.isIntersecting) {
        seen.value = true
        seenObserver?.disconnect()
      }
    }, { threshold: 0.15 })
    seenObserver.observe(root.value)
  } else seen.value = true
})
onBeforeUnmount(() => {
  window.removeEventListener('scroll', schedule)
  window.removeEventListener('resize', schedule)
  pinQuery?.removeEventListener('change', setPinned)
  seenObserver?.disconnect()
  if (frame) cancelAnimationFrame(frame)
})
</script>

<template>
  <section
    id="lich-su-phat-trien"
    ref="root"
    class="about-history about-chapter scroll-mt-[160px] max-[800px]:scroll-mt-[116px] min-[1800px]:scroll-mt-[184px]"
    :class="{ 'is-pinned': pinned, 'is-seen': seen, 'header-away': isHeaderHidden }"
    aria-labelledby="about-history-title"
  >
    <div class="about-history__stage">
      <div class="about-history__grid pts-container">
        <div class="about-history__main min-w-0">
          <h2 id="about-history-title">Lịch sử hình thành</h2>
          <p class="about-history__lead">{{ LEAD }}</p>

          <ol ref="line" class="about-history__line" aria-label="Các mốc phát triển của Phúc Thịnh Solar">
            <li
              v-for="(item, index) in MILESTONES"
              :key="item.period"
              :ref="(element) => { if (element) rows[index] = element as HTMLElement }"
              class="about-history__stop"
              :class="{ 'is-open': index === activeIndex, 'is-past': index < activeIndex }"
              :style="{ '--stop': index }"
            >
              <span class="about-history__dot" aria-hidden="true" />
              <h3>
                <button type="button" :aria-expanded="index === activeIndex" @click="openMilestone(index)">
                  <span class="about-history__year">{{ item.period }}</span>
                  <span class="about-history__title">{{ item.title }}</span>
                </button>
              </h3>
              <!-- Closed periods stay in the page for search engines; they fold rather than disappear. -->
              <div class="about-history__fold">
                <div>
                  <p v-for="text in item.paragraphs" :key="text">{{ text }}</p>
                </div>
              </div>
            </li>
          </ol>
        </div>

        <aside class="about-history__side">
          <figure :style="{ '--stage': activeIndex }">
            <img :src="image" alt="Không gian trưng bày thiết bị năng lượng mặt trời của Phúc Thịnh Solar" width="780" height="600" loading="lazy">
            <span :key="`sheen-${active.period}`" class="about-history__sheen" aria-hidden="true" />
            <figcaption :key="active.period" class="pts-gold-text">{{ active.period }}</figcaption>
          </figure>
          <blockquote>
            <strong class="about-history__founder pts-gold-text-light">Nhà sáng lập Mai Anh Tuấn</strong>
            <p>Luôn nhắc nhở: một doanh nghiệp muốn đi xa phải bắt đầu từ chữ tâm, chữ tín và giá trị thật. Đó là kim chỉ nam cho mọi quyết định của Phúc Thịnh Solar hôm nay và mai sau.</p>
            <p>Từ những bước đi đầu tiên đầy gian nan, Phúc Thịnh Solar tự hào trở thành đơn vị uy tín trong lĩnh vực nhập khẩu, phân phối thiết bị điện năng lượng mặt trời tại Việt Nam. Chúng tôi trân trọng và biết ơn những khách hàng, đối tác đã tin tưởng trao cơ hội được đồng hành và phụng sự.</p>
            <strong class="pts-gold-text-light">5 năm khởi nguồn, vững bước tương lai.</strong>
          </blockquote>
        </aside>
      </div>
    </div>
  </section>
</template>

<style scoped>
.about-history { padding-block: 40px; background: var(--color-slate-50); }
.about-history.is-pinned { height: calc(100vh + 240vh); padding-block: 0; }
/* The stage starts just under the section rail, whose place depends on the header. */
.about-history { --stage-top: 148px; }
.about-history.header-away { --stage-top: 60px; }
@media (min-width: 1321px) { .about-history:not(.header-away) { --stage-top: 164px; } }
@media (min-width: 1501px) { .about-history:not(.header-away) { --stage-top: 180px; } }
@media (min-width: 1800px) { .about-history:not(.header-away) { --stage-top: 196px; } }
.is-pinned .about-history__stage { position: sticky; top: var(--stage-top); height: calc(100vh - var(--stage-top)); padding-block: 24px; overflow: hidden; transition: top .3s cubic-bezier(.16, 1, .3, 1); }
/* Pinned, the story fills the whole stage: the photograph grows and the periods spread out, so no empty band is left below. */
.is-pinned .about-history__grid { height: 100%; max-height: 960px; grid-template-rows: minmax(0, 1fr); align-items: stretch; }
.is-pinned .about-history__main { display: flex; min-height: 0; flex-direction: column; }
.is-pinned .about-history__line { min-height: 0; flex: 1; display: flex; flex-direction: column; justify-content: space-between; }
.is-pinned .about-history__side { min-height: 0; grid-template-rows: minmax(0, 1fr) auto; }
.is-pinned .about-history__side figure { min-height: 0; aspect-ratio: auto; }
.about-history__grid { display: grid; grid-template-columns: minmax(0, 1.1fr) minmax(0, .9fr); gap: clamp(30px, 5vw, 76px); align-items: start; }
h2 { margin: 0; color: var(--color-navy-950); font-size: clamp(29px, 2.6vw, 38px); font-weight: 700; line-height: 1.22; }
.about-history__lead { margin: 12px 0 20px; color: #52627c; font-size: 16.5px; line-height: 1.6; }

/* An energy line: a gold beam fills with the scroll and a glowing head travels on its tip. */
.about-history__line { --fill: 0; position: relative; display: grid; margin: 0; padding: 0; list-style: none; }
.about-history__line::before, .about-history__line::after { content: ""; position: absolute; top: 12px; bottom: 18px; left: 5px; width: 2px; }
.about-history__line::before { background: rgb(201 150 46 / 24%); }
.about-history__line::after { background: linear-gradient(180deg, #f6dc8e, #c9962e); box-shadow: 0 0 10px rgb(224 182 84 / 70%); transform: scaleY(var(--fill)); transform-origin: top; }
.about-history__stop { position: relative; z-index: 1; padding: 0 0 4px 28px; opacity: 0; transform: translateX(-14px); transition: opacity .6s cubic-bezier(.22, 1, .36, 1) calc(var(--stop) * 80ms), transform .6s cubic-bezier(.22, 1, .36, 1) calc(var(--stop) * 80ms); }
.is-seen .about-history__stop { opacity: 1; transform: none; }
.about-history__dot { position: absolute; top: 15px; left: 0; width: 12px; height: 12px; border: 2px solid var(--color-gold-500); border-radius: 50%; background: var(--color-slate-50); transition: background-color .3s ease, box-shadow .3s ease, transform .3s ease; }
.is-past .about-history__dot { background: var(--color-gold-500); }
.is-open .about-history__dot { background: var(--color-gold-400); transform: scale(1.15); animation: history-pulse 2.2s ease-out infinite; }
h3 { margin: 0; font: inherit; }
h3 button { display: flex; width: 100%; min-height: 44px; flex-wrap: wrap; align-items: center; gap: 4px 12px; text-align: left; cursor: pointer; }
.about-history__year { display: inline-flex; padding: 1px 10px; border-radius: var(--radius-chip); background: rgb(201 150 46 / 15%); color: var(--color-gold-800); font-size: 18px; font-weight: 900; line-height: 1.35; transition: background .3s ease, color .3s ease; }
.is-open .about-history__year { background: var(--pts-gold-gradient); color: var(--color-navy-950); }
.about-history__title { color: #52627c; font-size: 17px; font-weight: 700; line-height: 1.3; transition: color .25s ease; }
h3 button:hover .about-history__title, .is-open .about-history__title { color: var(--color-navy-900); }
.is-open .about-history__title { font-size: clamp(19px, 1.5vw, 22px); font-weight: 800; }
.about-history__fold { display: grid; grid-template-rows: 0fr; visibility: hidden; transition: grid-template-rows .42s cubic-bezier(.22, 1, .36, 1), visibility .42s; }
.is-open .about-history__fold { grid-template-rows: 1fr; visibility: visible; }
.about-history__fold > div { min-height: 0; overflow: hidden; }
.about-history__fold p { margin: 0 0 8px; color: #52627c; font-size: 16px; line-height: 1.6; }
.is-open .about-history__fold p { animation: history-text .55s cubic-bezier(.22, 1, .36, 1) both; }
.is-open .about-history__fold p:nth-child(2) { animation-delay: 70ms; }
.is-open .about-history__fold p:nth-child(3) { animation-delay: 140ms; }

.about-history__side { display: grid; gap: 14px; }
.about-history__side figure { --stage: 0; position: relative; margin: 0; overflow: hidden; border-radius: var(--radius-btn); background: var(--color-navy-950); aspect-ratio: 16 / 9; }
/* One photograph, a slow camera move from period to period. */
.about-history__side img { display: block; width: 100%; height: 100%; object-fit: cover; transform: scale(calc(1.04 + var(--stage) * .035)) translate3d(calc(var(--stage) * -1%), calc(var(--stage) * .5%), 0); transition: transform 1.4s cubic-bezier(.22, 1, .36, 1); }
.about-history__side figure::after { content: ""; position: absolute; inset: 42% 0 0; background: linear-gradient(180deg, transparent, rgb(6 21 55 / 84%)); }
.about-history__side figcaption { position: absolute; z-index: 2; left: 20px; bottom: 10px; padding: 0 4px 4px 0; font-size: clamp(40px, 4.4vw, 68px); font-weight: 900; line-height: 1; letter-spacing: -.03em; animation: history-year .7s cubic-bezier(.22, 1, .36, 1) both; }
.about-history__sheen { position: absolute; z-index: 1; inset: 0; background: linear-gradient(105deg, transparent 35%, rgb(246 220 142 / 34%) 50%, transparent 65%); transform: translateX(-100%); animation: history-sheen 1.1s cubic-bezier(.22, 1, .36, 1) both; pointer-events: none; }
blockquote { display: grid; gap: 8px; margin: 0; padding-left: 16px; border-left: 3px solid var(--color-gold-500); }
blockquote p { margin: 0; color: #44546c; font-size: 14.5px; line-height: 1.55; }
blockquote strong { font-size: 18px; font-weight: 800; justify-self: start; }
blockquote .about-history__founder { font-size: clamp(22px, 1.9vw, 28px); font-weight: 900; line-height: 1.2; letter-spacing: -.015em; }
/* Tall screens give the story more room, so the type grows with it. */
@media (min-height: 950px) and (min-width: 1000px) {
  .is-pinned .about-history__year { font-size: 21px; }
  .is-pinned .about-history__title { font-size: 19px; }
  .is-pinned .is-open .about-history__title { font-size: 25px; }
  .is-pinned .about-history__lead, .is-pinned .about-history__fold p { font-size: 18px; line-height: 1.65; }
  .is-pinned blockquote p { font-size: 16px; }
}
@keyframes history-year { from { opacity: 0; filter: blur(6px); transform: translateY(16px) scale(.96); } }
@keyframes history-text { from { opacity: 0; transform: translateY(8px); } }
@keyframes history-sheen { to { transform: translateX(100%); } }
@keyframes history-pulse { 0% { box-shadow: 0 0 0 0 rgb(224 182 84 / 55%); } 100% { box-shadow: 0 0 0 11px rgb(224 182 84 / 0%); } }
@media (max-width: 800px) {
  .about-history { padding-block: 40px; }
  .about-history__grid { grid-template-columns: minmax(0, 1fr); gap: 22px; }
  .about-history__lead { margin-bottom: 18px; font-size: 16px; }
  .about-history__stop { padding-left: 24px; }
  .about-history__title { font-size: 15.5px; }
  .is-open .about-history__title { font-size: 17px; }
  .about-history__side figure { display: none; }
  blockquote p { font-size: 15px; line-height: 1.6; }
}
@media (prefers-reduced-motion: reduce) {
  .about-history__stop { opacity: 1; transform: none; transition: none; }
  .about-history__fold, .about-history__side img { transition: none; }
  .about-history__fold p, .about-history__side figcaption, .about-history__sheen, .is-open .about-history__dot { animation: none; }
  .about-history__sheen { display: none; }
}
</style>
