(function(){
  if (window.__tgconsultAdminNotifyBooted) {
    return;
  }

  var cfg = window.TGCONSULT_ADMIN_NOTIFY || null;
  if (!cfg || !cfg.poll_url || !cfg.chats_url) {
    return;
  }

  window.__tgconsultAdminNotifyBooted = true;

  var knownChats = {};
  var initialized = false;
  var pollBusy = false;
  var toastSerial = 0;
  var toastWrap = null;
  var bootTs = Math.floor(Date.now() / 1000);
  var freshWindow = 15;

  function parseTs(value) {
    var ts = parseInt(value, 10);
    return (!isNaN(ts) && ts > 0) ? ts : 0;
  }

  function escapeHtml(value) {
    return String(value == null ? '' : value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function isDialogsPage() {
    try {
      var url = new URL(window.location.href);
      return url.searchParams.get('plugin') === 'tgconsult' && url.searchParams.get('action') === 'chats';
    } catch (e) {
      return false;
    }
  }

  function chatsUrlWithId(chatId) {
    var separator = cfg.chats_url.indexOf('?') === -1 ? '?' : '&';
    return cfg.chats_url + separator + 'chat_id=' + encodeURIComponent(String(chatId || ''));
  }

  function ensureStyles() {
    if (document.getElementById('tgconsult-admin-notify-style')) {
      return;
    }

    var style = document.createElement('style');
    style.id = 'tgconsult-admin-notify-style';
    style.textContent = '' +
      '.tgconsult-admin-toast-wrap{position:fixed;top:20px;right:20px;z-index:2147483647;display:flex;flex-direction:column;gap:10px;max-height:calc(100vh - 40px);overflow:auto;pointer-events:none}' +
      '.tgconsult-admin-toast{min-width:280px;max-width:360px;padding:12px 14px;border-radius:10px;background:#c3ffa0;border:1px solid #d7e2f2;box-shadow:0 12px 30px rgba(0,0,0,.14);cursor:pointer;pointer-events:auto}' +
      '.tgconsult-admin-toast:hover{background:#f7fbff}' +
      '.tgconsult-admin-toast-title{font-size:13px;font-weight:700;color:#1f2d3d;margin-bottom:4px}' +
      '.tgconsult-admin-toast-text{font-size:12px;color:#516173;line-height:1.4;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}';
    document.head.appendChild(style);
  }

  function ensureToastWrap() {
    if (toastWrap && toastWrap.parentNode) {
      return toastWrap;
    }

    ensureStyles();
    toastWrap = document.createElement('div');
    toastWrap.id = 'tgconsult-admin-toast-wrap';
    toastWrap.className = 'tgconsult-admin-toast-wrap';
    document.body.appendChild(toastWrap);
    return toastWrap;
  }

  function removeToast(toastId) {
    var key = String(toastId || '');
    if (!key) {
      return;
    }

    var node = document.querySelector('.tgconsult-admin-toast[data-toast-id="' + key + '"]');
    if (node && node.parentNode) {
      node.parentNode.removeChild(node);
    }
  }

  function showToast(chat) {
    var chatId = parseInt(chat && chat.id, 10) || 0;
    if (!chatId || isDialogsPage()) {
      return;
    }

    var wrap = ensureToastWrap();
    toastSerial += 1;
    var toastId = 'tgca-' + toastSerial;

    var node = document.createElement('div');
    node.className = 'tgconsult-admin-toast';
    node.setAttribute('data-toast-id', toastId);
    node.innerHTML = '' +
      '<div class="tgconsult-admin-toast-title">Новое сообщение: ' + escapeHtml(chat.display_name || 'Гость') + '</div>' +
      '<div class="tgconsult-admin-toast-text">' + escapeHtml(chat.notify_text || chat.last_text || 'Открыть диалог') + '</div>';
    node.addEventListener('click', function(){
      removeToast(toastId);
      window.location.href = chatsUrlWithId(chatId);
    });

    wrap.appendChild(node);
  }

  function updateMenuBadge(chats) {
    var item = document.getElementById('tgconsult-oldui-menu');
    if (!item) {
      return;
    }

    var link = item.querySelector('a');
    if (!link) {
      return;
    }

    var count = 0;
    for (var i = 0; i < chats.length; i++) {
      if (chats[i] && chats[i].unread) {
        count += 1;
      }
    }

    var badge = link.querySelector('.indicator.red');
    if (count <= 0) {
      if (badge && badge.parentNode) {
        badge.parentNode.removeChild(badge);
      }
      return;
    }

    if (!badge) {
      badge = document.createElement('span');
      badge.className = 'indicator red';
      link.appendChild(document.createTextNode(' '));
      link.appendChild(badge);
    }

    badge.textContent = String(count);
  }

  function processChats(chats) {
    if (!Array.isArray(chats)) {
      return;
    }

    updateMenuBadge(chats);

    var nextKnown = {};
    var allowToast = initialized && !isDialogsPage();

    for (var i = 0; i < chats.length; i++) {
      var chat = chats[i] || {};
      var id = parseInt(chat.id, 10) || 0;
      if (!id) {
        continue;
      }

      var ts = parseTs(chat.notify_created_ts || chat.last_created_ts);
      var notifyId = parseInt(chat.notify_message_id, 10) || 0;
      var unread = !!chat.unread;
      var prev = knownChats[id] || null;
      var isFreshInitialMessage = !initialized && unread && ts >= (bootTs - freshWindow);
      var hasNewVisitorMessage = unread && notifyId > (prev ? (prev.notifyId || 0) : 0);

      if (allowToast && hasNewVisitorMessage) {
        showToast(chat);
      } else if (isFreshInitialMessage) {
        showToast(chat);
      }

      nextKnown[id] = {
        ts: ts,
        notifyId: notifyId,
        unread: unread
      };
    }

    knownChats = nextKnown;
    initialized = true;
  }

  function scheduleNext() {
    window.setTimeout(poll, 5000);
  }

  function poll() {
    if (pollBusy) {
      scheduleNext();
      return;
    }

    pollBusy = true;
    var xhr = new XMLHttpRequest();
    var separator = cfg.poll_url.indexOf('?') === -1 ? '?' : '&';
    xhr.open('GET', cfg.poll_url + separator + '_=' + Date.now(), true);
    xhr.onreadystatechange = function(){
      if (xhr.readyState !== 4) {
        return;
      }

      pollBusy = false;
      if (xhr.status >= 200 && xhr.status < 300) {
        try {
          var response = JSON.parse(xhr.responseText || '{}');
          if (response && response.status === 'ok') {
            processChats(response.chats || []);
          }
        } catch (e) {}
      }
      scheduleNext();
    };
    xhr.onerror = function(){
      pollBusy = false;
      scheduleNext();
    };
    xhr.send(null);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', poll);
  } else {
    poll();
  }
})();
