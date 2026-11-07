(() => {
  const form = document.querySelector('.insertroomform');
  const notice = document.getElementById('room-form-notice');
  if (!form || !notice) return;

  const search = document.getElementById('inventory-search');
  const filter = document.getElementById('inventory-filter');
  const filterInventory = () => {
    const rows = [...document.querySelectorAll('[data-room-row]')];
    let shown = 0;
    let accepting = 0;
    let units = 0;
    rows.forEach((row) => {
      const visible = row.dataset.visible === '1';
      const open = row.dataset.open === '1';
      const quantity = Number(row.dataset.quantity);
      const canBook = visible && open && quantity > 0;
      accepting += Number(canBook);
      units += quantity;
      const matchesStatus = filter.value === 'all'
        || (filter.value === 'accepting' && canBook)
        || (filter.value === 'hidden' && !visible)
        || (filter.value === 'closed' && !open)
        || (filter.value === 'soldout' && quantity === 0);
      row.hidden = !matchesStatus || !row.dataset.roomSearch.toLowerCase().includes(search.value.trim().toLowerCase());
      if (!row.hidden) shown++;
    });
    document.querySelector('[data-inventory-total]').textContent = rows.length;
    document.querySelector('[data-inventory-open]').textContent = accepting;
    document.querySelector('[data-inventory-units]').textContent = units;
    document.getElementById('inventory-results').textContent = `Showing ${shown} of ${rows.length} room types`;
    document.getElementById('inventory-no-results').hidden = shown > 0 || rows.length === 0;
  };
  search.addEventListener('input', filterInventory);
  filter.addEventListener('change', filterInventory);
  document.querySelector('.roomsindex').addEventListener('click', (event) => {
    if (event.target.closest('#inventory-clear')) {
      search.value = '';
      filter.value = 'all';
      filterInventory();
      search.focus();
    }
  });
  document.addEventListener('click', (event) => {
    document.querySelectorAll('.inventory-more[open]').forEach((menu) => {
      if (!menu.contains(event.target)) menu.open = false;
    });
  });
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') document.querySelectorAll('.inventory-more[open]').forEach((menu) => {
      menu.open = false;
      menu.querySelector('summary').focus();
    });
  });
  filterInventory();

  const refreshInventory = async () => {
    const response = await fetch(form.action, { cache: 'no-store', credentials: 'same-origin' });
    if (!response.ok) throw new Error('Inventory unavailable');
    const page = new DOMParser().parseFromString(await response.text(), 'text/html');
    const rows = page.querySelector('.rooms-table tbody');
    if (!rows) throw new Error('Inventory unavailable');
    document.querySelector('.rooms-table tbody').replaceWith(rows);
    filterInventory();
  };
  const dialog = document.getElementById('room-dialog');
  const frame = document.getElementById('room-dialog-frame');
  const loading = document.getElementById('room-dialog-loading');
  let popupTrigger = null;
  let popupSaved = false;
  document.querySelector('.roomsindex').addEventListener('click', (event) => {
    const link = event.target.closest('a[href^="room.php?"]');
    if (!link || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
    event.preventDefault();
    const menu = link.closest('.inventory-more');
    popupTrigger = menu ? menu.querySelector('summary') : link;
    if (menu) menu.open = false;
    popupSaved = false;
    const url = new URL(link.href);
    url.searchParams.set('popup', '1');
    document.getElementById('room-dialog-title').textContent = url.searchParams.get('action') === 'edit' ? 'Edit room' : 'Room details & bookings';
    loading.hidden = false;
    frame.hidden = true;
    frame.src = url.href;
    dialog.showModal();
    document.body.classList.add('room-dialog-open');
  });
  frame.addEventListener('load', () => {
    if (!dialog.open) return;
    loading.hidden = true;
    frame.hidden = false;
  });
  document.getElementById('room-dialog-close').addEventListener('click', () => dialog.close());
  dialog.addEventListener('click', (event) => {
    if (event.target !== dialog) return;
    const bounds = dialog.getBoundingClientRect();
    if (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom) dialog.close();
  });
  dialog.addEventListener('close', () => {
    document.body.classList.remove('room-dialog-open');
    frame.src = 'about:blank';
    if (popupTrigger?.isConnected) popupTrigger.focus();
    else if (popupSaved) search.focus();
  });
  window.addEventListener('message', async (event) => {
    if (event.origin !== window.location.origin || event.source !== frame.contentWindow) return;
    if (event.data?.type === 'staysmart-room-close') { dialog.close(); return; }
    if (event.data?.type !== 'staysmart-room-saved') return;
    popupSaved = true;
    document.getElementById('room-dialog-title').textContent = 'Room details & bookings';
    try { await refreshInventory(); }
    catch { showMessage('Room saved. Reload the page to refresh the inventory list.', true); }
  });

  const button = form.querySelector('[name="adminsubmit"]');
  let saving = false;
  const showMessage = (message, success) => {
    notice.textContent = message;
    notice.className = `room-management-notice ${success ? 'is-success' : 'is-error'}`;
    notice.hidden = false;
  };

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    if (saving || !form.reportValidity()) return;

    const image = form.elements.image.files[0];
    if (image && image.size > 5 * 1024 * 1024) {
      showMessage('Choose an image smaller than 5 MB.', false);
      return;
    }

    const data = new FormData(form);
    data.set('adminsubmit', '1');
    saving = true;
    button.disabled = true;
    form.setAttribute('aria-busy', 'true');
    const originalButton = button.innerHTML;
    button.textContent = 'Saving room…';
    notice.hidden = true;

    try {
      const response = await fetch(form.action, {
        method: 'POST',
        body: data,
        credentials: 'same-origin',
        headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
      });
      if (!response.headers.get('content-type')?.includes('application/json')) {
        throw new Error('The server returned an unexpected response. Check your inventory before trying again.');
      }
      const result = await response.json();
      if (!response.ok || !result.success) {
        throw new Error(result.message || 'The room could not be saved.');
      }

      form.reset();
      showMessage(result.message, true);
      // Refresh the existing server-rendered rows, including their action forms.
      try {
        await refreshInventory();
        document.querySelectorAll('.roomsindex > .room-management-notice').forEach((item) => item.remove());
      } catch {
        showMessage('Room saved. Reload the page to refresh the inventory list.', true);
      }
    } catch (error) {
      showMessage(error instanceof TypeError
        ? 'The connection was interrupted. Check your inventory before submitting again.'
        : error.message, false);
    } finally {
      saving = false;
      button.disabled = false;
      button.innerHTML = originalButton;
      form.removeAttribute('aria-busy');
    }
  });
})();
