(function() {
    'use strict';

    const data = window.pageVisitorCounterData;
    if (!data || !data.pageId) {
        return;
    }

    // Проверка на бота по User-Agent :cite[4]:cite[8]
    const userAgent = navigator.userAgent;
    const botPatterns = /Googlebot|Bingbot|YandexBot|DuckDuckBot|baiduspider|sogou|exabot|facebot|ia_archiver|AdsBot-Google|Google-Site-Verify|Google-Read-Aloud|Google-CloudVertexBot|FeedFetcher-Google|Googlebot-Image|Googlebot-Video|Googlebot-News|AdsBot-Google-Mobile|AdsBot-Google-Mobile-Apps|Applebot|Yeti|NaverBot|Twitterbot|FacebookExternalHit|LinkedInBot|Slurp|MSNBot|Teoma|Jakarta|CCBot|Seekport|ChatGPT-User|GPTBot|anthropic-ai|Claude-Web|Amazonbot|SemrushBot|AhrefsBot|MJ12bot|PetalBot|Bytespider|Zoho|ZoominfoBot|Shop-Script|Webasyst/i;
    if (botPatterns.test(userAgent)) {
        console.log('Bot detected, skipping tracking.');
        return;
    }

    let visitorId = waCookie.get('pv_visitor');
    if (!visitorId) {
        visitorId = Math.random().toString(36).substring(2) + Date.now().toString(36);
        waCookie.set('pv_visitor', visitorId, { expires: 365, path: '/' });
    }

    fetch(data.trackUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'page_id=' + data.pageId + '&visitor_hash=' + encodeURIComponent(visitorId)
    })
        .then(response => response.json())
        .then(data => {
            if (data && data.status === 'ok') {
                console.log('Page visit tracked successfully.');
            }
        })
        .catch(err => console.error('Error tracking page visit:', err));
})();