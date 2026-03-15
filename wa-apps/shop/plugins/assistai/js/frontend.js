// Скрипт заманивающей кнопки чата
document.addEventListener('DOMContentLoaded', function () {


    // Проверяем включена ли анимация
    const aiChatBlock = document.getElementById("aiChatBlockUpsale");
    // Проверить наличие атрибута isEnable
    if (aiChatBlock) {
        const isenable = aiChatBlock.getAttribute("isenable");
        if (isenable != 'true' ) {
            //Прерываем аниацию
            return;
        }
    }




    const helpWindow = document.getElementById('help');
    const chatMessage = document.getElementById('chat-message');
    const loader = document.getElementById('loader');
    const chatCircle = document.getElementById('chat-circle');
    const talkText = document.getElementById('talktext');
    const talkText1 = document.getElementById('talktext-1');



    helpWindow.style.display = 'none';
    chatMessage.style.display = 'none';

    setTimeout(function () {
        helpWindow.style.display = 'flex';
        helpWindow.style.opacity = '1';
        helpWindow.style.visibility = 'visible';
        //talkText.style.display = 'none';

        setTimeout(function () {
            loader.style.display = 'none';
            chatCircle.classList.add('bounce-5'); // Анимация
            talkText.style.display = 'block';
            chatMessage.style.display = 'flex';

            setTimeout(function () {
                chatCircle.classList.remove('bounce-5'); // Анимация
                helpWindow.style.opacity = '0';
                helpWindow.style.visibility = 'hidden';

                setTimeout(function () {
                    helpWindow.style.opacity = '1';
                    helpWindow.style.visibility = 'visible';
                    loader.style.display = 'block';
                    talkText.style.display = 'none';

                    setTimeout(function () {
                        loader.style.display = 'none';
                        chatCircle.classList.add('bounce-5'); // Анимация

                        chatMessage.textContent = "2";
                        talkText1.style.display = 'block';
                        chatMessage.style.display = 'flex';

                        setTimeout(function () {
                            chatCircle.classList.remove('bounce-5'); // Анимация
                            helpWindow.style.opacity = '0';
                            helpWindow.style.visibility = 'hidden';
                        }, 2000); // Продолжительность второго сообщения
                    }, 2000); // Задержка второго загрузчика
                }, 2000); // Задержка второго загрузчика
            }, 2000); // Продолжительность первого сообщения
        }, 2000); // Задержка первого загрузчика
    }, 6000); // Начальная задержка
});

// Открытие и закрытие чата
document.addEventListener('DOMContentLoaded', function () {
    const chatMessage = document.getElementById('chat-message');
    const chatCircle = document.getElementById('chat-circle');
    const chatIcons = document.getElementById('chat-icons-upsale');
    const helpWindow = document.getElementById('help');
    const blockChat = document.getElementById('blockChatUpsale');
    const iconChatClose = document.getElementById('iconChatUpsaleClose');

    // Открытие чата при клике на иконку
    chatCircle.addEventListener('click', function () {
        chatIcons.style.display = 'none';
        helpWindow.style.display = 'none';

        blockChat.style.display = 'block';
        setTimeout(function () {
            blockChat.classList.add('show');
        }, 10); // Маленькая задержка для анимации
    });

    // Закрытие чата при клике на кнопку закрытия
    iconChatClose.addEventListener('click', function () {
        blockChat.classList.remove('show'); // Убираем класс, который вызывает анимацию
        setTimeout(function () {
            chatIcons.style.display = 'block';
            blockChat.style.display = 'none'; // Скрываем блок после анимации
            chatMessage.style.display = 'none'; // Скрываем сообщение чата
        }, 300); // Задержка должна быть равна продолжительности анимации
    });
});
