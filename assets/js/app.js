/**
 * Secure Login System — Client-side helpers
 * No external dependencies.
 */

/* ── Password visibility toggle ─────────────────────────────────────────── */
document.querySelectorAll('.toggle-pw').forEach(btn => {
  btn.addEventListener('click', () => {
    const input = btn.previousElementSibling;
    if (!input) return;
    const isHidden = input.type === 'password';
    input.type = isHidden ? 'text' : 'password';
    btn.textContent = isHidden ? '🙈' : '👁';
  });
});

/* ── Password strength meter ─────────────────────────────────────────────── */
const pwInput      = document.getElementById('password');
const strengthFill = document.getElementById('strengthFill');
const strengthLabel = document.getElementById('strengthLabel');

if (pwInput && strengthFill) {
  pwInput.addEventListener('input', () => {
    const score = scorePassword(pwInput.value);
    const pct   = (score / 5) * 100;

    strengthFill.style.width = pct + '%';

    const levels = [
      { max: 20,  color: '#ef4444', label: 'Very weak'  },
      { max: 40,  color: '#f97316', label: 'Weak'       },
      { max: 60,  color: '#f59e0b', label: 'Fair'       },
      { max: 80,  color: '#22c55e', label: 'Strong'     },
      { max: 101, color: '#6c63ff', label: 'Very strong'},
    ];

    const level = levels.find(l => pct <= l.max) || levels[levels.length - 1];
    strengthFill.style.background = level.color;
    if (strengthLabel) {
      strengthLabel.textContent = pwInput.value.length ? level.label : '';
    }
  });
}

function scorePassword(pw) {
  let score = 0;
  if (pw.length >= 8)  score++;
  if (pw.length >= 12) score++;
  if (/[A-Z]/.test(pw)) score++;
  if (/[0-9]/.test(pw)) score++;
  if (/[\W_]/.test(pw)) score++;
  return score;
}

/* ── Live password-match indicator ──────────────────────────────────────── */
const confirmInput = document.getElementById('password_confirm');

if (pwInput && confirmInput) {
  function checkMatch() {
    if (!confirmInput.value) {
      confirmInput.style.borderColor = '';
      return;
    }
    const match = pwInput.value === confirmInput.value;
    confirmInput.style.borderColor = match ? '#22c55e' : '#ef4444';
  }
  confirmInput.addEventListener('input', checkMatch);
  pwInput.addEventListener('input', checkMatch);
}

/* ── Prevent double-submit ───────────────────────────────────────────────── */
document.querySelectorAll('form').forEach(form => {
  form.addEventListener('submit', () => {
    const btn = form.querySelector('button[type="submit"]');
    if (btn) {
      btn.disabled = true;
      btn.textContent = 'Please wait…';
    }
  });
});
