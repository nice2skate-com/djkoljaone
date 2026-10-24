/* ================================================================
   ARTIFICIAL SENTIMENTS — App Logic
================================================================ */

'use strict';

// ── Page Router ──────────────────────────────────────────────────
function showPage(id) {
  // Hide all pages
  document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));

  // Show target
  const target = document.getElementById('page-' + id);
  if (!target) return;
  target.classList.add('active');

  // Update nav link states
  document.querySelectorAll('.nav-links a[data-page]').forEach(a => {
    a.classList.toggle('active', a.dataset.page === id);
  });

  // Scroll to top
  window.scrollTo({ top: 0, behavior: 'instant' });

  // Trigger reveal on new page
  requestAnimationFrame(() => observeReveals());
}

// ── Nav Scroll Effect ─────────────────────────────────────────────
const nav = document.getElementById('nav');

window.addEventListener('scroll', () => {
  nav.classList.toggle('scrolled', window.scrollY > 40);
}, { passive: true });

// ── Mobile Menu ───────────────────────────────────────────────────
function toggleMenu() {
  const hamburger = document.getElementById('hamburger');
  const mobileMenu = document.getElementById('mobileMenu');
  hamburger.classList.toggle('open');
  mobileMenu.classList.toggle('open');
  document.body.style.overflow = mobileMenu.classList.contains('open') ? 'hidden' : '';
}

// ── Scroll Reveal ─────────────────────────────────────────────────
let revealObserver;

function observeReveals() {
  const items = document.querySelectorAll('.reveal:not(.observed)');

  if (!revealObserver) {
    revealObserver = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('visible');
          revealObserver.unobserve(entry.target);
        }
      });
    }, { threshold: 0.12, rootMargin: '0px 0px -32px 0px' });
  }

  items.forEach(el => {
    el.classList.add('observed');
    revealObserver.observe(el);
  });

  // Also immediately reveal items already in viewport
  document.querySelectorAll('.reveal.observed').forEach(el => {
    const rect = el.getBoundingClientRect();
    if (rect.top < window.innerHeight - 32) {
      el.classList.add('visible');
    }
  });
}

// ── FAQ Accordion ─────────────────────────────────────────────────
function toggleFaq(el) {
  const item = el.closest('.faq-item');
  const answer = item.querySelector('.faq-a');
  const isOpen = item.classList.contains('open');

  // Close all
  document.querySelectorAll('.faq-item.open').forEach(openItem => {
    openItem.classList.remove('open');
    openItem.querySelector('.faq-a').style.maxHeight = '0';
  });

  // Open clicked (if it was closed)
  if (!isOpen) {
    item.classList.add('open');
    answer.style.maxHeight = answer.scrollHeight + 'px';
  }
}

// ── Equipment Filter ──────────────────────────────────────────────
function filterEq(btn, cat) {
  document.querySelectorAll('.eq-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');

  document.querySelectorAll('.eq-item').forEach(item => {
    const itemCat = item.dataset.cat;
    const show = cat === 'all' || itemCat === cat;
    item.style.display = show ? '' : 'none';
  });
}

// ── Contact Form — Formspree ──────────────────────────────────────
async function handleSubmit(e) {
  e.preventDefault();
  const form = e.target;
  const success = document.getElementById('form-success');
  const btn = form.querySelector('.form-submit-btn');

  btn.textContent = 'Wird gesendet…';
  btn.disabled = true;

  try {
    const res = await fetch(form.action, {
      method: 'POST',
      body: new FormData(form),
      headers: { 'Accept': 'application/json' }
    });

    if (res.ok) {
      form.reset();
      success.style.display = 'block';
      setTimeout(() => { success.style.display = 'none'; }, 7000);
    } else {
      const data = await res.json();
      const msg = data.errors ? data.errors.map(e => e.message).join(', ') : 'Fehler beim Senden. Bitte versuche es erneut.';
      alert(msg);
    }
  } catch {
    alert('Netzwerkfehler. Bitte versuche es erneut.');
  } finally {
    btn.textContent = 'Anfrage absenden';
    btn.disabled = false;
  }
}

// ── Service Cards — hover gold line via JS for gap fix ────────────
// (handled purely in CSS — no JS needed)

// ── Init ──────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  observeReveals();

  // Re-observe when scrolling
  window.addEventListener('scroll', observeReveals, { passive: true });
});
