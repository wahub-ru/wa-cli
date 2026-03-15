//<button type="submit" className="btn btn-primary">Отправить</button>


$(document).ready(function () {

    $('#assistai-submit').on('click', function (event) {
        event.preventDefault();
        // Найти форму
        const form = $(this).closest('form')[0];  // Получаем DOM-элемент формы
        //Скываем блоки валидации
        $('.invalid-feedback').hide();
        //Если все поля скрыты значит получаем временный код
        let mode = 'getTemporaryCode';

        //Валидцаия.
        if ($('input#email').val() == '') {
            $('#email-block .invalid-feedback').show();
            return;
        }
        if ($('input#password').is(':visible')) {
            mode = 'tokenByLogin';
            //Валидцаия.
            if ($('input#password').val() == '') {
                $('#password-block .invalid-feedback').show();
                return;
            }
        } else if ($('input#one-time-code').is(':visible')) {
            mode = 'tokenByOneTimeCode';
            if ($('input#one-time-code').val() == '') {
                $('#one-time-code-block .invalid-feedback').show();
                return;
            }
        }
        let oneTimeCode = $('input#one-time-code').val();
        let email = $('input#email').val();
        let password = $('input#password').val();

        // Проверка валидности формы
        if (form.checkValidity()) {

            //Покзаываем спинер
            $("#assistai-submit").prop('disabled', true);
            $("#assistai-submit .spinner-border").show();


            // Если форма валидна, выполняем AJAX-запрос
            $.ajax({
                url: '?plugin=assistai&action=registration',
                type: 'POST',
                dataType: 'json',
                data: {mode, email, password, oneTimeCode},
                success: function (response) {

                    if (response.error) {
                        $('#error-message-block').show();
                        $('#error-message').html(response.error);
                    } else {
                        $('#error-message-block').hide();
                    }

                    if (response.hint) {
                        $('#current-hint-block').show();
                        $('#current-hint').html(response.hint);
                    } else {
                        $('#current-hint-block').hide();
                    }
                    const code = response.code;
                    if (code == '50' || code == '51' || code == '52') {
                        //Введите пароль
                        $('#password-block').show();
                        //Переписать Email
                        if (code == '52') {
                            if (response?.data?.email) {
                                $('input#email').val(response?.data?.email);
                            }
                        }
                    }
                    //Выслан одноразовый пароль.
                    if (code == '53') {
                        $('#one-time-code-block').show();
                    }
                    //console.log('Форма успешно отправлена:', response);


                    //Токен получен при запросе по паролю
                    if (code == '55' || code == '57') {
                        //Убираем форму
                        $("#reg-assistai-form").hide();
                        //Перепиываем в форме email на актуальный
                        $("#email-field").text(response?.data?.email);
                        //Отображаем форму входа
                        $("#cardPassword-block").show();

                        //Записываем пароль и отображаем его.
                        if (code == '57') {
                            let password = response?.data?.password
                            $("#password-view").val(password);
                            $("#password-view-block").show();

                            location.reload();
                        }
                    }


                    //Убираем спиннер
                    $("#assistai-submit").prop('disabled', false);
                    $("#assistai-submit .spinner-border").hide();

                },
                error: function (error) {
                    console.error('Ошибка при отправке:', error);
                }
            });
        } else {
            // Если форма невалидна, показываем стандартные сообщения об ошибке
            form.reportValidity();
        }


    });


    //Меняем состояние формы если пароль уже есть.
    $(document).on('click', 'span.enter-password', function (event) {
        $('#password-block').show();
        let html = `
            <strong>Подключение!</strong>
            Для свзи установки Shop-Script с вашим аккаунтом в AsistAi введите пароль<br>
            <span class="enter-password-return">я ещё не зарегистирован(а)</span>`
        $('#current-hint').html(html);
    });

    //Возвращаем к начальному состоянию для регистрации по email
    $(document).on('click', 'span.enter-password-return', function (event) {

        //Скрытие формы ввода одноразового кода
        $('#one-time-code-block').hide();

        //Скрытие ошибки. Показ уведомления
        $('#error-message-block').hide();
        $('#current-hint-block').show();

        $('#password-block').hide();
        let html = `
            <strong>Регистрация!</strong>
            Для регистрации в сервисе укажите свой Email. На него придёт код для заверешения регистрации.<br>
            <span class="enter-password">я уже зарегистирован(а)</span>`
        $('#current-hint').html(html);
    });


    //Кнопка показа пароля.
    $('#togglePassword').click(function () {
        var passwordField = $('#password-view');
        var eyeIcon = $(this).find('.fa-eye');
        var eyeSlashIcon = $(this).find('.fa-eye-slash');

        // Переключаем тип поля между 'password' и 'text'
        if (passwordField.attr('type') === 'password') {
            passwordField.attr('type', 'text');
            eyeIcon.hide();        // Скрываем иконку глаза
            eyeSlashIcon.show();   // Показываем иконку перекрещенного глаза
        } else {
            passwordField.attr('type', 'password');
            eyeIcon.show();        // Показываем иконку глаза
            eyeSlashIcon.hide();   // Скрываем иконку перекрещенного глаза
        }
    });


    let activeAssistants;
    let activeActions;


    function reloadAjaxBlock() {

        //Добавляем прозрачность.
        $('#load-ajax-block').css('opacity', '0.2');
        $('.spinnerAiAsist').show();

        let jsonSetting = $('#jsonSetting').text();
        // Если форма валидна, выполняем AJAX-запрос
        $.ajax({
            url: '?plugin=assistai&action=settings',
            type: 'POST',
            dataType: 'html',
            data: {activeAssistants, activeActions, jsonSetting},
            success: function (response) {
                $('#load-ajax-block').html(response);
                $('#load-ajax-block').css('opacity', '1');
                $('.spinnerAiAsist').hide();
            },
            error: function (error) {
                console.error('Ошибка при отправке:', error);
            }
        });

    }

    //Переключение вкладки асистентов
    $(document).on('click', '#assistants-list button', function (event) {
        $('#assistants-list button').removeClass('active');
        if (activeAssistants != $(this).attr('data-id')) {
            activeAssistants = $(this).attr('data-id');
            reloadAjaxBlock();
        }
        $(this).addClass('active');
    });
    //Переключение валкдки экшена
    //Переключение вкладки асистентов
    $(document).on('click', '#actions-list button', function (event) {
        $('#actions-list button').removeClass('active');
        if (activeActions != $(this).attr('data-id')) {
            activeActions = $(this).attr('data-id');
            reloadAjaxBlock();
        }
        $(this).addClass('active');
    });
    //инициируем начальными значениями
    activeAssistants = $('#assistants-list button.active').attr('data-id');
    activeActions = $('#actions-list button.active').attr('data-id');
    //Если есть начальные данные загружаем контент
    if (activeAssistants && activeActions) {
        reloadAjaxBlock();
    }


    //Кнопка на странице встраивание
    $(document).on('click', 'button#save-embedding', function (event) {

        event.preventDefault();
        //Включаем спинер
        $(this).prop('disabled', true);
        $("button#save-embedding .spinner-border").show();

        let formData = new FormData($("#save-embedding-form")[0]);
        // Добавляем параметры
        formData.append("mode", "saveEmbedding");
        formData.append("activeAssistants", activeAssistants);
        formData.append("jsonSetting", $('#jsonSetting').text());


        //Добавляем чекбокс включения
        if (!formData.has('enabled')) {
            formData.append('enabled', 'false');
        }
        //Чекбокс привествия
        if (!formData.has('enableGreetings')) {
            formData.append('enableGreetings', 'false');
        }


        // Отправка AJAX-запроса
        $.ajax({
            url: '?plugin=assistai&action=ajax',
            type: "POST",
            data: formData,
            processData: false, // Не преобразовывать данные в строку
            contentType: false, // Устанавливается автоматически
            success: function (response) {


                //Убираем спиннер кнопки
                $("button#save-embedding").prop('disabled', false);
                $("button#save-embedding .spinner-border").hide();
            },
            error: function (xhr, status, error) {
                $("#response").text("Ошибка: " + error);
            },
        });
    });


    //Клик по кнопке удалить изображение
    $(document).on('click', 'button.hide-container-preview', function (event) {
        event.preventDefault();
        let formGroup = $(this).closest('.form-group');
        formGroup.find('input').show();

        let containerPreview = $(this).closest('.container-preview');
        containerPreview.hide();
        let type = $(this).attr('type');
        $.ajax({
            url: '?plugin=assistai&action=ajax',
            type: "POST",
            data: {mode: 'removeIcon', type, activeAssistants},
            success: function (response) {
                //
            },
            error: function (xhr, status, error) {
                $("#response").text("Ошибка: " + error);
            },
        });
    });

    //Подсчёт и ограничения при вводе в textarea
    $(document).on('input', '#rules-textarea', function () {
        const $textarea = $(this);
        const $currentCount = $('.current_characters');
        const maxLength = parseInt($textarea.attr('max-length'), 10);

        const text = $textarea.val();
        if (text.length > maxLength) {
            $textarea.val(text.slice(0, maxLength)); // Удаляем лишние символы
        }
        $currentCount.text($textarea.val().length); // Обновляем текущий счётчик
    });

    //Кнопка сохранить на странице RULE
    $(document).on('click', 'button#save-rules', function (event) {

        event.preventDefault();
        //Включаем спинер
        $(this).prop('disabled', true);
        $("button#save-rules .spinner-border").show();

        let formData = new FormData($("#save-rules-form")[0]);
        // Добавляем параметры
        formData.append("mode", "saveRules");
        formData.append("activeAssistants", activeAssistants);
        //formData.append("jsonSetting", $('#jsonSetting').text());


        // Отправка AJAX-запроса
        $.ajax({
            url: '?plugin=assistai&action=ajax',
            type: "POST",
            data: formData,
            processData: false, // Не преобразовывать данные в строку
            contentType: false, // Устанавливается автоматически
            success: function (response) {


                //Убираем спиннер кнопки
                $("button#save-rules").prop('disabled', false);
                $("button#save-rules .spinner-border").hide();
            },
            error: function (xhr, status, error) {
                console.log("Ошибка: " + error);
            },
        });
    });

});