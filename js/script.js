document.addEventListener('DOMContentLoaded', () => {
  // 1. Mobile Menu Toggle
  const mobileToggle = document.getElementById('mobileToggle');
  const navMenu = document.getElementById('navMenu');

  if (mobileToggle && navMenu) {
    mobileToggle.addEventListener('click', () => {
      navMenu.classList.toggle('active');
      const isExpanded = navMenu.classList.contains('active');
      mobileToggle.setAttribute('aria-expanded', isExpanded);
      const icon = mobileToggle.querySelector('i');
      if (icon) {
        icon.className = isExpanded ? 'fas fa-times' : 'fas fa-bars';
      }
    });

    // Close menu when clicking link (except dropdown toggle)
    navMenu.querySelectorAll('a:not(.dropdown-toggle)').forEach(link => {
      link.addEventListener('click', () => {
        navMenu.classList.remove('active');
        mobileToggle.setAttribute('aria-expanded', 'false');
        const icon = mobileToggle.querySelector('i');
        if (icon) icon.className = 'fas fa-bars';
      });
    });

    // Mobile dropdown toggle
    const dropdownToggle = navMenu.querySelector('.dropdown-toggle');
    const dropdownParent = navMenu.querySelector('.nav-item-dropdown');
    if (dropdownToggle && dropdownParent) {
      dropdownToggle.addEventListener('click', (e) => {
        if (window.innerWidth <= 991) {
          if (!dropdownParent.classList.contains('open')) {
            e.preventDefault();
            dropdownParent.classList.add('open');
          }
        }
      });
    }
  }

  // 2. Accordion Functionality for FAQ
  const accordionHeaders = document.querySelectorAll('.accordion-header');

  accordionHeaders.forEach(header => {
    header.addEventListener('click', () => {
      const item = header.parentElement;
      const isActive = item.classList.contains('active');

      // Close all accordion items
      document.querySelectorAll('.accordion-item').forEach(el => {
        el.classList.remove('active');
        const btn = el.querySelector('.accordion-header');
        if (btn) btn.setAttribute('aria-expanded', 'false');
      });

      // Toggle current if it wasn't active
      if (!isActive) {
        item.classList.add('active');
        header.setAttribute('aria-expanded', 'true');
      }
    });
  });

  // 3. Cookie Bar Preference
  const cookieBar = document.getElementById('cookieBar');
  const acceptBtn = document.getElementById('cookieAccept');
  const rejectBtn = document.getElementById('cookieReject');
  const settingsBtn = document.getElementById('cookieSettings');

  if (cookieBar) {
    const cookieConsent = localStorage.getItem('hm_cookie_consent');
    if (!cookieConsent) {
      cookieBar.classList.remove('hidden');
    }

    if (acceptBtn) {
      acceptBtn.addEventListener('click', () => {
        localStorage.setItem('hm_cookie_consent', 'accepted');
        cookieBar.classList.add('hidden');
      });
    }

    if (rejectBtn) {
      rejectBtn.addEventListener('click', () => {
        localStorage.setItem('hm_cookie_consent', 'rejected');
        cookieBar.classList.add('hidden');
      });
    }

    if (settingsBtn) {
      settingsBtn.addEventListener('click', () => {
        alert('Cookies na tomto webu slouží výhradně pro základní měření návštěvnosti a anonymní statistiky.');
        localStorage.setItem('hm_cookie_consent', 'custom');
        cookieBar.classList.add('hidden');
      });
    }
  }

  // 4. Formspree Contact Form Submission with AJAX & Redirection
  const contactForm = document.getElementById('contactForm');
  if (contactForm) {
    contactForm.addEventListener('submit', async (e) => {
      e.preventDefault();

      const hpField = contactForm.querySelector('input[name="website"]');
      if (hpField && hpField.value !== '') {
        // Spam bot detected!
        return false;
      }

      const submitBtn = contactForm.querySelector('button[type="submit"]');
      const originalBtnText = submitBtn ? submitBtn.innerHTML : 'ODESLAT ZPRÁVU';

      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right: 8px;"></i> Odesílám...';
      }

      try {
        const formData = new FormData(contactForm);
        const response = await fetch(contactForm.action, {
          method: 'POST',
          body: formData,
          headers: {
            'Accept': 'application/json'
          }
        });

        if (response.ok) {
          window.location.href = 'dekuji';
        } else {
          const data = await response.json();
          let errorMessage = 'Při odesílání formuláře došlo k chybě. Zkuste to prosím znovu.';
          if (data && data.errors && data.errors.length > 0) {
            errorMessage = data.errors.map(err => err.message).join(', ');
          }
          alert(errorMessage);
          if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
          }
        }
      } catch (error) {
        alert('Došlo k chybě připojení. Zkontrolujte prosím připojení k internetu nebo nám napište přímo na e-mail.');
        if (submitBtn) {
          submitBtn.disabled = false;
          submitBtn.innerHTML = originalBtnText;
        }
      }
    });
  }
});
