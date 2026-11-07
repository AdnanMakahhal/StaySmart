(() => {
  const form = document.getElementById('dashboard-report-filter');
  if (!form) return;
  const start = document.getElementById('report-start');
  const end = document.getElementById('report-end');
  const dates = document.getElementById('report-date-row');
  const sync = () => {
    const custom = form.elements.period.value === 'custom';
    dates.hidden = !custom;
    start.disabled = end.disabled = !custom;
    start.required = end.required = custom;
    end.min = start.value || '1000-01-01';
    end.setCustomValidity(custom && start.value && end.value && end.value < start.value
      ? 'End date must be on or after the start date.' : '');
  };
  form.addEventListener('change', sync);
  start.addEventListener('input', sync);
  end.addEventListener('input', sync);
  sync();
})();
