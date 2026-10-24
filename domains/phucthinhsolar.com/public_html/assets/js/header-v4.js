(function () {
    'use strict';

    var header = document.querySelector('.pts-header-v4');
    if (!header) return;

    var toggle = header.querySelector('.pts-header-search-toggle');
    var panel = header.querySelector('#ptsHeaderSearch');
    var input = header.querySelector('#keyword-res');

    function setSearch(open, restoreFocus) {
        if (!toggle || !panel) return;
        if (!open && panel.contains(document.activeElement) && document.activeElement.blur) {
            document.activeElement.blur();
        }
        panel.hidden = !open;
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        toggle.setAttribute('aria-label', open ? 'Đóng tìm kiếm' : 'Mở tìm kiếm');
        var icon = toggle.querySelector('i');
        if (icon) {
            icon.classList.toggle('fa-search', !open);
            icon.classList.toggle('fa-times', open);
        }
        if (open) {
            document.dispatchEvent(new CustomEvent('pts:header-search-open'));
            if (input) window.setTimeout(function () { input.focus({preventScroll: true}); }, 20);
        } else if (restoreFocus) {
            toggle.focus({preventScroll: true});
        }
    }

    if (toggle && panel) {
        toggle.addEventListener('click', function () {
            setSearch(toggle.getAttribute('aria-expanded') !== 'true', false);
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') {
                event.preventDefault();
                setSearch(false);
                toggle.focus({preventScroll: true});
            }
        });

        document.addEventListener('click', function (event) {
            if (toggle.getAttribute('aria-expanded') !== 'true') return;
            if (!header.contains(event.target)) setSearch(false);
        });

        document.addEventListener('pts:header-search-close', function (event) {
            if (toggle.getAttribute('aria-expanded') !== 'true') return;
            var detail = event && event.detail ? event.detail : {};
            setSearch(false, detail.restoreFocus === true);
        });
    }

})();
