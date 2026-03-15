(function () {
  const cfg = window.TGCONSULT_CFG;
  if (!cfg) return;
  if (window.__tgconsult_booted__) return;
  window.__tgconsult_booted__ = true;

  function esc(s) {
    const d = document.createElement('div');
    d.textContent = String(s == null ? '' : s);
    return d.innerHTML;
  }
  function getToken() {
    try { return localStorage.getItem('tgc_token') || ''; } catch (e) { return ''; }
  }
  function setToken(t) {
    if (!t) return;
    try { localStorage.setItem('tgc_token', t); } catch (e) {}
  }
  function toPx(v, fallback) {
    const n = parseInt(v, 10);
    if (isNaN(n) || n < 0) return fallback;
    return n;
  }
  function normalizePosition(raw) {
    const value = String(raw == null ? '' : raw).toLowerCase().trim();
    return (
      value === 'left' ||
      value === 'l' ||
      value === '1' ||
      value === '0' ||
      value === 'true' ||
      value === 'yes' ||
      value === 'on' ||
      value.indexOf('left') !== -1
    ) ? 'left' : 'right';
  }

  let blinkTimer = null;
  const savedTitle = document.title;
  function startBlink() {
    if (blinkTimer) return;
    let on = false;
    blinkTimer = setInterval(function () {
      document.title = on ? 'Новое сообщение' : savedTitle;
      on = !on;
    }, 900);
  }
  function stopBlink() {
    if (!blinkTimer) return;
    clearInterval(blinkTimer);
    blinkTimer = null;
    document.title = savedTitle;
  }
  window.addEventListener('focus', stopBlink);

  function boot() {
    let root = document.getElementById('tgconsult-root');
    if (!root) {
      root = document.createElement('div');
      root.id = 'tgconsult-root';
      root.className = 'tgc-root';
      document.body.appendChild(root);
    }

    const rawPosition = String(
      (typeof cfg.position_raw !== 'undefined'
        ? cfg.position_raw
        : (typeof cfg.position !== 'undefined' ? cfg.position : cfg.widget_position)
      ) || ''
    ).toLowerCase().trim();
    let position = normalizePosition(rawPosition);
    let sideOffset = toPx(
      (typeof cfg.offset_side !== 'undefined' ? cfg.offset_side : cfg.widget_offset_side),
      22
    );
    let bottomOffset = toPx(
      (typeof cfg.offset_bottom !== 'undefined' ? cfg.offset_bottom : cfg.widget_offset_bottom),
      70
    );
    function applyPlacement(nextPosition, nextSideOffset, nextBottomOffset) {
      position = normalizePosition(nextPosition);
      sideOffset = toPx(nextSideOffset, sideOffset);
      bottomOffset = toPx(nextBottomOffset, bottomOffset);

      root.classList.remove('tgc-pos-left', 'tgc-pos-right');
      root.classList.add(position === 'left' ? 'tgc-pos-left' : 'tgc-pos-right');
      root.setAttribute('data-tgc-position', position);
      root.style.setProperty('--tgc-bottom', bottomOffset + 'px');
      root.style.setProperty('--tgc-side', sideOffset + 'px');
      root.style.setProperty('bottom', bottomOffset + 'px', 'important');
      if (position === 'left') {
        root.style.setProperty('left', sideOffset + 'px', 'important');
        root.style.setProperty('right', 'auto', 'important');
      } else {
        root.style.setProperty('right', sideOffset + 'px', 'important');
        root.style.setProperty('left', 'auto', 'important');
      }
    }

    root.style.setProperty('--tgc-color', cfg.icon_color || '#0D6EFD');
    applyPlacement(position, sideOffset, bottomOffset);

    const headLeft = cfg.manager_photo
      ? '<img class="tgc-ava" src="' + esc(cfg.manager_photo) + '" alt="">'
      : '';

    root.innerHTML =
      '<div class="tgc-button" id="tgc-btn" aria-label="Открыть чат" title="Открыть чат">' +
        '<svg viewBox="0 0 24 24" aria-hidden="true">' +
          '<path fill="currentColor" d="M6 3a3 3 0 0 0-3 3v8a3 3 0 0 0 3 3h1v3.6a.4.4 0 0 0 .68.28L11.57 17H18a3 3 0 0 0 3-3V6a3 3 0 0 0-3-3H6z"/>' +
        '</svg>' +
      '</div>' +
      '<div class="tgc-window" id="tgc-win" hidden>' +
        '<div class="tgc-head">' +
          headLeft +
          '<div class="tgc-title">' + esc(cfg.manager_name || 'Онлайн-консультант') + '</div>' +
          '<button class="tgc-close" id="tgc-close" type="button" aria-label="Закрыть">×</button>' +
        '</div>' +
        '<div class="tgc-body" id="tgc-body" role="log" aria-live="polite"></div>' +
        '<button class="tgc-scroll-down" id="tgc-scroll-down" type="button" title="К новым сообщениям" hidden>↓</button>' +
        '<form class="tgc-form" id="tgc-form" autocomplete="off">' +
          '<input type="text" name="website" class="tgc-hp" tabindex="-1" aria-hidden="true">' +
          '<input class="tgc-input" name="text" placeholder="Введите сообщение">' +
          '<button class="tgc-send" type="submit" title="Отправить">➤</button>' +
        '</form>' +
      '</div>';

    const btn = root.querySelector('#tgc-btn');
    const win = root.querySelector('#tgc-win');
    const body = root.querySelector('#tgc-body');
    const form = root.querySelector('#tgc-form');
    const close = root.querySelector('#tgc-close');
    const hp = form.querySelector('input[name="website"]');
    const down = root.querySelector('#tgc-scroll-down');

    function isNearBottom() {
      return (body.scrollHeight - body.clientHeight - body.scrollTop) < 72;
    }
    function updateDownButton() {
      down.hidden = isNearBottom();
    }
    function scrollToBottom() {
      body.scrollTop = body.scrollHeight;
      updateDownButton();
    }

    let welcomeShown = false;
    function showWelcome() {
      if (welcomeShown) return;
      renderMessage({
        sender: 'manager',
        text: cfg.welcome || 'Здравствуйте! Чем помочь?',
        author_name: cfg.manager_name || 'Менеджер'
      }, true);
      welcomeShown = true;
    }

    function renderMessage(m, forceScroll) {
      if (!m || !m.text) return;
      const shouldStick = forceScroll || isNearBottom();

      const bubble = document.createElement('div');
      bubble.className = 'tgc-msg ' + (m.sender === 'manager' ? 'tgc-msg-manager' : 'tgc-msg-visitor');
      if (m.sender === 'manager' && m.author_name) {
        const author = document.createElement('div');
        author.className = 'tgc-msg-author';
        author.textContent = m.author_name;
        bubble.appendChild(author);
      }
      const text = document.createElement('div');
      text.className = 'tgc-msg-text';
      text.textContent = m.text;
      bubble.appendChild(text);
      body.appendChild(bubble);

      if (shouldStick) {
        scrollToBottom();
      } else {
        updateDownButton();
      }
    }

    btn.addEventListener('click', function () {
      win.hidden = false;
      stopBlink();
      scrollToBottom();
    });
    close.addEventListener('click', function (e) {
      e.preventDefault();
      win.hidden = true;
      stopBlink();
    });
    body.addEventListener('scroll', updateDownButton);
    down.addEventListener('click', function () {
      scrollToBottom();
    });

    let last_id = 0;
    let initialized = false;

    function normalizeMessages(j) {
      if (!j) return [];
      if (Array.isArray(j.messages)) return j.messages;
      if (Array.isArray(j.items)) return j.items;
      return [];
    }

    async function poll() {
      try {
        const params = new URLSearchParams();
        params.set('after_id', String(last_id));
        const tok = getToken();
        if (tok) params.set('chat_token', tok);

        const url = cfg.load_url + (cfg.load_url.indexOf('?') > -1 ? '&' : '?') + params.toString();
        const r = await fetch(url, { credentials: 'same-origin' });
        const j = await r.json();

        if (j && j.chat_token) setToken(j.chat_token);
        if (j && j.widget) {
          const widget = j.widget || {};
          const serverRawPos = (typeof widget.position_raw !== 'undefined')
            ? widget.position_raw
            : (typeof widget.position !== 'undefined' ? widget.position : widget.widget_position);
          const serverSide = (typeof widget.offset_side !== 'undefined')
            ? widget.offset_side
            : widget.widget_offset_side;
          const serverBottom = (typeof widget.offset_bottom !== 'undefined')
            ? widget.offset_bottom
            : widget.widget_offset_bottom;
          applyPlacement(serverRawPos, serverSide, serverBottom);
        }

        const msgs = normalizeMessages(j);
        if (msgs.length) {
          const prev = last_id;
          let maxId = prev;
          let gotManagerNew = false;

          msgs.forEach(function (m) {
            const mid = parseInt(m.id, 10) || 0;
            if (mid > prev) {
              renderMessage(m, false);
              if (m.sender === 'manager') gotManagerNew = true;
              if (mid > maxId) maxId = mid;
            }
          });

          if (typeof j.last_id !== 'undefined') {
            const srvLast = parseInt(j.last_id, 10) || 0;
            if (srvLast > maxId) maxId = srvLast;
          }
          last_id = maxId;

          if (initialized && gotManagerNew) {
            if (win.hidden) win.hidden = false;
            startBlink();
          }
        } else {
          if (last_id === 0 && !welcomeShown) showWelcome();
          if (typeof j.last_id !== 'undefined') {
            const srvLast = parseInt(j.last_id, 10) || 0;
            if (srvLast > last_id) last_id = srvLast;
          }
        }
      } catch (e) {
      }

      if (!initialized) initialized = true;
      setTimeout(poll, 2500);
    }
    poll();

    form.addEventListener('submit', async function (e) {
      e.preventDefault();
      if (hp && (hp.value || '').trim() !== '') return;

      const text = (form.text.value || '').trim();
      if (!text) return;
      form.text.value = '';

      try {
        const tok = getToken();
        const payload = 'text=' + encodeURIComponent(text) + (tok ? '&chat_token=' + encodeURIComponent(tok) : '');
        const r = await fetch(cfg.send_url, {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
          credentials: 'same-origin',
          body: payload
        });
        const j = await r.json().catch(function () { return null; });
        if (j && j.chat_token) setToken(j.chat_token);
      } catch (e) {
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})();
