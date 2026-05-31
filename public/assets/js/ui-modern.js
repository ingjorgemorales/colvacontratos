(function () {
  function ready(fn) {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
  }

  ready(function () {
    var body = document.body;
    var loader = document.getElementById('appLoader');
    var mobileToggle = document.getElementById('mobileMenuToggle');
    var sidebarClose = document.getElementById('sidebarClose');
    var backdrop = document.getElementById('sidebarBackdrop');

    function openSidebar() {
      body.classList.add('sidebar-open');
      body.classList.remove('sidebar-collapsed');
    }

    function closeSidebar() {
      body.classList.remove('sidebar-open');
    }

    function showLoader() {
      if (!loader) return;
      loader.classList.remove('is-hidden');
      body.classList.add('is-loading');
    }

    function hideLoader() {
      if (!loader) return;
      loader.classList.add('is-hidden');
      body.classList.remove('is-loading');
    }

    function isDownloadOrExportUrl(href) {
      href = (href || '').toLowerCase();
      return href.indexOf('excel') !== -1 ||
        href.indexOf('csv') !== -1 ||
        href.indexOf('pdf') !== -1 ||
        href.indexOf('export') !== -1 ||
        href.indexOf('download') !== -1;
    }

    function shouldSkipLoaderForLink(link) {
      var href = link.getAttribute('href') || '';
      var target = link.getAttribute('target') || '';

      if (!href) return true;
      if (href.charAt(0) === '#') return true;
      if (target === '_blank') return true;
      if (link.hasAttribute('download')) return true;
      if (href.indexOf('javascript:') === 0) return true;
      if (link.classList.contains('no-loader')) return true;
      if (isDownloadOrExportUrl(href)) return true;

      return false;
    }

    if (mobileToggle) {
      mobileToggle.addEventListener('click', function (event) {
        event.preventDefault();
        openSidebar();
      });
    }

    if (sidebarClose) {
      sidebarClose.addEventListener('click', function (event) {
        event.preventDefault();
        closeSidebar();
      });
    }

    if (backdrop) backdrop.addEventListener('click', closeSidebar);

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') closeSidebar();
    });

    document.querySelectorAll('.modern-menu a[href]').forEach(function (link) {
      link.addEventListener('click', closeSidebar);
    });

    window.addEventListener('load', function () {
      setTimeout(hideLoader, 180);
    });

    window.addEventListener('pageshow', function () {
      hideLoader();
      closeSidebar();
    });

    window.addEventListener('focus', hideLoader);

    document.addEventListener('visibilitychange', function () {
      if (!document.hidden) hideLoader();
    });

    document.querySelectorAll('a[href]').forEach(function (link) {
      link.addEventListener('click', function () {
        if (shouldSkipLoaderForLink(link)) {
          hideLoader();
          return;
        }
        showLoader();
      });
    });

    document.querySelectorAll('form').forEach(function (form) {
      form.addEventListener('submit', function () {
        if (form.checkValidity && !form.checkValidity()) return;

        var action = form.getAttribute('action') || '';
        var route = '';
        var routeInput = form.querySelector('[name="r"]');
        if (routeInput) route = routeInput.value || '';

        if (isDownloadOrExportUrl(action) || isDownloadOrExportUrl(route)) {
          hideLoader();
          return;
        }

        showLoader();
      });
    });

    setTimeout(hideLoader, 900);
  });
})();
