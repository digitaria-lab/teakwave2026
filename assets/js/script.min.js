/* Teakwave shared JavaScript
   Berlaku untuk index.html, produk.php, produk-detail.html, profil.html, dan kontak.html. */
(function () {
  'use strict';


  const defaultExternalUrls = Object.freeze({
    tokopedia: 'https://www.tokopedia.com/teakwave',
    shopee: 'https://shopee.co.id/teakwave',
    whatsapp: 'https://wa.me/6282112345678',
    instagram: 'https://www.instagram.com/teak.wave/',
    facebook: 'https://www.facebook.com/teakwave'
  });

  let externalUrls = { ...defaultExternalUrls };

  const siteBaseUrl = (() => {
    const configured = document.querySelector('meta[name="teakwave-base-url"]')?.content?.trim();
    if (configured) return configured.replace(/\/$/, '');

    const parts = window.location.pathname.split('/').filter(Boolean);
    const firstSegment = parts.length ? `/${parts[0]}` : '';
    return `${window.location.origin}${firstSegment}`.replace(/\/$/, '');
  })();

  function normalizePublicAssetUrl(path, fallbackPath = '') {
    let raw = String(path || '').trim();
    if (!raw) raw = String(fallbackPath || '').trim();
    if (!raw) return '';
    if (/^https?:\/\//i.test(raw)) return raw;

    raw = raw.replace(/\\/g, '/').replace(/[?#].*$/, '');
    const knownPath = raw.match(/(?:^|\/)(uploads|produk|assets)\/(.+)$/i);
    if (knownPath) {
      raw = `${knownPath[1].toLowerCase()}/${knownPath[2]}`;
    }

    raw = raw.replace(/^(?:\.\/|\.\.\/)+/, '').replace(/^\/+/, '');
    if (raw.startsWith('utakatik/assets/uploads/') || raw.startsWith('assets/uploads/')) {
      raw = `uploads/${raw.split('/').pop()}`;
    } else if (!raw.includes('/')) {
      // Nama file dari dashboard umumnya berasal dari folder uploads.
      raw = `uploads/${raw}`;
    }

    return `${siteBaseUrl}/${raw}`;
  }

  function preloadImageUrl(url) {
    return new Promise((resolve, reject) => {
      const image = new Image();
      image.onload = () => resolve(url);
      image.onerror = reject;
      image.src = url;
    });
  }

  function bindImageFallbacks(root = document) {
    root.querySelectorAll('img[data-fallback-src]').forEach((image) => {
      if (image.dataset.fallbackBound === 'true') return;
      image.dataset.fallbackBound = 'true';

      const normalizedSource = normalizePublicAssetUrl(image.getAttribute('src'), image.dataset.fallbackSrc);
      const fallbackSource = normalizePublicAssetUrl(image.dataset.fallbackSrc || 'produk/1.png');
      if (normalizedSource) image.src = normalizedSource;

      const applyFallback = () => {
        if (image.dataset.fallbackApplied !== 'true' && fallbackSource && image.src !== fallbackSource) {
          image.dataset.fallbackApplied = 'true';
          image.src = fallbackSource;
          return;
        }
        image.classList.add('is-image-missing');
      };

      image.addEventListener('error', applyFallback);
      if (image.complete && image.naturalWidth === 0) {
        applyFallback();
      }
    });
  }

  function getExternalUrl(key) {
    return externalUrls[key] || defaultExternalUrls[key] || '#';
  }

  function buildWhatsappUrl(message = '') {
    const baseUrl = getExternalUrl('whatsapp');
    if (!message) return baseUrl;

    try {
      const url = new URL(baseUrl, window.location.origin);
      url.searchParams.set('text', message);
      return url.toString();
    } catch (error) {
      const separator = baseUrl.includes('?') ? '&' : '?';
      return `${baseUrl}${separator}text=${encodeURIComponent(message)}`;
    }
  }

  function applyExternalUrlSettings(root = document) {
    root.querySelectorAll('[data-external-url]').forEach((link) => {
      const key = String(link.dataset.externalUrl || '').toLowerCase();
      if (!Object.prototype.hasOwnProperty.call(defaultExternalUrls, key)) return;

      const message = key === 'whatsapp' ? String(link.dataset.whatsappMessage || '') : '';
      link.setAttribute('href', key === 'whatsapp' ? buildWhatsappUrl(message) : getExternalUrl(key));
      link.setAttribute('target', '_blank');
      link.setAttribute('rel', 'noopener');
    });

    initFixedMarketplaceLinks(root);
    initFixedFooterSocialLinks(root);
  }

  async function initExternalUrlSettings() {
    applyExternalUrlSettings(document);

    try {
      const response = await fetch(`${siteBaseUrl}/api/settings.php`, {
        headers: { 'Accept': 'application/json' },
        cache: 'no-store'
      });

      if (!response.ok) throw new Error(`HTTP ${response.status}`);

      const payload = await response.json();
      if (payload && payload.urls && typeof payload.urls === 'object') {
        Object.keys(defaultExternalUrls).forEach((key) => {
          const value = String(payload.urls[key] || '').trim();
          if (/^https?:\/\//i.test(value)) externalUrls[key] = value;
        });
      }
    } catch (error) {
      console.warn('Pengaturan URL eksternal belum dapat dimuat. Menggunakan URL bawaan.', error);
    }

    applyExternalUrlSettings(document);
  }

  document.addEventListener('DOMContentLoaded', function () {
    applyExternalUrlSettings(document);
    initHeroHeaderSlides();
    initFixedMarketplaceLinks();
    initFixedFooterSocialLinks();
    initRevealAnimation();
    initFloatingActions();
    initNavbarActiveState();
    bindImageFallbacks(document);
    initBestSellerProducts();
    initProductCatalog();
    initProductDetailGallery();

    const loadDynamicContent = () => {
      initExternalUrlSettings();
      initDynamicBanners();
      initDynamicContents();
    };

    if ('requestIdleCallback' in window) {
      requestIdleCallback(loadDynamicContent, { timeout: 1800 });
    } else {
      window.setTimeout(loadDynamicContent, 500);
    }
  });


  // URL marketplace dibuat tetap di sisi frontend agar nilai href dari konten
  // database (misalnya "#") tidak menimpa tautan toko resmi.
  function initFixedMarketplaceLinks(root = document) {
    const marketplaceUrls = {
      tokopedia: getExternalUrl('tokopedia'),
      shopee: getExternalUrl('shopee')
    };

    root.querySelectorAll('a.market-btn').forEach((link) => {
      const image = link.querySelector('img');
      const marker = [
        link.className,
        link.textContent,
        image?.getAttribute('src'),
        image?.getAttribute('alt')
      ].filter(Boolean).join(' ').toLowerCase();

      if (marker.includes('tokopedia')) {
        link.setAttribute('href', marketplaceUrls.tokopedia);
      } else if (marker.includes('shopee')) {
        link.setAttribute('href', marketplaceUrls.shopee);
      } else {
        return;
      }

      link.setAttribute('target', '_blank');
      link.setAttribute('rel', 'noopener');
    });
  }


  // URL media sosial footer dibuat tetap di sisi frontend agar nilai href dari
  // konten database (termasuk "#") tidak menimpa tautan akun resmi.
  function initFixedFooterSocialLinks(root = document) {
    const socialUrls = {
      instagram: getExternalUrl('instagram'),
      facebook: getExternalUrl('facebook')
    };

    root.querySelectorAll('.social a').forEach((link) => {
      const marker = [
        link.getAttribute('aria-label'),
        link.className,
        link.textContent,
        link.querySelector('i')?.className
      ].filter(Boolean).join(' ').toLowerCase();

      if (marker.includes('instagram')) {
        link.setAttribute('href', socialUrls.instagram);
      } else if (marker.includes('facebook')) {
        link.setAttribute('href', socialUrls.facebook);
      } else {
        return;
      }

      link.setAttribute('target', '_blank');
      link.setAttribute('rel', 'noopener');
    });
  }


  function initHeroHeaderSlides() {
    const heroCarousel = document.getElementById('heroCarousel');
    if (!heroCarousel) return;

    const heroSlides = heroCarousel.querySelectorAll('.hero-slide');
    if (!heroSlides.length) return;

    heroSlides.forEach((slide, index) => {
      let image = slide.querySelector('img');

      // Kompatibilitas: jika slide lama masih memakai inline background-image,
      // ubah otomatis menjadi <img> agar tinggi mobile mengikuti rasio gambar.
      if (!image) {
        const backgroundSource = slide.style.backgroundImage || slide.style.background || '';
        const matchedUrl = backgroundSource.match(/url\(["']?([^"')]+)["']?\)/i);

        if (matchedUrl && matchedUrl[1]) {
          image = document.createElement('img');
          image.src = matchedUrl[1];
          image.alt = `Banner Teakwave ${index + 1}`;
          image.loading = index === 0 ? 'eager' : 'lazy';
          image.decoding = 'async';
          slide.prepend(image);
          slide.style.background = '';
          slide.style.backgroundImage = '';
          slide.style.backgroundPosition = '';
          slide.style.backgroundSize = '';
        }
      }

      if (image) {
        image.classList.add('hero-slide-img');
        slide.classList.add('hero-image-slide');
      }
    });

    function updateHeroIndicatorPosition() {
      if (window.innerWidth > 767.98) {
        heroCarousel.style.removeProperty('--hero-slide-height');
        return;
      }

      window.requestAnimationFrame(() => {
        const activeSlide = heroCarousel.querySelector('.carousel-item.active .hero-slide');
        if (!activeSlide) return;

        const activeImage = activeSlide.querySelector('.hero-slide-img, img');
        const imageHeight = activeImage ? activeImage.getBoundingClientRect().height : 0;
        const slideHeight = activeSlide.getBoundingClientRect().height;
        const finalHeight = Math.max(imageHeight, slideHeight);

        if (finalHeight > 20) {
          heroCarousel.style.setProperty('--hero-slide-height', `${Math.ceil(finalHeight)}px`);
        }
      });
    }

    heroCarousel.querySelectorAll('.hero-slide-img').forEach((image) => {
      if (image.complete) return;
      image.addEventListener('load', updateHeroIndicatorPosition);
      image.addEventListener('error', updateHeroIndicatorPosition);
    });

    heroCarousel.addEventListener('slide.bs.carousel', () => {
      window.setTimeout(updateHeroIndicatorPosition, 80);
    });
    heroCarousel.addEventListener('slid.bs.carousel', updateHeroIndicatorPosition);
    window.addEventListener('resize', updateHeroIndicatorPosition, { passive: true });
    window.addEventListener('load', updateHeroIndicatorPosition);

    updateHeroIndicatorPosition();
    window.setTimeout(updateHeroIndicatorPosition, 250);
  }


  function initRevealAnimation() {
    const revealElements = document.querySelectorAll('.reveal');
    if (!revealElements.length) return;

    if (!('IntersectionObserver' in window)) {
      revealElements.forEach((el) => el.classList.add('show'));
      return;
    }

    const revealObserver = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add('show');
          revealObserver.unobserve(entry.target);
        }
      });
    }, {
      threshold: 0.15,
      rootMargin: '0px 0px -40px 0px'
    });

    revealElements.forEach((el) => revealObserver.observe(el));
  }

  function initFloatingActions() {
    const floatingActions = document.getElementById('floatingActions');
    const backToTop = document.getElementById('backToTop');
    const navbar = document.querySelector('.navbar');

    function toggleFloatingButtons() {
      if (floatingActions) {
        floatingActions.classList.toggle('show', window.scrollY > 320);
      }

      if (navbar) {
        navbar.classList.toggle('shrink', window.scrollY > 80);
      }
    }

    window.addEventListener('scroll', toggleFloatingButtons, { passive: true });
    window.addEventListener('load', toggleFloatingButtons);
    toggleFloatingButtons();

    if (backToTop) {
      backToTop.addEventListener('click', () => {
        window.scrollTo({ top: 0, behavior: 'smooth' });
      });
    }
  }

  function initNavbarActiveState() {
    const navLinks = document.querySelectorAll('.navbar .nav-link');
    if (!navLinks.length) return;

    const currentFile = (window.location.pathname.split('/').pop() || 'index.html').toLowerCase();
    const hashLinks = document.querySelectorAll('.navbar .nav-link[href^="#"]');
    const sections = document.querySelectorAll('header[id], section[id], footer[id]');

    navLinks.forEach((link) => {
      const href = (link.getAttribute('href') || '').split('#')[0].toLowerCase();
      if (href && href === currentFile) {
        link.classList.add('active');
      }
    });

    if (!hashLinks.length || !sections.length) return;

    function setActiveNavOnScroll() {
      let current = 'home';

      sections.forEach((section) => {
        const sectionTop = section.offsetTop - 140;
        if (window.scrollY >= sectionTop) {
          current = section.getAttribute('id');
        }
      });

      hashLinks.forEach((link) => {
        link.classList.remove('active');
        if (link.getAttribute('href') === `#${current}`) {
          link.classList.add('active');
        }
      });
    }

    window.addEventListener('scroll', setActiveNavOnScroll, { passive: true });
    window.addEventListener('load', setActiveNavOnScroll);
    setActiveNavOnScroll();
  }


  function escapeSharedHTML(value) {
    return String(value ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function productSlug(value) {
    return String(value || 'produk')
      .toLowerCase()
      .trim()
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/^-+|-+$/g, '') || 'produk';
  }

  function initDynamicBanners() {
    const heroCarousel = document.getElementById('heroCarousel');

    if (heroCarousel && heroCarousel.dataset.bannerPlacement) {
      loadHomeHeroBanners(heroCarousel, heroCarousel.dataset.bannerPlacement);
    }

    document.querySelectorAll('[data-banner-placement]').forEach((target) => {
      if (target.id === 'heroCarousel') return;
      loadSingleHeroBanner(target, target.dataset.bannerPlacement);
    });
  }

  async function fetchBanners(placement) {
    const response = await fetch(`${siteBaseUrl}/api/banners.php?placement=${encodeURIComponent(placement)}`, {
      headers: { 'Accept': 'application/json' },
      cache: 'no-store'
    });

    if (!response.ok) {
      throw new Error('HTTP ' + response.status);
    }

    const payload = await response.json();

    if (!payload.success) {
      throw new Error(payload.message || 'Gagal mengambil banner.');
    }

    return Array.isArray(payload.banners) ? payload.banners : [];
  }

  async function loadHomeHeroBanners(heroCarousel, placement) {
    try {
      const banners = await fetchBanners(placement);
      if (!banners.length) return;

      // Jangan mengganti banner statis sebelum file dari database terbukti dapat dimuat.
      const checkedBanners = await Promise.all(banners.map(async (banner) => {
        const image = normalizePublicAssetUrl(banner.image);
        if (!image) return null;
        try {
          await preloadImageUrl(image);
          return { ...banner, image };
        } catch (error) {
          console.warn('File banner tidak ditemukan:', image);
          return null;
        }
      }));
      const validBanners = checkedBanners.filter(Boolean);
      if (!validBanners.length) return;

      const indicators = heroCarousel.querySelector('.carousel-indicators');
      const inner = heroCarousel.querySelector('.carousel-inner');
      if (!inner) return;

      if (indicators) {
        indicators.innerHTML = validBanners.map((banner, index) => `
          <button
            aria-current="${index === 0 ? 'true' : 'false'}"
            aria-label="Slide ${index + 1}"
            class="${index === 0 ? 'active' : ''}"
            data-bs-slide-to="${index}"
            data-bs-target="#heroCarousel"
            type="button"></button>
        `).join('');
      }

      inner.innerHTML = validBanners.map((banner, index) => {
        const image = escapeSharedHTML(banner.image);
        const title = escapeSharedHTML(banner.title || `Banner Teakwave ${index + 1}`);
        const link = banner.link_url ? escapeSharedHTML(banner.link_url) : '';

        const imageHTML = `
          <div class="hero-slide hero-image-slide text-center">
            <img class="hero-slide-img" src="${image}" alt="${title}" loading="${index === 0 ? 'eager' : 'lazy'}" decoding="async">
          </div>
        `;

        return `
          <div class="carousel-item ${index === 0 ? 'active' : ''}">
            ${link ? `<a href="${link}" class="hero-banner-link" rel="noopener">${imageHTML}</a>` : imageHTML}
          </div>
        `;
      }).join('');

      if (window.bootstrap) {
        const instance = bootstrap.Carousel.getInstance(heroCarousel);
        if (instance) instance.dispose();
        new bootstrap.Carousel(heroCarousel);
      }

      window.setTimeout(initHeroHeaderSlides, 80);
    } catch (error) {
      console.warn('Banner homepage tidak bisa dimuat:', error);
    }
  }

  async function loadSingleHeroBanner(target, placement) {
    try {
      const banners = await fetchBanners(placement);
      if (!banners.length || !banners[0].image) return;

      const image = normalizePublicAssetUrl(banners[0].image);
      if (!image) return;
      await preloadImageUrl(image);

      target.style.backgroundImage = `url("${image}")`;
      target.style.backgroundSize = 'cover';
      target.style.backgroundPosition = 'center center';

      if (banners[0].title) {
        target.setAttribute('aria-label', banners[0].title);
      }
    } catch (error) {
      // Banner statis dari PHP tetap dipertahankan bila file database tidak valid.
      console.warn('Banner halaman tidak bisa dimuat:', error);
    }
  }


  function renderDynamicTestimonials(target, body) {
    const template = document.createElement('template');
    template.innerHTML = body || '';

    function cleanText(value) {
      return String(value || '')
        .replace(/\s+/g, ' ')
        .replace(/[“”]/g, '"')
        .trim();
    }

    function escapeText(value) {
      return escapeSharedHTML(cleanText(value));
    }

    function isQuoteText(text) {
      const lower = text.toLowerCase();
      return /^["']/.test(text) ||
        lower.includes('pelayanan') ||
        lower.includes('rma') ||
        lower.includes('harga') ||
        lower.includes('bagus') ||
        lower.includes('kompetitif');
    }

    function isAuthorText(text) {
      const lower = text.toLowerCase();
      return lower.includes('bapak') ||
        lower.includes('ibu') ||
        lower.includes('—') ||
        lower.includes('- ') ||
        lower.includes('net') ||
        lower.includes('comp');
    }

    let label = 'Testimonials';
    let title = 'Apa Kata Mereka';
    let intro = 'Banyak pelanggan telah mempercayakan kebutuhan perangkat jaringan mereka kepada Teakwave.';
    let testimonials = [];

    const structuredCards = Array.from(template.content.querySelectorAll('.testimonial-card'));

    if (structuredCards.length) {
      const labelEl = template.content.querySelector('.section-label');
      const titleEl = template.content.querySelector('.section-title, h1, h2, h3');
      const introEl = Array.from(template.content.querySelectorAll('p')).find((item) => !item.closest('.testimonial-card'));

      if (labelEl) label = cleanText(labelEl.textContent) || label;
      if (titleEl) {
        const titleText = cleanText(titleEl.textContent);
        title = titleText.replace(/^Testimonials\s*/i, '') || title;
      }
      if (introEl) intro = cleanText(introEl.textContent) || intro;

      testimonials = structuredCards.map((card) => ({
        quote: cleanText(card.querySelector('p')?.textContent || ''),
        author: cleanText(card.querySelector('small')?.textContent || '')
      })).filter((item) => item.quote);
    } else {
      const nodes = Array.from(template.content.querySelectorAll('h1,h2,h3,h4,p,small,li,blockquote,div,span'))
        .map((el) => cleanText(el.textContent))
        .filter(Boolean)
        .filter((text, index, arr) => arr.indexOf(text) === index)
        .filter((text) => text !== '★★★★★' && text !== '★ ★ ★ ★ ★');

      const combinedTitle = nodes.find((text) => /testimonials?\s*apa kata mereka/i.test(text));
      if (combinedTitle) {
        label = 'Testimonials';
        title = combinedTitle.replace(/testimonials?/i, '').trim() || title;
      } else {
        const labelText = nodes.find((text) => /^testimonials?$/i.test(text));
        const titleText = nodes.find((text) => /apa kata/i.test(text));

        if (labelText) label = labelText;
        if (titleText) title = titleText.replace(/^Testimonials\s*/i, '').trim() || title;
      }

      const introText = nodes.find((text) =>
        !/^testimonials?$/i.test(text) &&
        !/apa kata/i.test(text) &&
        !isQuoteText(text) &&
        !isAuthorText(text)
      );

      if (introText) intro = introText;

      for (let i = 0; i < nodes.length; i += 1) {
        const text = nodes[i];

        if (!isQuoteText(text)) continue;
        if (/^testimonials?/i.test(text) || /apa kata/i.test(text)) continue;

        let author = '';
        for (let j = i + 1; j < Math.min(i + 4, nodes.length); j += 1) {
          if (isAuthorText(nodes[j]) && !isQuoteText(nodes[j])) {
            author = nodes[j];
            break;
          }
        }

        testimonials.push({
          quote: text.replace(/^["']|["']$/g, ''),
          author: author || 'Pelanggan Teakwave'
        });
      }
    }

    if (!testimonials.length) {
      testimonials = [
        {
          quote: 'Pelayanan cepat, RMA bagus, harga kompetitif.',
          author: 'Bapak Donny — Gloria Net'
        },
        {
          quote: 'Selain harga yang bersaing, RMA pun cepat dan bagus.',
          author: 'Bapak David — DS Comp'
        }
      ];
    }

    target.innerHTML = `
      <div class="row g-4 align-items-center">
        <div class="col-lg-4 reveal slide-left show">
          <span class="section-label">${escapeText(label)}</span>
          <h2 class="section-title mb-3">${escapeText(title)}</h2>
          <p>${escapeText(intro)}</p>
        </div>

        ${testimonials.slice(0, 4).map((item, index) => `
          <div class="col-lg-4 reveal ${index % 2 === 0 ? '' : 'slide-right'} show">
            <div class="testimonial-card">
              <p>“${escapeText(item.quote)}”</p>
              <small>${escapeText(item.author)}</small>
              <div class="stars mt-2">
                <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
              </div>
            </div>
          </div>
        `).join('')}
      </div>
    `;

    target.classList.add('content-from-database', 'testimonial-panel-fixed');
    return true;
  }




  function renderDynamicFooterContact(target, body) {
    const template = document.createElement('template');
    template.innerHTML = body || '';

    function cleanText(value) {
      return String(value || '').replace(/\s+/g, ' ').trim();
    }

    function escapeText(value) {
      return escapeSharedHTML(cleanText(value));
    }

    const texts = Array.from(template.content.querySelectorAll('h1,h2,h3,h4,h5,h6,p,span,small,li,a,div'))
      .map((el) => cleanText(el.textContent))
      .filter(Boolean)
      .filter((text, index, arr) => arr.indexOf(text) === index);

    const allText = texts.join(' ');
    const emailMatch = allText.match(/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i);

    const phoneText = texts.find((text) =>
      !/@/.test(text) &&
      (
        /\(?0\d{2,4}\)?[\s.-]?\d{3,}[\s.-]?\d*/.test(text) ||
        /\+\d[\d\s().-]{6,}/.test(text)
      )
    );

    const addressText = texts.find((text) =>
      !/@/.test(text) &&
      text !== phoneText &&
      /kompleks|jalan|jl\.?|jakarta|blok|raya|mangga|alamat|dki/i.test(text)
    ) || texts
      .filter((text) => !/@/.test(text) && text !== phoneText && !/contact|hubungi kami/i.test(text))
      .sort((a, b) => b.length - a.length)[0];

    const labelText = texts.find((text) => /^contact$/i.test(text)) || 'Contact';
    const titleText = texts.find((text) => /hubungi kami/i.test(text)) || 'Hubungi Kami';
    const email = emailMatch ? emailMatch[0] : 'sales@teakwave.com';
    const phone = phoneText || '(021) 6121 005';
    const phoneHref = phone.replace(/[^\d+]/g, '');
    const address = addressText || 'Kompleks Harco Elektronik Mangga Dua Blok H-6 Raya Jl. Mangga Dua Dalam Jakarta, DKI Jakarta 10730';

    target.innerHTML = `
      <span class="section-label">${escapeText(labelText)}</span>
      <h2 class="fw-bold mb-3">${escapeText(titleText)}</h2>
      <p class="mb-2"><i class="bi bi-geo-alt-fill contact-icon"></i>${escapeText(address)}</p>
      <p class="mb-2"><i class="bi bi-envelope-fill contact-icon"></i><a href="mailto:${escapeText(email)}">${escapeText(email)}</a></p>
      <p class="mb-0"><i class="bi bi-telephone-fill contact-icon"></i><a href="tel:${escapeText(phoneHref)}">${escapeText(phone)}</a></p>
    `;

    target.classList.add('content-from-database', 'content-design-locked', 'footer-contact-fixed');
    return true;
  }

  function renderDynamicFooterCompany(target, body) {
    const template = document.createElement('template');
    template.innerHTML = body || '';

    function cleanText(value) {
      return String(value || '').replace(/\s+/g, ' ').trim();
    }

    function escapeText(value) {
      return escapeSharedHTML(cleanText(value));
    }

    const texts = Array.from(template.content.querySelectorAll('h1,h2,h3,h4,h5,h6,p,span,small,li,div'))
      .map((el) => cleanText(el.textContent))
      .filter(Boolean)
      .filter((text, index, arr) => arr.indexOf(text) === index);

    const title = texts.find((text) => /teakwave/i.test(text)) || 'Teakwave';
    const description = texts.find((text) =>
      text !== title &&
      /distributor|perangkat|jaringan|internet|berkualitas|indonesia/i.test(text)
    ) || 'Distributor perangkat jaringan nirkabel dan internet berkualitas untuk berbagai kebutuhan jaringan di Indonesia.';

    const instagram = getExternalUrl('instagram');
    const facebook = getExternalUrl('facebook');

    target.innerHTML = `
      <h2 class="fw-bold mb-3">${escapeText(title)}</h2>
      <p>${escapeText(description)}</p>
      <div class="social mt-3">
        <a aria-label="Instagram" href="${escapeText(instagram)}" target="_blank" rel="noopener"><i class="bi bi-instagram"></i></a>
        <a aria-label="Facebook" href="${escapeText(facebook)}" target="_blank" rel="noopener"><i class="bi bi-facebook"></i></a>
      </div>
    `;

    target.classList.add('content-from-database', 'content-design-locked', 'footer-company-fixed');
    return true;
  }


  function applyContentKeepingDesign(target, body) {
    const originalHTML = target.dataset.originalContentTemplate || target.innerHTML || '';

    if (!originalHTML || !body) return false;

    const sourceTemplate = document.createElement('template');
    sourceTemplate.innerHTML = body;

    const targetTemplate = document.createElement('template');
    targetTemplate.innerHTML = originalHTML;

    function cleanText(value) {
      return String(value || '').replace(/\s+/g, ' ').trim();
    }

    function isIconOnly(el) {
      return el.classList.contains('bi') ||
        el.classList.contains('stars') ||
        el.closest('.stars') ||
        el.closest('script,style');
    }

    function isEditableTextElement(el) {
      if (isIconOnly(el)) return false;

      const tag = el.tagName ? el.tagName.toLowerCase() : '';
      const allowedTags = ['h1','h2','h3','h4','h5','h6','p','small','span','li','a','button'];

      const explicitEditableClass =
        el.classList.contains('section-label') ||
        el.classList.contains('section-title') ||
        el.classList.contains('stat-number') ||
        el.classList.contains('stat-text') ||
        el.classList.contains('product-name') ||
        el.classList.contains('catalog-name') ||
        el.classList.contains('catalog-meta') ||
        el.classList.contains('catalog-sku') ||
        el.classList.contains('catalog-price');

      if (!allowedTags.includes(tag) && !explicitEditableClass) return false;
      if (!cleanText(el.textContent)) return false;

      const meaningfulChildren = Array.from(el.children).filter((child) => {
        const childTag = child.tagName ? child.tagName.toLowerCase() : '';
        const onlyIcon = childTag === 'i' || child.classList.contains('bi') || child.closest('.stars');
        const media = ['img','svg','picture','video','iframe'].includes(childTag);
        return !onlyIcon && !media && cleanText(child.textContent);
      });

      return meaningfulChildren.length === 0;
    }

    function collectEditableElements(root) {
      const selector = [
        'h1','h2','h3','h4','h5','h6',
        'p','small','span','li','a','button',
        '.stat-number','.stat-text','.product-name',
        '.catalog-name','.catalog-meta','.catalog-sku','.catalog-price'
      ].join(',');

      return Array.from(root.querySelectorAll(selector)).filter(isEditableTextElement);
    }

    function setTextPreserveIcons(el, text) {
      const value = cleanText(text);
      let textNode = null;

      Array.from(el.childNodes).forEach((node) => {
        if (node.nodeType === Node.TEXT_NODE && cleanText(node.textContent)) {
          if (!textNode) {
            textNode = node;
          } else {
            node.textContent = '';
          }
        }
      });

      if (textNode) {
        const prefix = textNode.textContent.match(/^\s*/)?.[0] || '';
        const suffix = textNode.textContent.match(/\s*$/)?.[0] || '';
        textNode.textContent = prefix + value + suffix;
      } else {
        el.appendChild(document.createTextNode(value));
      }
    }

    const sourceTextElements = collectEditableElements(sourceTemplate.content);
    const sourceTexts = sourceTextElements.map((el) => cleanText(el.textContent)).filter(Boolean);
    const targetTextElements = collectEditableElements(targetTemplate.content);

    if (!sourceTexts.length || !targetTextElements.length) {
      return false;
    }

    targetTextElements.forEach((el, index) => {
      if (sourceTexts[index]) {
        setTextPreserveIcons(el, sourceTexts[index]);
      }
    });

    const sourceLinks = Array.from(sourceTemplate.content.querySelectorAll('a[href]'));
    const targetLinks = Array.from(targetTemplate.content.querySelectorAll('a[href]'));

    targetLinks.forEach((link, index) => {
      const sourceHref = sourceLinks[index]?.getAttribute('href');
      if (sourceHref) link.setAttribute('href', sourceHref);
    });

    const sourceImages = Array.from(sourceTemplate.content.querySelectorAll('img[src]'));
    const targetImages = Array.from(targetTemplate.content.querySelectorAll('img[src]'));

    targetImages.forEach((img, index) => {
      const sourceImg = sourceImages[index];
      if (!sourceImg) return;

      const src = sourceImg.getAttribute('src');
      const alt = sourceImg.getAttribute('alt');

      if (src) img.setAttribute('src', src);
      if (alt) img.setAttribute('alt', alt);
    });

    target.innerHTML = targetTemplate.innerHTML;
    target.classList.add('content-from-database', 'content-design-locked');
    target.querySelectorAll('.reveal').forEach((el) => el.classList.add('show'));

    return true;
  }


  function initDynamicContents() {
    document.querySelectorAll('[data-content-slug]').forEach((target) => {
      if (!target.dataset.originalContentTemplate) {
        target.dataset.originalContentTemplate = target.innerHTML;
      }

      loadDynamicContent(target, target.dataset.contentSlug);
    });
  }

  async function loadDynamicContent(target, slug) {
    if (!slug) return;

    try {
      const response = await fetch(`${siteBaseUrl}/api/contents.php?slug=${encodeURIComponent(slug)}`, {
        headers: { 'Accept': 'application/json' },
        cache: 'no-store'
      });

      if (!response.ok) return;

      const payload = await response.json();

      if (!payload.success || !payload.content || !payload.content.body) return;

      if (slug === 'index-testimonials') {
        renderDynamicTestimonials(target, payload.content.body);
      } else if (slug === 'footer-contact') {
        renderDynamicFooterContact(target, payload.content.body);
      } else if (slug === 'footer-company') {
        renderDynamicFooterCompany(target, payload.content.body);
      } else {
        const applied = applyContentKeepingDesign(target, payload.content.body);

        if (!applied) {
          target.innerHTML = payload.content.body;
          target.classList.add('content-from-database');

          // Content baru dari database bisa berisi elemen .reveal.
          // Supaya tidak tersembunyi karena dimuat setelah observer awal, tampilkan langsung.
          target.querySelectorAll('.reveal').forEach((el) => el.classList.add('show'));
        }
      }

      // Terapkan kembali URL marketplace setelah konten dinamis selesai dirender.
      // Ini mempertahankan teks dari database, tetapi URL tetap berasal dari script.
      initFixedMarketplaceLinks(target);
      initFixedFooterSocialLinks(target);

      if (payload.content.title) {
        target.setAttribute('data-content-title', payload.content.title);
      }
    } catch (error) {
      console.warn('Content database tidak bisa dimuat:', error);
    }
  }



  function initBestSellerProducts() {
    const carousel = document.querySelector('[data-best-seller-products="true"]');
    if (!carousel) return;

    const inner = carousel.querySelector('.carousel-inner');
    if (!inner) return;

    function formatProductUrl(product) {
      const slug = product.slug || productSlug(product.name);
      return `${siteBaseUrl}/produk-${encodeURIComponent(slug)}`;
    }

    function renderBestSellerCarousel(products) {
      if (!products.length) return;

      const fallbackImage = normalizePublicAssetUrl('produk/1.png');
      const chunks = [];
      for (let i = 0; i < products.length; i += 5) {
        chunks.push(products.slice(i, i + 5));
      }

      inner.innerHTML = chunks.map((chunk, chunkIndex) => `
        <div class="carousel-item ${chunkIndex === 0 ? 'active' : ''}">
          <div class="best-seller-grid">
            ${chunk.map((product) => {
              const image = normalizePublicAssetUrl(product.image, 'produk/1.png');
              return `
              <div class="best-seller-item">
                <a class="best-seller-link" href="${formatProductUrl(product)}" aria-label="Lihat detail ${escapeSharedHTML(product.name)}">
                  <div class="product-card">
                    <div class="product-img">
                      <img class="best-seller-product-photo" src="${escapeSharedHTML(image)}" data-fallback-src="${escapeSharedHTML(fallbackImage)}" alt="${escapeSharedHTML(product.name)}" loading="lazy" decoding="async" width="720" height="720">
                    </div>
                    <h3 class="product-name">${escapeSharedHTML(product.name)}</h3>
                  </div>
                </a>
              </div>
            `}).join('')}
          </div>
        </div>
      `).join('');

      bindImageFallbacks(inner);

      if (window.bootstrap) {
        const oldInstance = bootstrap.Carousel.getInstance(carousel);
        if (oldInstance) oldInstance.dispose();
        new bootstrap.Carousel(carousel, { interval: false, ride: false });
      }
    }

    const initialDataNode = document.getElementById('initialBestSellerData');
    if (initialDataNode) {
      try {
        const initialProducts = JSON.parse(initialDataNode.textContent || '[]');
        if (Array.isArray(initialProducts) && initialProducts.length) {
          renderBestSellerCarousel(initialProducts);
          return;
        }
      } catch (error) {
        console.warn('Data awal best seller tidak valid:', error);
      }
    }

    fetch(`${siteBaseUrl}/api/products.php?best_seller=1&limit=10`, {
      headers: { 'Accept': 'application/json' },
      cache: 'no-store'
    })
      .then((response) => {
        if (!response.ok) throw new Error('HTTP ' + response.status);
        return response.json();
      })
      .then((payload) => {
        if (!payload.success || !Array.isArray(payload.products)) return;
        renderBestSellerCarousel(payload.products);
      })
      .catch((error) => {
        console.warn('Best seller products tidak bisa dimuat:', error);
      });
  }


  function initProductCatalog() {
    const catalogGrid = document.getElementById('catalogGrid');
    const productPagination = document.getElementById('productPagination');
    const productSearch = document.getElementById('productSearch');
    const emptyProductState = document.getElementById('emptyProductState');
    const filterButtonsWrap = document.querySelector('.brand-tabs');
    const catalogGridWrap = catalogGrid ? catalogGrid.closest('.catalog-grid-wrap') : null;

    if (!catalogGrid || !productPagination) return;

    const productsPerPage = 10;
    let products = [];
    let activeBrand = 'all';
    let currentProductPage = 1;
    let isCatalogLoading = false;
    let searchDebounceTimer = null;

    function escapeHTML(value) {
      return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    }

    function getSearchKeyword() {
      return productSearch ? productSearch.value.trim().toLowerCase() : '';
    }

    function normalizeAssetPath(path, fallbackPath = 'produk/1.png') {
      return normalizePublicAssetUrl(path, fallbackPath);
    }

    function setCatalogLoading(isLoading) {
      isCatalogLoading = isLoading;
      if (catalogGridWrap) catalogGridWrap.classList.toggle('is-loading', isLoading);
      catalogGrid.setAttribute('aria-busy', isLoading ? 'true' : 'false');
      productPagination.classList.toggle('is-disabled', isLoading);
      productPagination.querySelectorAll('button').forEach((button) => {
        button.disabled = isLoading || button.dataset.pageDisabled === 'true';
      });
    }

    function showCatalogPreloader(message = 'Memuat produk dari database...') {
      catalogGrid.innerHTML = `
        <div class="catalog-preloader" role="status" aria-live="polite">
          <span class="catalog-spinner" aria-hidden="true"></span>
          <span>${escapeHTML(message)}</span>
        </div>
      `;
    }

    function getFilteredProducts() {
      const keyword = getSearchKeyword();
      return products.filter((product) => {
        const brand = product.brand || '';
        const name = product.name || '';
        const sku = product.sku || '';
        const category = product.category || '';
        const matchedBrand = activeBrand === 'all' || brand === activeBrand;
        const matchedKeyword = !keyword || `${brand} ${name} ${sku} ${category}`.toLowerCase().includes(keyword);
        return matchedBrand && matchedKeyword;
      });
    }

    function getProductsByPage(filteredProducts, page) {
      const startIndex = (page - 1) * productsPerPage;
      return filteredProducts.slice(startIndex, startIndex + productsPerPage);
    }

    function createProductCardHTML(product) {
      const safeBrand = escapeHTML(product.brand || '');
      const safeName = escapeHTML(product.name || 'Produk Teakwave');
      const imageSrc = escapeHTML(normalizeAssetPath(product.image));
      const fallbackSrc = escapeHTML(normalizeAssetPath('produk/1.png'));
      const detailSlug = escapeHTML(product.slug || productSlug(product.name || 'produk'));

      return `
        <div data-product-card data-brand="${safeBrand}" data-name="${safeName}">
          <a class="catalog-link" href="${siteBaseUrl}/produk-${detailSlug}" aria-label="Lihat detail ${safeName}">
            <article class="catalog-card">
              <div class="catalog-img">
                <img class="catalog-product-photo" src="${imageSrc}" data-fallback-src="${fallbackSrc}" alt="${safeName}" loading="lazy" decoding="async" width="720" height="720">
                <div class="catalog-device catalog-device-fallback" aria-hidden="true" hidden></div>
              </div>
              <h2 class="catalog-name">${safeName}</h2>
            </article>
          </a>
        </div>
      `;
    }

    function bindCatalogImageFallbacks() {
      bindImageFallbacks(catalogGrid);
      catalogGrid.querySelectorAll('.catalog-product-photo').forEach((img) => {
        if (img.dataset.catalogFallbackBound === 'true') return;
        img.dataset.catalogFallbackBound = 'true';
        img.addEventListener('error', () => {
          if (img.dataset.fallbackApplied !== 'true') return;
          const imageBox = img.closest('.catalog-img');
          const fallbackDevice = imageBox ? imageBox.querySelector('.catalog-device-fallback') : null;
          if (imageBox) imageBox.classList.add('is-fallback');
          if (fallbackDevice) fallbackDevice.removeAttribute('hidden');
        });
      });
    }

    function renderProductCards(filteredProducts, page) {
      const productsToShow = getProductsByPage(filteredProducts, page);

      if (!productsToShow.length) {
        catalogGrid.innerHTML = '';
        return;
      }

      catalogGrid.innerHTML = productsToShow.map(createProductCardHTML).join('');
      bindCatalogImageFallbacks();
    }

    function renderPagination(totalPages, totalProducts) {
      productPagination.innerHTML = '';
      if (totalProducts === 0) return;

      const createButton = (content, label, disabled, onClick, pageNumber) => {
        const button = document.createElement('button');
        button.className = `page-btn${pageNumber === currentProductPage ? ' active' : ''}`;
        button.type = 'button';
        button.setAttribute('aria-label', label);
        button.dataset.pageDisabled = disabled ? 'true' : 'false';
        button.disabled = disabled || isCatalogLoading;
        button.innerHTML = content;
        button.addEventListener('click', onClick);
        return button;
      };

      productPagination.appendChild(createButton('<i class="bi bi-arrow-left"></i>', 'Halaman sebelumnya', currentProductPage === 1, () => {
        if (currentProductPage > 1) loadCatalogPage(currentProductPage - 1, true);
      }));

      for (let page = 1; page <= totalPages; page += 1) {
        productPagination.appendChild(createButton(String(page), `Halaman ${page}`, false, () => {
          if (page !== currentProductPage) loadCatalogPage(page, true);
        }, page));
      }

      productPagination.appendChild(createButton('<i class="bi bi-arrow-right"></i>', 'Halaman berikutnya', currentProductPage === totalPages, () => {
        if (currentProductPage < totalPages) loadCatalogPage(currentProductPage + 1, true);
      }));
    }

    function renderCatalog(page) {
      const filteredProducts = getFilteredProducts();
      const totalPages = Math.max(1, Math.ceil(filteredProducts.length / productsPerPage));
      currentProductPage = Math.min(Math.max(page, 1), totalPages);

      renderProductCards(filteredProducts, currentProductPage);

      if (emptyProductState) {
        emptyProductState.classList.toggle('show', filteredProducts.length === 0);
      }

      renderPagination(totalPages, filteredProducts.length);
    }

    function loadCatalogPage(page, usePreloader) {
      if (isCatalogLoading) return;

      if (!usePreloader) {
        renderCatalog(page);
        return;
      }

      setCatalogLoading(true);
      showCatalogPreloader();

      window.setTimeout(() => {
        renderCatalog(page);
        setCatalogLoading(false);
      }, 220);
    }

    function renderBrandFilters(brands) {
      if (!filterButtonsWrap || !Array.isArray(brands) || !brands.length) return;

      const uniqueBrands = Array.from(new Set(brands.filter(Boolean)));
      filterButtonsWrap.innerHTML = [
        '<button class="filter-pill active" data-filter-brand="all" type="button">Semua</button>',
        ...uniqueBrands.map((brand) => `<button class="filter-pill" data-filter-brand="${escapeHTML(brand)}" type="button">${escapeHTML(brand)}</button>`)
      ].join('');

      filterButtonsWrap.querySelectorAll('[data-filter-brand]').forEach((button) => {
        button.addEventListener('click', () => {
          if (isCatalogLoading) return;
          activeBrand = button.dataset.filterBrand || 'all';
          currentProductPage = 1;
          filterButtonsWrap.querySelectorAll('[data-filter-brand]').forEach((btn) => btn.classList.remove('active'));
          button.classList.add('active');
          loadCatalogPage(1, true);
        });
      });
    }

    async function loadProductsFromDatabase() {
      const initialDataNode = document.getElementById('initialCatalogData');
      if (initialDataNode) {
        try {
          const initialPayload = JSON.parse(initialDataNode.textContent || '{}');
          if (Array.isArray(initialPayload.products) && initialPayload.products.length) {
            products = initialPayload.products;
            renderBrandFilters(initialPayload.brands || products.map((product) => product.brand));
            renderCatalog(1);
            return;
          }
        } catch (error) {
          console.warn('Data awal katalog tidak valid:', error);
        }
      }

      setCatalogLoading(true);
      showCatalogPreloader();

      try {
        const response = await fetch(`${siteBaseUrl}/api/products.php`, {
          headers: { 'Accept': 'application/json' },
          cache: 'no-store'
        });

        if (!response.ok) {
          throw new Error('HTTP ' + response.status);
        }

        const payload = await response.json();

        if (!payload.success || !Array.isArray(payload.products)) {
          throw new Error(payload.message || 'Data produk tidak valid.');
        }

        products = payload.products;
        renderBrandFilters(payload.brands || products.map((product) => product.brand));
        renderCatalog(1);
      } catch (error) {
        console.warn('Produk katalog tidak bisa dimuat dari database:', error);
        products = [];
        catalogGrid.innerHTML = `
          <div class="empty-state show">Produk belum bisa dimuat dari database. Pastikan database aktif, file SQL sudah diimport, dan endpoint api/products.php dapat diakses.</div>
        `;
        productPagination.innerHTML = '';
      } finally {
        setCatalogLoading(false);
      }
    }

    if (productSearch) {
      productSearch.addEventListener('input', () => {
        window.clearTimeout(searchDebounceTimer);
        searchDebounceTimer = window.setTimeout(() => {
          currentProductPage = 1;
          loadCatalogPage(1, true);
        }, 220);
      });
    }

    loadProductsFromDatabase();
  }

  function initProductDetailGallery() {
    const mainProductImage = document.getElementById('mainProductImage');
    const mainPhotoButton = document.getElementById('mainPhotoButton');
    const productThumbs = document.getElementById('productThumbs');
    const modalProductImage = document.getElementById('modalProductImage');
    const modalProductTitle = document.getElementById('productImageModalTitle');
    const modalProductPrev = document.getElementById('modalProductPrev');
    const modalProductNext = document.getElementById('modalProductNext');
    const modalProductCounter = document.getElementById('modalProductCounter');
    const detailProductName = document.getElementById('detailProductName');
    const productImageModalElement = document.getElementById('productImageModal');
    const detailContent = document.querySelector('.product-detail-content');

    if (!mainProductImage || !mainPhotoButton || !productThumbs || !detailProductName) return;

    const searchParams = new URLSearchParams(window.location.search);
    const productId = searchParams.get('id');
    const productSlugParam = searchParams.get('slug') || (() => {
      const path = window.location.pathname;
      const filename = (path.split('/').filter(Boolean).pop() || '').toLowerCase();

      // Format baru: /produk-nama-produk
      if (filename.startsWith('produk-') && filename !== 'produk-detail' && filename !== 'produk.php') {
        return filename.replace(/^produk-/, '');
      }

      // Kompatibilitas format sebelumnya: /produk-detail/nama-produk
      const parts = path.split('/').filter(Boolean);
      const detailIndex = parts.indexOf('produk-detail');
      return detailIndex >= 0 ? parts[detailIndex + 1] : '';
    })();
    const fallbackImage = normalizePublicAssetUrl('produk/1.png');
    let productPhotos = [{ alt: 'Foto produk', src: fallbackImage }];
    let activeProductPhotoIndex = 0;
    let productImageModalInstance = null;

    function escapeHTML(value) {
      return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    }

    function stripHTML(value) {
      const div = document.createElement('div');
      div.innerHTML = value || '';
      return div.textContent || div.innerText || '';
    }

    function getProductPriceNumber(value) {
      const normalizedValue = String(value ?? '')
        .replace(/[^0-9,.-]/g, '')
        .replace(/\./g, '')
        .replace(',', '.');
      const number = Number(normalizedValue || 0);
      return Number.isFinite(number) ? number : 0;
    }

    function hasValidProductPrice(value) {
      return getProductPriceNumber(value) > 0;
    }

    function formatPrice(value) {
      const number = getProductPriceNumber(value);
      return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0
      }).format(number);
    }

    function normalizePhotoIndex(index) {
      if (!productPhotos.length) return 0;
      return (index + productPhotos.length) % productPhotos.length;
    }

    function getCurrentProductTitle() {
      return document.getElementById('detailProductName')?.textContent?.trim() || 'Detail Produk';
    }

    function updateModalProductPhoto(index) {
      if (!modalProductImage) return;

      activeProductPhotoIndex = normalizePhotoIndex(index);
      const selectedPhoto = productPhotos[activeProductPhotoIndex] || productPhotos[0];
      const titleText = getCurrentProductTitle();
      const hasMultiplePhotos = productPhotos.length > 1;

      if (modalProductTitle) modalProductTitle.textContent = titleText;
      if (modalProductPrev) modalProductPrev.classList.toggle('is-hidden', !hasMultiplePhotos);
      if (modalProductNext) modalProductNext.classList.toggle('is-hidden', !hasMultiplePhotos);
      if (modalProductCounter) {
        modalProductCounter.textContent = hasMultiplePhotos
          ? `${activeProductPhotoIndex + 1} / ${productPhotos.length}`
          : '';
      }

      modalProductImage.style.opacity = '0';
      window.setTimeout(() => {
        modalProductImage.src = selectedPhoto.src;
        modalProductImage.alt = `${titleText} - ${selectedPhoto.alt}`;
        modalProductImage.style.opacity = '1';
      }, 90);
    }

    function setActiveProductPhoto(index, syncModal = true) {
      activeProductPhotoIndex = normalizePhotoIndex(index);
      const selectedPhoto = productPhotos[activeProductPhotoIndex] || productPhotos[0];
      mainProductImage.style.opacity = '0';
      setTimeout(() => {
        mainProductImage.src = selectedPhoto.src;
        mainProductImage.alt = selectedPhoto.alt;
        mainProductImage.style.opacity = '1';
      }, 120);

      document.querySelectorAll('.thumb-btn').forEach((button, buttonIndex) => {
        button.classList.toggle('active', buttonIndex === activeProductPhotoIndex);
        button.setAttribute('aria-selected', buttonIndex === activeProductPhotoIndex ? 'true' : 'false');
      });

      if (syncModal && productImageModalElement?.classList.contains('show')) {
        updateModalProductPhoto(activeProductPhotoIndex);
      }
    }

    function moveProductPhoto(direction) {
      setActiveProductPhoto(activeProductPhotoIndex + direction, false);
      updateModalProductPhoto(activeProductPhotoIndex);
    }

    function renderProductThumbs() {
      productThumbs.innerHTML = '';
      productPhotos.forEach((photo, index) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = `thumb-btn${index === 0 ? ' active' : ''}`;
        button.setAttribute('aria-label', `Lihat ${photo.alt}`);
        button.setAttribute('aria-selected', index === 0 ? 'true' : 'false');
        button.innerHTML = `<img src="${escapeHTML(photo.src)}" alt="${escapeHTML(photo.alt)}" width="720" height="720" loading="lazy" decoding="async">`;
        button.addEventListener('click', () => setActiveProductPhoto(index));
        productThumbs.appendChild(button);
      });
      setActiveProductPhoto(0);
    }

    function syncDetailPriceWidth() {
      const priceCard = detailContent?.querySelector('.detail-price-card');
      const backButton = detailContent?.querySelector('.detail-action-btn.secondary');

      if (!priceCard || !backButton) return;

      if (window.matchMedia('(min-width: 768px)').matches) {
        window.requestAnimationFrame(() => {
          const buttonWidth = Math.ceil(backButton.getBoundingClientRect().width);
          if (buttonWidth > 0) {
            priceCard.style.width = `${buttonWidth}px`;
          }
        });
      } else {
        priceCard.style.removeProperty('width');
      }
    }

    function renderProductDetail(product) {
      const plainDescription = stripHTML(product.description || '');
      const descriptionHTML = product.description
        ? product.description
        : '<p>Deskripsi produk belum tersedia.</p>';

      document.title = `${product.name} - Detail Produk Teakwave`;

      const canonical = document.getElementById('canonicalProductUrl');
      if (canonical && product.slug) {
        canonical.href = `${window.location.origin}${window.location.pathname.split(/\/produk(?:-detail)?/)[0]}/produk-${product.slug}`;
      }
      detailProductName.textContent = product.name;

      if (detailContent) {
        const shouldShowPrice = hasValidProductPrice(product.price);
        const productPriceHTML = shouldShowPrice ? `
          <div class="detail-price-card" aria-label="Harga produk">
            <span class="detail-price-label">Harga</span>
            <strong class="detail-price-value">${escapeHTML(formatPrice(product.price))}</strong>
            <span class="detail-price-note">Cek stok dan penawaran terbaru via WhatsApp.</span>
          </div>
        ` : '';

        detailContent.innerHTML = `
          <h1 id="detailProductName">${escapeHTML(product.name)}</h1>
          <div class="detail-divider"></div>
          <div class="product-description-from-db">${descriptionHTML}</div>
          <div class="detail-subtitle">Spesifikasi Singkat</div>
          <ul>
            <li>Brand: ${escapeHTML(product.brand || '-')}</li>
            <li>Kategori: ${escapeHTML(product.category || '-')}</li>
            <li>SKU: ${escapeHTML(product.sku || '-')}</li>
            <li>Stok: ${escapeHTML(product.stock ?? '-')}</li>
          </ul>
          ${productPriceHTML}
          <div class="detail-actions">
            <a class="detail-action-btn primary" data-external-url="whatsapp" data-whatsapp-message="${escapeHTML('Halo, saya tertarik dengan produk ' + product.name)}" href="${escapeHTML(buildWhatsappUrl('Halo, saya tertarik dengan produk ' + product.name))}" rel="noopener" target="_blank"><i class="bi bi-whatsapp"></i> Tanya via WhatsApp</a>
            <a class="detail-action-btn secondary" href="produk"><i class="bi bi-grid"></i> Kembali ke Produk</a>
          </div>
        `;
        if (shouldShowPrice) syncDetailPriceWidth();
      }

      const images = Array.isArray(product.images) && product.images.length ? product.images : [product.image || fallbackImage];
      productPhotos = images.map((src, index) => ({
        src: src || fallbackImage,
        alt: `${product.name} foto ${index + 1}`
      }));

      renderProductThumbs();
    }

    async function loadProductDetail() {
      const initialDataNode = document.getElementById('initialProductData');
      if (initialDataNode) {
        try {
          const initialProduct = JSON.parse(initialDataNode.textContent || '{}');
          if (initialProduct && initialProduct.name) {
            renderProductDetail(initialProduct);
            return;
          }
        } catch (error) {
          console.warn('Data awal detail produk tidak valid:', error);
        }
      }

      if (!productId && !productSlugParam) {
        renderProductThumbs();
        return;
      }

      try {
        const detailUrl = productId
          ? `${siteBaseUrl}/api/products.php?mode=detail&id=${encodeURIComponent(productId)}`
          : `${siteBaseUrl}/api/products.php?mode=detail&slug=${encodeURIComponent(productSlugParam)}`;

        const response = await fetch(detailUrl, {
          headers: { 'Accept': 'application/json' },
          cache: 'no-store'
        });

        if (!response.ok) {
          throw new Error('HTTP ' + response.status);
        }

        const payload = await response.json();

        if (!payload.success || !payload.product) {
          throw new Error(payload.message || 'Produk tidak ditemukan.');
        }

        renderProductDetail(payload.product);
      } catch (error) {
        console.error(error);
        if (detailContent) {
          detailContent.innerHTML = `
            <h1>Produk tidak ditemukan</h1>
            <div class="detail-divider"></div>
            <p>Produk belum bisa dimuat dari database. Pastikan database dashboard aktif dan produk berstatus active.</p>
            <div class="detail-actions">
              <a class="detail-action-btn secondary" href="produk"><i class="bi bi-grid"></i> Kembali ke Produk</a>
            </div>
          `;
        }
        renderProductThumbs();
      }
    }

    mainPhotoButton.addEventListener('click', () => {
      if (!productImageModalElement || !window.bootstrap || !modalProductImage) return;
      productImageModalInstance = productImageModalInstance || new bootstrap.Modal(productImageModalElement);
      updateModalProductPhoto(activeProductPhotoIndex);
      productImageModalInstance.show();
    });

    modalProductPrev?.addEventListener('click', () => moveProductPhoto(-1));
    modalProductNext?.addEventListener('click', () => moveProductPhoto(1));

    productImageModalElement?.addEventListener('keydown', (event) => {
      if (productPhotos.length <= 1) return;
      if (event.key === 'ArrowLeft') {
        event.preventDefault();
        moveProductPhoto(-1);
      }
      if (event.key === 'ArrowRight') {
        event.preventDefault();
        moveProductPhoto(1);
      }
    });

    window.addEventListener('resize', syncDetailPriceWidth, { passive: true });

    loadProductDetail();
  }

})();
