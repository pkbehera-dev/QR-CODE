/* ═══════════════════════════════════════════════════════
   DASHBOARD UTILITIES & STATE
   White Workspace + Dark Sidebar Color System
═══════════════════════════════════════════════════════ */

let State = {
  products: [],
  customFields: [],
  serials: [],
  userPrefix: '',
  loaded: false
};

const escHtml = (s) => {
    if (!s) return '';
    const div = document.createElement('div');
    div.textContent = s;
    return div.innerHTML;
};

async function api(endpoint, method='GET', body=null) {
  const cleanEndpoint = endpoint.replace(/\.php(\?|$)/, '$1');
  const opts = { method, headers: {} };
  if (body) { 
      opts.headers['Content-Type'] = 'application/json'; 
      opts.body = JSON.stringify(body); 
  }
  try {
    const r = await fetch(BASE_URL + '/' + cleanEndpoint, opts);
    return await r.json();
  } catch(e) { return {success:false, message:'Network error.'}; }
}

/* ═══════════════════════════════════════════════════════
   MODAL CONTROLS
═══════════════════════════════════════════════════════ */
function openModal(id) { document.getElementById(id).classList.add('active'); }
function closeModal(id) { document.getElementById(id).classList.remove('active'); }

function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    sidebar.classList.toggle('active');
}

/* ═══════════════════════════════════════════════════════
   OVERVIEW & TAB RENDERING
═══════════════════════════════════════════════════════ */

const TAB_LOADERS = {
  overview: renderOverview,
  allserials: renderAllSerials,
  products: renderProductTable,
  create:   renderCreateProducts,
  print:    renderPrintGrid,
  billing:  loadBillingHistory,
  settings: () => {}
};

function renderOverview() {
  document.getElementById('stat-products').textContent = State.products.length;
  document.getElementById('stat-serials').textContent  = State.serials.length;

  const tbody = document.getElementById('recent-body');
  if (!State.serials.length) {
    tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding:3rem; color:var(--text-secondary)">No serials generated yet.</td></tr>'; return;
  }
  
  tbody.innerHTML = State.serials.slice(0, 10).map(s => {
    const cf = s.custom_fields || {};
    const userText = cf.user || '—';
    return `<tr>
      <td><span style="font-family:monospace; font-weight:700; color:var(--accent)">${escHtml(s.serial_number)}</span></td>
      <td>${escHtml(userText)}</td>
      <td><span class="btn btn-ghost btn-sm" style="pointer-events:none">${escHtml(s.product_name)}</span></td>
      <td style="color:var(--text-secondary)">${escHtml(s.created_at ? s.created_at.slice(0,10) : '—')}</td>
      <td>
         <div style="display:flex; gap:0.5rem">
           <button class="action-icon" onclick="openViewSerialModal(${s.id})" title="View Overview"><i class="bi bi-eye"></i></button>
           <button class="action-icon" onclick="openEditSerialModal(${s.id})" title="Edit Details"><i class="bi bi-pencil-square"></i></button>
         </div>
      </td>
    </tr>`;
  }).join('');
}

function renderAllSerials() {
  const tbody = document.getElementById('all-serials-tbody');
  const search = document.getElementById('all-search').value.toLowerCase();
  const prodFilter = document.getElementById('all-filter-prod').value;

  const filterSel = document.getElementById('all-filter-prod');
  const currentVal = filterSel.value;
  filterSel.innerHTML = '<option value="">All Products</option>' +
    State.products.map(p => `<option value="${p.id}">${escHtml(p.name)}</option>`).join('');
  filterSel.value = currentVal;

  let list = State.serials;
  if (prodFilter) list = list.filter(s => String(s.product_id) === prodFilter);
  if (search) {
    list = list.filter(s => {
      const sn = s.serial_number.toLowerCase();
      const prod = s.product_name.toLowerCase();
      const fields = s.custom_fields ? JSON.stringify(s.custom_fields).toLowerCase() : '';
      return sn.includes(search) || prod.includes(search) || fields.includes(search);
    });
  }

  if (!list.length) {
    tbody.innerHTML = `<tr><td colspan="6" style="text-align:center; padding:4rem; color:var(--text-secondary)">${State.serials.length ? 'No matching serials found.' : 'No serials generated yet.'}</td></tr>`; return;
  }

  tbody.innerHTML = list.map(s => {
    const cf = s.custom_fields || {};
    const userText = cf.user ? `<strong>${cf.user}</strong><br><small style="color:var(--text-secondary)">${cf.department || 'No Dept'}</small>` : '—';
    const itemsCount = cf.items ? cf.items.length : 0;
    return `<tr>
      <td><span style="font-family:monospace; font-weight:800; color:var(--accent)">${escHtml(s.serial_number)}</span></td>
      <td>${userText}</td>
      <td><span class="btn btn-ghost btn-sm" style="pointer-events:none">${escHtml(s.product_name)}</span></td>
      <td><span style="font-size:0.75rem; color:var(--text-secondary)">${itemsCount} Component(s)</span></td>
      <td style="color:var(--text-secondary)">${escHtml(s.created_at ? s.created_at.slice(0,10) : '—')}</td>
      <td>
        <div style="display:flex; gap:0.5rem">
          <button class="action-icon" onclick="openViewSerialModal(${s.id})" title="View Overview"><i class="bi bi-eye"></i></button>
          <button class="action-icon" onclick="openEditSerialModal(${s.id})" title="Edit Details"><i class="bi bi-pencil"></i></button>
          <button class="action-icon" style="color:#ef4444" onclick="deleteSerial(${s.id})" title="Delete"><i class="bi bi-trash"></i></button>
        </div>
      </td>
    </tr>`;
  }).join('');
}

/* ═══════════════════════════════════════════════════════
   SERIAL EDITING & HARDWARE
═══════════════════════════════════════════════════════ */

function openViewSerialModal(id) {
  const s = State.serials.find(x => x.id == id);
  if (!s) return;
  
  document.getElementById('view-serial-sn').textContent = s.serial_number;
  document.getElementById('view-serial-prod').textContent = s.product_name || s.short_name || 'Asset';
  
  const cf = s.custom_fields || {};
  document.getElementById('view-user-name').textContent = cf.user || 'Unassigned Custodian';
  
  const parts = [];
  if (cf.designation) parts.push(cf.designation);
  if (cf.department) parts.push(cf.department);
  document.getElementById('view-user-meta').textContent = parts.length ? parts.join(' • ') : 'No Dept / Role recorded';
  
  const listCont = document.getElementById('view-components-list');
  listCont.innerHTML = '';
  
  if (cf.items && Array.isArray(cf.items) && cf.items.length) {
    cf.items.forEach(item => {
      const div = document.createElement('div');
      div.style = 'padding:10px 14px; background:#f8fafc; border:1px solid var(--border-color); border-radius:10px; text-align:left; display:flex; justify-content:space-between; align-items:center';
      
      let leftHtml = `<div style="font-weight:700; font-size:0.9rem; color:var(--text-primary)">${escHtml(item.name || 'Component')}</div>`;
      if (item.details) leftHtml += `<div style="font-size:0.8rem; color:var(--text-secondary); margin-top:2px">${escHtml(item.details)}</div>`;
      
      let rightHtml = '';
      if (item.sn) rightHtml = `<div style="font-family:monospace; font-size:0.8rem; font-weight:700; background:#ffffff; border:1px solid var(--border-color); padding:2px 8px; border-radius:6px; color:var(--accent)">S/N: ${escHtml(item.sn)}</div>`;
      
      div.innerHTML = `<div>${leftHtml}</div><div>${rightHtml}</div>`;
      listCont.appendChild(div);
    });
  } else {
    listCont.innerHTML = '<div style="text-align:center; padding:1rem; color:var(--text-secondary); font-size:0.85rem">No custom hardware components documented.</div>';
  }
  
  document.getElementById('view-created-at').textContent = s.created_at ? 'Generated on ' + s.created_at : '';
  openModal('view-serial-modal');
}

function openEditSerialModal(id) {
  const s = State.serials.find(x => x.id == id);
  if (!s) return;
  
  document.getElementById('edit-serial-id').value = s.id;
  document.getElementById('edit-serial-sn').textContent = s.serial_number;
  
  const cf = s.custom_fields || {};
  document.getElementById('edit-user').value = cf.user || '';
  document.getElementById('edit-desig').value = cf.designation || '';
  document.getElementById('edit-dept').value = cf.department || '';
  
  const container = document.getElementById('edit-hardware-items-container');
  container.innerHTML = '';
  
  if (cf.items && Array.isArray(cf.items)) {
    cf.items.forEach(item => addEditHardwareItem(item));
  }
  
  openModal('edit-serial-modal');
}

function addHardwareItem(containerId = 'hardware-items-container', data = {}) {
  const container = document.getElementById(containerId);
  const div = document.createElement('div');
  div.className = 'hardware-item-row';
  div.style = 'background:#f8fafc; border:1px solid var(--border-color); border-radius:12px; padding:1.25rem; margin-bottom:1rem; position:relative;';
  
  const name = data.name || '';
  const details = data.details || '';
  const sn = data.sn || '';

  div.innerHTML = `
    <button type="button" class="action-icon" style="position:absolute; top:8px; right:8px; color:#ef4444" onclick="this.parentElement.remove()"><i class="bi bi-x-circle"></i></button>
    <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:10px">
      <div class="form-group" style="margin:0">
        <label style="font-size:0.7rem">Component Name</label>
        <input type="text" class="h-name" placeholder="CPU, Monitor..." value="${escHtml(name)}" required>
      </div>
      <div class="form-group" style="margin:0">
        <label style="font-size:0.7rem">Specifications</label>
        <input type="text" class="h-details" placeholder="i7, 16GB..." value="${escHtml(details)}">
      </div>
      <div class="form-group" style="margin:0">
        <label style="font-size:0.7rem">Device S/N</label>
        <input type="text" class="h-sn" placeholder="Manufacturer S/N" value="${escHtml(sn)}">
      </div>
    </div>
  `;
  container.appendChild(div);
}

function addEditHardwareItem(data = {}) {
  addHardwareItem('edit-hardware-items-container', data);
}

async function saveSerialEdit() {
  const id = document.getElementById('edit-serial-id').value;
  const user = document.getElementById('edit-user').value;
  const designation = document.getElementById('edit-desig').value;
  const department = document.getElementById('edit-dept').value;
  
  const itemRows = document.querySelectorAll('#edit-hardware-items-container .hardware-item-row');
  const items = [];
  itemRows.forEach(row => {
      const name = row.querySelector('.h-name').value;
      const details = row.querySelector('.h-details').value;
      const sn = row.querySelector('.h-sn').value;
      if (name) items.push({name, details, sn});
  });

  const custom_fields = { user, designation, department, items };
  const btn = document.getElementById('save-edit-btn');
  btn.disabled = true;
  const d = await api('api/serials', 'PUT', {id, custom_fields});
  btn.disabled = false;
  
  if (d.success) {
      const s = State.serials.find(x => x.id == id);
      if (s) s.custom_fields = custom_fields;
      closeModal('edit-serial-modal');
      const activeTab = document.querySelector('.tab-btn.active').dataset.tab;
      if (TAB_LOADERS[activeTab]) TAB_LOADERS[activeTab]();
      showToast('Serial updated successfully.');
  } else {
      showToast(d.message, 'error');
  }
}

async function deleteSerial(id) {
  if (!confirm('Permanently delete this serial number?')) return;
  const d = await api('api/serials', 'DELETE', {id});
  if (d.success) {
    State.serials = State.serials.filter(s => s.id !== id);
    const activeTab = document.querySelector('.tab-btn.active').dataset.tab;
    if (TAB_LOADERS[activeTab]) TAB_LOADERS[activeTab]();
    showToast('Serial deleted successfully.');
  } else showToast(d.message, 'error');
}

/* ═══════════════════════════════════════════════════════
   PRODUCTS
═══════════════════════════════════════════════════════ */
function renderProductTable() {
  const tbody = document.getElementById('product-tbody');
  if (!State.products.length) {
    tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding:3rem; color:var(--text-secondary)">No products defined yet.</td></tr>'; return;
  }
  tbody.innerHTML = State.products.map(p => `
    <tr>
      <td style="font-weight:700">${escHtml(p.name)}</td>
      <td><span class="btn btn-ghost btn-sm" style="pointer-events:none">${escHtml(p.short_name)}</span></td>
      <td><span style="font-weight:800; color:var(--accent)">${p.serial_count}</span></td>
      <td style="color:var(--text-secondary)">${escHtml(p.created_at ? p.created_at.slice(0,10) : '—')}</td>
      <td>
        <div style="display:flex; gap:0.5rem">
          <button class="action-icon" onclick="editProduct(${p.id})"><i class="bi bi-pencil-square"></i></button>
          <button class="action-icon" style="color:#ef4444" onclick="deleteProduct(${p.id})"><i class="bi bi-trash"></i></button>
        </div>
      </td>
    </tr>`).join('');
}

function editProduct(id) {
  const p = State.products.find(x => x.id == id);
  if (!p) return;
  document.getElementById('prod-id').value = p.id;
  document.getElementById('prod-name').value = p.name;
  document.getElementById('prod-short').value = p.short_name;
  document.getElementById('prod-btn').textContent = 'Update Product';
  document.getElementById('prod-cancel-btn').style.display = 'inline-block';
  switchTab('products');
  const form = document.getElementById('product-form');
  if (form) form.scrollIntoView({ behavior: 'smooth' });
}

function renderCreateProducts() {
    const sel = document.getElementById('create-product');
    if (!sel) return;
    sel.innerHTML = '<option value="">Choose a product...</option>' + 
        State.products.map(p => `<option value="${p.id}">${escHtml(p.name)}</option>`).join('');
}

async function handleProductSubmit(e) {
  e.preventDefault();
  const id = document.getElementById('prod-id').value;
  const name = document.getElementById('prod-name').value;
  const short_name = document.getElementById('prod-short').value;
  
  const btn = document.getElementById('prod-btn');
  const method = id ? 'PUT' : 'POST';
  
  btn.disabled = true;
  const d = await api('api/products', method, { id, name, short_name });
  btn.disabled = false;
  
  if (d.success) {
    if (id) {
      const idx = State.products.findIndex(x => x.id == id);
      if (idx !== -1) {
        State.products[idx].name = name;
        State.products[idx].short_name = short_name;
      }
    } else {
      State.products.unshift(d.product);
    }
    cancelProductEdit();
    renderProductTable();
    showToast(id ? 'Product updated.' : 'Product created.');
  } else showToast(d.message, 'error');
}

function cancelProductEdit() {
  document.getElementById('prod-id').value = '';
  document.getElementById('prod-name').value = '';
  document.getElementById('prod-short').value = '';
  document.getElementById('prod-btn').textContent = 'Create Product';
  document.getElementById('prod-cancel-btn').style.display = 'none';
}

async function deleteProduct(id) {
    if (!confirm('Delete this product and all associated serials?')) return;
    const d = await api('api/products', 'DELETE', {id});
    if (d.success) {
        State.products = State.products.filter(p => p.id != id);
        renderProductTable();
        showToast('Product deleted.');
    } else showToast(d.message, 'error');
}

/* ═══════════════════════════════════════════════════════
   PRINT & QR
═══════════════════════════════════════════════════════ */
function renderPrintGrid() {
  const fsel = document.getElementById('filter-product');
  if (!fsel) return;
  const currentFilter = fsel.value;
  fsel.innerHTML = '<option value="">All Products</option>' +
    State.products.map(p => `<option value="${p.id}">${escHtml(p.name)}</option>`).join('');
  if (currentFilter) fsel.value = currentFilter;
  fsel.onchange = renderPrintGrid;

  const pid   = fsel.value;
  const list  = pid ? State.serials.filter(s => String(s.product_id) === pid) : State.serials;
  const grid  = document.getElementById('print-grid');
  if (!list.length) { grid.innerHTML = '<p style="text-align:center; padding:4rem; color:var(--text-secondary)">No serials to print.</p>'; return; }
  
  grid.innerHTML = '';
  list.forEach(s => {
    const card = document.createElement('div');
    card.className = 'qr-preview-card';
    card.innerHTML = `
      <div class="no-print qr-card-actions">
        <input type="checkbox" class="qr-select" data-id="${s.id}" checked title="Include in print">
        <button class="action-icon" onclick="downloadQR(${s.id}, '${s.serial_number}')" title="Download QR">
          <i class="bi bi-download"></i>
        </button>
      </div>
      <div class="qr-card-body">
        <div class="qr-serial">${escHtml(s.serial_number)}</div>
        <div id="qr-${s.id}" class="qr-img-wrap"></div>
      </div>
    `;
    grid.appendChild(card);
    new QRCode(document.getElementById('qr-' + s.id), {
      text: SCAN_BASE + '/scan?s=' + s.id,
      width: 130, height: 130, correctLevel: QRCode.CorrectLevel.H
    });
  });
}

function downloadQR(id, serial) {
    const container = document.getElementById('qr-' + id);
    const canvas = container.querySelector('canvas');
    if (!canvas) return;
    const link = document.createElement('a');
    link.download = `QR-${serial}.png`;
    link.href = canvas.toDataURL('image/png');
    link.click();
}

function toggleAllQR(checked) {
    document.querySelectorAll('.qr-select').forEach(cb => cb.checked = checked);
}

/* ═══════════════════════════════════════════════════════
   FILTERS & GLOBAL SEARCH
═══════════════════════════════════════════════════════ */
function resetAllFilters() {
    document.getElementById('all-search').value = '';
    document.getElementById('all-filter-prod').value = '';
    renderAllSerials();
}

function handleGlobalSearch(input) {
    const search = input.value.toLowerCase();
    if (search.length > 2) {
        document.querySelector('.tab-btn[data-tab="allserials"]').click();
        document.getElementById('all-search').value = search;
        renderAllSerials();
    }
}

/* ═══════════════════════════════════════════════════════
   INITIALIZATION
═══════════════════════════════════════════════════════ */
async function initialLoad() {
  const [pd, sd, fd, setd] = await Promise.all([
    api('api/products'),
    api('api/serials'),
    api('api/custom_fields'),
    api('api/settings')
  ]);
  State.products = pd.products || [];
  State.serials = sd.serials || [];
  State.customFields = fd.custom_fields || [];
  State.userPrefix = setd.user?.serial_prefix || '';
  State.loaded = true;
  
  const activeTab = document.querySelector('.tab-btn.active').dataset.tab;
  if (TAB_LOADERS[activeTab]) TAB_LOADERS[activeTab]();
}

document.addEventListener('DOMContentLoaded', () => {
    initialLoad();
    
    // Tab switching
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            
            btn.classList.add('active');
            const tab = btn.dataset.tab;
            document.getElementById('tab-' + tab).classList.add('active');
            
            // Render if loaders exist (don't wait for State.loaded)
            if (TAB_LOADERS[tab]) TAB_LOADERS[tab]();
            
            // Auto-hide sidebar on mobile after clicking
            const sidebar = document.querySelector('.sidebar');
            if (window.innerWidth < 1024) sidebar.classList.remove('active');
            
            window.scrollTo(0, 0);
        });
    });

    const refreshPrintBtn = document.getElementById('refresh-print-btn');
    if (refreshPrintBtn) refreshPrintBtn.addEventListener('click', renderPrintGrid);

    // Product form
    const prodForm = document.getElementById('product-form');
    if (prodForm) prodForm.addEventListener('submit', handleProductSubmit);
    
    const cancelProdBtn = document.getElementById('prod-cancel-btn');
    if (cancelProdBtn) cancelProdBtn.addEventListener('click', cancelProductEdit);

    // Generate Serial Logic
    const genBtn = document.getElementById('generate-btn');
    const createProdSel = document.getElementById('create-product');
    
    if (createProdSel) {
        createProdSel.addEventListener('change', async () => {
            const pid = createProdSel.value;
            const previewWrap = document.getElementById('serial-preview-wrap');
            if (!pid) {
                genBtn.disabled = true;
                previewWrap.style.display = 'none';
                return;
            }
            
            genBtn.disabled = false;
            previewWrap.style.display = 'block';
            document.getElementById('serial-preview').textContent = 'Loading...';
            
            // Preview next sequential serial
            const d = await api(`api/serials?preview_next=1&product_id=${pid}`);
            if (d.success) {
                document.getElementById('serial-preview').textContent = d.next_serial;
            }
        });
    }

    if (genBtn) {
        genBtn.addEventListener('click', async () => {
            const pid = createProdSel.value;
            const user = document.getElementById('create-user').value;
            const designation = document.getElementById('create-desig').value;
            const department = document.getElementById('create-dept').value;
            
            if (!user) { showToast('User name is required.', 'error'); return; }
            
            const itemRows = document.querySelectorAll('#hardware-items-container .hardware-item-row');
            const items = [];
            itemRows.forEach(row => {
                const name = row.querySelector('.h-name').value;
                const details = row.querySelector('.h-details').value;
                const sn = row.querySelector('.h-sn').value;
                if (name) items.push({name, details, sn});
            });

            const custom_fields = { user, designation, department, items };
            
            genBtn.disabled = true;
            genBtn.textContent = 'Generating...';
            
            const d = await api('api/serials', 'POST', { product_id: pid, custom_fields });
            
            genBtn.disabled = false;
            genBtn.textContent = 'Generate & Save Asset';
            
            if (d.success) {
                State.serials.unshift(d.serial);
                showToast('Asset generated successfully!');
                
                // Show result
                document.getElementById('generate-result').style.display = 'block';
                document.getElementById('result-serial').textContent = d.serial.serial_number;
                const qrRes = document.getElementById('result-qr');
                qrRes.innerHTML = '';
                new QRCode(qrRes, {
                    text: SCAN_BASE + '/scan?s=' + d.serial.id,
                    width: 160, height: 160
                });
                
                // Clear form
                document.getElementById('create-user').value = '';
                document.getElementById('create-desig').value = '';
                document.getElementById('create-dept').value = '';
                document.getElementById('hardware-items-container').innerHTML = '';
                
                // Trigger preview refresh for next one
                createProdSel.dispatchEvent(new Event('change'));
            } else {
                showToast(d.message, 'error');
            }
        });
    }

    // Settings Forms
    const prefixForm = document.getElementById('prefix-form');
    if (prefixForm) {
        prefixForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const prefix = document.getElementById('prefix-input').value;
            const d = await api('api/settings', 'POST', { action: 'update_prefix', serial_prefix: prefix });
            if (d.success) { State.userPrefix = prefix; showToast(d.message); }
            else showToast(d.message, 'error');
        });
    }

    const headingForm = document.getElementById('heading-form');
    if (headingForm) {
        headingForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const heading = document.getElementById('heading-input').value;
            const subheading = document.getElementById('subheading-input').value;
            const d = await api('api/settings', 'POST', { action: 'update_heading', company_heading: heading, company_subheading: subheading });
            if (d.success) showToast(d.message);
            else showToast(d.message, 'error');
        });
    }

    const emailForm = document.getElementById('email-form');
    if (emailForm) {
        emailForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const email = document.getElementById('email-input').value;
            const d = await api('api/settings', 'POST', { action: 'update_email', email });
            if (d.success) showToast(d.message);
            else showToast(d.message, 'error');
        });
    }

    const pwForm = document.getElementById('pw-form');
    if (pwForm) {
        pwForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const cur = document.getElementById('cur-pw').value;
            const n1 = document.getElementById('new-pw').value;
            const n2 = document.getElementById('con-pw').value;
            const d = await api('api/settings', 'POST', { 
                action: 'update_password', 
                current_password: cur, 
                new_password: n1, 
                confirm_password: n2 
            });
            if (d.success) { pwForm.reset(); showToast(d.message); }
            else showToast(d.message, 'error');
        });
    }

    const logoUploadForm = document.getElementById('logo-upload-form');
    if (logoUploadForm) {
        logoUploadForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const fileInput = document.getElementById('logo-file-input');
            const urlInput = document.getElementById('logo-url-input');
            
            const btn = logoUploadForm.querySelector('button[type="submit"]');
            btn.disabled = true;
            btn.textContent = 'Updating...';

            try {
                let d;
                if (fileInput.files[0]) {
                    // Handle File Upload
                    const fd = new FormData();
                    fd.append('logo_file', fileInput.files[0]);
                    const res = await fetch(BASE_URL + '/api/settings', { method: 'POST', body: fd });
                    d = await res.json();
                } else if (urlInput.value.trim()) {
                    // Handle URL Update
                    d = await api('api/settings', 'POST', { action: 'update_logo', logo_url: urlInput.value.trim() });
                } else {
                    showToast('Please select a file or enter a URL.', 'error');
                    btn.disabled = false;
                    btn.textContent = 'Update Logo';
                    return;
                }

                btn.disabled = false;
                btn.textContent = 'Update Logo';

                if (d.success) {
                    showToast(d.message);
                    const finalUrl = d.logo_url || urlInput.value.trim();
                    const fullUrl = (finalUrl.indexOf('http') === 0) ? finalUrl : BASE_URL + '/' + finalUrl;
                    
                    // Update UI
                    document.getElementById('logo-preview-wrap').innerHTML = `<img src="${fullUrl}" style="width:100%; height:100%; object-fit:cover">`;
                    document.getElementById('sidebar-user-avatar').innerHTML = `<img src="${fullUrl}" alt="Logo" style="width:100%; height:100%; object-fit:cover; border-radius:10px">`;
                    
                    fileInput.value = '';
                } else showToast(d.message, 'error');
            } catch (e) {
                btn.disabled = false;
                btn.textContent = 'Update Logo';
                showToast('Update failed.', 'error');
            }
        });
    }
});

async function loadBillingHistory() {
  const tbody = document.getElementById('billing-tbody');
  if (!tbody) return;
  tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding:2rem; color:var(--text-secondary)">Loading history...</td></tr>';
  
  try {
    const res = await fetch(BASE_URL + '/api/subscriptions');
    const data = await res.json();
    if (data.success && data.subscriptions && data.subscriptions.length) {
      tbody.innerHTML = '';
      data.subscriptions.forEach(s => {
        const date = s.created_at ? s.created_at.slice(0, 10) : '—';
        const statusBadge = {
          pending: '<span style="color:#f59e0b; font-weight:700">⏳ Pending</span>',
          active: '<span style="color:#10b981; font-weight:700">✔ Active</span>',
          expired: '<span style="color:#ef4444; font-weight:700">✘ Expired</span>',
          rejected: '<span style="color:#ef4444; font-weight:700">✘ Rejected</span>'
        }[s.status] || s.status;

        tbody.innerHTML += `<tr>
          <td>${date}</td>
          <td><b>${s.asset_limit} Assets</b></td>
          <td style="text-transform:uppercase">${s.billing_cycle}</td>
          <td>₹${parseFloat(s.amount).toFixed(0)}</td>
          <td><code style="font-size:0.8rem">${s.transaction_id || '—'}</code></td>
          <td>${statusBadge}</td>
        </tr>`;
      });
    } else {
      tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding:2rem; color:var(--text-secondary)">No billing history found.</td></tr>';
    }
  } catch (e) {
    tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding:2rem; color:#ef4444">Error loading history.</td></tr>';
  }
}
