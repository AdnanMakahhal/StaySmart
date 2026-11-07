(() => {
  const form = document.getElementById('stay-booking-form');
  if (!form) return;
  const checkIn = document.getElementById('check-in');
  const checkOut = document.getElementById('check-out');
  const guests = document.getElementById('stay-guests');
  const money = new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' });
  const update = () => {
    const start = checkIn.value ? Date.parse(checkIn.value + 'T00:00:00Z') : NaN;
    const end = checkOut.value ? Date.parse(checkOut.value + 'T00:00:00Z') : NaN;
    checkOut.min = Number.isFinite(start) ? new Date(start + 86400000).toISOString().slice(0, 10) : checkIn.min;
    const nights = (end - start) / 86400000;
    const valid = Number.isFinite(nights) && nights > 0 && checkIn.value >= checkIn.min;
    checkIn.setCustomValidity(checkIn.value && checkIn.value < checkIn.min ? 'Check-in cannot be in the past. Choose today or a future date.' : '');
    checkOut.setCustomValidity(checkIn.value && checkOut.value && end <= start ? 'Check-out must be after check-in.' : '');
    document.getElementById('stay-nights').textContent = valid ? `${nights} ${nights === 1 ? 'night' : 'nights'}` : 'Choose dates';
    document.getElementById('stay-total').textContent = valid ? money.format(nights * Number(form.dataset.rate)) : '—';
    document.getElementById('booking-submit-total').textContent = valid ? money.format(nights * Number(form.dataset.rate)) : 'Choose your dates';
    document.getElementById('stay-total-help').textContent = valid ? `${money.format(Number(form.dataset.rate))} × ${nights} ${nights === 1 ? 'night' : 'nights'} · USD` : 'Select valid dates to calculate your room total.';
    document.getElementById('stay-summary-guests').textContent = guests.value;
  };
  [checkIn, checkOut, guests].forEach(input => input.addEventListener('input', update));
  let submitting = false;
  form.addEventListener('submit', event => {
    update();
    if (submitting || !form.reportValidity()) { event.preventDefault(); return; }
    submitting = true;
    // Retain the named submit button in the POST while preventing duplicate clicks.
    document.getElementById('submit-booking').setAttribute('aria-disabled', 'true');
    document.getElementById('booking-submit-status').textContent = 'Preparing your checkout…';
  });
  window.addEventListener('pageshow', () => {
    submitting = false;
    document.getElementById('submit-booking').removeAttribute('aria-disabled');
    document.getElementById('booking-submit-status').textContent = '';
  });
  update();
})();
