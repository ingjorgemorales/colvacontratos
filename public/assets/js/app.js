document.addEventListener('DOMContentLoaded', function () {
  const toggle = document.getElementById('sideToggle');

  function isMobile() {
    return window.matchMedia('(max-width: 991px)').matches;
  }

  if (toggle) {
    toggle.addEventListener('click', function (event) {
      event.preventDefault();

      if (isMobile()) {
        document.body.classList.remove('sidebar-collapsed');
        return;
      }

      document.body.classList.toggle('sidebar-collapsed');
    });
  }

  window.addEventListener('resize', function () {
    if (isMobile()) {
      document.body.classList.remove('sidebar-collapsed');
    }
  });
});
