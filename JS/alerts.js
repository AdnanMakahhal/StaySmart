(() => {
  document.querySelectorAll('.stay-toast-dismiss').forEach(button => button.addEventListener('click', () => button.closest('.stay-toast').remove()));
  const dialog = document.getElementById('stay-confirm-dialog');
  if (!dialog) return;
  let pending = null;
  const approved = new WeakSet();
  document.addEventListener('submit', event => {
    const form = event.target;
    if (!form.matches('form[data-confirm]') || approved.has(form)) return;
    event.preventDefault();
    pending = { form, submitter: event.submitter, focus: document.activeElement };
    document.getElementById('stay-confirm-message').textContent = form.dataset.confirm;
    dialog.showModal();
  });
  document.getElementById('stay-confirm-back').addEventListener('click', () => dialog.close());
  document.getElementById('stay-confirm-yes').addEventListener('click', () => {
    const current = pending;
    if (!current) return;
    approved.add(current.form);
    dialog.close();
    current.form.requestSubmit(current.submitter || undefined);
    approved.delete(current.form);
  });
  dialog.addEventListener('close', () => { pending?.focus?.focus(); pending = null; });
})();
