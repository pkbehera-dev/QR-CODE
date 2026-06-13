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

function getStatusBadge(status) {
    status = status || 'In Stock';
    let bg = 'rgba(0,0,0,0.05)', color = 'var(--text-primary)';
    if (status === 'Active') { bg = '#dcfce7'; color = '#15803d'; }
    else if (status === 'Breakdown') { bg = '#fee2e2'; color = '#b91c1c'; }
    else if (status === 'Under Repair') { bg = '#fef3c7'; color = '#d97706'; }
    else if (status === 'Retired') { bg = '#f1f5f9'; color = '#475569'; }
    else if (status === 'In Stock') { bg = '#dbeafe'; color = '#1d4ed8'; }
    return `<span class="badge" style="background:${bg}; color:${color}; font-weight:800; font-size:0.75rem; text-transform:uppercase">${escHtml(status)}</span>`;
}
window.getStatusBadge = getStatusBadge;

function updateCreateCustodianVisibility() {
  const pId = document.getElementById('create-parent')?.value || '';
  const status = document.getElementById('create-status')?.value || 'In Stock';
  const createCustodianSec = document.getElementById('create-custodian-section');
  if (createCustodianSec) {
      createCustodianSec.style.display = (!pId && status === 'Active') ? 'block' : 'none';
  }
}
window.updateCreateCustodianVisibility = updateCreateCustodianVisibility;

function updateEditCustodianVisibility() {
  const pId = document.getElementById('edit-parent')?.value || '';
  const status = document.getElementById('edit-status')?.value || 'In Stock';
  const editCustodianSec = document.getElementById('edit-custodian-section');
  if (editCustodianSec) {
      editCustodianSec.style.display = (!pId && status === 'Active') ? 'block' : 'none';
  }
}
window.updateEditCustodianVisibility = updateEditCustodianVisibility;

function updateEditMaintenanceNoteVisibility() {
  const status = document.getElementById('edit-status')?.value || 'In Stock';
  const section = document.getElementById('edit-maintenance-note-section');
  const label = document.getElementById('edit-maintenance-note-label');
  const textarea = document.getElementById('edit-maintenance-note');
  if (section && label && textarea) {
      if (status === 'In Stock' || status === 'Active') {
          section.style.display = 'none';
      } else {
          section.style.display = 'block';
          if (status === 'Breakdown') {
              label.textContent = 'Breakdown Reason (Optional)';
              textarea.placeholder = 'Describe why it broke down (e.g. power surge, physical damage)...';
          } else if (status === 'Under Repair') {
              label.textContent = 'What Repaired / Repair Details (Optional)';
              textarea.placeholder = 'Describe what is being repaired or what was replaced...';
          } else if (status === 'Retired') {
              label.textContent = 'Why Retired (Optional)';
              textarea.placeholder = 'Describe why this asset is being retired...';
          } else {
              label.textContent = 'Maintenance / Repair Note (Optional)';
              textarea.placeholder = 'Describe what was repaired or updated...';
          }
      }
  }
}
window.updateEditMaintenanceNoteVisibility = updateEditMaintenanceNoteVisibility;

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
   ICON PREVIEW UTILITY
═══════════════════════════════════════════════════════ */
function updateIconPreview(val) {
  const preview = document.getElementById('icon-preview-i');
  if (preview) {
    const iconClass = (val || '').trim();
    preview.className = 'bi ' + (iconClass ? iconClass : 'bi-box-seam');
  }
}
window.updateIconPreview = updateIconPreview;

/* ═══════════════════════════════════════════════════════
   MODAL CONTROLS
═══════════════════════════════════════════════════════ */
function openModal(id) { document.getElementById(id).classList.add('active'); }
function closeModal(id) { document.getElementById(id).classList.remove('active'); }


function getProductIcon(productId) {
  const p = State.products.find(x => String(x.id) === String(productId));
  return (p && p.icon) ? p.icon : 'bi-box-seam';
}
window.getProductIcon = getProductIcon;

function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    sidebar.classList.toggle('active');
}

/* ═══════════════════════════════════════════════════════
   OVERVIEW & TAB RENDERING
═══════════════════════════════════════════════════════ */

const TAB_LOADERS = {
  overview: renderOverview,
  assetlist: renderAssetList,
  products: renderProductTable,
  create:   renderCreateProducts,
  print:    renderPrintGrid,
  maintenance: renderMaintenanceLogs,
  settings: () => {}
};

function renderOverview() {
  document.getElementById('stat-products').textContent = State.products.length;
  document.getElementById('stat-serials').textContent  = State.serials.length;

  const tbody = document.getElementById('recent-body');
  if (!State.serials.length) {
    tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding:3rem; color:var(--text-secondary)">No assets generated yet.</td></tr>'; return;
  }
  tbody.innerHTML = State.serials.slice(0, 10).map(s => {
    const cf = s.custom_fields || {};
    const userText = cf.user || '—';
    const pIcon = s.product_icon || getProductIcon(s.product_id);
    return `<tr>
      <td><span style="font-family:monospace; font-weight:700; color:var(--accent)">${escHtml(s.serial_number)}</span></td>
      <td>${escHtml(userText)}</td>
      <td><span class="btn btn-ghost btn-sm" style="pointer-events:none; display:inline-flex; align-items:center; gap:6px"><i class="bi ${pIcon}" style="font-size:1rem; color:var(--accent)"></i> ${escHtml(s.product_name)}</span></td>
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



function renderAssetList() {
  const tbody = document.getElementById('assetlist-tbody');
  const search = document.getElementById('assetlist-search').value.toLowerCase();
  const prodFilter = document.getElementById('assetlist-filter-prod').value;
  
  const filterSel = document.getElementById('assetlist-filter-prod');
  const currentVal = filterSel.value;
  filterSel.innerHTML = '<option value="">All Asset Types</option>' +
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
    tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding:4rem; color:var(--text-secondary)">No assets found.</td></tr>';
    return;
  }
  
  tbody.innerHTML = list.map(s => {
    const cf = s.custom_fields || {};
    const userText = cf.user ? `<strong>${cf.user}</strong>` : '<span style="color:var(--text-secondary); background:rgba(0,0,0,0.05); padding:2px 8px; border-radius:6px; font-size:0.75rem;">In Stock (Unassigned)</span>';
    const parentText = s.parent_serial_number ? `<span style="font-family:monospace; font-weight:700; color:var(--accent)">${escHtml(s.parent_serial_number)}</span>` : '—';
    const pIcon = s.product_icon || getProductIcon(s.product_id);
    return `<tr>
      <td><span style="font-family:monospace; font-weight:800; color:var(--accent)">${escHtml(s.serial_number)}</span></td>
      <td><span class="btn btn-ghost btn-sm" style="pointer-events:none; display:inline-flex; align-items:center; gap:6px"><i class="bi ${pIcon}" style="font-size:1rem; color:var(--accent)"></i> ${escHtml(s.product_name)}</span></td>
      <td>${parentText}</td>
      <td>${userText}</td>
      <td>${getStatusBadge(s.status)}</td>
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
window.renderAssetList = renderAssetList;

function resetAssetListFilters() {
    document.getElementById('assetlist-search').value = '';
    document.getElementById('assetlist-filter-prod').value = '';
    renderAssetList();
}
window.resetAssetListFilters = resetAssetListFilters;

async function renderMaintenanceLogs() {
  const tbody = document.getElementById('maintenance-tbody');
  if (!tbody) return;
  
  const search = document.getElementById('maintenance-search').value;
  const status = document.getElementById('maintenance-filter-status').value;
  
  const d = await api(`api/maintenance?search=${encodeURIComponent(search)}&status=${encodeURIComponent(status)}`);
  
  if (d.success && d.logs && d.logs.length > 0) {
      tbody.innerHTML = d.logs.map(log => {
          return `<tr>
              <td><span style="font-family:monospace; font-weight:800; color:var(--accent)">${escHtml(log.serial_number)}</span></td>
              <td><span class="btn btn-ghost btn-sm" style="pointer-events:none">${escHtml(log.product_name)}</span></td>
              <td><strong>${escHtml(log.custodian)}</strong></td>
              <td>${getStatusBadge(log.status)}</td>
              <td style="max-width:300px; word-break:break-word">${escHtml(log.note)}</td>
              <td style="color:var(--text-secondary)">${escHtml(log.created_at ? log.created_at.slice(0,16) : '—')}</td>
              <td style="text-align:right">
                <div style="display:flex; gap:0.5rem; justify-content:flex-end">
                  <button class="action-icon" onclick="openEditLogModal(${log.id}, ${JSON.stringify(log.note).replace(/"/g, '&quot;')}, '${log.status}')" title="Edit Log"><i class="bi bi-pencil-square"></i></button>
                  <button class="action-icon" style="color:#ef4444" onclick="deleteLog(${log.id})" title="Delete Log"><i class="bi bi-trash"></i></button>
                </div>
              </td>
          </tr>`;
      }).join('');
  } else {
      tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding:4rem; color:var(--text-secondary)">No maintenance logs found.</td></tr>';
  }
}
window.renderMaintenanceLogs = renderMaintenanceLogs;

function resetMaintenanceFilters() {
  document.getElementById('maintenance-search').value = '';
  document.getElementById('maintenance-filter-status').value = '';
  renderMaintenanceLogs();
}
window.resetMaintenanceFilters = resetMaintenanceFilters;

/* ═══════════════════════════════════════════════════════
   SERIAL EDITING & HARDWARE
═══════════════════════════════════════════════════════ */

function populateParentDropdown(selectId, excludeId = null) {
  const sel = document.getElementById(selectId);
  if (!sel) return;
  const currentVal = sel.value;
  
  let optionsHtml = '<option value="">None (Independent Asset)</option>';
  State.serials.forEach(s => {
      if (excludeId && String(s.id) === String(excludeId)) return;
      optionsHtml += `<option value="${s.id}">${escHtml(s.serial_number)} (${escHtml(s.product_name)})</option>`;
  });
  sel.innerHTML = optionsHtml;
  sel.value = currentVal;
}

function openViewSerialModal(id) {
  const s = State.serials.find(x => x.id == id);
  if (!s) return;
  
  document.getElementById('view-serial-sn').textContent = s.serial_number;
  document.getElementById('view-serial-prod').textContent = s.product_name || s.short_name || 'Asset';
  document.getElementById('view-serial-status').innerHTML = getStatusBadge(s.status);
  
  // Parent Asset Link
  const parentWrap = document.getElementById('view-parent-wrap');
  if (s.parent_id) {
      parentWrap.style.display = 'block';
      document.getElementById('view-parent-sn').textContent = s.parent_serial_number || 'S/N ' + s.parent_id;
      
      const parentAsset = State.serials.find(x => x.id == s.parent_id);
      if (parentAsset) {
          const parentUser = parentAsset.custom_fields?.user || 'Unassigned';
          document.getElementById('view-parent-details').textContent = `${parentAsset.product_name} • Assigned to ${parentUser}`;
      } else {
          document.getElementById('view-parent-details').textContent = '';
      }
      
      document.getElementById('view-parent-btn').onclick = () => {
          closeModal('view-serial-modal');
          openViewSerialModal(s.parent_id);
      };
  } else {
      parentWrap.style.display = 'none';
  }

  const cf = s.custom_fields || {};
  document.getElementById('view-user-name').textContent = cf.user || 'Unassigned Custodian';
  
  const parts = [];
  if (cf.designation) parts.push(cf.designation);
  if (cf.department) parts.push(cf.department);
  document.getElementById('view-user-meta').textContent = parts.length ? parts.join(' • ') : 'No Dept / Role recorded';
  
  document.getElementById('view-name').textContent = cf.name || '—';
  document.getElementById('view-description').textContent = cf.description || 'No description recorded.';
  
  const snWrap = document.getElementById('view-serial-no-wrap');
  if (cf.serial_no) {
      document.getElementById('view-serial-no').textContent = cf.serial_no;
      snWrap.style.display = 'inline-block';
  } else {
      snWrap.style.display = 'none';
  }

  // Linked child assets (peripherals)
  const childrenWrap = document.getElementById('view-children-wrap');
  const childrenList = document.getElementById('view-children-list');
  childrenList.innerHTML = '';
  
  const children = State.serials.filter(x => x.parent_id == s.id);
  if (children.length > 0) {
      childrenWrap.style.display = 'block';
      children.forEach(child => {
          const div = document.createElement('div');
          div.style = 'padding:10px 14px; background:#f8fafc; border:1px solid var(--border-color); border-radius:10px; text-align:left; display:flex; justify-content:space-between; align-items:center; margin-bottom: 6px;';
          div.innerHTML = `
              <div style="flex:1; min-width:0;">
                  <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-bottom:4px;">
                      <strong style="font-size:0.9rem; color:var(--text-primary)">${escHtml(child.product_name)}</strong>
                      <span style="font-family:monospace; font-size:0.75rem; font-weight:700; color:var(--accent)">${escHtml(child.serial_number)}</span>
                      ${getStatusBadge(child.status)}
                  </div>
                  <div style="font-weight:600; font-size:0.85rem; color:var(--text-primary); margin-bottom:2px;">${escHtml(child.custom_fields?.name || '—')}</div>
                  <div style="font-size:0.8rem; color:var(--text-secondary); line-height:1.4">${escHtml(child.custom_fields?.description || '')}</div>
              </div>
              <button class="btn btn-ghost btn-sm" style="flex-shrink:0; margin-left:12px;" onclick="closeModal('view-serial-modal'); openViewSerialModal(${child.id})">View Asset</button>
          `;
          childrenList.appendChild(div);
      });
  } else {
      childrenWrap.style.display = 'none';
  }

  // Maintenance History Logs
  const histWrap = document.getElementById('view-maintenance-wrap');
  const histList = document.getElementById('view-maintenance-history');
  histList.innerHTML = '<div style="text-align:center; padding:1rem; color:var(--text-secondary); font-size:0.85rem">Loading logs...</div>';
  histWrap.style.display = 'block';
  
  api(`api/maintenance?serial_id=${s.id}`).then(d => {
      if (d.success && d.logs && d.logs.length > 0) {
          histList.innerHTML = d.logs.map(log => {
              return `<div style="padding:10px 14px; background:#ffffff; border:1px solid var(--border-color); border-radius:10px; text-align:left; margin-bottom: 6px;">
                  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                      ${getStatusBadge(log.status)}
                      <div style="display:flex; align-items:center; gap:8px;">
                          <span style="font-size:0.75rem; color:var(--text-secondary)">${escHtml(log.created_at ? log.created_at.slice(0,16) : '')}</span>
                          <button class="action-icon" style="padding:2px; font-size:0.85rem;" onclick="closeModal('view-serial-modal'); openEditLogModal(${log.id}, ${JSON.stringify(log.note).replace(/"/g, '&quot;')}, '${log.status}')" title="Edit Log"><i class="bi bi-pencil-square"></i></button>
                          <button class="action-icon" style="color:#ef4444; padding:2px; font-size:0.85rem;" onclick="closeModal('view-serial-modal'); deleteLog(${log.id})" title="Delete Log"><i class="bi bi-trash"></i></button>
                      </div>
                  </div>
                  <div style="font-size:0.85rem; color:var(--text-primary); line-height:1.4">${escHtml(log.note)}</div>
              </div>`;
          }).join('');
      } else {
          histList.innerHTML = '<div style="text-align:center; padding:1.25rem; color:var(--text-secondary); font-size:0.85rem">No maintenance history recorded yet.</div>';
      }
  });
  
  document.getElementById('view-created-at').textContent = s.created_at ? 'Generated on ' + s.created_at : '';
  openModal('view-serial-modal');
}
window.openViewSerialModal = openViewSerialModal;

function openEditSerialModal(id) {
  const s = State.serials.find(x => x.id == id);
  if (!s) return;
  
  document.getElementById('edit-serial-id').value = s.id;
  document.getElementById('edit-serial-sn').textContent = s.serial_number;
  
  populateParentDropdown('edit-parent', s.id);
  document.getElementById('edit-parent').value = s.parent_id || '';
  
  const cf = s.custom_fields || {};
  document.getElementById('edit-name').value = cf.name || '';
  document.getElementById('edit-serial-no').value = cf.serial_no || '';
  document.getElementById('edit-description').value = cf.description || '';
  document.getElementById('edit-user').value = cf.user || '';
  document.getElementById('edit-desig').value = cf.designation || '';
  document.getElementById('edit-dept').value = cf.department || '';
  
  document.getElementById('edit-status').value = s.status || 'In Stock';
  document.getElementById('edit-maintenance-note').value = '';
  
  updateEditCustodianVisibility();
  updateEditMaintenanceNoteVisibility();
  
  openModal('edit-serial-modal');
}
window.openEditSerialModal = openEditSerialModal;

async function saveSerialEdit() {
  const id = document.getElementById('edit-serial-id').value;
  const parent_id = document.getElementById('edit-parent').value;
  const name = document.getElementById('edit-name').value;
  const serial_no = document.getElementById('edit-serial-no').value;
  const description = document.getElementById('edit-description').value;
  const user = document.getElementById('edit-user').value;
  const designation = document.getElementById('edit-desig').value;
  const department = document.getElementById('edit-dept').value;
  
  const status = document.getElementById('edit-status').value;
  const maintenance_note = document.getElementById('edit-maintenance-note').value;

  const custom_fields = { name, description, serial_no, user, designation, department };
  const btn = document.getElementById('save-edit-btn');
  btn.disabled = true;
  const d = await api('api/serials', 'POST', {id, parent_id, custom_fields, status, maintenance_note});
  btn.disabled = false;
  
  if (d.success) {
      const sd = await api('api/serials');
      if (sd.success) {
          State.serials = sd.serials || [];
      }
      closeModal('edit-serial-modal');
      const activeTab = document.querySelector('.tab-btn.active').dataset.tab;
      if (TAB_LOADERS[activeTab]) TAB_LOADERS[activeTab]();
      showToast('Asset updated successfully.');
  } else {
      showToast(d.message, 'error');
  }
}
window.saveSerialEdit = saveSerialEdit;

async function deleteSerial(id) {
  if (!confirm('Permanently delete this asset?')) return;
  const d = await api('api/serials', 'DELETE', {id});
  if (d.success) {
    State.serials = State.serials.filter(s => s.id !== id);
    const activeTab = document.querySelector('.tab-btn.active').dataset.tab;
    if (TAB_LOADERS[activeTab]) TAB_LOADERS[activeTab]();
    showToast('Asset deleted successfully.');
  } else showToast(d.message, 'error');
}
window.deleteSerial = deleteSerial;

/* ═══════════════════════════════════════════════════════
   PRODUCTS
═══════════════════════════════════════════════════════ */
function renderProductTable() {
  const tbody = document.getElementById('product-tbody');
  if (!State.products.length) {
    tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding:3rem; color:var(--text-secondary)">No asset types defined yet.</td></tr>'; return;
  }
  tbody.innerHTML = State.products.map(p => {
    const icon = p.icon || 'bi-box-seam';
    return `
    <tr>
      <td style="font-weight:700">
        <span style="display:inline-flex; align-items:center; gap:8px">
          <span style="width:32px; height:32px; border-radius:8px; background:rgba(0, 207, 232, 0.1); display:inline-flex; align-items:center; justify-content:center; flex-shrink:0">
            <i class="bi ${icon}" style="font-size:1rem; color:var(--accent)"></i>
          </span>
          ${escHtml(p.name)}
        </span>
      </td>
      <td><span class="btn btn-ghost btn-sm" style="pointer-events:none">${escHtml(p.short_name)}</span></td>
      <td><span style="font-weight:800; color:var(--accent)">${p.serial_count}</span></td>
      <td style="color:var(--text-secondary)">${escHtml(p.created_at ? p.created_at.slice(0,10) : '—')}</td>
      <td>
        <div style="display:flex; gap:0.5rem">
          <button class="action-icon" onclick="editProduct(${p.id})"><i class="bi bi-pencil-square"></i></button>
          <button class="action-icon" style="color:#ef4444" onclick="deleteProduct(${p.id})"><i class="bi bi-trash"></i></button>
        </div>
      </td>
    </tr>`;
  }).join('');
}

function openAddProductModal() {
  document.getElementById('prod-id').value = '';
  document.getElementById('prod-name').value = '';
  document.getElementById('prod-short').value = '';
  document.getElementById('prod-icon').value = 'bi-box-seam';
  updateIconPreview('bi-box-seam');
  document.getElementById('product-modal-title').textContent = 'Create Asset Type';
  document.getElementById('prod-btn').textContent = 'Create Asset Type';
  openModal('product-modal');
}
window.openAddProductModal = openAddProductModal;

function editProduct(id) {
  console.log("editProduct called with ID:", id);
  const p = State.products.find(x => String(x.id) === String(id));
  if (!p) {
      console.warn("Asset Type not found for ID:", id);
      return;
  }
  const icon = p.icon || 'bi-box-seam';
  document.getElementById('prod-id').value = p.id;
  document.getElementById('prod-name').value = p.name;
  document.getElementById('prod-short').value = p.short_name;
  document.getElementById('prod-icon').value = icon;
  updateIconPreview(icon);
  document.getElementById('product-modal-title').textContent = 'Edit Asset Type';
  document.getElementById('prod-btn').textContent = 'Update Asset Type';
  openModal('product-modal');
}
window.editProduct = editProduct;

function setupAutocomplete(inputId, searchInputId, suggestionsId, dataCallback, onSelectCallback) {
    const input = document.getElementById(inputId);
    const searchInput = document.getElementById(searchInputId);
    const suggestionsWrap = document.getElementById(suggestionsId);
    if (!input || !searchInput || !suggestionsWrap) return;

    let highlightedIndex = -1;
    let filteredData = [];

    function renderSuggestions() {
        const query = searchInput.value.toLowerCase().trim();
        filteredData = dataCallback(query);

        if (filteredData.length === 0) {
            suggestionsWrap.innerHTML = '<div style="padding:10px; color:var(--text-secondary); font-size:0.85rem; text-align:center;">No matches found</div>';
        } else {
            suggestionsWrap.innerHTML = filteredData.map((item, idx) => {
                return `<div class="autocomplete-suggestion-item${idx === highlightedIndex ? ' highlighted' : ''}" data-id="${item.id}" data-index="${idx}">
                    ${item.icon ? `<i class="bi ${item.icon}" style="color:var(--accent)"></i>` : ''}
                    <span>${escHtml(item.label)}</span>
                </div>`;
            }).join('');
        }
        suggestionsWrap.style.display = 'block';
    }

    searchInput.addEventListener('focus', () => {
        highlightedIndex = -1;
        renderSuggestions();
    });

    searchInput.addEventListener('input', () => {
        highlightedIndex = -1;
        renderSuggestions();
    });

    searchInput.addEventListener('keydown', (e) => {
        const items = suggestionsWrap.querySelectorAll('.autocomplete-suggestion-item');
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            highlightedIndex = (highlightedIndex + 1) % filteredData.length;
            renderSuggestions();
            scrollIntoView();
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            highlightedIndex = (highlightedIndex - 1 + filteredData.length) % filteredData.length;
            renderSuggestions();
            scrollIntoView();
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (highlightedIndex >= 0 && highlightedIndex < filteredData.length) {
                selectItem(filteredData[highlightedIndex]);
            }
        } else if (e.key === 'Escape') {
            closeDropdown();
        }
    });

    function scrollIntoView() {
        const activeItem = suggestionsWrap.querySelector('.autocomplete-suggestion-item.highlighted');
        if (activeItem) {
            activeItem.scrollIntoView({ block: 'nearest' });
        }
    }

    suggestionsWrap.addEventListener('click', (e) => {
        const itemEl = e.target.closest('.autocomplete-suggestion-item');
        if (itemEl) {
            const idx = parseInt(itemEl.dataset.index, 10);
            selectItem(filteredData[idx]);
        }
    });

    function selectItem(item) {
        input.value = item.id;
        searchInput.value = item.label;
        closeDropdown();
        
        input.dispatchEvent(new Event('change'));
        if (onSelectCallback) onSelectCallback(item);
    }

    function closeDropdown() {
        suggestionsWrap.style.display = 'none';
        highlightedIndex = -1;
    }

    document.addEventListener('click', (e) => {
        if (!searchInput.contains(e.target) && !suggestionsWrap.contains(e.target)) {
            closeDropdown();
            if (input.value === '') {
                searchInput.value = '';
            } else {
                const allData = dataCallback('');
                const selected = allData.find(x => String(x.id) === String(input.value));
                if (selected) {
                    searchInput.value = selected.label;
                } else {
                    searchInput.value = '';
                }
            }
        }
    });
}
window.setupAutocomplete = setupAutocomplete;

function renderCreateProducts() {
    setupAutocomplete(
        'create-product', 
        'create-product-search', 
        'create-product-suggestions',
        (query) => {
            return State.products
                .filter(p => p.name.toLowerCase().includes(query) || p.short_name.toLowerCase().includes(query))
                .map(p => ({ id: p.id, label: `${p.name} (${p.short_name})`, icon: p.icon || 'bi-box-seam' }));
        }
    );

    setupAutocomplete(
        'create-parent', 
        'create-parent-search', 
        'create-parent-suggestions',
        (query) => {
            return State.serials
                .filter(s => s.serial_number.toLowerCase().includes(query) || (s.custom_fields?.name || '').toLowerCase().includes(query))
                .map(s => ({ id: s.id, label: `${s.serial_number} - ${s.custom_fields?.name || s.product_name}` }));
        }
    );
}

async function handleProductSubmit(e) {
  e.preventDefault();
  const id = document.getElementById('prod-id').value;
  const name = document.getElementById('prod-name').value;
  const short_name = document.getElementById('prod-short').value;
  const icon = document.getElementById('prod-icon').value || 'bi-box-seam';
  
  const btn = document.getElementById('prod-btn');
  const method = 'POST';
  
  btn.disabled = true;
  const d = await api('api/products', method, { id, name, short_name, icon });
  btn.disabled = false;
  
  if (d.success) {
    if (id) {
      const idx = State.products.findIndex(x => x.id == id);
      if (idx !== -1) {
        State.products[idx].name = name;
        State.products[idx].short_name = short_name;
        State.products[idx].icon = icon;
      }
    } else {
      State.products.unshift(d.product);
    }
    closeModal('product-modal');
    renderProductTable();
    showToast(id ? 'Asset type updated.' : 'Asset type created.');
  } else showToast(d.message, 'error');
}

async function deleteProduct(id) {
    if (!confirm('Delete this asset type and all associated assets?')) return;
    const d = await api('api/products', 'DELETE', {id});
    if (d.success) {
        State.products = State.products.filter(p => p.id != id);
        renderProductTable();
        showToast('Asset type deleted.');
    } else showToast(d.message, 'error');
}
window.deleteProduct = deleteProduct;

/* ═══════════════════════════════════════════════════════
   PRINT & QR
═══════════════════════════════════════════════════════ */
function renderPrintGrid() {
  const fsel = document.getElementById('filter-product');
  if (!fsel) return;
  const currentFilter = fsel.value;
  fsel.innerHTML = '<option value="">All Asset Types</option>' +
    State.products.map(p => `<option value="${p.id}">${escHtml(p.name)}</option>`).join('');
  if (currentFilter) fsel.value = currentFilter;
  fsel.onchange = renderPrintGrid;

  const pid   = fsel.value;
  let list  = pid ? State.serials.filter(s => String(s.product_id) === pid) : State.serials;
  
  // Only print assets that are parent/independent (parent_id is null)
  list = list.filter(s => s.parent_id === null);

  const grid  = document.getElementById('print-grid');
  if (!list.length) { grid.innerHTML = '<p style="text-align:center; padding:4rem; color:var(--text-secondary)">No assets to print.</p>'; return; }
  
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
        document.querySelector('.tab-btn[data-tab="assetlist"]').click();
        document.getElementById('assetlist-search').value = search;
        renderAssetList();
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
            
            const parentId = document.getElementById('create-parent').value;
            const d = await api(`api/serials?preview_next=1&product_id=${pid}&parent_id=${parentId}`);
            if (d.success) {
                document.getElementById('serial-preview').textContent = d.next_serial;
            }
        });
    }

    const createParentSel = document.getElementById('create-parent');
    if (createParentSel) {
        createParentSel.addEventListener('change', () => {
            const pId = createParentSel.value;
            updateCreateCustodianVisibility();
            if (pId) {
                const parent = State.serials.find(x => String(x.id) === String(pId));
                if (parent) {
                    const cf = parent.custom_fields || {};
                    document.getElementById('create-user').value = cf.user || '';
                    document.getElementById('create-desig').value = cf.designation || '';
                    document.getElementById('create-dept').value = cf.department || '';
                }
            } else {
                document.getElementById('create-user').value = '';
                document.getElementById('create-desig').value = '';
                document.getElementById('create-dept').value = '';
            }
            if (createProdSel && createProdSel.value) {
                createProdSel.dispatchEvent(new Event('change'));
            }
        });
    }

    const createStatusSel = document.getElementById('create-status');
    if (createStatusSel) {
        createStatusSel.addEventListener('change', () => {
            updateCreateCustodianVisibility();
        });
    }

    // Call initially to hide custodian fields for default status 'In Stock'
    updateCreateCustodianVisibility();

    const editParentSel = document.getElementById('edit-parent');
    if (editParentSel) {
        editParentSel.addEventListener('change', () => {
            const pId = editParentSel.value;
            updateEditCustodianVisibility();
            if (pId) {
                const parent = State.serials.find(x => String(x.id) === String(pId));
                if (parent) {
                    const cf = parent.custom_fields || {};
                    document.getElementById('edit-user').value = cf.user || '';
                    document.getElementById('edit-desig').value = cf.designation || '';
                    document.getElementById('edit-dept').value = cf.department || '';
                }
            }
        });
    }

    const editStatusSel = document.getElementById('edit-status');
    if (editStatusSel) {
        editStatusSel.addEventListener('change', () => {
            updateEditCustodianVisibility();
            updateEditMaintenanceNoteVisibility();
        });
    }

    if (genBtn) {
        genBtn.addEventListener('click', async () => {
            const pid = createProdSel.value;
            const parent_id = document.getElementById('create-parent').value;
            const name = document.getElementById('create-name').value;
            const serial_no = document.getElementById('create-serial-no').value;
            const description = document.getElementById('create-description').value;
            const user = parent_id ? '' : document.getElementById('create-user').value;
            const designation = parent_id ? '' : document.getElementById('create-desig').value;
            const department = parent_id ? '' : document.getElementById('create-dept').value;
            
            const custom_fields = { name, description, serial_no, user, designation, department };
            const status = document.getElementById('create-status').value;

            genBtn.disabled = true;
            genBtn.textContent = 'Generating...';
            
            const d = await api('api/serials', 'POST', { product_id: pid, parent_id, custom_fields, status });
            
            genBtn.disabled = false;
            genBtn.textContent = 'Generate & Save Asset';
            
            if (d.success) {
                const sd = await api('api/serials');
                if (sd.success) {
                    State.serials = sd.serials || [];
                } else {
                    State.serials.unshift(d.serial);
                }
                showToast('Asset generated successfully!');
                

                
                // Clear form
                document.getElementById('create-name').value = '';
                document.getElementById('create-serial-no').value = '';
                document.getElementById('create-description').value = '';
                document.getElementById('create-user').value = '';
                document.getElementById('create-desig').value = '';
                document.getElementById('create-dept').value = '';
                document.getElementById('create-parent').value = '';
                document.getElementById('create-parent-search').value = '';
                document.getElementById('create-product').value = '';
                document.getElementById('create-product-search').value = '';
                document.getElementById('create-status').value = 'In Stock';
                updateCreateCustodianVisibility();
                
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

    const editLogForm = document.getElementById('edit-log-form');
    if (editLogForm) {
        editLogForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            await saveLogEdit();
        });
    }
});

function openEditLogModal(logId, currentNote, currentStatus) {
  document.getElementById('edit-log-id').value = logId;
  document.getElementById('edit-log-status').value = currentStatus || 'In Stock';
  document.getElementById('edit-log-note').value = currentNote || '';
  openModal('edit-log-modal');
}
window.openEditLogModal = openEditLogModal;

async function saveLogEdit() {
  const id = document.getElementById('edit-log-id').value;
  const status = document.getElementById('edit-log-status').value;
  const note = document.getElementById('edit-log-note').value;
  
  const btn = document.getElementById('edit-log-btn');
  btn.disabled = true;
  btn.textContent = 'Saving...';
  
  const d = await api('api/maintenance', 'PUT', { id, status, note });
  btn.disabled = false;
  btn.textContent = 'Save Changes';
  
  if (d.success) {
      closeModal('edit-log-modal');
      showToast('Maintenance log updated.');
      
      const activeTab = document.querySelector('.tab-btn.active').dataset.tab;
      if (activeTab === 'maintenance') {
          renderMaintenanceLogs();
      }
  } else {
      showToast(d.message, 'error');
  }
}
window.saveLogEdit = saveLogEdit;

async function deleteLog(id) {
  if (!confirm('Permanently delete this maintenance log?')) return;
  const d = await api('api/maintenance', 'DELETE', { id });
  if (d.success) {
      showToast('Maintenance log deleted.');
      const activeTab = document.querySelector('.tab-btn.active').dataset.tab;
      if (activeTab === 'maintenance') {
          renderMaintenanceLogs();
      }
  } else {
      showToast(d.message, 'error');
  }
}
window.deleteLog = deleteLog;

