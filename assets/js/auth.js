const Auth = {
    // ─── Login Logic ───
    login: async (formId, btnId, msgId, baseUrl, grecaptcha) => {
        const form = document.getElementById(formId);
        const btn = document.getElementById(btnId);
        const msg = document.getElementById(msgId);
        msg.innerHTML = '';
        
        const fd = new FormData(form);
        const token = (typeof grecaptcha !== 'undefined') ? grecaptcha.getResponse() : '';
        if (token) fd.append('g-recaptcha-response', token);
        
        try {
            const res = await fetch(`${baseUrl}/api/auth?action=login`, { method: 'POST', body: fd });
            const data = await res.json();
            if (data.success) {
                if (data.requires_2fa) {
                    window.location.href = `${baseUrl}/2fa`;
                } else {
                    window.location.href = data.role === 'admin' ? `${baseUrl}/admin/` : `${baseUrl}/dashboard`;
                }
            } else {
                if (data.requires_verification) {
                    msg.innerHTML = `<div class="alert alert-error">${data.message} Redirecting...</div>`;
                    setTimeout(() => window.location.href = `${baseUrl}/verify-email`, 1500);
                    return;
                }
                msg.innerHTML = `<div class="alert alert-error">${data.message}</div>`;
                btn.disabled = false; btn.textContent = 'Sign In';
                if (typeof grecaptcha !== 'undefined') grecaptcha.reset();
            }
        } catch (err) {
            msg.innerHTML = `<div class="alert alert-error">Connection failed.</div>`;
            btn.disabled = false; btn.textContent = 'Sign In';
            if (typeof grecaptcha !== 'undefined') grecaptcha.reset();
        }
    },

    // ─── Signup Logic ───
    signup: async (formId, btnId, msgId, baseUrl, grecaptcha) => {
        const form = document.getElementById(formId);
        const btn = document.getElementById(btnId);
        const msg = document.getElementById(msgId);
        
        const fd = new FormData(form);
        const token = (typeof grecaptcha !== 'undefined') ? grecaptcha.getResponse() : '';
        if (token) fd.append('g-recaptcha-response', token);
        
        try {
            const res = await fetch(`${baseUrl}/api/auth?action=signup`, { method: 'POST', body: fd });
            const data = await res.json();
            if (data.success) {
                msg.innerHTML = `<div class="alert alert-success">Account created! Check your email.</div>`;
                setTimeout(() => window.location.href = `${baseUrl}/verify-email`, 1200);
            } else {
                msg.innerHTML = `<div class="alert alert-error">${data.message}</div>`;
                btn.disabled = false; btn.textContent = 'Start Free Trial';
                if (typeof grecaptcha !== 'undefined') grecaptcha.reset();
            }
        } catch {
            msg.innerHTML = `<div class="alert alert-error">Connection failed.</div>`;
            btn.disabled = false; btn.textContent = 'Start Free Trial';
            if (typeof grecaptcha !== 'undefined') grecaptcha.reset();
        }
    }
};
