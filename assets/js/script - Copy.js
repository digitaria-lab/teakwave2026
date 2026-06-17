/* Teakwave shared JavaScript
   Berlaku untuk index.html, produk.html, produk-detail.html, profil.html, dan kontak.html. */
(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    initRevealAnimation();
    initFloatingActions();
    initNavbarActiveState();
    initProductCatalog();
    initProductDetailGallery();
  });

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

  function initProductCatalog() {
    const catalogGrid = document.getElementById('catalogGrid');
    const productPagination = document.getElementById('productPagination');
    const productSearch = document.getElementById('productSearch');
    const emptyProductState = document.getElementById('emptyProductState');
    const filterButtons = document.querySelectorAll('[data-filter-brand]');
    const catalogGridWrap = catalogGrid ? catalogGrid.closest('.catalog-grid-wrap') : null;

    if (!catalogGrid || !productPagination) return;

    // Product metadata disimpan ringan di JavaScript, tetapi DOM card tidak dibuat semuanya saat awal load.
    // Saat halaman dibuka pertama kali, hanya 10 produk halaman pertama yang dirender.
    // Produk halaman berikutnya baru dibuat ketika tombol pagination diklik.
    const products = [
      { id: 1, brand: 'Ubiquiti', name: 'UniFi U6 Lite Access Point', shape: 'round' },
      { id: 2, brand: 'Ubiquiti', name: 'UniFi U6 Plus Indoor AP', shape: 'round' },
      { id: 3, brand: 'Ubiquiti', name: 'UniFi U7 Pro WiFi AP', shape: 'round' },
      { id: 4, brand: 'Ubiquiti', name: 'UniFi Dream Router', shape: 'box' },
      { id: 5, brand: 'Ubiquiti', name: 'UniFi Cloud Gateway Ultra', shape: 'box' },
      { id: 6, brand: 'Ubiquiti', name: 'UniFi Switch Lite 8 PoE', shape: 'box' },
      { id: 7, brand: 'Ubiquiti', name: 'UniFi Switch 24 PoE', shape: 'box' },
      { id: 8, brand: 'Ubiquiti', name: 'EdgeRouter X Gigabit', shape: 'box' },
      { id: 9, brand: 'Ubiquiti', name: 'LiteBeam 5AC Gen2', shape: 'round' },
      { id: 10, brand: 'Ubiquiti', name: 'NanoBeam 5AC Bridge', shape: 'round' },
      { id: 11, brand: 'Ubiquiti', name: 'PowerBeam 5AC ISO', shape: 'round' },
      { id: 12, brand: 'Ubiquiti', name: 'Rocket Prism AC Radio', shape: 'box' },
      { id: 13, brand: 'Ubiquiti', name: 'airMAX Sector Antenna', shape: 'round' },
      { id: 14, brand: 'Ubiquiti', name: 'UniFi Protect G5 Dome', shape: 'round' },
      { id: 15, brand: 'Ubiquiti', name: 'UniFi Flex Mini Switch', shape: 'box' },
      { id: 16, brand: 'V-SOL', name: 'V-SOL GPON ONU 1GE', shape: 'box' },
      { id: 17, brand: 'V-SOL', name: 'V-SOL ONU WiFi AC1200', shape: 'box' },
      { id: 18, brand: 'V-SOL', name: 'V-SOL HG323AC Dual Band', shape: 'box' },
      { id: 19, brand: 'V-SOL', name: 'V-SOL V2802RH Optical Unit', shape: 'box' },
      { id: 20, brand: 'V-SOL', name: 'V-SOL V2801SG Mini ONU', shape: 'box' },
      { id: 21, brand: 'V-SOL', name: 'V-SOL OLT 4 Port GPON', shape: 'box' },
      { id: 22, brand: 'V-SOL', name: 'V-SOL OLT 8 Port GPON', shape: 'box' },
      { id: 23, brand: 'V-SOL', name: 'V-SOL OLT 16 Port Rack', shape: 'box' },
      { id: 24, brand: 'V-SOL', name: 'V-SOL XPON Router WiFi', shape: 'box' },
      { id: 25, brand: 'V-SOL', name: 'V-SOL Fiber ONT Voice', shape: 'box' },
      { id: 26, brand: 'V-SOL', name: 'V-SOL PoE ONU Outdoor', shape: 'box' },
      { id: 27, brand: 'V-SOL', name: 'V-SOL CATV Optical Node', shape: 'round' },
      { id: 28, brand: 'V-SOL', name: 'V-SOL SFP GPON Module', shape: 'box' },
      { id: 29, brand: 'V-SOL', name: 'V-SOL Optical Splitter 1:8', shape: 'box' },
      { id: 30, brand: 'Mikrotik', name: 'MikroTik hAP ax2 Router', shape: 'box' },
      { id: 31, brand: 'Mikrotik', name: 'MikroTik hAP ax3 WiFi 6', shape: 'box' },
      { id: 32, brand: 'Mikrotik', name: 'MikroTik RB750Gr3 hEX', shape: 'box' },
      { id: 33, brand: 'Mikrotik', name: 'MikroTik RB5009 Router', shape: 'box' },
      { id: 34, brand: 'Mikrotik', name: 'MikroTik CRS326 Switch', shape: 'box' },
      { id: 35, brand: 'Mikrotik', name: 'MikroTik CSS610 8G Switch', shape: 'box' },
      { id: 36, brand: 'Mikrotik', name: 'MikroTik cAP ax Ceiling', shape: 'round' },
      { id: 37, brand: 'Mikrotik', name: 'MikroTik SXT LTE Kit', shape: 'round' },
      { id: 38, brand: 'Mikrotik', name: 'MikroTik LHG 5 Antenna', shape: 'round' },
      { id: 39, brand: 'Mikrotik', name: 'MikroTik mANTBox 15s', shape: 'round' },
      { id: 40, brand: 'Mikrotik', name: 'MikroTik NetMetal ac²', shape: 'box' },
      { id: 41, brand: 'Mikrotik', name: 'MikroTik wAP ac Outdoor', shape: 'round' },
      { id: 42, brand: 'Mikrotik', name: 'MikroTik RouterBOARD PoE', shape: 'box' },
      { id: 43, brand: 'VOL.TECH', name: 'VOL.TECH GPON ONT 1GE', shape: 'box' },
      { id: 44, brand: 'VOL.TECH', name: 'VOL.TECH ONT Dual Band', shape: 'box' },
      { id: 45, brand: 'VOL.TECH', name: 'VOL.TECH OLT 4 Port', shape: 'box' },
      { id: 46, brand: 'VOL.TECH', name: 'VOL.TECH OLT 8 Port', shape: 'box' },
      { id: 47, brand: 'VOL.TECH', name: 'VOL.TECH PoE Switch 8 Port', shape: 'box' },
      { id: 48, brand: 'VOL.TECH', name: 'VOL.TECH Gigabit Switch 16', shape: 'box' },
      { id: 49, brand: 'VOL.TECH', name: 'VOL.TECH Media Converter', shape: 'box' },
      { id: 50, brand: 'VOL.TECH', name: 'VOL.TECH SFP Module 1.25G', shape: 'box' },
      { id: 51, brand: 'VOL.TECH', name: 'VOL.TECH Fiber Patch Cord', shape: 'box' },
      { id: 52, brand: 'VOL.TECH', name: 'VOL.TECH Optical Splitter 1:16', shape: 'box' },
      { id: 53, brand: 'VOL.TECH', name: 'VOL.TECH Outdoor CPE AC', shape: 'round' },
      { id: 54, brand: 'VOL.TECH', name: 'VOL.TECH Access Point Pro', shape: 'round' },
      { id: 55, brand: 'VOL.TECH', name: 'VOL.TECH Rackmount PDU', shape: 'box' }
    ];

    const productsPerPage = 10;
    const pageCache = new Map();
    let activeBrand = 'all';
    let currentProductPage = 1;
    let isCatalogLoading = false;
    let searchDebounceTimer = null;

    function getSearchKeyword() {
      return productSearch ? productSearch.value.trim().toLowerCase() : '';
    }

    function getFilteredProducts() {
      const keyword = getSearchKeyword();
      return products.filter((product) => {
        const matchedBrand = activeBrand === 'all' || product.brand === activeBrand;
        const matchedKeyword = !keyword || `${product.brand} ${product.name}`.toLowerCase().includes(keyword);
        return matchedBrand && matchedKeyword;
      });
    }

    function getCatalogCacheKey(page, filteredProducts) {
      const ids = filteredProducts.map((product) => product.id).join('-');
      return `${activeBrand}|${getSearchKeyword()}|${page}|${ids}`;
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

    function showCatalogPreloader() {
      catalogGrid.innerHTML = `
        <div class="catalog-preloader" role="status" aria-live="polite">
          <span class="catalog-spinner" aria-hidden="true"></span>
          <span>Memuat produk...</span>
        </div>
      `;
    }

    function createProductCardHTML(product) {
      const deviceClass = product.shape === 'round' ? 'catalog-device round' : 'catalog-device';
      return `
        <div data-product-card data-brand="${product.brand}" data-name="${product.name}">
          <a class="catalog-link" href="produk-detail.html?id=${product.id}" aria-label="Lihat detail ${product.name}">
            <article class="catalog-card">
              <div class="catalog-img"><div class="${deviceClass}" aria-hidden="true"></div></div>
              <div class="catalog-name">${product.name}</div>
            </article>
          </a>
        </div>
      `;
    }

    function getProductsByPage(filteredProducts, page) {
      const startIndex = (page - 1) * productsPerPage;
      return filteredProducts.slice(startIndex, startIndex + productsPerPage);
    }

    function renderProductCards(filteredProducts, page) {
      const cacheKey = getCatalogCacheKey(page, filteredProducts);
      const productsToShow = getProductsByPage(filteredProducts, page);

      if (!productsToShow.length) {
        catalogGrid.innerHTML = '';
        return;
      }

      if (!pageCache.has(cacheKey)) {
        pageCache.set(cacheKey, productsToShow.map(createProductCardHTML).join(''));
      }

      catalogGrid.innerHTML = pageCache.get(cacheKey);
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
      }, 380);
    }

    filterButtons.forEach((button) => {
      button.addEventListener('click', () => {
        if (isCatalogLoading) return;
        activeBrand = button.dataset.filterBrand || 'all';
        currentProductPage = 1;
        filterButtons.forEach((btn) => btn.classList.remove('active'));
        button.classList.add('active');
        loadCatalogPage(1, true);
      });
    });

    if (productSearch) {
      productSearch.addEventListener('input', () => {
        window.clearTimeout(searchDebounceTimer);
        searchDebounceTimer = window.setTimeout(() => {
          currentProductPage = 1;
          loadCatalogPage(1, true);
        }, 220);
      });
    }

    // Initial load hanya halaman pertama, tanpa membuat 55 product card sekaligus.
    loadCatalogPage(1, false);
  }

  function initProductDetailGallery() {
    const mainProductImage = document.getElementById('mainProductImage');
    const mainPhotoButton = document.getElementById('mainPhotoButton');
    const productThumbs = document.getElementById('productThumbs');
    const modalProductImage = document.getElementById('modalProductImage');
    const modalProductTitle = document.getElementById('productImageModalTitle');
    const detailProductName = document.getElementById('detailProductName');
    const productImageModalElement = document.getElementById('productImageModal');

    if (!mainProductImage || !mainPhotoButton || !productThumbs || !modalProductImage || !productImageModalElement || !window.bootstrap) return;

    const productImageModal = new bootstrap.Modal(productImageModalElement);

    function svgToDataUri(svg) {
      return 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(svg);
    }

    const productPhotos = [
      {
        alt: 'UCG-Ultra tampak utama',
        src: svgToDataUri(`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 620 460">
          <defs>
            <linearGradient id="bg" x1="0" x2="1" y1="0" y2="1"><stop stop-color="#fff"/><stop offset="1" stop-color="#f3f5f9"/></linearGradient>
            <linearGradient id="body" x1="0" x2="1" y1="0" y2="1"><stop stop-color="#ffffff"/><stop offset="1" stop-color="#e9edf4"/></linearGradient>
            <filter id="shadow" x="-20%" y="-20%" width="140%" height="150%"><feDropShadow dx="0" dy="28" stdDeviation="18" flood-color="#0b1b35" flood-opacity=".18"/></filter>
          </defs>
          <rect width="620" height="460" fill="url(#bg)"/>
          <path d="M120 250 L320 145 L502 246 L300 352 Z" fill="url(#body)" filter="url(#shadow)"/>
          <path d="M120 250 L300 352 L300 388 L120 284 Z" fill="#dfe5ef"/>
          <path d="M502 246 L300 352 L300 388 L502 281 Z" fill="#eef2f7"/>
          <path d="M150 270 L181 287 L181 304 L150 286 Z" fill="#073fbc"/>
          <path d="M162 275 L181 286 L181 296 L162 286 Z" fill="#0b2b72"/>
          <circle cx="313" cy="250" r="17" fill="#e8edf6"/>
          <path d="M313 237a13 13 0 0 1 0 26" fill="none" stroke="#b7c1cf" stroke-width="5" stroke-linecap="round"/>
          <path d="M313 242a8 8 0 0 1 0 16" fill="none" stroke="#d6dce6" stroke-width="5" stroke-linecap="round"/>
        </svg>`)
      },
      {
        alt: 'UCG-Ultra port ethernet',
        src: svgToDataUri(`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 620 460">
          <defs>
            <linearGradient id="bg" x1="0" x2="1" y1="0" y2="1"><stop stop-color="#fff"/><stop offset="1" stop-color="#f2f4f8"/></linearGradient>
            <linearGradient id="side" x1="0" x2="1"><stop stop-color="#e9edf4"/><stop offset="1" stop-color="#d7dee9"/></linearGradient>
            <filter id="shadow" x="-20%" y="-20%" width="140%" height="140%"><feDropShadow dx="0" dy="22" stdDeviation="18" flood-color="#0b1b35" flood-opacity=".15"/></filter>
          </defs>
          <rect width="620" height="460" fill="url(#bg)"/>
          <path d="M94 218 L342 108 L530 214 L279 333 Z" fill="#fff" filter="url(#shadow)"/>
          <path d="M94 218 L279 333 L279 382 L94 267 Z" fill="url(#side)"/>
          <path d="M279 333 L530 214 L530 262 L279 382 Z" fill="#f1f4f9"/>
          <rect x="148" y="244" width="44" height="26" rx="4" transform="rotate(29 170 257)" fill="#0a3fba"/>
          <rect x="208" y="276" width="42" height="24" rx="4" transform="rotate(29 229 288)" fill="#151b28" opacity=".25"/>
          <rect x="268" y="307" width="42" height="24" rx="4" transform="rotate(29 289 319)" fill="#151b28" opacity=".2"/>
          <circle cx="338" cy="218" r="17" fill="#e6ebf3"/>
          <path d="M338 206a13 13 0 0 1 0 25" fill="none" stroke="#c9d1de" stroke-width="5" stroke-linecap="round"/>
        </svg>`)
      },
      {
        alt: 'UCG-Ultra sisi depan',
        src: svgToDataUri(`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 620 460">
          <defs>
            <linearGradient id="bg" x1="0" x2="1" y1="0" y2="1"><stop stop-color="#fff"/><stop offset="1" stop-color="#f6f7fa"/></linearGradient>
            <filter id="shadow" x="-20%" y="-20%" width="140%" height="140%"><feDropShadow dx="0" dy="18" stdDeviation="15" flood-color="#0b1b35" flood-opacity=".15"/></filter>
          </defs>
          <rect width="620" height="460" fill="url(#bg)"/>
          <rect x="105" y="190" width="410" height="94" rx="16" fill="#eef2f7" filter="url(#shadow)"/>
          <rect x="126" y="205" width="368" height="18" rx="9" fill="#fff" opacity=".85"/>
          <rect x="154" y="242" width="56" height="30" rx="5" fill="#0647d8"/>
          <rect x="226" y="243" width="42" height="27" rx="5" fill="#1f2937" opacity=".28"/>
          <rect x="282" y="243" width="42" height="27" rx="5" fill="#1f2937" opacity=".28"/>
          <circle cx="406" cy="256" r="8" fill="#9be078"/>
        </svg>`)
      }
    ];

    function setActiveProductPhoto(index) {
      const selectedPhoto = productPhotos[index] || productPhotos[0];
      mainProductImage.style.opacity = '0';
      setTimeout(() => {
        mainProductImage.src = selectedPhoto.src;
        mainProductImage.alt = selectedPhoto.alt;
        mainProductImage.style.opacity = '1';
      }, 120);

      document.querySelectorAll('.thumb-btn').forEach((button, buttonIndex) => {
        button.classList.toggle('active', buttonIndex === index);
        button.setAttribute('aria-selected', buttonIndex === index ? 'true' : 'false');
      });
    }

    function renderProductThumbs() {
      productThumbs.innerHTML = '';
      productPhotos.forEach((photo, index) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = `thumb-btn${index === 0 ? ' active' : ''}`;
        button.setAttribute('aria-label', `Lihat ${photo.alt}`);
        button.setAttribute('aria-selected', index === 0 ? 'true' : 'false');
        button.innerHTML = `<img src="${photo.src}" alt="${photo.alt}">`;
        button.addEventListener('click', () => setActiveProductPhoto(index));
        productThumbs.appendChild(button);
      });
      setActiveProductPhoto(0);
    }

    mainPhotoButton.addEventListener('click', () => {
      const titleText = detailProductName?.textContent?.trim() || 'Detail Produk';
      if (modalProductTitle) modalProductTitle.textContent = titleText;
      modalProductImage.src = mainProductImage.src;
      modalProductImage.alt = `${titleText} - ${mainProductImage.alt}`;
      productImageModal.show();
    });

    renderProductThumbs();
  }
})();
