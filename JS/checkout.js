(() => {
  const form = document.getElementById('confirm-booking-form');
  if (form) {
    let submitting = false;
    form.addEventListener('submit', event => {
      if (submitting) { event.preventDefault(); return; }
      submitting = true;
      form.querySelector('button[type="submit"]').setAttribute('aria-disabled','true');
      document.getElementById('confirm-booking-status').textContent = 'Reserving your room…';
    });
    window.addEventListener('pageshow', () => {
      submitting = false;
      form.querySelector('button[type="submit"]').removeAttribute('aria-disabled');
      document.getElementById('confirm-booking-status').textContent = '';
    });
  }
  const success = document.getElementById('booking-success-dialog');
  if (success) {
    document.getElementById('close-booking-success').addEventListener('click',()=>success.close());
    document.getElementById('continue-after-booking').addEventListener('click',()=>success.close());
    success.addEventListener('close',()=>document.body.classList.remove('booking-success-open'));
    success.showModal();
    document.body.classList.add('booking-success-open');
  }
})();
