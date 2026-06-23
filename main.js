// assets/js/main.js

document.addEventListener('DOMContentLoaded', () => {

  // ── Auto-dismiss alerts ──────────────────────────────────────
  document.querySelectorAll('.alert[data-auto-dismiss]').forEach(el => {
    setTimeout(() => el.remove(), 4000);
  });

  // ── Active nav link highlight ────────────────────────────────
  const path = window.location.pathname;
  document.querySelectorAll('.sidebar-nav a').forEach(a => {
    if (a.getAttribute('href') === path) a.classList.add('active');
  });

  // ── Cart quantity buttons ────────────────────────────────────
  document.querySelectorAll('.qty-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const input = btn.closest('.cart-qty')?.querySelector('input[type="number"]');
      if (!input) return;
      const delta = btn.dataset.action === 'plus' ? 1 : -1;
      input.value = Math.max(1, parseInt(input.value || 1) + delta);
      input.dispatchEvent(new Event('change'));
    });
  });

  // ── Star rating hover ────────────────────────────────────────
  const stars = document.querySelectorAll('.star-rating');
  stars.forEach(group => {
    const labels = [...group.querySelectorAll('label')].reverse();
    labels.forEach((lbl, i) => {
      lbl.addEventListener('mouseover', () => {
        labels.forEach((l, j) => l.style.color = j <= i ? 'var(--gold)' : '');
      });
    });
    group.addEventListener('mouseleave', () => {
      labels.forEach(l => l.style.color = '');
    });
  });

  // ── Chat: scroll to bottom ───────────────────────────────────
  const chatMessages = document.querySelector('.chat-messages');
  if (chatMessages) chatMessages.scrollTop = chatMessages.scrollHeight;

  // ── Confirm delete/reject dialogs ───────────────────────────
  document.querySelectorAll('[data-confirm]').forEach(el => {
    el.addEventListener('click', e => {
      if (!confirm(el.dataset.confirm)) e.preventDefault();
    });
  });

  // ── Form: reset button custom behavior ──────────────────────
  document.querySelectorAll('button[type="reset"]').forEach(btn => {
    btn.addEventListener('click', () => {
      const form = btn.closest('form');
      if (form) {
        form.reset();
        form.querySelectorAll('.form-control').forEach(el => el.blur());
      }
    });
  });

});
