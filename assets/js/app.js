// Mobile navigation toggle (kept in an external file so the CSP can forbid inline scripts).
(function () {
  var btn = document.querySelector('.nav-toggle');
  var nav = document.getElementById('nav');
  if (!btn || !nav) return;
  btn.addEventListener('click', function () {
    var open = nav.classList.toggle('open');
    btn.setAttribute('aria-expanded', open ? 'true' : 'false');
  });
})();

// Confirm dialogs for destructive admin actions: <form data-confirm="Are you sure?">
document.addEventListener('submit', function (e) {
  var msg = e.target && e.target.getAttribute && e.target.getAttribute('data-confirm');
  if (msg && !window.confirm(msg)) e.preventDefault();
});

// Select-all checkbox in admin tables
document.addEventListener('change', function (e) {
  if (e.target && e.target.matches && e.target.matches('[data-select-all]')) {
    document.querySelectorAll('input[name="ids[]"]').forEach(function (c) { c.checked = e.target.checked; });
  }
});
