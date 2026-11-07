(() => {
  const form = document.getElementById('edit-booking-dates');
  if (!form) return;
  const start = document.getElementById('edit-check-in');
  const end = document.getElementById('edit-check-out');
  const update = () => {
    const from = Date.parse(start.value + 'T00:00:00Z');
    const to = Date.parse(end.value + 'T00:00:00Z');
    if (Number.isFinite(from)) end.min = new Date(from + 86400000).toISOString().slice(0, 10);
    end.setCustomValidity(start.value && end.value && to <= from ? 'Check-out must be after check-in.' : '');
    const nights = (to - from) / 86400000;
    document.getElementById('edit-booking-total').textContent = nights > 0 ? new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(nights * Number(form.dataset.rate)) : 'Choose valid dates';
  };
  start.addEventListener('input', update);
  end.addEventListener('input', update);
  update();
})();
