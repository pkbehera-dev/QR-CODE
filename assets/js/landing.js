document.addEventListener('DOMContentLoaded', () => {
    // ─── Navbar Scroll Effect ───
    const header = document.querySelector('.public-header');
    if (header) {
        window.addEventListener('scroll', () => {
            if (window.scrollY > 50) {
                header.classList.add('header-dark');
            } else {
                header.classList.remove('header-dark');
            }
        });
    }

    // ─── Stats Counter Animation ───
    const stats = document.querySelectorAll('.stat-value');
    if (stats.length > 0) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const el = entry.target;
                    const originalText = el.getAttribute('data-original') || el.innerText.trim();
                    if (!el.hasAttribute('data-original')) {
                        el.setAttribute('data-original', originalText);
                    }
                    
                    const match = originalText.match(/[\d.]+/);
                    if (match) {
                        const targetNum = parseFloat(match[0]);
                        const isFloat = originalText.includes('.');
                        const prefix = originalText.substring(0, match.index);
                        const suffix = originalText.substring(match.index + match[0].length);
                        
                        let current = 0;
                        const steps = 40;
                        const inc = targetNum / steps;
                        
                        const timer = setInterval(() => {
                            current += inc;
                            if (current >= targetNum) {
                                clearInterval(timer);
                                el.innerText = originalText;
                            } else {
                                el.innerText = prefix + (isFloat ? current.toFixed(1) : Math.floor(current)) + suffix;
                            }
                        }, 30);
                    }
                    observer.unobserve(el);
                }
            });
        }, { threshold: 0.2 });

        stats.forEach(s => observer.observe(s));
    }
});
