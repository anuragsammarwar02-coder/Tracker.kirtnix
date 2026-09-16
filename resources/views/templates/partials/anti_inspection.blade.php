{{-- Reusable Public Landing Page UX Protection & Anti-Inspection Deterrence --}}
<style>
  /* Public Landing Page Anti-Copy & Text Selection Deterrence */
  body.kx-public-protected,
  body.kx-public-protected *:not(input):not(textarea):not(select):not([contenteditable="true"]) {
    -webkit-user-select: none !important;
    -moz-user-select: none !important;
    -ms-user-select: none !important;
    user-select: none !important;
    -webkit-touch-callout: none !important;
  }
  body.kx-public-protected input,
  body.kx-public-protected textarea,
  body.kx-public-protected select,
  body.kx-public-protected [contenteditable="true"] {
    -webkit-user-select: auto !important;
    -moz-user-select: auto !important;
    -ms-user-select: auto !important;
    user-select: auto !important;
  }
</style>

<script>
(function() {
  'use strict';

  // 1. Add protection class to body
  if (document.body) {
    document.body.classList.add('kx-public-protected');
  } else {
    document.addEventListener('DOMContentLoaded', function() {
      document.body.classList.add('kx-public-protected');
    });
  }

  // Helper to determine if an event target is an interactive form element
  function isFormInput(target) {
    if (!target) return false;
    var tag = (target.tagName || '').toUpperCase();
    return tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT' || target.isContentEditable;
  }

  // 2. Disable Context Menu (Right-Click) on Public Landing Page
  document.addEventListener('contextmenu', function(e) {
    e.preventDefault();
    return false;
  }, { capture: true, passive: false });

  // 3. Disable DevTools / View Source Keyboard Shortcuts
  document.addEventListener('keydown', function(e) {
    var isMac = navigator.platform.toUpperCase().indexOf('MAC') >= 0;
    var ctrlOrCmd = isMac ? e.metaKey : e.ctrlKey;
    var altOrOpt = e.altKey;
    var shift = e.shiftKey;
    var code = e.keyCode || e.which;
    var key = (e.key || '').toUpperCase();

    // Check for F12
    if (code === 123 || key === 'F12') {
      e.preventDefault();
      e.stopPropagation();
      return false;
    }

    // Check for Ctrl+Shift+I / Cmd+Option+I (Inspect DevTools)
    if ((ctrlOrCmd && shift && (code === 73 || key === 'I')) || (isMac && ctrlOrCmd && altOrOpt && (code === 73 || key === 'I'))) {
      e.preventDefault();
      e.stopPropagation();
      return false;
    }

    // Check for Ctrl+Shift+J / Cmd+Option+J (DevTools Console)
    if ((ctrlOrCmd && shift && (code === 74 || key === 'J')) || (isMac && ctrlOrCmd && altOrOpt && (code === 74 || key === 'J'))) {
      e.preventDefault();
      e.stopPropagation();
      return false;
    }

    // Check for Ctrl+Shift+C / Cmd+Option+C (Inspect Element)
    if ((ctrlOrCmd && shift && (code === 67 || key === 'C')) || (isMac && ctrlOrCmd && altOrOpt && (code === 67 || key === 'C'))) {
      e.preventDefault();
      e.stopPropagation();
      return false;
    }

    // Check for Ctrl+U / Cmd+U (View Page Source)
    if (ctrlOrCmd && (code === 85 || key === 'U')) {
      e.preventDefault();
      e.stopPropagation();
      return false;
    }

    // Check for Ctrl+S / Cmd+S (Save Webpage)
    if (ctrlOrCmd && (code === 83 || key === 'S')) {
      e.preventDefault();
      e.stopPropagation();
      return false;
    }

    // Check for Ctrl+P / Cmd+P (Print Page)
    if (ctrlOrCmd && (code === 80 || key === 'P')) {
      e.preventDefault();
      e.stopPropagation();
      return false;
    }
  }, { capture: true, passive: false });

  // 4. Disable Copy / Cut / Drag on Non-Input Elements
  document.addEventListener('copy', function(e) {
    if (!isFormInput(e.target)) {
      e.preventDefault();
      return false;
    }
  }, { capture: true, passive: false });

  document.addEventListener('cut', function(e) {
    if (!isFormInput(e.target)) {
      e.preventDefault();
      return false;
    }
  }, { capture: true, passive: false });

  document.addEventListener('dragstart', function(e) {
    var tag = (e.target && e.target.tagName) ? e.target.tagName.toUpperCase() : '';
    if (tag === 'IMG' || tag === 'A') {
      e.preventDefault();
      return false;
    }
  }, { capture: true, passive: false });

})();
</script>
