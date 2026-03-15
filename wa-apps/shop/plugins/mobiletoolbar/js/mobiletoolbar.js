(function(){
  /* ================== DOM helpers ================== */
  var __mtbDrilldownInited = false;
  var __mtbOpen = null;
  var $j = window.jQuery || null;

  function $q(s,r){ return (r||document).querySelector(s); }
  function $qa(s,r){ return Array.prototype.slice.call((r||document).querySelectorAll(s)); }
  function lockBody(x){
    document.documentElement.classList.toggle('shop-mobiletoolbar--locked', x);
    document.body.classList.toggle('shop-mobiletoolbar--locked', x);
  }
  function applyBarHeightVar(){
    var bar = $q('.shop-mobiletoolbar');
    var h = bar ? bar.offsetHeight : 64;
    document.documentElement.style.setProperty('--mtb-h', h+'px');
  }

  /* ================== Runtime CSS ================== */
function ensureRuntimeStyle(){
  if (document.getElementById('mtb-runtime-style')) return;

  var css = [
    /* блокируем фон скроллом через наш класс */
    '.shop-mobiletoolbar--locked{overflow:hidden!important;touch-action:none;}',
    '.mtb-no-scroll-x{overflow-x:hidden!important;}',

    /* строка поиска всегда выше всего */
    '[data-mtb-search]{position:relative;z-index:2147483000!important;}',
    '.mtb-searchbar,.mtb-searchbar__inner{position:relative;z-index:2147483000!important;}',

    /* ============ SearchPRO (мобилка) ============ */
    '@media (max-width:1199px){',

      /* УБИРАЕМ затемнение темы (оно мешает и перекрывает поле) */
      '.c-page_searchpro .js-searchpro__dropdown:before,',
      '.c-page_searchpro .js-searchpro__helper:before{',
      '  display:none!important; content:none!important;',
      '}',

      /* Выравниваем дропдаун по нашему инпуту (перебиваем left:-49px и проч.) */
      '.c-page_searchpro .searchpro__dropdown{',
      '  position:fixed!important;',
      '  left:var(--mtb-s-left,0px)!important;',
      '  top:var(--mtb-s-top,0px)!important;',
      '  width:var(--mtb-s-width,100vw)!important;',
      '  right:auto!important; max-width:none!important; margin-left:0!important;',
      '  border-radius:0!important; box-shadow:none!important;',
      '  z-index:2147482999!important;', /* Чуть ниже строки поиска, но выше всего остального */
      '  transform:none!important;',
      '}',
    '}'
  ].join('');

  var st = document.createElement('style');
  st.id = 'mtb-runtime-style';
  st.appendChild(document.createTextNode(css));
  (document.head || document.documentElement).appendChild(st);
}

  function toggleNoScrollX(on){
    document.documentElement.classList.toggle('mtb-no-scroll-x', !!on);
    document.body.classList.toggle('mtb-no-scroll-x', !!on);
  }

  /* ========= Выравнивание дропдауна SearchPRO по инпуту ========= */
  var __mtbAlignSPListener = null;
  function mtbRectOfSearchInput(){
    var wrap = $q('[data-mtb-search]');
    if (!wrap) return null;
    var a = wrap.querySelector('.mtb-searchbar__inner') ||
            wrap.querySelector('.mtb-searchbar') ||
            wrap.querySelector('.mtb-searchbar__input') ||
            wrap.querySelector('input[type="search"]') || wrap;
    return a.getBoundingClientRect();
  }
  function mtbApplyDropdownStyle(){
    var r = mtbRectOfSearchInput(); if (!r) return;
    var top = Math.round(r.top + r.height);
    var left = Math.round(r.left);
    var w = Math.round(r.width);

    // пробрасываем в CSS-переменные
    document.documentElement.style.setProperty('--mtb-s-left',  left + 'px');
    document.documentElement.style.setProperty('--mtb-s-top',   top  + 'px');
    document.documentElement.style.setProperty('--mtb-s-width', w    + 'px');

    // и дубль инлайном на сам дропдаун (перебьём любые left:-49px)
    var dd = document.querySelector('.c-page_searchpro .searchpro__dropdown, .searchpro__dropdown');
    if (dd){
      dd.style.position   = 'fixed';
      dd.style.left       = left + 'px';
      dd.style.top        = top  + 'px';
      dd.style.width      = w    + 'px';
      dd.style.right      = 'auto';
      dd.style.maxWidth   = 'none';
      dd.style.marginLeft = '0';
      dd.style.borderRadius = '0';
      dd.style.boxShadow  = 'none';
      dd.style.zIndex     = '10001';
      // адекватная высота на экране
      dd.style.maxHeight  = 'calc(100vh - ' + top + 'px)';
      dd.style.overflow   = 'auto';
    }
  }
  function mtbStartAlignWatcher(){
    [10,60,150,300].forEach(function(ms){ setTimeout(mtbApplyDropdownStyle, ms); });
    __mtbAlignSPListener = function(){ mtbApplyDropdownStyle(); };
    window.addEventListener('resize', __mtbAlignSPListener, {passive:true});
    window.addEventListener('orientationchange', __mtbAlignSPListener, {passive:true});
    window.addEventListener('scroll', __mtbAlignSPListener, {passive:true});
  }
  function mtbStopAlignWatcher(){
    if (__mtbAlignSPListener){
      window.removeEventListener('resize', __mtbAlignSPListener);
      window.removeEventListener('orientationchange', __mtbAlignSPListener);
      window.removeEventListener('scroll', __mtbAlignSPListener);
      __mtbAlignSPListener = null;
    }
    document.documentElement.style.removeProperty('--mtb-s-left');
    document.documentElement.style.removeProperty('--mtb-s-top');
    document.documentElement.style.removeProperty('--mtb-s-width');
  }

  /* ================== Слои: Каталог/Поиск ================== */
  function closeLayer(){
    var c  = $q('[data-shop-mobiletoolbar-catalog]');
    var s1 = $q('[data-shop-mobiletoolbar-search]');
    var s2 = $q('[data-mtb-search]');
    var b  = $q('[data-shop-mobiletoolbar-backdrop]');

    if (c)  c.hidden = true;
    if (s1) s1.hidden = true;
    if (s2) s2.style.display = 'none';
    if (b)  b.hidden = true;

    mtbStopAlignWatcher();
    toggleNoScrollX(false);
    lockBody(false);
    __mtbOpen = null;
  }

  function openLayer(type){
    var c  = $q('[data-shop-mobiletoolbar-catalog]');
    var s1 = $q('[data-shop-mobiletoolbar-search]');
    var s2 = $q('[data-mtb-search]');
    var b  = $q('[data-shop-mobiletoolbar-backdrop]');

    if (__mtbOpen === type) { closeLayer(); return; }
    closeLayer();

    if (type === 'catalog' && c) {
      c.hidden = false;
      if (!__mtbDrilldownInited) { try { mtbInitDrilldown(); __mtbDrilldownInited = true; } catch(e){} }
      if (window.__mtbDrilldown) { try { mtbGoRoot(window.__mtbDrilldown); } catch(e){} }
      if (b) b.hidden = false;
      lockBody(true);
      toggleNoScrollX(false);
      __mtbOpen = 'catalog';
      return;
    }

    if (type === 'search') {
      if (s1) {
        s1.hidden = false;
        var i1 = s1.querySelector('input[type=search], input');
        if (i1) setTimeout(function(){ i1.focus(); }, 0);
      }
      if (s2) {
        if (!s2.__promoted) { document.body.appendChild(s2); s2.__promoted = true; }
        s2.style.display = 'block';
        var i2 = s2.querySelector('.mtb-searchbar__input, input[type=search], input');
        if (i2) setTimeout(function(){ i2.focus(); if(i2.setSelectionRange){var L=i2.value.length;i2.setSelectionRange(L,L);} }, 0);
        var closeBtn = s2.querySelector('[data-mtb-search-close]');
        if (closeBtn && !closeBtn.__binded) { closeBtn.addEventListener('click', closeLayer); closeBtn.__binded = true; }
      }
      if (b) b.hidden = true;      // фон делает :before у SearchPRO
      lockBody(true);              // фон не прокручиваем
      toggleNoScrollX(true);       // запрет горизонтального сдвига
      mtbStartAlignWatcher();      // держим дропдаун у инпута
      __mtbOpen = 'search';
      return;
    }
  }

  /* ========= Остальной код плагина (бейджи, куки, перехваты) — без изменений ========= */
  /* ===== Drilldown Catalog ===== */
  function mtbBuildIndex(root){
    var byId = new Map();
    function walk(nodes, parentId){
      (nodes||[]).forEach(function(n){
        byId.set(String(n.id), { id:n.id, name:n.name, url:n.frontend_url||n.url||'#', children:n.children||[], parent: parentId });
        if (n.children && n.children.length) walk(n.children, n.id);
      });
    }
    walk(root, null);
    return byId;
  }
  function mtbRenderLevel(state){
    var container = document.querySelector('[data-shop-mobiletoolbar-catalog]');
    var titleEl = document.querySelector('[data-mtb-title]');
    var listEl  = document.querySelector('[data-mtb-list]');
    var backEl  = document.querySelector('[data-mtb-back]');
    var allEl   = document.querySelector('[data-mtb-all]');
    if(!listEl) return;

    var cur = state.byId.get(String(state.curId));
    var isRoot = (state.curId === 'root');

    if(container){ container.setAttribute('data-root', isRoot ? '1' : '0'); }
    if (backEl) backEl.hidden = isRoot;
    if (titleEl) titleEl.textContent = isRoot ? 'Каталог товаров' : (cur ? cur.name : 'Каталог товаров');

    if (!isRoot && cur && cur.url) {
      allEl.hidden = false; allEl.removeAttribute('hidden'); allEl.setAttribute('href', cur.url);
    } else if (allEl) {
      allEl.hidden = true;  allEl.setAttribute('hidden','');  allEl.removeAttribute('href');
    }

    var items = isRoot ? state.root : (cur ? cur.children : []);
    listEl.innerHTML = '';
    items.forEach(function(n){
      var li = document.createElement('li');
      li.className = 'shop-mobiletoolbar__catalog-item' + (n.children && n.children.length ? ' shop-mobiletoolbar__catalog-item_has-children' : '');
      var a = document.createElement('a');
      a.className = 'shop-mobiletoolbar__catalog-link';
      a.textContent = n.name || '';
      a.href = (n.children && n.children.length) ? 'javascript:void(0)' : (n.frontend_url || n.url || '#');
      a.addEventListener('click', function(e){
        if (n.children && n.children.length) {
          e.preventDefault();
          state.stack.push(state.curId);
          state.curId = String(n.id);
          mtbRenderLevel(state);
        }
      });
      li.appendChild(a);
      listEl.appendChild(li);
    });
  }
  function mtbGoRoot(state){ state.curId = 'root'; state.stack = []; mtbRenderLevel(state); }
  function mtbInitDrilldown(){
    var script = document.getElementById('mtb-catalog-data');
    if (!script) return;
    var raw; try{ raw = JSON.parse(script.textContent||'[]'); }catch(e){ raw = []; }
    var byId = mtbBuildIndex(raw);
    var state = { byId: byId, root: raw, curId: 'root', stack: [] };
    var back = document.querySelector('[data-mtb-back]');
    if (back) back.addEventListener('click', function(){
      if (state.stack.length) { state.curId = String(state.stack.pop()); mtbRenderLevel(state); }
    });
    mtbRenderLevel(state);
    window.__mtbDrilldown = state;
  }

  /* ===== Бейджи и счётчики (как было) ===== */
  function ensureBadges(){
    var items = $qa('.shop-mobiletoolbar__nav .shop-mobiletoolbar__item');
    items.forEach(function(a){
      var icon = a.querySelector('.shop-mobiletoolbar__icon');
      if (!icon) return;
      var href = (a.getAttribute('href')||'');
      if (/\/cart(\b|\/|\?)/i.test(href)) {
        if (!icon.querySelector('[data-mtb-cart-badge]')) {
          var s = document.createElement('span'); s.className='shop-mobiletoolbar__badge';
          s.setAttribute('data-mtb-cart-badge',''); s.style.display='none'; s.textContent='0'; icon.appendChild(s);
        }
      }
      if (/\bsearch\/?\?/i.test(href) && /list=favorite/i.test(href)) {
        if (!icon.querySelector('[data-mtb-fav-badge]')) {
          var s2 = document.createElement('span'); s2.className='shop-mobiletoolbar__badge';
          s2.setAttribute('data-mtb-fav-badge',''); s2.style.display='none'; s2.textContent='0'; icon.appendChild(s2);
        }
      }
      if (/\bcompare(\b|\/|\?)/i.test(href)) {
        if (!icon.querySelector('[data-mtb-compare-badge]')) {
          var s3 = document.createElement('span'); s3.className='shop-mobiletoolbar__badge';
          s3.setAttribute('data-mtb-compare-badge',''); s3.style.display='none'; s3.textContent='0'; icon.appendChild(s3);
        }
      }
    });
  }

  function getCookie(name){
    var list = document.cookie ? document.cookie.split('; ') : [];
    for (var i=0;i<list.length;i++){
      var p = list[i].split('='), k = p.shift(), v = p.join('=');
      if (k === name) return decodeURIComponent(v||'');
    }
    return '';
  }
  function parseIdList(str){
    if (!str) return [];
    var parts = String(str).split(/[\s,;]+/), out=[];
    for (var i=0;i<parts.length;i++){ var n=parseInt(parts[i],10); if(!isNaN(n)) out.push(n); }
    return out;
  }
  function readFavoriteCount(){
    var candidates=['shop_favorites','shop_favorite','wishlist','wa_favorites'];
    for (var i=0;i<candidates.length;i++){
      var c=getCookie(candidates[i]); if(!c) continue;
      try{ var j=JSON.parse(c); if(Array.isArray(j)) return j.length;
        if(j&&typeof j==='object'){ var sum=0; Object.keys(j).forEach(function(k){ if(Array.isArray(j[k])) sum+=j[k].length; }); if(sum>0) return sum; }
      }catch(_){ var n=parseIdList(c).length; if(n) return n; }
    }
    try{
      for (var k=0;k<candidates.length;k++){
        var v=localStorage.getItem(candidates[k]); if(!v) continue;
        try{ var j2=JSON.parse(v); if(Array.isArray(j2)) return j2.length;
          if(j2&&typeof j2==='object'){ var s=0; Object.keys(j2).forEach(function(x){ if(Array.isArray(j2[x])) s+=j2[x].length; }); if(s>0) return s; }
        }catch(_){ var n2=parseIdList(v).length; if(n2) return n2; }
      }
    }catch(_){}
    return null;
  }
  function readCompareCount(){
    var all=(document.cookie?document.cookie.split('; '):[]), sum=0, found=false;
    for (var i=0;i<all.length;i++){
      var pair=all[i].split('='), key=pair.shift(), val=decodeURIComponent(pair.join('=')||'');
      if (!/^(?:shop_)?compare/i.test(key) && !/^wa_compare/i.test(key)) continue;
      found=true; if(!val) continue;
      try{ var j=JSON.parse(val);
        if(Array.isArray(j)){ sum+=j.length; continue; }
        if(j&&typeof j==='object'){ Object.keys(j).forEach(function(k){ var v=j[k]; if(Array.isArray(v)) sum+=v.length; }); continue; }
      }catch(_){ sum+=parseIdList(val).length; }
    }
    if(found) return sum;
    var keys=['shop_compare','wa_compare','compare'];
    for (var k=0;k<keys.length;k++){
      var v=null; try{ v=localStorage.getItem(keys[k]); }catch(_){}
      if(!v) continue;
      try{ var jj=JSON.parse(v); if(Array.isArray(jj)) return jj.length;
        if(jj&&typeof jj==='object'){ var s=0; Object.keys(jj).forEach(function(t){ if(Array.isArray(jj[t])) s+=jj[t].length; }); return s; }
      }catch(_){ var n=parseIdList(v).length; if(n) return n; }
    }
    return null;
  }

  var SEL_CART='[data-mtb-cart-badge]', SEL_FAV='[data-mtb-fav-badge]', SEL_CMP='[data-mtb-compare-badge]';
  function setBadgeBySel(sel, n){ var el=$q(sel); if(!el) return; var v=Math.max(0,parseInt(n,10)||0); el.textContent=v; el.style.display=v>0?'':'none'; }
  function bumpBadgeBySel(sel, d){ var el=$q(sel); if(!el) return; var cur=parseInt(el.textContent,10)||0; setBadgeBySel(sel,cur+(parseInt(d,10)||0)); }

  window.mtbSetCartCount     = function(n){ setBadgeBySel(SEL_CART,n); };
  window.mtbSetFavoriteCount = function(n){ setBadgeBySel(SEL_FAV,n); };
  window.mtbSetCompareCount  = function(n){ setBadgeBySel(SEL_CMP,n); };

  var RE={CART:/(\/cart\/(add|update|delete|remove)|[\?&]cart=(add|update|delete|remove))/i,
          FAV:/\b(favorite|favorites|wishlist|bookmark|like|favourite)\b/i,
          CMP:/\b(compare|comparison|to_compare|add2compare)\b/i};

  var FAV_SELECTORS=['[data-action="add-to-favorite"]','[data-favorite-toggle]','.js-favorite-toggle','.js-add-to-favorite','.add-to-favorite','.favorite-toggle','.pw-favorite','.to-favorite'].join(',');
  var CMP_SELECTORS=['[data-action="add-to-compare"]','[data-compare-toggle]','.js-compare-toggle','.add-to-compare','.compare-toggle','.pw-compare','.to-compare'].join(',');
  function hasActiveClass(el){ var cls=el.className||''; return /\b(is-active|active|selected|on|in-favorite|in-compare)\b/.test(cls); }
  function observeToggleOnce(el,type){
    if(!el||!window.MutationObserver) return;
    var initial=hasActiveClass(el);
    var mo=new MutationObserver(function(){ var now=hasActiveClass(el); if(now!==initial){ bumpBadgeBySel(type==='fav'?SEL_FAV:SEL_CMP, now?+1:-1); try{mo.disconnect();}catch(_){}}});
    try{ mo.observe(el,{attributes:true,attributeFilter:['class']}); }catch(_){}
    setTimeout(function(){ try{mo.disconnect();}catch(_){}} ,2000);
  }
  document.addEventListener('click',function(e){
    var fav=e.target.closest(FAV_SELECTORS); if(fav){ observeToggleOnce(fav,'fav'); syncFavCmpLater(); return; }
    var cmp=e.target.closest(CMP_SELECTORS); if(cmp){ observeToggleOnce(cmp,'cmp'); syncFavCmpLater(); return; }
  },true);

  function toInt(v){ var n=parseInt(v,10); return isNaN(n)?null:n; }
  function extractCartCount(x){ if(!x)return null; if(typeof x==='object'){ if(x.count!=null)return toInt(x.count); if(x.data&&x.data.count!=null)return toInt(x.data.count);} else { var m=String(x).match(/"count"\s*:\s*(\d+)/i); if(m)return toInt(m[1]); } return null; }
  function extractFavCount(x){ if(!x)return null; if(typeof x==='object'){ if(x.favorites_count!=null)return toInt(x.favorites_count); if(x.data&&x.data.favorites_count!=null)return toInt(x.data.favorites_count); if(Array.isArray(x.favorites))return x.favorites.length; if(x.type==='favorite'&&x.count!=null)return toInt(x.count);} else { var s=String(x),m=s.match(/"favorites?_count"\s*:\s*(\d+)/i); if(m)return toInt(m[1]); var m2=s.match(/"type"\s*:\s*"favorite"[^}]*"count"\s*:\s*(\d+)/i); if(m2)return toInt(m2[1]); } return null; }
  function extractCmpCount(x){ if(!x)return null; if(typeof x==='object'){ if(x.compare_count!=null)return toInt(x.compare_count); if(x.data&&x.data.compare_count!=null)return toInt(x.data.compare_count); if(Array.isArray(x.compare))return x.compare.length; if(x.type==='compare'&&x.count!=null)return toInt(x.count);} else { var s=String(x),m=s.match(/"compare_count"\s*:\s*(\d+)/i); if(m)return toInt(m[1]); var m2=s.match(/"type"\s*:\s*"compare"[^}]*"count"\s*:\s*(\d+)/i); if(m2)return toInt(m2[1]); } return null; }

  function syncFavCmpNow(){ var f0=readFavoriteCount(); if(f0!=null) setBadgeBySel(SEL_FAV,f0); var c0=readCompareCount(); if(c0!=null) setBadgeBySel(SEL_CMP,c0); }
  function syncFavCmpLater(){ [80,180,350,700,1200].forEach(function(ms){ setTimeout(syncFavCmpNow,ms); }); }

  function startCookieWatcher(){
    var lastFav=readFavoriteCount(), lastCmp=readCompareCount(), timer=null, period=600;
    if(lastFav!=null) setBadgeBySel(SEL_FAV,lastFav);
    if(lastCmp!=null) setBadgeBySel(SEL_CMP,lastCmp);
    function tick(){ var f=readFavoriteCount(), c=readCompareCount(); if(f!=null&&f!==lastFav){ lastFav=f; setBadgeBySel(SEL_FAV,f);} if(c!=null&&c!==lastCmp){ lastCmp=c; setBadgeBySel(SEL_CMP,c);} }
    function start(){ if(!timer) timer=setInterval(tick,period); }
    function stop(){ if(timer){ clearInterval(timer); timer=null; } }
    document.addEventListener('visibilitychange',function(){ if(document.hidden) stop(); else { tick(); start(); }});
    start();
  }

  document.addEventListener('wa_cart_changed',function(e){ if(e&&e.detail&&typeof e.detail.count!=='undefined') setBadgeBySel(SEL_CART,e.detail.count); });
  document.addEventListener('wa_favorite_changed',function(){ syncFavCmpLater(); });
  document.addEventListener('wa_compare_changed', function(){ syncFavCmpLater(); });

  if($j){
    $j(document).on('cart:add cart_added added_to_cart wa_cart_changed',function(_e,data){ if(data&&typeof data.count!=='undefined') setBadgeBySel(SEL_CART,data.count); });
    $j(document).on('favorite:add favorite_added favorite:remove favorite_removed', function(){ syncFavCmpLater(); });
    $j(document).on('compare:add compare_added compare:remove compare_removed', function(){ syncFavCmpLater(); });
    $j(document).ajaxSuccess(function(_e,xhr,settings){
      var url=(settings&&settings.url)||''; if(!url) return;
      var txt=xhr.responseText||''; var json=xhr.responseJSON; if(!json){ try{ json=JSON.parse(txt);}catch(_){ json=null; } }
      if(RE.CART.test(url)){ var c=extractCartCount(json); if(c==null) c=extractCartCount(txt); if(c!=null) setBadgeBySel(SEL_CART,c); }
      if(RE.FAV.test(url)){ var f=extractFavCount(json); if(f==null) f=extractFavCount(txt); if(f!=null) setBadgeBySel(SEL_FAV,f); else syncFavCmpLater(); }
      if(RE.CMP.test(url)){ var k=extractCmpCount(json); if(k==null) k=extractCmpCount(txt); if(k!=null) setBadgeBySel(SEL_CMP,k); else syncFavCmpLater(); }
    });
  }

  if(window.fetch && !window.fetch.__mtbPatchedFavCmp){
    var _fetch=window.fetch;
    window.fetch=function(input,init){
      var url=(typeof input==='string')?input:((input&&input.url)||'');
      var p=_fetch.call(this,input,init);
      if(RE.CART.test(url)||RE.FAV.test(url)||RE.CMP.test(url)){
        p.then(function(resp){
          var clone=resp.clone();
          clone.json().then(function(json){
            if(RE.CART.test(url)){ var c=extractCartCount(json); if(c!=null) setBadgeBySel(SEL_CART,c); }
            if(RE.FAV.test(url)){ var f=extractFavCount(json); if(f!=null) setBadgeBySel(SEL_FAV,f); else syncFavCmpLater(); }
            if(RE.CMP.test(url)){ var k=extractCmpCount(json); if(k!=null) setBadgeBySel(SEL_CMP,k); else syncFavCmpLater(); }
          }).catch(function(){
            clone.text().then(function(txt){
              if(RE.CART.test(url)){ var c=extractCartCount(txt); if(c!=null) setBadgeBySel(SEL_CART,c); }
              if(RE.FAV.test(url)){ var f=extractFavCount(txt); if(f!=null) setBadgeBySel(SEL_FAV,f); else syncFavCmpLater(); }
              if(RE.CMP.test(url)){ var k=extractCmpCount(txt); if(k!=null) setBadgeBySel(SEL_CMP,k); else syncFavCmpLater(); }
            });
          });
        }).catch(function(){});
      }
      return p;
    };
    window.fetch.__mtbPatchedFavCmp=true;
  }

  (function(){
    var XHR=window.XMLHttpRequest;
    if(!XHR||XHR.__mtbPatchedFavCmp) return;
    var _open=XHR.prototype.open, _send=XHR.prototype.send;
    XHR.prototype.open=function(method,url){ this.__mtbUrl=(typeof url==='string')?url:String(url||''); return _open.apply(this,arguments); };
    XHR.prototype.send=function(){ var self=this; this.addEventListener('loadend',function(){
      var url=self.__mtbUrl||''; if(!RE.CART.test(url)&&!RE.FAV.test(url)&&!RE.CMP.test(url)) return;
      var txt=self.responseText||''; var json=null; try{ var ct=(self.getResponseHeader&&self.getResponseHeader('Content-Type'))||''; if(ct.indexOf('application/json')>=0 && self.response) json=self.response; }catch(_){}
      if(RE.CART.test(url)){ var c=extractCartCount(json); if(c==null) c=extractCartCount(txt); if(c!=null) setBadgeBySel(SEL_CART,c); }
      if(RE.FAV.test(url)){ var f=extractFavCount(json); if(f==null) f=extractFavCount(txt); if(f!=null) setBadgeBySel(SEL_FAV,f); else syncFavCmpLater(); }
      if(RE.CMP.test(url)){ var k=extractCmpCount(json); if(k==null) k=extractCmpCount(txt); if(k!=null) setBadgeBySel(SEL_CMP,k); else syncFavCmpLater(); }
    }); return _send.apply(this,arguments); };
    XHR.__mtbPatchedFavCmp=true;
  })();

  /* ================== Boot ================== */
  function init(){
    ensureRuntimeStyle();
    applyBarHeightVar();
    window.addEventListener('resize', applyBarHeightVar);

    document.addEventListener('click', function(e){
      var t = e.target.closest('[data-shop-mobiletoolbar-open], [data-shop-mobiletoolbar-action], [data-mtb-search-close]');
      if (!t) return;
      if (t.hasAttribute('data-mtb-search-close')) { e.preventDefault(); closeLayer(); return; }
      var act = t.getAttribute('data-shop-mobiletoolbar-open') || t.getAttribute('data-shop-mobiletoolbar-action');
      if (act === 'catalog' || act === 'search') { e.preventDefault(); openLayer(act); }
    });

    $qa('[data-shop-mobiletoolbar-close]').forEach(function(b){ b.addEventListener('click', closeLayer); });
    var back = $q('[data-shop-mobiletoolbar-backdrop]'); if (back) back.addEventListener('click', closeLayer);
    document.addEventListener('keydown', function(e){ if (e.key === 'Escape') closeLayer(); });

    var s2 = $q('[data-mtb-search]');
    if (s2 && !s2.__promoted) { document.body.appendChild(s2); s2.__promoted = true; }

    ensureBadges();
    syncFavCmpNow();
    startCookieWatcher();
  }
  if(document.readyState==='loading'){document.addEventListener('DOMContentLoaded',init);}else{init();}
})();
