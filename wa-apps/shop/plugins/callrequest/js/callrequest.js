(function () {

  /* ====================== НАСТРОЙКИ ====================== */
  var S = (window.CallRequestSettings || {});
  if (!S.trigger_class) S.trigger_class = 'callrequest-open';

  var PRICE_DATA = null;

  /* ====================== UTILS ====================== */

  function el(html) {
    var d = document.createElement('div');
    d.innerHTML = html.trim();
    return d.firstChild;
  }

  function uniq(arr) {
    return Array.from(new Set(arr));
  }

  function clear(el) {
    el.innerHTML = '';
  }

  /* ====================== DATA ====================== */

  function loadPriceData() {
    if (PRICE_DATA) return Promise.resolve();

    return fetch((window.waURL || '') + 'callrequest/getPrices/')
        .then(r => r.json())
        .then(resp => {
          PRICE_DATA = {};
          if (!resp || !Array.isArray(resp.prices)) return;

          resp.prices.forEach(function (item) {
            if (!item.type) return;

            if (!PRICE_DATA[item.type]) PRICE_DATA[item.type] = [];

            PRICE_DATA[item.type].push({
              size: item.size,
              thickness: parseInt(item.thickness, 10),
              print: item.print,
              qty: parseInt(item.qty, 10),
              price: parseFloat(item.price)
            });
          });
        });
  }

  /* ====================== TEMPLATE ====================== */

  function calculatorTemplate() {
    return `
      <div class="cr-calculator">
        <form class="cr-form">
          <input type="hidden" name="from_url" value="${location.href}">
          <input type="hidden" name="phone_fmt">

          <div class="cr-quiz"></div>

          <label class="cr-field" style="display:none">
            ФИО
            <input name="name" required>
          </label>

          <label class="cr-field" style="display:none">
            Телефон
            <input name="phone" required>
          </label>

          <button type="submit" class="cr-submit" style="display:none">
            Отправить
          </button>
        </form>

        <div class="cr-status" style="display:none"></div>
      </div>
    `;
  }

  /* ====================== RENDER ====================== */

  function renderCalculator(container) {
    if (container.dataset.inited) return;

    container.innerHTML = calculatorTemplate();
    container.dataset.inited = '1';

    initCalculator(container);
  }

  /* ====================== CORE ====================== */

  function initCalculator(root) {
    var form = root.querySelector('.cr-form');
    var quiz = root.querySelector('.cr-quiz');
    var status = root.querySelector('.cr-status');

    var answers = {};

    var steps = [
      { title: 'Выберите тип пакета', render: stepType },
      { title: 'Выберите размер', render: stepSize },
      { title: 'Выберите толщину', render: stepThickness },
      { title: 'Выберите печать', render: stepPrint },
      { title: 'Выберите тираж', render: stepQty },
      { title: 'Результат расчёта', render: stepResult }
    ];

    function buildSteps() {
      quiz.innerHTML = '';
      steps.forEach(function (s, i) {
        quiz.appendChild(el(`
          <div class="cr-step" data-step="${i}">
            <h3>${s.title}</h3>
            <div class="cr-buttons"></div>
            ${i > 0 && i < 5 ? '<button type="button" class="cr-back">Назад</button>' : ''}
          </div>
        `));
      });
      showStep(0);
      steps[0].render();
    }

    function showStep(i) {
      quiz.querySelectorAll('.cr-step').forEach(function (el) {
        el.style.display = el.dataset.step == i ? '' : 'none';
      });
    }

    function bindButtons() {
      quiz.addEventListener('click', function (e) {
        var btn = e.target.closest('button[data-k]');
        if (!btn) return;

        answers[btn.dataset.k] = btn.dataset.v;
        var step = parseInt(btn.closest('.cr-step').dataset.step, 10);

        if (step < 5) {
          showStep(step + 1);
          steps[step + 1].render();
        }
      });

      quiz.addEventListener('click', function (e) {
        if (e.target.classList.contains('cr-back')) {
          var step = parseInt(e.target.closest('.cr-step').dataset.step, 10);
          showStep(step - 1);
        }
      });
    }

    function renderButtons(step, key, values) {
      var box = step.querySelector('.cr-buttons');
      clear(box);
      values.forEach(function (v) {
        var b = document.createElement('button');
        b.type = 'button';
        b.dataset.k = key;
        b.dataset.v = v;
        b.textContent = v;
        box.appendChild(b);
      });
    }

    function stepType() {
      var step = quiz.querySelector('[data-step="0"]');
      var box = step.querySelector('.cr-buttons');
      clear(box);

      Object.keys(PRICE_DATA).forEach(function (type, index) {
        var id = index + 1;

        var card = document.createElement('button');
        card.type = 'button';
        card.className = 'type-card';
        card.dataset.k = 'Тип пакета';
        card.dataset.v = type;

        var img = document.createElement('img');
        img.src = '/wa-data/public/site/type/' + id + '.png';
        img.alt = type;
        img.className = 'type-card-image';

        var title = document.createElement('span');
        title.className = 'type-card-title';
        title.textContent = type;

        card.appendChild(img);
        card.appendChild(title);
        box.appendChild(card);
      });
    }


    function stepSize() {
      var rows = PRICE_DATA[answers['Тип пакета']] || [];
      renderButtons(
          quiz.querySelector('[data-step="1"]'),
          'Размер',
          uniq(rows.map(r => r.size))
      );
    }

    function stepThickness() {
      var rows = PRICE_DATA[answers['Тип пакета']]
          .filter(r => r.size == answers['Размер']);
      renderButtons(
          quiz.querySelector('[data-step="2"]'),
          'Толщина',
          uniq(rows.map(r => r.thickness))
      );
    }

    function stepPrint() {
      var rows = PRICE_DATA[answers['Тип пакета']]
          .filter(r => r.size == answers['Размер'] && r.thickness == answers['Толщина']);
      renderButtons(
          quiz.querySelector('[data-step="3"]'),
          'Печать',
          uniq(rows.map(r => r.print))
      );
    }

    function stepQty() {
      var rows = PRICE_DATA[answers['Тип пакета']]
          .filter(r =>
              r.size == answers['Размер'] &&
              r.thickness == answers['Толщина'] &&
              r.print == answers['Печать']
          );
      renderButtons(
          quiz.querySelector('[data-step="4"]'),
          'Тираж',
          uniq(rows.map(r => r.qty))
      );
    }

    function stepResult() {
      var step = quiz.querySelector('[data-step="5"]');
      var box = step.querySelector('.cr-buttons');
      clear(box);

      var rows = PRICE_DATA[answers['Тип пакета']]
          .filter(r =>
              r.size == answers['Размер'] &&
              r.thickness == answers['Толщина'] &&
              r.print == answers['Печать']
          );

      var row = rows.reduce((a, b) =>
          Math.abs(b.qty - answers['Тираж']) < Math.abs(a.qty - answers['Тираж']) ? b : a
      );

      var price = Number(row.price);
      var total = price * answers['Тираж'];

      answers['Цена за 1 шт.'] = price.toFixed(2);
      answers['Итого'] = total.toFixed(2);

      box.innerHTML = `
    <div class="cr-result">
      <div class="cr-result-line">
        Цена за один пакет —
        <b>${price.toLocaleString('ru-RU')} ₽</b>
      </div>

      <div class="cr-result-line cr-result-total">
        Цена за печать всего тиража —
        <b>${total.toLocaleString('ru-RU')} ₽</b>
      </div>

      <button type="button" class="cr-link cr-restart">
        Посчитать цену ещё раз
      </button>

      <button type="button" class="cr-btn cr-send">
        Оставить заявку
      </button>
    </div>
  `;
    }


    quiz.addEventListener('click', function (e) {
      if (e.target.classList.contains('cr-restart')) {
        answers = {};
        buildSteps();
        form.querySelectorAll('.cr-field,.cr-submit').forEach(el => el.style.display = 'none');
      }
      if (e.target.classList.contains('cr-send')) {
        quiz.querySelector('[data-step="5"] h3').textContent = 'Оставить заявку';
        form.querySelectorAll('.cr-field').forEach(el => el.style.display = '');
        form.querySelector('.cr-submit').style.display = '';
      }
    });

    form.addEventListener('submit', function (e) {
      e.preventDefault();

      form.querySelectorAll('input[name^="fields["]').forEach(el => el.remove());

      Object.keys(answers).forEach(function (k) {
        var i = document.createElement('input');
        i.type = 'hidden';
        i.name = 'fields[' + k + ']';
        i.value = answers[k];
        form.appendChild(i);
      });

      status.style.display = 'block';
      status.textContent = 'Отправка...';

      fetch((window.waURL || '') + 'callrequest/send/', {
        method: 'POST',
        body: new FormData(form),
        credentials: 'same-origin'
      })
          .then(r => r.json())
          .then(() => {
            form.style.display = 'none';
            status.textContent = 'Спасибо! Мы свяжемся с вами.';
          })
          .catch(err => {
            status.textContent = 'Ошибка: ' + err.message;
          });
    });

    buildSteps();
    bindButtons();
  }

  /* ====================== MODAL ====================== */

  function openModal() {
    var modal = document.querySelector('.cr-modal');
    if (!modal) {
      modal = el(`
        <div class="cr-modal">
          <div class="cr-backdrop"></div>
          <div class="cr-dialog">
            <button class="cr-close">×</button>
            <div data-callrequest-calculator></div>
          </div>
        </div>
      `);
      document.body.appendChild(modal);
      renderCalculator(modal.querySelector('[data-callrequest-calculator]'));
    }
    modal.style.display = 'block';
  }

  /* ====================== BOOT ====================== */

  document.addEventListener('DOMContentLoaded', function () {
    loadPriceData().then(function () {
      document
          .querySelectorAll('[data-callrequest-calculator]')
          .forEach(renderCalculator);
    });

    document.body.addEventListener('click', function (e) {
      if (e.target.closest('.' + S.trigger_class)) openModal();
      if (e.target.classList.contains('cr-close') || e.target.classList.contains('cr-backdrop'))
        e.target.closest('.cr-modal').style.display = 'none';
    });
  });

})();
