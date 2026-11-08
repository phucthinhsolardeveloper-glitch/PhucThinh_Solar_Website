<script setup lang="ts">
import '~/assets/css/experience-design-system.css'
import PtsChapterNav from '~/components/design-system/PtsChapterNav.vue'
import PtsJourney from '~/components/design-system/PtsJourney.vue'
import PtsLayout from '~/components/design-system/PtsLayout.vue'
import PtsMediaFrame from '~/components/design-system/PtsMediaFrame.vue'
import PtsMetric from '~/components/design-system/PtsMetric.vue'
import PtsMotionReveal from '~/components/design-system/PtsMotionReveal.vue'
import PtsPageIntro from '~/components/design-system/PtsPageIntro.vue'
import PtsSection from '~/components/design-system/PtsSection.vue'

const chapters = [
  { id: 'precision-paper', label: 'Precision Paper' },
  { id: 'energy-stage', label: 'Spatial Energy' },
  { id: 'solar-cinema', label: 'Solar Cinema' },
  { id: 'architectural-editorial', label: 'Editorial' },
  { id: 'technical-journal', label: 'Technical Journal' },
  { id: 'product-atelier', label: 'Product Atelier' },
  { id: 'evidence-narrative', label: 'Evidence' },
  { id: 'connected-journey', label: 'Journey' },
]

const journeySteps = [
  { id: 'orient', title: 'Định hướng', description: 'Nêu rõ nhóm nhu cầu và lối vào tiếp theo.' },
  { id: 'verify', title: 'Kiểm chứng', description: 'Dữ liệu thật, nhãn nguồn và trạng thái thiếu dữ liệu.' },
  { id: 'compare', title: 'Cân nhắc', description: 'So sánh thuộc tính cùng loại trong cùng ngữ cảnh.' },
  { id: 'act', title: 'Hành động', description: 'Đi tới sản phẩm hoặc yêu cầu tư vấn có ngữ cảnh.' },
]

useSeoMeta({
  title: 'PTS Experience Design System — preview',
  description: 'Preview nội bộ các token và primitive thiết kế dùng chung.',
  robots: 'noindex, nofollow, noarchive',
})
</script>

<template>
  <main class="pts-experience-system">
    <PtsSection id="precision-paper" composition="precision-paper" tone="paper" spacing="roomy" aria-labelledby="system-title">
      <PtsLayout width="site">
        <div class="pts-grid pts-grid--2 items-end">
          <div>
            <p class="pts-composition__label">PTS Experience System · Preview</p>
            <PtsPageIntro
              title="Một hệ thống, nhiều nhịp kể chuyện."
              description="Nền tảng chung cho showroom sản phẩm và tư vấn kỹ thuật: nhận diện navy/gold, layout phẳng, nội dung có bằng chứng và tương tác dùng được trên mọi màn hình."
            />
          </div>
          <div class="pts-stack" style="--pts-stack-gap: var(--pts-space-4)">
            <p class="m-0 text-sm leading-6 text-slate-600">Các mẫu dưới đây dùng cùng token, layout và component. Mỗi phong cách thay đổi nhịp trình bày theo công việc của trang.</p>
            <div class="pts-cluster" aria-label="Đặc tính hệ thống">
              <span class="pts-system-tag">Navy / Gold</span>
              <span class="pts-system-tag">Nuxt UI + Vue</span>
              <span class="pts-system-tag">Reduced motion</span>
            </div>
          </div>
        </div>
        <div class="pts-token-strip" aria-label="Token nền tảng">
          <div><span class="pts-token-swatch pts-token-swatch--navy" /> <span>Navy 800</span><code>#0A2251</code></div>
          <div><span class="pts-token-swatch pts-token-swatch--gold" /> <span>Gold 500</span><code>#C9962E</code></div>
          <div><span class="pts-token-swatch pts-token-swatch--paper" /> <span>Paper</span><code>#FFFFFF</code></div>
          <div><span class="pts-token-swatch pts-token-swatch--line" /> <span>Boundary</span><code>#E5E7EB</code></div>
        </div>
      </PtsLayout>
    </PtsSection>

    <PtsChapterNav :items="chapters" sticky label="Các phong cách trong hệ thống" />

    <PtsSection id="energy-stage" composition="spatial-energy-stage" tone="stage" spacing="roomy" aria-labelledby="energy-stage-title">
      <PtsLayout width="site">
        <div class="pts-grid pts-grid--2 items-center">
          <PtsMotionReveal>
            <p class="pts-composition__label">02 · Spatial Energy Stage</p>
            <h2 id="energy-stage-title" class="pts-composition__title">Không gian năng lượng có chiều sâu.</h2>
            <p class="pts-composition__body pts-muted">Dùng nền tối như một sân khấu có chủ đích cho trạng thái hoặc mô hình. Ánh sáng chỉ định hướng mắt; thông tin quan trọng vẫn là chữ và điều khiển thật.</p>
          </PtsMotionReveal>
          <PtsMediaFrame
            src="/assets/images/site-visuals/solution-bess-v2.jpg"
            alt="Minh họa hệ thống lưu trữ năng lượng trong không gian gia đình"
            ratio="wide"
            fit="cover"
            :width="1600"
            :height="1000"
            caption="Ảnh minh họa ứng dụng, không phải bằng chứng dự án hoặc dữ liệu vận hành."
          />
        </div>
      </PtsLayout>
    </PtsSection>

    <PtsSection id="solar-cinema" composition="solar-cinema" tone="cinema" spacing="roomy" aria-labelledby="solar-cinema-title">
      <PtsLayout width="site">
        <div class="pts-cinema-layout">
          <div class="pts-cinema-layout__copy">
            <p class="pts-composition__label">03 · Solar Cinema</p>
            <h2 id="solar-cinema-title" class="pts-cinema-title">Dẫn mắt bằng ánh sáng, giữ đường đi rõ ràng.</h2>
            <p class="pts-composition__body pts-muted">Một nhịp cinematic có điểm bắt đầu, khoảng thở và hành động kế tiếp. Ảnh chiến dịch lấy từ Admin; không autoplay và không dùng chuyển động để giấu CTA.</p>
            <a class="pts-action-link" href="#connected-journey">Xem cấu trúc hành trình <span aria-hidden="true">↘</span></a>
          </div>
          <PtsMediaFrame
            class="pts-cinema-media"
            src="/assets/images/site-visuals/solution-home-solar-v2.jpg"
            alt="Minh họa nhà ở sử dụng điện mặt trời"
            ratio="wide"
            fit="cover"
            :width="1600"
            :height="1000"
            caption="Ảnh minh họa giải pháp, không đại diện khách hàng hoặc công trình đã xác minh."
          />
        </div>
      </PtsLayout>
    </PtsSection>

    <PtsSection id="architectural-editorial" composition="architectural-editorial" tone="paper" spacing="roomy" aria-labelledby="editorial-title">
      <PtsLayout width="site">
        <p class="pts-composition__label">04 · Architectural Editorial</p>
        <div class="pts-composition__split">
          <div>
            <h2 id="editorial-title" class="pts-composition__title">Nội dung có cấu trúc như một mặt bằng.</h2>
            <p class="pts-composition__body">Lưới bất đối xứng giúp tiêu đề, nội dung biên tập và ảnh có vai trò riêng. Tỷ lệ ảnh theo đúng field: dự án 4:3, bài viết 16:9, nội dung 8:5.</p>
          </div>
          <PtsMediaFrame
            src="/assets/images/site-visuals/home-outdoor-lighting.jpg"
            alt="Minh họa chiếu sáng ngoài trời"
            ratio="project"
            fit="cover"
            :width="1200"
            :height="900"
            caption="Ảnh minh họa ứng dụng, dùng làm vùng media trong nhịp editorial."
          />
        </div>
      </PtsLayout>
    </PtsSection>

    <PtsSection id="technical-journal" composition="technical-journal" tone="quiet" spacing="roomy" aria-labelledby="journal-title">
      <PtsLayout width="site">
        <div class="pts-journal-head">
          <div>
            <p class="pts-composition__label">05 · Technical Journal</p>
            <h2 id="journal-title" class="pts-composition__title">Thông tin kỹ thuật theo thứ tự đọc.</h2>
          </div>
          <p class="pts-composition__body">Label, giá trị và đơn vị lấy từ DTO. Trường trống hiển thị “Chưa có dữ liệu”, không tự diễn giải từ tên sản phẩm.</p>
        </div>
        <dl class="pts-data-ledger">
          <div class="pts-data-ledger__row"><dt>Thông số</dt><dd>Do dữ liệu sản phẩm cung cấp</dd></div>
          <div class="pts-data-ledger__row"><dt>Đơn vị</dt><dd>Hiển thị cạnh giá trị</dd></div>
          <div class="pts-data-ledger__row"><dt>Nguồn</dt><dd>CMS / API đã xác minh</dd></div>
        </dl>
      </PtsLayout>
    </PtsSection>

    <PtsSection id="product-atelier" composition="product-atelier" tone="paper" spacing="roomy" aria-labelledby="atelier-title">
      <PtsLayout width="site">
        <p class="pts-composition__label">06 · Product Atelier</p>
        <div class="pts-composition__product">
          <PtsMediaFrame
            src="/assets/images/site-visuals/media-fallbacks/product-x3-pro-30.webp"
            alt="Thiết bị sản phẩm trên nền sáng"
            ratio="square"
            fit="contain"
            :width="640"
            :height="640"
            caption="Khung ảnh sản phẩm 1:1, contain; nguồn media gắn với dữ liệu sản phẩm."
          />
          <div>
            <h2 id="atelier-title" class="pts-composition__title">Sản phẩm là trung tâm, giao diện lùi lại.</h2>
            <p class="pts-composition__body">Gallery, thông số và CTA là các thành phần độc lập. Khung ảnh không crop sản phẩm; giá, mã và tình trạng luôn lấy từ PDP.</p>
            <div class="pts-grid pts-grid--2 pts-atelier-metrics">
              <PtsMetric label="Mã sản phẩm" value="Từ PDP" note="Không tạo mã mẫu." />
              <PtsMetric label="Thông số còn thiếu" :value="null" note="Fallback an toàn." />
            </div>
          </div>
        </div>
      </PtsLayout>
    </PtsSection>

    <PtsSection id="evidence-narrative" composition="evidence-narrative" tone="warm" spacing="roomy" aria-labelledby="evidence-title">
      <PtsLayout width="site">
        <p class="pts-composition__label">07 · Evidence Narrative</p>
        <div class="pts-composition__split">
          <div>
            <h2 id="evidence-title" class="pts-composition__title">Câu chuyện luôn đi cùng nguồn.</h2>
            <p class="pts-composition__body">Ảnh dự án, tuyên bố kỹ thuật và nội dung khách hàng cần có dữ liệu xác minh. Khi thiếu nguồn hoặc quyền sử dụng, component cho phép ẩn nội dung hoặc nêu trạng thái rõ ràng.</p>
          </div>
          <div class="pts-evidence-example">
            <div class="pts-evidence"><span class="pts-evidence__mark" aria-hidden="true" /><div><strong>Thông tin được quản lý</strong><div class="pts-evidence__source">Nguồn hiển thị: CMS / DTO · trạng thái do owner xác nhận</div></div></div>
            <div class="pts-evidence"><span class="pts-evidence__mark" aria-hidden="true" /><div><strong>Media có quyền sử dụng</strong><div class="pts-evidence__source">Caption, alt text và provenance đi cùng asset</div></div></div>
            <div class="pts-evidence"><span class="pts-evidence__mark" aria-hidden="true" /><div><strong>Thiếu dữ liệu</strong><div class="pts-evidence__source">Ẩn an toàn hoặc hiển thị “Chưa có dữ liệu”</div></div></div>
          </div>
        </div>
      </PtsLayout>
    </PtsSection>

    <PtsSection id="connected-journey" composition="connected-journey" tone="paper" spacing="roomy" aria-labelledby="journey-title">
      <PtsLayout width="site">
        <div class="pts-grid pts-grid--2 items-end">
          <div>
            <p class="pts-composition__label">08 · Connected Journey</p>
            <h2 id="journey-title" class="pts-composition__title">Từ khám phá tới hành động tiếp theo.</h2>
          </div>
          <p class="pts-composition__body">Chapter links là anchor tiêu chuẩn, hỗ trợ bàn phím, touch và URL hash. Cuộn trang vẫn do trình duyệt điều khiển; không chặn wheel hoặc swipe.</p>
        </div>
        <div class="pts-journey-showcase">
          <PtsJourney :steps="journeySteps" current-id="verify" />
        </div>
      </PtsLayout>
    </PtsSection>

    <footer class="pts-system-footer">
      <PtsLayout width="site">
        <p class="m-0">PTS Experience Design System · Local preview · Không đại diện trang production</p>
      </PtsLayout>
    </footer>
  </main>
</template>

<style scoped>
.pts-experience-system { color: var(--pts-ink); font-family: var(--pts-font-body); }
.pts-system-tag { display: inline-flex; min-height: 2rem; align-items: center; padding: .4rem .7rem; color: var(--pts-ink); background: var(--pts-surface); font-size: .75rem; font-weight: 650; }
.pts-token-strip { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 1.5rem; margin-top: clamp(2rem, 5vw, 4.5rem); padding-top: 1.25rem; border-top: 1px solid var(--pts-line); }
.pts-token-strip > div { display: grid; grid-template-columns: 1.25rem minmax(0, 1fr); align-items: center; column-gap: .6rem; row-gap: .2rem; font-size: .8rem; }
.pts-token-strip code { grid-column: 2; color: var(--pts-muted); font-size: .75rem; }
.pts-token-swatch { grid-row: 1 / span 2; width: 1rem; height: 1rem; border: 1px solid rgb(10 34 81 / 14%); border-radius: 2px; }
.pts-token-swatch--navy { background: #0a2251; }
.pts-token-swatch--gold { background: #c9962e; }
.pts-token-swatch--paper { background: #fff; }
.pts-token-swatch--line { background: #e5e7eb; }
.pts-cinema-layout { display: grid; min-height: min(58vw, 37rem); grid-template-columns: minmax(0, .9fr) minmax(18rem, 1.1fr); align-items: center; gap: clamp(2rem, 7vw, 7rem); }
.pts-cinema-layout__copy { position: relative; z-index: 1; max-width: 40rem; }
.pts-cinema-title { max-width: 12ch; margin: 0; color: #fff; font-size: var(--pts-type-display); line-height: .99; letter-spacing: -.055em; }
.pts-action-link { display: inline-flex; min-height: 44px; align-items: center; gap: .6rem; margin-top: 1.5rem; color: var(--pts-accent-light); font-weight: 700; text-decoration: none; }
.pts-cinema-media { transform: translateY(1rem); }
.pts-journal-head { display: grid; grid-template-columns: minmax(0, 1fr) minmax(16rem, .7fr); align-items: end; gap: 2rem; }
.pts-journal-head .pts-composition__body { max-width: 34rem; }
.pts-atelier-metrics { margin-top: 2rem; }
.pts-evidence-example { display: grid; gap: 1.5rem; padding-left: clamp(0rem, 4vw, 3rem); }
.pts-journey-showcase { margin-top: clamp(2rem, 5vw, 4rem); }
.pts-system-footer { padding-block: 1.5rem; color: #dbeafe; background: var(--pts-ink-deep); font-size: .75rem; }
@media (max-width: 820px) {
  .pts-token-strip { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem; }
  .pts-cinema-layout, .pts-journal-head { grid-template-columns: minmax(0, 1fr); }
  .pts-cinema-layout { min-height: auto; }
  .pts-cinema-media { transform: none; }
  .pts-evidence-example { padding-left: 0; }
}
</style>
