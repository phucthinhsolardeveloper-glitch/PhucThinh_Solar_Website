(function () {
    'use strict';

    var header = document.querySelector('.pts-header-v4');
    if (!header) return;

    var toggle = header.querySelector('.pts-header-search-toggle');
    var panel = header.querySelector('#ptsHeaderSearch');
    var input = header.querySelector('#keyword-res');

    function setSearch(open) {
        if (!toggle || !panel) return;
        panel.hidden = !open;
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        toggle.setAttribute('aria-label', open ? 'Đóng tìm kiếm' : 'Mở tìm kiếm');
        var icon = toggle.querySelector('i');
        if (icon) {
            icon.classList.toggle('fa-search', !open);
            icon.classList.toggle('fa-times', open);
        }
        if (open && input) window.setTimeout(function () { input.focus(); }, 20);
    }

    if (toggle && panel) {
        toggle.addEventListener('click', function () {
            setSearch(toggle.getAttribute('aria-expanded') !== 'true');
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') {
                setSearch(false);
                toggle.focus();
            }
        });

        document.addEventListener('click', function (event) {
            if (toggle.getAttribute('aria-expanded') !== 'true') return;
            if (!header.contains(event.target)) setSearch(false);
        });
    }

})();
