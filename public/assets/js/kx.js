/**
 * KIRTNIX UNIVERSAL DETERMINISTIC TRACKER (kx.js)
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

  function getCtaElements() {
    return document.querySelectorAll(
      '[data-kx-cta], [data-kx-cta="1"], [data-cta], a[href*="t.me"], button[data-kx-cta], .kx-cta-button, .telegram-join-btn, #btn-join-telegram, #btn-join-telegram-alt'
    );
  }

  function applyInviteToCtas(webUrl, deepLink) {
    var ctas = getCtaElements();
    for (var i = 0; i < ctas.length; i++) {
      var el = ctas[i];
      el.classList.remove('kx-loading', 'opacity-50', 'pointer-events-none');
      if (deepLink) {
        el.setAttribute('data-deep-link', deepLink);
      }
      if (webUrl) {
        el.setAttribute('data-web-url', webUrl);
        if (el.tagName === 'A') {
          el.href = webUrl;
        }
      } else {
        var fallback = el.getAttribute('data-kx-fallback') || el.getAttribute('data-fallback');
        if (fallback && el.tagName === 'A' && (el.getAttribute('href') === '#' || !el.getAttribute('href'))) {
          el.href = fallback;
        }
      }
    }
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
      if (xhr.readyState === 4) {
        if (xhr.status === 200) {
          try {
            var res = JSON.parse(xhr.responseText);
            if (res.deep_link) {
              targetDeepLink = res.deep_link;
            }
            if (res.web_url) {
              targetWebUrl = res.web_url;
            }
            applyInviteToCtas(targetWebUrl, targetDeepLink);
          } catch(e) {
            applyInviteToCtas(null, null);
          }
        } else {
          applyInviteToCtas(null, null);
        }
      }
    };
    xhr.send(JSON.stringify(payload));
  }

  function handleCtaClick(e, el) {
    var fallbackUrl = el.getAttribute('data-kx-fallback') || el.getAttribute('data-fallback');
    var rawHref = el.getAttribute('href');
    var currentHref = (rawHref && rawHref !== '#') ? rawHref : null;
    
    // Priority: 1. Kirtnix Tracked Web URL -> 2. Existing valid href -> 3. Fallback URL -> 4. Generic https://t.me
    var webUrl = el.getAttribute('data-web-url') || targetWebUrl || currentHref || fallbackUrl || 'https://t.me';
    var deepLink = el.getAttribute('data-deep-link') || targetDeepLink;
    
    if (!deepLink && webUrl) {
      if (webUrl.indexOf('t.me/+') !== -1) {
        deepLink = 'tg://join?invite=' + webUrl.split('t.me/+')[1];
      } else if (webUrl.indexOf('t.me/') !== -1) {
        deepLink = 'tg://resolve?domain=' + webUrl.split('t.me/')[1];
      }
    }

    var clickEventId = 'cta_' + Math.random().toString(36).substring(2, 10) + '_' + Date.now();

    if (typeof window.fbq === 'function') {
      try {
        // Track CTA Click as funnel event (TelegramClick & Lead for optimization) - NEVER Subscribe (Subscribe is only for actual Telegram join)
        window.fbq('trackCustom', 'TelegramClick', {
          button_text: el.innerText ? el.innerText.trim() : 'Join Telegram',
          destination: webUrl,
          visitor_id: visitorId
        }, { eventID: clickEventId });
        window.fbq('track', 'Lead', {
          content_name: el.innerText ? el.innerText.trim() : 'Telegram CTA',
          content_category: 'Telegram',
          source: 'kirtnix_tracker',
          visitor_id: visitorId
        }, { eventID: clickEventId });
      } catch(err) {}
    }

    var slug = scriptSlug || window.location.pathname.replace(/^\/lp\//, '').replace(/^\//, '');
    var clickPayload = {
      slug: slug,
      visitor_id: visitorId,
      session_id: trackingSessionId,
      destination_url: webUrl,
      event_id: clickEventId,
      button_text: el.innerText ? el.innerText.trim() : 'Join Telegram',
      utm_source: urlParams.utm_source || 'direct',
      utm_campaign: urlParams.utm_campaign || ''
    };

    var payloadStr = JSON.stringify(clickPayload);

    // Guaranteed click delivery via modern fetch keepalive + sendBeacon fallback
    if (window.fetch) {
      try {
        fetch(baseUrl + '/api/track/click', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: payloadStr,
          keepalive: true
        }).catch(function() {});
      } catch(fetchErr) {}
    }

    if (navigator.sendBeacon) {
      try {
        var blob = new Blob([payloadStr], { type: 'application/json' });
        navigator.sendBeacon(baseUrl + '/api/track/click', blob);
      } catch(beaconErr) {}
    }

    e.preventDefault();

    var isMobile = /iPhone|iPad|iPod|Android/i.test(navigator.userAgent);
    var redirected = false;

    function doRedirect() {
      if (redirected) return;
      redirected = true;

      if (isMobile && deepLink) {
        var now = Date.now();
        window.location.href = deepLink;

        setTimeout(function() {
          if (Date.now() - now < 2500 && webUrl && webUrl !== '#') {
            window.location.href = webUrl;
          }
        }, 1200);
      } else if (webUrl && webUrl !== '#') {
        window.location.href = webUrl;
      }
    }

    // Allow 120ms for the browser network layer to dispatch keepalive fetch & pixel beacons
    setTimeout(doRedirect, 120);
  }

  function bindCtaClicks() {
    var ctaElements = getCtaElements();
    for (var i = 0; i < ctaElements.length; i++) {
      (function(el) {
        if (!el._kx_bound) {
          el._kx_bound = true;
          el.addEventListener('click', function(e) {
            handleCtaClick(e, el);
          });
        }
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
