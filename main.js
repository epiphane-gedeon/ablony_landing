/* ============================================================
   ABLONY — main.js
   Interactions, animations, scroll reveal, FAQ, forms
   ============================================================ */

document.addEventListener('DOMContentLoaded', () => {

  // ——— NAVBAR SCROLL ———
  const navbar = document.getElementById('navbar');
  if (navbar) {
    window.addEventListener('scroll', () => {
      navbar.classList.toggle('scrolled', window.scrollY > 20);
    }, { passive: true });
  }

  // ——— HAMBURGER MENU ———
  const hamburger = document.getElementById('hamburger');
  const mobileMenu = document.getElementById('mobileMenu');
  if (hamburger && mobileMenu) {
    hamburger.addEventListener('click', () => {
      mobileMenu.classList.toggle('open');
      const spans = hamburger.querySelectorAll('span');
      if (mobileMenu.classList.contains('open')) {
        spans[0].style.transform = 'rotate(45deg) translate(5px, 5px)';
        spans[1].style.opacity = '0';
        spans[2].style.transform = 'rotate(-45deg) translate(5px, -5px)';
      } else {
        spans[0].style.transform = '';
        spans[1].style.opacity = '';
        spans[2].style.transform = '';
      }
    });

    // Close on link click
    mobileMenu.querySelectorAll('.mobile-link').forEach(link => {
      link.addEventListener('click', () => {
        mobileMenu.classList.remove('open');
        hamburger.querySelectorAll('span').forEach(s => {
          s.style.transform = '';
          s.style.opacity = '';
        });
      });
    });
  }

  // ——— SCROLL REVEAL ———
  const revealElements = document.querySelectorAll('.reveal');
  if (revealElements.length) {
    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry, i) => {
        if (entry.isIntersecting) {
          // Stagger children in a container
          const delay = entry.target.dataset.delay || 0;
          setTimeout(() => {
            entry.target.classList.add('visible');
          }, delay);
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

    // Add stagger delays to sibling groups
    const staggerGroups = ['.steps', '.features-grid', '.trust-grid', '.faq-list'];
    staggerGroups.forEach(selector => {
      const container = document.querySelector(selector);
      if (!container) return;
      const children = container.querySelectorAll('.reveal');
      children.forEach((child, i) => {
        child.dataset.delay = i * 100;
      });
    });

    revealElements.forEach(el => observer.observe(el));
  }

  // ——— FAQ ACCORDION ———
  const faqItems = document.querySelectorAll('.faq-item');
  faqItems.forEach(item => {
    const btn = item.querySelector('.faq-q');
    if (!btn) return;
    btn.addEventListener('click', () => {
      const isOpen = item.classList.contains('open');
      // Close all
      faqItems.forEach(i => {
        i.classList.remove('open');
        i.querySelector('.faq-q')?.setAttribute('aria-expanded', 'false');
      });
      // Open clicked (if was closed)
      if (!isOpen) {
        item.classList.add('open');
        btn.setAttribute('aria-expanded', 'true');
      }
    });
  });

  // ——— CTA FORM ———
  const ctaForm = document.getElementById('ctaForm');
  const ctaSuccess = document.getElementById('ctaSuccess');
  if (ctaForm && ctaSuccess) {
    ctaForm.addEventListener('submit', (e) => {
      e.preventDefault();
      const email = document.getElementById('ctaEmail')?.value;
      if (!email) return;
      // Simulate submission
      const btn = ctaForm.querySelector('button[type="submit"]');
      if (btn) {
        btn.innerHTML = '<span>Inscription...</span>';
        btn.disabled = true;
      }
      setTimeout(() => {
        ctaForm.style.display = 'none';
        ctaSuccess.style.display = 'block';
        ctaSuccess.style.animation = 'fadeInUp 0.5s ease forwards';
      }, 800);
    });
  }

  // ——— SMOOTH ANCHOR SCROLL ———
  document.querySelectorAll('a[href^="#"]').forEach(link => {
    link.addEventListener('click', (e) => {
      const target = document.querySelector(link.getAttribute('href'));
      if (target) {
        e.preventDefault();
        const offset = 80;
        const top = target.getBoundingClientRect().top + window.scrollY - offset;
        window.scrollTo({ top, behavior: 'smooth' });
      }
    });
  });

  // ——— PROGRESS BARS ANIMATION (coming soon) ———
  const fills = document.querySelectorAll('.coming-fill');
  if (fills.length) {
    const progObserver = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          const fill = entry.target;
          const target = fill.style.width;
          fill.style.width = '0%';
          setTimeout(() => { fill.style.width = target; }, 200);
          progObserver.unobserve(fill);
        }
      });
    }, { threshold: 0.5 });
    fills.forEach(f => progObserver.observe(f));
  }

});

// ——— CTA FORM HANDLER (global) ———
function handleCTASubmit(e) {
  e.preventDefault();
  const form = e.target;
  const btn = form.querySelector('button[type="submit"]');
  const formData = new FormData(form);
  
  if (btn) {
    btn.innerHTML = '<span>Inscription...</span>';
    btn.disabled = true;
  }
  
  fetch('submit_waitlist.php', {
    method: 'POST',
    body: formData
  })
  .then(response => {
    const contentType = response.headers.get('content-type');
    if (!contentType || !contentType.includes('application/json')) {
      throw new Error('Réponse invalide du serveur.');
    }
    return response.json();
  })
  .then(data => {
    if (data.success) {
      form.style.display = 'none';
      const success = document.getElementById('ctaSuccess');
      if (success) {
        success.style.display = 'block';
        success.style.animation = 'fadeInUp 0.5s ease forwards';
      }
    } else {
      if (data.errors && data.errors.length > 0) {
        alert('Erreur(s):\n' + data.errors.join('\n'));
      }
      if (btn) {
        btn.innerHTML = '<span>M\'inscrire</span>';
        btn.disabled = false;
      }
    }
  })
  .catch(error => {
    console.error('Erreur:', error);
    alert('Erreur lors de l\'inscription. Veuillez réessayer.');
    if (btn) {
      btn.innerHTML = '<span>M\'inscrire</span>';
      btn.disabled = false;
    }
  });
}

// ——— CONTACT FORM ———
function handleContactSubmit(e) {
  e.preventDefault();
  const form = e.target;
  const btn = form.querySelector('button[type="submit"]');
  const formData = new FormData(form);

  console.log(formData.get('name'), formData.get('email'), formData.get('message'), formData.get('message')); 
  
  if (btn) {
    btn.innerHTML = '<span>Envoi en cours...</span>';
    btn.disabled = true;
  }
  
  fetch('submit_form.php', {
    method: 'POST',
    body: formData
  })
  .then(response => {
    // Check if response is JSON
    const contentType = response.headers.get('content-type');
    if (!contentType || !contentType.includes('application/json')) {
      throw new Error('Réponse invalide du serveur. Veuillez contacter support.');
    }
    return response.json();
  })
  .then(data => {
    if (data.success) {
      form.style.display = 'none';
      const success = document.getElementById('formSuccess');
      if (success) {
        success.style.display = 'block';
        success.style.animation = 'fadeInUp 0.5s ease forwards';
      }
    } else {
      // Afficher les erreurs
      if (data.errors && data.errors.length > 0) {
        alert('Erreur(s):\n' + data.errors.join('\n'));
      }
      if (btn) {
        btn.innerHTML = '<span>Envoyer le message</span>';
        btn.disabled = false;
      }
    }
  })
  .catch(error => {
    console.error('Erreur:', error);
    alert('Erreur lors de l\'envoi du message. Veuillez réessayer.');
    if (btn) {
      btn.innerHTML = '<span>Envoyer le message</span>';
      btn.disabled = false;
    }
  });
}

// ——— WAITLIST FORM ———
function handleWaitlistSubmit(e) {
  e.preventDefault();
  const form = e.target;
  const btn = form.querySelector('button[type="submit"]');
  const formData = new FormData(form);
  console.log (formData);
  
  if (btn) {
    btn.innerHTML = '<span>Inscription...</span>';
    btn.disabled = true;
  }
  
  fetch('submit_waitlist.php', {
    method: 'POST',
    body: formData
  })
  .then(response => {
    // Check if response is JSON
    const contentType = response.headers.get('content-type');
    if (!contentType || !contentType.includes('application/json')) {
      throw new Error('Réponse invalide du serveur. Veuillez contacter support.');
    }
    return response.json();
  })
  .then(data => {
    if (data.success) {
      form.style.display = 'none';
      const success = document.getElementById('waitlistSuccess');
      if (success) {
        success.style.display = 'block';
        success.style.animation = 'fadeInUp 0.5s ease forwards';
      }
    } else {
      // Afficher les erreurs
      if (data.errors && data.errors.length > 0) {
        alert('Erreur(s):\n' + data.errors.join('\n'));
      }
      if (btn) {
        btn.innerHTML = '<span>Je rejoins la liste d\'attente</span>';
        btn.disabled = false;
      }
    }
  })
  .catch(error => {
    console.error('Erreur:', error);
    alert('Erreur lors de l\'inscription. Veuillez réessayer.');
    if (btn) {
      btn.innerHTML = '<span>Je rejoins la liste d\'attente</span>';
      btn.disabled = false;
    }
  });
}
