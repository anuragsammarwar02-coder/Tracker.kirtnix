/**
 * KIRTNIX UNIVERSAL DETERMINISTIC TRACKER (Vercel, Netlify & External Sites)
 * (c) Kirtnix Performance Agency
 *
 * Embed snippet:
 * <script 
 *   src="https://tracker.kirtnix.agency/assets/tracker.js" 
 *   data-lp="your-page-slug" 
 *   data-endpoint="https://tracker.kirtnix.agency" 
 *   async>
 * </script>
 */
(function(window, document) {
  'use strict';

  var currentScript = document.currentScript || (function() {
    var scripts = document.getElementsByTagName('script');
    for (var i = 0; i < scripts.length; i++) {
      if (scripts[i].src && scripts[i].src.indexOf('tracker.js') !== -1) {
        return scripts[i];
      }
    }
    return null;
  })();

  var scriptEndpoint = currentScript ? (currentScript.getAttribute('data-endpoint') || '') : '';
  var scriptSlug = currentScript ? (currentScript.getAttribute('data-lp') || currentScript.getAttribute('data-page') || '') : '';
  var scriptClient = currentScript ? (currentScript.getAttribute('data-client') || '') : '';

  var baseUrl = scriptEndpoint.replace(/\/+$/, '') || window.location.origin;

  // 1. Cookie & LocalStorage Helper
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

  // 2. Persistent Unique Visitor Identification (_kx_vid)
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

  // 3. Parse UTM, Meta, and tracking parameters
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

  var visitorId = getVisitorId();
  var urlParams = getUrlParams();
  var trackingSessionId = null;
  var targetDeepLink = null;
  var targetWebUrl = null;

  // 4. Send Landing Page View
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
          if (typeof callback === 'function') callback(res);
        } catch(e) {}
      }
    };
    xhr.send(JSON.stringify(payload));
  }

  // 5. Generate Unique Telegram Invite for this visitor session
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

          // Enable CTA buttons if disabled
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

  // 6. Handle CTA Click Interception & Smart Telegram Launch
  function handleCtaClick(e, el) {
    // Dispatch browser Meta Pixel Lead if present
    if (typeof window.fbq === 'function') {
      try {
        window.fbq('track', 'Lead', {
          content_name: el.innerText || 'Telegram CTA',
          source: 'kirtnix_tracker',
          visitor_id: visitorId
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

    // Asynchronously record CTA click in backend (separate from verified join)
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

    // Smart Device Deep-Link with Web fallback for iOS/Android & in-app browsers
    var isMobile = /iPhone|iPad|iPod|Android/i.test(navigator.userAgent);

    if (isMobile && deepLink) {
      e.preventDefault();
      var now = Date.now();
      window.location.href = deepLink;

      // If app does not open within 1.2s, fallback to web URL
      setTimeout(function() {
        if (Date.now() - now < 2000) {
          window.location.href = webUrl;
        }
      }, 1200);
    }
  }

  // 7. Bind All CTAs on Page
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

  // 8. Lifecycle Initialization
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
    bindCtas: bindCtaClicks
  };
})(window, document);
