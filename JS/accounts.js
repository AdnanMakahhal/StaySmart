(() => {
  const dialog = document.getElementById('account-history-dialog');
  const frame = document.getElementById('account-history-frame');
  let trigger;
  document.addEventListener('click', event => {
    const link = event.target.closest('.account-history-link');
    if (!link || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
    event.preventDefault();
    trigger = link;
    const url = new URL(link.href);
    url.searchParams.set('popup', '1');
    frame.src = url.href;
    dialog.showModal();
    document.body.classList.add('account-history-open');
  });
  document.getElementById('close-account-history').addEventListener('click', () => dialog.close());
  dialog.addEventListener('close', () => {
    frame.src = 'about:blank';
    document.body.classList.remove('account-history-open');
    trigger?.focus();
  });
  window.addEventListener('message', event => {
    if (event.origin === location.origin && event.source === frame.contentWindow && event.data?.type === 'staysmart-history-close') dialog.close();
  });
})();
