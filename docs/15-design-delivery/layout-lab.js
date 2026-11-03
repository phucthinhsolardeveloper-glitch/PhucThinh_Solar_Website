/* PTS Solar Layout Lab — reference interactions, not CMS/production logic. */
(() => {
  'use strict';
  const site = document.getElementById('demo-site');
  if (!site) return;
  const hero = document.getElementById('hero');
  const heroPhoto = document.getElementById('hero-photo');
  const headerToggle = document.getElementById('header-toggle');
  const motionToggle = document.getElementById('motion-toggle');
  const layoutCaption = document.getElementById('layout-caption');
  const layoutDescription = document.getElementById('layout-description');
  const storyImage = document.getElementById('story-image');
  const storyHeading = document.getElementById('story-heading');
  const storyCopy = document.getElementById('story-copy');
  const range = document.getElementById('shade-range');
  const shadeValue = document.getElementById('shade-value');
  const nav = document.getElementById('site-nav');
  const menuToggle = document.getElementById('menu-toggle');
  const dialog = document.getElementById('lightbox');
  const dialogImage = document.getElementById('lightbox-image');
  const dialogCaption = document.getElementById('lightbox-caption');
  const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)');

  const layouts = {
    bleed: {
      src: 'assets/hero-rooftop.webp',
      alt: 'Ảnh minh họa mái nhà có tấm pin, được phủ dưới header trong suốt.',
      caption: 'Immersive / Full-bleed',
      description: 'Ảnh tràn qua header trong suốt, overlay bảo vệ chữ, và header đổi sang nền sáng sau khi rời hero.'
    },
    editorial: {
      src: 'assets/hero-home.jpg',
      alt: 'Ảnh minh họa nhà dân dụng với điện mặt trời; dùng trong bố cục editorial tách ảnh và chữ.',
      caption: 'Editorial / Split Layout',
      description: 'Chữ nằm trên nền giấy sáng, ảnh chiếm một nửa bố cục; phù hợp đọc kỹ và dẫn dắt tới giải pháp.'
    },
    frame: {
      src: 'assets/hero-solar-farm.webp',
      alt: 'Ảnh minh họa hệ tấm pin quy mô rộng, được đặt trong khung ảnh kiến trúc.',
      caption: 'Architectural / Framed Image',
      description: 'Ảnh là một khối riêng biệt và được căn theo lưới; vùng chữ giữ vai trò dẫn chuyện.'
    }
  };
  const stories = {
    home: {
      src: 'assets/hero-rooftop.webp',
      alt: 'Ảnh minh họa một mái nhà được lắp tấm pin.',
      heading: 'Một mái nhà, một câu chuyện về năng lượng.',
      copy: 'Hình ảnh định hướng trước, chi tiết về giải pháp theo sau. Khi là trang thực tế, mọi thông số và hình ảnh công trình phải liên kết nguồn CMS đã xác minh.',
      caption: 'Ảnh minh họa ứng dụng dân dụng từ thư viện site-visuals.'
    },
    farm: {
      src: 'assets/hero-solar-farm.webp',
      alt: 'Ảnh minh họa hệ điện mặt trời đặt trên mặt đất.',
      heading: 'Không gian rộng cần một góc nhìn rộng.',
      copy: 'Bố cục panorama dành cho nội dung về hệ điện quy mô lớn. Không tự thêm công suất, vị trí, hiệu quả hoặc chủ đầu tư khi thiếu nguồn dữ liệu.',
      caption: 'Ảnh minh họa hệ năng lượng quy mô rộng, không phải hồ sơ một dự án đã xác minh.'
    },
    device: {
      src: 'assets/story-inverter.webp',
      alt: 'Ảnh minh họa một khu vực thiết bị điện.',
      heading: 'Từ toàn cảnh đến chi tiết thiết bị.',
      copy: 'Khi chuyển sang trang sản phẩm, đưa ảnh và thông số từ dữ liệu xác minh; phần tham chiếu chỉ cho thấy tỷ lệ ảnh và luồng đọc, không xác nhận thiết bị cụ thể.',
      caption: 'Ảnh minh họa thiết bị trong không gian lắp đặt.'
    },
    lighting: {
      src: 'assets/story-lighting.webp',
      alt: 'Ảnh minh họa khu vực chiếu sáng ngoài trời.',
      heading: 'Ánh sáng ngoài trời nên được đặt trong bối cảnh sử dụng.',
      copy: 'Ảnh ứng dụng giúp giải thích nhu cầu chiếu sáng. Thông tin độ sáng, công suất và thời gian hoạt động phải đến từ dữ liệu thiết bị có xác minh, không được tự đặt.',
      caption: 'Ảnh minh họa ứng dụng chiếu sáng ngoài trời.'
    }
  };
  const layoutButtons = [...document.querySelectorAll('[data-layout]')].filter((el) => el.tagName === 'BUTTON');
  const storyButtons = [...document.querySelectorAll('button[data-story]')];

  function closeMenu() {
    menuToggle.setAttribute('aria-expanded', 'false');
    nav.classList.remove('is-open');
  }
  function updateScrollHeader() {
    const offset = document.querySelector('.lab-toolbar').getBoundingClientRect().height + document.getElementById('site-header').getBoundingClientRect().height + 8;
    site.dataset.scrolled = String(hero.getBoundingClientRect().bottom < offset);
  }
  function setLayout(id) {
    if (!Object.prototype.hasOwnProperty.call(layouts, id)) return;
    const details = layouts[id];
    site.dataset.layout = id;
    heroPhoto.src = details.src;
    heroPhoto.alt = details.alt;
    layoutCaption.textContent = details.caption;
    layoutDescription.textContent = details.description;
    layoutButtons.forEach((button) => button.setAttribute('aria-pressed', String(button.dataset.layout === id)));
    headerToggle.disabled = id !== 'bleed';
    headerToggle.textContent = id === 'bleed' && site.dataset.header === 'transparent' ? 'Header trong suốt' : 'Header nền đặc';
    headerToggle.setAttribute('aria-pressed', String(id === 'bleed' && site.dataset.header === 'transparent'));
    headerToggle.title = id === 'bleed' ? 'Đổi kiểu header trong Hero ảnh tràn' : 'Header cần nền đặc khi ảnh và chữ tách bố cục';
    closeMenu();
    updateScrollHeader();
  }
  for (const button of layoutButtons) button.addEventListener('click', () => setLayout(button.dataset.layout));

  function toggleHeader() {
    if (site.dataset.layout !== 'bleed') return;
    const transparent = site.dataset.header !== 'transparent';
    site.dataset.header = transparent ? 'transparent' : 'solid';
    headerToggle.setAttribute('aria-pressed', String(transparent));
    headerToggle.textContent = transparent ? 'Header trong suốt' : 'Header nền đặc';
  }
  headerToggle.addEventListener('click', toggleHeader);

  const applyMotion = (on) => {
    site.dataset.motion = on ? 'on' : 'off';
    motionToggle.setAttribute('aria-pressed', String(on));
    motionToggle.textContent = on ? 'Motion bật' : 'Motion tắt';
  };
  applyMotion(!prefersReduced.matches);
  motionToggle.addEventListener('click', () => applyMotion(site.dataset.motion !== 'on'));
  prefersReduced.addEventListener?.('change', (e) => { if (e.matches) applyMotion(false); });

  function setStory(id) {
    const entry = stories[id];
    if (!entry) return;
    storyImage.src = entry.src;
    storyImage.alt = entry.alt;
    storyHeading.textContent = entry.heading;
    storyCopy.textContent = entry.copy;
    dialogCaption.textContent = entry.caption;
    storyButtons.forEach((button) => button.setAttribute('aria-pressed', String(button.dataset.story === id)));
  }
  storyButtons.forEach((button) => button.addEventListener('click', () => setStory(button.dataset.story)));
  setStory('home');

  const open = document.getElementById('open-lightbox');
  const close = document.getElementById('close-lightbox');
  open.addEventListener('click', () => {
    dialogImage.src = storyImage.currentSrc || storyImage.src;
    dialogImage.alt = storyImage.alt;
    if (!dialog.open) dialog.showModal();
  });
  close.addEventListener('click', () => dialog.close());
  dialog.addEventListener('click', (event) => { if (event.target === dialog) dialog.close(); });

  menuToggle.addEventListener('click', () => {
    const isOpen = menuToggle.getAttribute('aria-expanded') !== 'true';
    menuToggle.setAttribute('aria-expanded', String(isOpen));
    nav.classList.toggle('is-open', isOpen);
  });
  nav.querySelectorAll('a').forEach((a) => a.addEventListener('click', closeMenu));
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeMenu(); });

  const changeShade = () => {
    const number = Math.max(58, Math.min(92, Number(range.value) || 79));
    site.style.setProperty('--shade', String(number / 100));
    shadeValue.textContent = number + '%';
  };
  range.addEventListener('input', changeShade);
  changeShade();

  window.addEventListener('scroll', updateScrollHeader, { passive: true });
  window.addEventListener('resize', updateScrollHeader);
  updateScrollHeader();
})();
