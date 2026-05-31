/* ═══════════════════════════════════════════════════════
   GLOBAL UI UTILITIES
   QAMS - QR Asset Management System
   ═══════════════════════════════════════════════════════ */

function showToast(txt, type = 'success') {
    let container = document.getElementById('toast-container');
    
    // Create container if it doesn't exist
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        document.body.appendChild(container);
    }

    const t = document.createElement('div');
    t.className = `toast toast-${type}`;
    
    const icon = type === 'success' ? 'check-circle' : 'exclamation-circle';
    t.innerHTML = `<i class="bi bi-${icon}-fill"></i> ${txt}`;
    
    container.appendChild(t);
    
    // Animate out and remove
    setTimeout(() => {
        t.classList.add('toast-out');
        setTimeout(() => t.remove(), 300);
    }, 2500);
}
