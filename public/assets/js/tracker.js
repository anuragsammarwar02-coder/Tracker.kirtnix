/**
 * KIRTNIX UNIVERSAL DETERMINISTIC TRACKER (kx.js / tracker.js)
 * High-Speed Visitor Tracking, Auto Meta Pixel (Browser & CAPI) & Dynamic Bot Invites
 * (c) Kirtnix Performance Agency
 */
(function(window, document) {
  'use strict';

  var currentScript = document.currentScript || (function() {
    var scripts = document.getElementsByTagName('script');
    for (var i = 0; i < scripts.length; i++) {
      if (scripts[i].src && (scripts[i].src.indexOf('tracker.js') !== -1 || scripts[i].src.indexOf('kx.js') !== -1)) {
        return scripts[i];
      }
    }
    return null;
  })();

  var scriptEndpoint = currentScript ? (currentScript.getAttribute('data-endpoint') || '') : '';
  var scriptSlug = currentScript ? (currentScript.getAttribute('data-kx-lp') || currentScript.getAttribute('data-lp') || currentScript.getAttribute('data-page') || '') : '';
  
  if (!scriptSlug && currentScript && currentScript.src) {
    var match = currentScript.src.match(/[?&]lp=([^&#]*)/);
    if (match) scriptSlug = decodeURIComponent(match[1]);
  }
  
  var scriptClient = currentScript ? (currentScript.getAttribute('data-client') || '') : '';

  // Extract explicit pixel ID from script tag attributes or query params
  var scriptPixelId = currentScript ? (
    currentScript.getAttribute('data-pixel') || 
    currentScript.getAttribute('data-pixel-id') || 
    currentScript.getAttribute('data-meta-pixel') || ''
  ) : '';

  if (!scriptPixelId && currentScript && currentScript.src) {
    var pixelMatch = currentScript.src.match(/[?&](?:pixel|pixel_id|meta_pixel)=([^&#]*)/);
    if (pixelMatch) scriptPixelId = decodeURIComponent(pixelMatch[1]);
  }

  if (!scriptPixelId && window.__KX_CONFIG__ && window.__KX_CONFIG__.meta_pixel_id) {
    scriptPixelId = window.__KX_CONFIG__.meta_pixel_id;
  }

  var baseUrl = scriptEndpoint.replace(/\/+$/, '') || (function() {
    if (currentScript && currentScript.src) {
      try {
        var urlObj = new URL(currentScript.src);
        return urlObj.origin;
      } catch(e) {}
    }
    return window.location.origin;
  })();

  function getCookie(name) {
    var match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
    return match ? decodeURIComponent(match[2]) : null;
  }

  function setCookie(name, value, days) {
    var expires = '';
    if (days) {
      var d = new Date();
      d.setTime(d.getTime() + (days * 24 * 60 * 60 * 1000));
      expires = '; expires=' + d.toUTCString();
    }
    document.cookie = name + '=' + encodeURIComponent(value) + expires + '; path=/; SameSite=Lax';
  }

  function getVisitorId() {
    var key = '_kx_vid';
    var vid = getCookie(key);
    if (!vid) {
      try {
        vid = localStorage.getItem(key);
      } catch(e) {}
    }

    if (!vid || vid.length < 10) {
      vid = 'kx_' + Date.now().toString(36) + '_' + Math.random().toString(36).substr(2, 9);
    }

    setCookie(key, vid, 365);
    try {
      localStorage.setItem(key, vid);
    } catch(e) {}

    return vid;
  }

  function getUrlParams() {
    var params = {};
    var search = window.location.search.substring(1);
    if (!search) return params;
    var pairs = search.split('&');
    for (var i = 0; i < pairs.length; i++) {
      var pair = pairs[i].split('=');
      if (pair[0]) {
        params[decodeURIComponent(pair[0])] = decodeURIComponent(pair[1] || '');
      }
    }
    return params;
  }

  // ==========================================
  // DYNAMIC CLIENT-SIDE META PIXEL INJECTOR
  // ==========================================
  function initMetaPixel(pixelId) {
    if (!pixelId) return;
    pixelId = String(pixelId).trim();
    if (!pixelId || pixelId === 'null' || pixelId === 'undefined') return;

    if (window._kx_meta_pixel_initialized === pixelId) return;
    window._kx_meta_pixel_initialized = pixelId;

    if (!window.fbq) {
      (function(f, b, e, v, n, t, s) {
        if (f.fbq) return;
        n = f.fbq = function() {
          n.callMethod ? n.callMethod.apply(n, arguments) : n.queue.push(arguments);
        };
        if (!f._fbq) f._fbq = n;
        n.push = n;
        n.loaded = !0;
        n.version = '2.0';
        n.queue = [];
        t = b.createElement(e);
        t.async = !0;
        t.src = v;
        s = b.getElementsByTagName(e)[0];
        s.parentNode.insertBefore(t, s);
      })(window, document, 'script', 'https://connect.facebook.net/en_US/fbevents.js');
    }

    try {
      window.fbq('init', pixelId);
      window.fbq('track', 'PageView');
      console.log('[Kirtnix] Meta Pixel ' + pixelId + ' initialized & PageView fired successfully.');
    } catch(e) {
      console.warn('[Kirtnix] Meta Pixel initialization note:', e);
    }
  }

  // Immediately initialize Meta Pixel if Pixel ID is already available
  if (scriptPixelId) {
    initMetaPixel(scriptPixelId);
  }

  var visitorId = getVisitorId();
  var urlParams = getUrlParams();
  var trackingSessionId = null;
  var targetDeepLink = null;
  var targetWebUrl = null;

  function trackPageView(callback) {
    var slug = scriptSlug || window.location.pathname.replace(/^\/lp\//, '').replace(/^\//, '') || 'default';
    var payload = {
      slug: slug,
      visitor_id: visitorId,
      referrer: document.referrer || '',
      url: window.location.href,
      utm_source: urlParams.utm_source || 'direct',
      utm_medium: urlParams.utm_medium || '',
      utm_campaign: urlParams.utm_campaign || '',
      utm_content: urlParams.utm_content || '',
      utm_term: urlParams.utm_term || '',
      fbclid: urlParams.fbclid || '',
      fbc: getCookie('_fbc') || (urlParams.fbclid ? ('fb.1.' + Date.now() + '.' + urlParams.fbclid) : null),
      fbp: getCookie('_fbp') || null,
      client_kx: scriptClient
    };

    var xhr = new XMLHttpRequest();
    xhr.open('POST', baseUrl + '/api/track/view', true);
    xhr.setRequestHeader('Content-Type', 'application/json');
    xhr.onreadystatechange = function() {
      if (xhr.readyState === 4 && xhr.status === 200) {
        try {
          var res = JSON.parse(xhr.responseText);
          if (res.session_id) {
            trackingSessionId = res.session_id;
            window._kx_session_id = trackingSessionId;
          }
          // Dynamically load Meta Pixel if returned by server configuration
          if (res.meta_pixel_id) {
            initMetaPixel(res.meta_pixel_id);
          }
          if (typeof callback === 'function') callback(res);
        } catch(e) {}
      }
    };
    xhr.send(JSON.stringify(payload));
  }

  function requestUniqueInvite() {
    var slug = scriptSlug || window.location.pathname.replace(/^\/lp\//, '').replace(/^\//, '') || 'default';
    var payload = {
      slug: slug,
      visitor_id: visitorId,
      session_id: trackingSessionId
    };

    var xhr = new XMLHttpRequest();
    xhr.open('POST', baseUrl + '/api/track/invite', true);
    xhr.setRequestHeader('Content-Type', 'application/json');
    xhr.onreadystatechange = function() {
      if (xhr.readyState === 4 && xhr.status === 200) {
        try {
          var res = JSON.parse(xhr.responseText);
          if (res.deep_link) {
            targetDeepLink = res.deep_link;
          }
          if (res.web_url) {
            targetWebUrl = res.web_url;
          }

          var ctas = document.querySelectorAll('[data-kx-cta], a[href*="t.me"], .kx-cta-button, .telegram-join-btn');
          for (var i = 0; i < ctas.length; i++) {
            ctas[i].classList.remove('kx-loading', 'opacity-50', 'pointer-events-none');
            if (targetDeepLink && ctas[i].tagName === 'A') {
              ctas[i].setAttribute('data-deep-link', targetDeepLink);
              ctas[i].setAttribute('data-web-url', targetWebUrl);
            }
          }
        } catch(e) {}
      }
    };
    xhr.send(JSON.stringify(payload));
  }

  function handleCtaClick(e, el) {
    if (typeof window.fbq === 'function') {
      try {
        window.fbq('track', 'Lead', {
          content_name: el.innerText || 'Telegram CTA',
          content_category: 'Telegram',
          source: 'kirtnix_tracker',
          visitor_id: visitorId
        });
        window.fbq('trackCustom', 'TelegramClick', {
          button_text: el.innerText || 'Join Telegram',
          destination: targetWebUrl || el.getAttribute('href')
        });
      } catch(err) {}
    }

    var slug = scriptSlug || window.location.pathname.replace(/^\/lp\//, '').replace(/^\//, '');
    var clickPayload = {
      slug: slug,
      visitor_id: visitorId,
      session_id: trackingSessionId,
      destination_url: targetWebUrl || el.getAttribute('href') || 'https://t.me',
      button_text: el.innerText || 'Join Telegram',
      utm_source: urlParams.utm_source || 'direct',
      utm_campaign: urlParams.utm_campaign || ''
    };

    if (navigator.sendBeacon) {
      var blob = new Blob([JSON.stringify(clickPayload)], { type: 'application/json' });
      navigator.sendBeacon(baseUrl + '/api/track/click', blob);
    } else {
      var clickXhr = new XMLHttpRequest();
      clickXhr.open('POST', baseUrl + '/api/track/click', true);
      clickXhr.setRequestHeader('Content-Type', 'application/json');
      clickXhr.send(JSON.stringify(clickPayload));
    }

    var deepLink = el.getAttribute('data-deep-link') || targetDeepLink;
    var webUrl = el.getAttribute('data-web-url') || targetWebUrl || el.getAttribute('href');

    var isMobile = /iPhone|iPad|iPod|Android/i.test(navigator.userAgent);

    if (isMobile && deepLink) {
      e.preventDefault();
      var now = Date.now();
      window.location.href = deepLink;

      setTimeout(function() {
        if (Date.now() - now < 2000) {
          window.location.href = webUrl;
        }
      }, 1200);
    }
  }

  function bindCtaClicks() {
    var ctaElements = document.querySelectorAll('[data-kx-cta], a[href*="t.me"], .kx-cta-button, .telegram-join-btn');
    for (var i = 0; i < ctaElements.length; i++) {
      (function(el) {
        el.addEventListener('click', function(e) {
          handleCtaClick(e, el);
        });
      })(ctaElements[i]);
    }
  }

  function init() {
    trackPageView(function() {
      requestUniqueInvite();
    });
    bindCtaClicks();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  window.KirtnixTracker = {
    visitorId: visitorId,
    trackView: trackPageView,
    requestInvite: requestUniqueInvite,
    bindCtas: bindCtaClicks,
    initMetaPixel: initMetaPixel
  };
})(window, document);
