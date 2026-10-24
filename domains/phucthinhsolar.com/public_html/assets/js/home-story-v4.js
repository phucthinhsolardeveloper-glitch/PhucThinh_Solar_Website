(function () {
    'use strict';

    var root = document.querySelector('.pts-home-v4');
    if (!root) return;

    var heroSlider = root.querySelector('[data-home-hero-slider]');
    if (heroSlider) {
        var heroSlides = Array.prototype.slice.call(heroSlider.querySelectorAll('[data-home-hero-slide]'));
        var heroPrev = heroSlider.querySelector('[data-home-hero-prev]');
        var heroNext = heroSlider.querySelector('[data-home-hero-next]');
        var heroPause = heroSlider.querySelector('[data-home-hero-pause]');
        var heroCurrent = heroSlider.querySelector('[data-home-hero-current]');
        var heroIndex = 0;
        var heroTimer = null;
        var heroTouchStart = null;
        var heroPaused = false;

        function showHeroSlide(nextIndex) {
            if (!heroSlides.length) return;
            heroIndex = (nextIndex + heroSlides.length) % heroSlides.length;
            heroSlides.forEach(function (slide, slideIndex) {
                var active = slideIndex === heroIndex;
                slide.classList.toggle('is-active', active);
                slide.setAttribute('aria-hidden', active ? 'false' : 'true');
                var link = slide.querySelector('a');
                if (link) link.tabIndex = active ? 0 : -1;
            });
            if (heroCurrent) heroCurrent.textContent = String(heroIndex + 1).padStart(2, '0');
        }

        function stopHeroAutoplay() {
            if (heroTimer) window.clearInterval(heroTimer);
            heroTimer = null;
        }

        function startHeroAutoplay() {
            stopHeroAutoplay();
            if (heroSlides.length > 1 && !heroPaused && !document.hidden) {
                heroTimer = window.setInterval(function () { showHeroSlide(heroIndex + 1); }, 4200);
            }
        }

        function updateHeroPauseState() {
            if (!heroPause) return;
            var label = heroPaused ? 'Tiếp tục chuyển banner' : 'Dừng chuyển banner';
            heroPause.setAttribute('aria-pressed', heroPaused ? 'true' : 'false');
            heroPause.setAttribute('aria-label', label);
            heroPause.title = label;
            heroPause.innerHTML = heroPaused ? '<i class="fas fa-play" aria-hidden="true"></i>' : '<i class="fas fa-pause" aria-hidden="true"></i>';
        }

        if (heroPrev) heroPrev.addEventListener('click', function () { showHeroSlide(heroIndex - 1); startHeroAutoplay(); });
        if (heroNext) heroNext.addEventListener('click', function () { showHeroSlide(heroIndex + 1); startHeroAutoplay(); });
        if (heroPause) heroPause.addEventListener('click', function () {
            heroPaused = !heroPaused;
            updateHeroPauseState();
            startHeroAutoplay();
        });
        /* Chỉ ngắt ngắn khi đang vuốt hoặc khi người dùng chuyển tab. Nút pause
           giữ quyền kiểm soát rõ ràng mà không thay đổi autoplay mặc định. */
        heroSlider.addEventListener('touchstart', function (event) {
            heroTouchStart = event.touches && event.touches[0] ? event.touches[0].clientX : null;
            stopHeroAutoplay();
        }, { passive: true });
        heroSlider.addEventListener('touchend', function (event) {
            if (heroTouchStart !== null && event.changedTouches && event.changedTouches[0]) {
                var distance = event.changedTouches[0].clientX - heroTouchStart;
                if (Math.abs(distance) > 45) showHeroSlide(heroIndex + (distance < 0 ? 1 : -1));
            }
            heroTouchStart = null;
            startHeroAutoplay();
        }, { passive: true });
        document.addEventListener('visibilitychange', function () {
            if (document.hidden) stopHeroAutoplay();
            else startHeroAutoplay();
        });

        showHeroSlide(0);
        updateHeroPauseState();
        startHeroAutoplay();
    }

    var tabs = Array.prototype.slice.call(root.querySelectorAll('.pts-home-tab'));
    var panels = Array.prototype.slice.call(root.querySelectorAll('.pts-home-tab-panel'));

    function activateTab(index, moveFocus) {
        tabs.forEach(function (tab, tabIndex) {
            var selected = tabIndex === index;
            tab.setAttribute('aria-selected', selected ? 'true' : 'false');
            tab.tabIndex = selected ? 0 : -1;
            if (selected && moveFocus) tab.focus();
        });
        panels.forEach(function (panel, panelIndex) {
            panel.hidden = panelIndex !== index;
        });
    }

    tabs.forEach(function (tab, index) {
        tab.addEventListener('click', function () { activateTab(index, false); });
        tab.addEventListener('keydown', function (event) {
            var target = index;
            if (event.key === 'ArrowRight') target = (index + 1) % tabs.length;
            else if (event.key === 'ArrowLeft') target = (index - 1 + tabs.length) % tabs.length;
            else if (event.key === 'Home') target = 0;
            else if (event.key === 'End') target = tabs.length - 1;
            else return;
            event.preventDefault();
            activateTab(target, true);
        });
    });

    var interest = document.getElementById('ptsHomeInterest');
    var message = document.getElementById('ptsHomeMessage');
    root.addEventListener('click', function (event) {
        var trigger = event.target.closest && event.target.closest('[data-home-quote]');
        if (!trigger) return;
        var requested = (trigger.getAttribute('data-home-quote') || '').trim();
        if (!requested || !interest) return;

        var matched = false;
        Array.prototype.forEach.call(interest.options, function (option) {
            if (option.value === requested || requested.toLowerCase().indexOf(option.value.toLowerCase()) !== -1) {
                interest.value = option.value;
                matched = true;
            }
        });
        if (!matched) {
            interest.value = 'Khác';
            if (message && !message.value.trim()) message.value = 'Tôi cần tư vấn: ' + requested + '.';
        }
    });
})();
