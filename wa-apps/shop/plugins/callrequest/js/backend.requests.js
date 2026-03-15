(function(){
  var CSRF = (function(){
    var el = document.querySelector('#cr-csrf input[name="_csrf"]');
    return el ? el.value : '';
  })();

  var MARK_URL = '?plugin=callrequest&module=backend&action=mark';

  function parseJsonSafe(text){ try{ return JSON.parse(text);}catch(e){ return null; } }

  function request(op, payload){
    var fd = new FormData();
    fd.append('op', op);
    Object.keys(payload||{}).forEach(function(k){ fd.append(k, payload[k]); });
    if (CSRF) fd.append('_csrf', CSRF);

    return fetch(MARK_URL, {
      method:'POST',
      credentials:'same-origin',
      headers:{
        'X-Requested-With':'XMLHttpRequest',
        'Accept':'application/json',
        'X-CSRF-Token': CSRF || ''
      },
      body: fd
    }).then(function(r){
      return r.text().then(function(t){
        var j = parseJsonSafe(t);
        if (j && (j.ok || j.status==='ok')) return j;
        return Promise.reject((j && (j.error||j.message)) || ('Неожиданный ответ сервера: '+t.slice(0,200)));
      });
    });
  }

  function reloadPage(){ location.reload(); }
  function notifyError(msg){ (window.wa && wa.ui && wa.ui.alert)? wa.ui.alert(msg) : alert(msg); }

  // --- элементы модалки
  var MODAL  = document.getElementById('cr-comment-modal');
  var BG     = document.getElementById('cr-comment-bg');
  var TA     = document.getElementById('cr-comment-text');
  var SAVE   = document.getElementById('cr-comment-save');
  var CANCEL = document.getElementById('cr-comment-cancel');

  // ВАЖНО: показываем/скрываем не классом, а напрямую стилем (перебиваем inline display:none)
  function openModal(){ if(!MODAL) return; MODAL.style.display='block'; if(BG) BG.style.display='block'; if (TA) { TA.focus(); TA.select && TA.select(); } }
  function closeModal(){ if(!MODAL) return; MODAL.style.display='none';  if(BG) BG.style.display='none'; }

  function crDone(id){    request('done',    {id:id}).then(reloadPage).catch(notifyError); return false; }
  function crDelete(id){  request('delete',  {id:id}).then(reloadPage).catch(notifyError); return false; }
  function crRestore(id){ request('restore', {id:id}).then(reloadPage).catch(notifyError); return false; }

  function crComment(id, initialText){
    if (MODAL && TA && SAVE){
      TA.value = initialText || '';
      openModal();

      var onSave   = function(){
        var txt = (TA.value || '').trim();
        closeModal();
        request('comment', {id:id, comment:txt}).then(reloadPage).catch(notifyError);
        cleanup();
      };
      var onCancel = function(){ closeModal(); cleanup(); };
      var onEsc    = function(e){ if (e.key === 'Escape'){ closeModal(); cleanup(); } };
      function cleanup(){
        SAVE.removeEventListener('click', onSave);
        if (CANCEL) CANCEL.removeEventListener('click', onCancel);
        document.removeEventListener('keydown', onEsc);
      }

      SAVE.addEventListener('click', onSave);
      if (CANCEL) CANCEL.addEventListener('click', onCancel);
      document.addEventListener('keydown', onEsc);
    } else {
      var v = prompt('Комментарий менеджера:');
      if (v !== null) request('comment', {id:id, comment:v}).then(reloadPage).catch(notifyError);
    }
    return false;
  }

  window.crDone    = crDone;
  window.crDelete  = crDelete;
  window.crRestore = crRestore;
  window.crComment = crComment;
})();
