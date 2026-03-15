(function(){
  if (window.__tgconsultCustomerHistoryBooted) {
    return;
  }

  var cfg = window.TGCONSULT_CUSTOMER_HISTORY || null;
  if (!cfg || !cfg.endpoint) {
    return;
  }

  window.__tgconsultCustomerHistoryBooted = true;

  var state = {
    customerId: 0,
    customerName: '',
    messages: []
  };
  var ui = null;

  function escapeHtml(value) {
    return String(value == null ? '' : value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function ensureStyles() {
    if (document.getElementById('tgc-customer-history-style')) {
      return;
    }

    var style = document.createElement('style');
    style.id = 'tgc-customer-history-style';
    style.textContent = '' +
      'body.tgc-customer-history-lock{overflow:hidden !important;}' +
      '.tgc-ch-modal{position:fixed;inset:0;display:none;align-items:center;justify-content:center;padding:24px;background:rgba(15,23,42,.36);z-index:2147483647;}' +
      '.tgc-ch-modal.is-open{display:flex;}' +
      '.tgc-ch-dialog{width:min(980px,calc(100vw - 48px));max-height:calc(100vh - 48px);display:flex;flex-direction:column;background:#fff;border-radius:18px;box-shadow:0 24px 64px rgba(15,23,42,.24);overflow:hidden;}' +
      '.tgc-ch-head{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:18px 20px;border-bottom:1px solid #e5e7eb;}' +
      '.tgc-ch-title{font-size:22px;font-weight:700;line-height:1.2;color:#1f2937;}' +
      '.tgc-ch-subtitle{margin-top:4px;font-size:12px;color:#6b7280;}' +
      '.tgc-ch-close{border:0;background:transparent;font-size:28px;line-height:1;cursor:pointer;color:#6b7280;padding:0 4px;}' +
      '.tgc-ch-toolbar{display:flex;gap:12px;padding:14px 20px;border-bottom:1px solid #e5e7eb;flex-wrap:wrap;background:#f8fafc;}' +
      '.tgc-ch-field{display:flex;flex-direction:column;gap:6px;min-width:180px;}' +
      '.tgc-ch-field.wide{flex:1 1 280px;}' +
      '.tgc-ch-label{font-size:12px;font-weight:600;color:#475569;}' +
      '.tgc-ch-input{width:100%;height:38px;padding:0 12px;border:1px solid #d1d5db;border-radius:10px;background:#fff;box-sizing:border-box;}' +
      '.tgc-ch-body{flex:1;overflow:auto;padding:20px;background:#eef3f9;}' +
      '.tgc-ch-loading,.tgc-ch-empty{display:flex;align-items:center;justify-content:center;min-height:240px;color:#64748b;font-size:14px;}' +
      '.tgc-ch-divider{margin:8px auto 14px;padding:6px 10px;border-radius:999px;background:#dbeafe;color:#1d4ed8;font-size:12px;font-weight:700;}' +
      '.tgc-ch-row{display:flex;margin:0 0 12px;}' +
      '.tgc-ch-row.is-manager{justify-content:flex-end;}' +
      '.tgc-ch-row.is-visitor{justify-content:flex-start;}' +
      '.tgc-ch-bubble{max-width:min(78%,720px);padding:12px 14px;border-radius:16px;background:#fff;color:#111827;box-shadow:0 2px 10px rgba(15,23,42,.06);}' +
      '.tgc-ch-row.is-manager .tgc-ch-bubble{background:#d1f5d3;color:#0b3d0b;}' +
      '.tgc-ch-meta{display:flex;gap:8px;align-items:baseline;justify-content:space-between;margin-bottom:6px;font-size:12px;color:#64748b;}' +
      '.tgc-ch-row.is-manager .tgc-ch-meta{color:#276b27;}' +
      '.tgc-ch-author{font-weight:700;}' +
      '.tgc-ch-time{white-space:nowrap;opacity:.85;}' +
      '.tgc-ch-text{white-space:pre-wrap;word-break:break-word;line-height:1.45;}' +
      '@media (max-width: 760px){' +
      '.tgc-ch-modal{padding:12px;}' +
      '.tgc-ch-dialog{width:calc(100vw - 24px);max-height:calc(100vh - 24px);border-radius:14px;}' +
      '.tgc-ch-head,.tgc-ch-toolbar,.tgc-ch-body{padding-left:14px;padding-right:14px;}' +
      '.tgc-ch-bubble{max-width:92%;}' +
      '}';
    document.head.appendChild(style);
  }

  function ensureUi() {
    if (ui) {
      return ui;
    }

    ensureStyles();

    var modal = document.createElement('div');
    modal.className = 'tgc-ch-modal';
    modal.innerHTML = '' +
      '<div class="tgc-ch-dialog" role="dialog" aria-modal="true" aria-label="История сообщений">' +
        '<div class="tgc-ch-head">' +
          '<div>' +
            '<div class="tgc-ch-title">История сообщений</div>' +
            '<div class="tgc-ch-subtitle" id="tgc-ch-subtitle">Загрузка…</div>' +
          '</div>' +
          '<button type="button" class="tgc-ch-close js-tgc-ch-close" aria-label="Закрыть">&times;</button>' +
        '</div>' +
        '<div class="tgc-ch-toolbar">' +
          '<label class="tgc-ch-field">' +
            '<span class="tgc-ch-label">Дата</span>' +
            '<input type="date" class="tgc-ch-input" id="tgc-ch-date">' +
          '</label>' +
          '<label class="tgc-ch-field wide">' +
            '<span class="tgc-ch-label">Поиск по сообщениям</span>' +
            '<input type="search" class="tgc-ch-input" id="tgc-ch-search" placeholder="Введите текст сообщения">' +
          '</label>' +
        '</div>' +
        '<div class="tgc-ch-body" id="tgc-ch-body"><div class="tgc-ch-loading">Загрузка истории…</div></div>' +
      '</div>';

    document.body.appendChild(modal);

    ui = {
      modal: modal,
      subtitle: modal.querySelector('#tgc-ch-subtitle'),
      date: modal.querySelector('#tgc-ch-date'),
      search: modal.querySelector('#tgc-ch-search'),
      body: modal.querySelector('#tgc-ch-body')
    };

    modal.addEventListener('click', function(event){
      if (event.target === modal || event.target.closest('.js-tgc-ch-close')) {
        closeModal();
      }
    });

    ui.date.addEventListener('input', renderMessages);
    ui.search.addEventListener('input', renderMessages);
    document.addEventListener('keydown', function(event){
      if (event.key === 'Escape' && ui && ui.modal.classList.contains('is-open')) {
        closeModal();
      }
    });

    return ui;
  }

  function openModal() {
    var refs = ensureUi();
    refs.modal.classList.add('is-open');
    document.body.classList.add('tgc-customer-history-lock');
  }

  function closeModal() {
    if (!ui) {
      return;
    }
    ui.modal.classList.remove('is-open');
    document.body.classList.remove('tgc-customer-history-lock');
  }

  function setLoading(message) {
    var refs = ensureUi();
    refs.subtitle.textContent = message || 'Загрузка…';
    refs.body.innerHTML = '<div class="tgc-ch-loading">Загрузка истории…</div>';
  }

  function setError(message) {
    var refs = ensureUi();
    refs.subtitle.textContent = state.customerName ? ('Покупатель: ' + state.customerName) : 'История сообщений';
    refs.body.innerHTML = '<div class="tgc-ch-empty">' + escapeHtml(message || 'Не удалось загрузить историю сообщений') + '</div>';
  }

  function renderMessages() {
    var refs = ensureUi();
    var dateFilter = refs.date.value || '';
    var query = (refs.search.value || '').trim().toLowerCase();
    var filtered = [];

    for (var i = 0; i < state.messages.length; i++) {
      var message = state.messages[i] || {};
      if (dateFilter && message.created_date !== dateFilter) {
        continue;
      }

      if (query) {
        var haystack = ((message.text || '') + ' ' + (message.author_name || '')).toLowerCase();
        if (haystack.indexOf(query) === -1) {
          continue;
        }
      }

      filtered.push(message);
    }

    refs.subtitle.textContent = state.customerName
      ? ('Покупатель: ' + state.customerName + ' | Сообщений: ' + filtered.length)
      : ('Сообщений: ' + filtered.length);

    if (!filtered.length) {
      refs.body.innerHTML = '<div class="tgc-ch-empty">Сообщения не найдены</div>';
      return;
    }

    var html = '';
    var lastChatId = null;
    for (var j = 0; j < filtered.length; j++) {
      var item = filtered[j];
      if (lastChatId !== item.chat_id) {
        html += '<div class="tgc-ch-divider">' + escapeHtml(item.chat_label || ('Диалог #' + item.chat_id)) + '</div>';
        lastChatId = item.chat_id;
      }

      html += '' +
        '<div class="tgc-ch-row ' + (item.sender === 'manager' ? 'is-manager' : 'is-visitor') + '">' +
          '<div class="tgc-ch-bubble">' +
            '<div class="tgc-ch-meta">' +
              '<span class="tgc-ch-author">' + escapeHtml(item.author_name || '') + '</span>' +
              '<span class="tgc-ch-time">' + escapeHtml(item.created || '') + '</span>' +
            '</div>' +
            '<div class="tgc-ch-text">' + escapeHtml(item.text || '') + '</div>' +
          '</div>' +
        '</div>';
    }

    refs.body.innerHTML = html;
    refs.body.scrollTop = 0;
  }

  function loadHistory(customerId) {
    setLoading('Загрузка истории…');

    var xhr = new XMLHttpRequest();
    var separator = cfg.endpoint.indexOf('?') === -1 ? '?' : '&';
    xhr.open('GET', cfg.endpoint + separator + 'customer_id=' + encodeURIComponent(String(customerId)) + '&_=' + Date.now(), true);
    xhr.onreadystatechange = function(){
      if (xhr.readyState !== 4) {
        return;
      }

      if (xhr.status < 200 || xhr.status >= 300) {
        setError('Не удалось загрузить историю сообщений');
        return;
      }

      try {
        var response = JSON.parse(xhr.responseText || '{}');
        var payload = (response && response.data) ? response.data : response;
        var status = (response && response.status) ? response.status : ((payload && payload.status) ? payload.status : 'ok');
        if (!payload || status !== 'ok') {
          setError((response && response.errors && response.errors[0]) || (payload && payload.errors && payload.errors[0]) || (response && response.error) || (payload && payload.error) || 'Не удалось загрузить историю сообщений');
          return;
        }

        state.customerId = customerId;
        state.customerName = payload.customer_name || '';
        state.messages = Array.isArray(payload.messages) ? payload.messages : [];
        renderMessages();
      } catch (e) {
        setError('Некорректный ответ сервера');
      }
    };
    xhr.onerror = function(){
      setError('Ошибка сети при загрузке истории сообщений');
    };
    xhr.send(null);
  }

  document.addEventListener('click', function(event){
    var trigger = event.target.closest('.js-tgc-customer-history-link');
    if (!trigger) {
      return;
    }

    event.preventDefault();
    var customerId = parseInt(trigger.getAttribute('data-customer-id'), 10) || 0;
    if (!customerId) {
      return;
    }

    var refs = ensureUi();
    refs.date.value = '';
    refs.search.value = '';
    openModal();
    loadHistory(customerId);
  });
})();
