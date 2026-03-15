{literal}
<div v-if="li==='handlers'">
<div class="alert blue" style="; max-width: 1000px;">
[`В перечне хуков отображаются только те хуки, которые используются в работе включенных в инсталлере плагинов и
приложений`]
</div>
<div class="custom-ml-24">
<input v-model="handlerFilter" type="text" class="long" placeholder="Начните вводите название хука для фильтра">
</div>
<div style="margin: 10px 30px 30px 30px; display: flex;  column-gap: 40px;">
<template v-if="handlers.length">
    <div>
        <template v-for="(app, idx) in handlers">
            <div style="display: flex;column-gap: 20px;margin-bottom: 20px;">
                <div style="display: flex;flex-direction: column;">
                    <strong style="margin: 8px;">[`Хуки приложения`] {{app.name}}</strong>
                    <template v-for="(item, hdx) in app.handlers">
                        <div v-if="checkHandler(item.handler)" @click="setHandler(idx, hdx)"
                             :class="getHandlerClass(app.app_id, item.handler)">
                            <span>{{item.handler}} <span class="gray">[{{item.items.length}}]</span></span>
                        </div>
                    </template>
                </div>
            </div>
        </template>
    </div>
    <template v-if="premium">
        <template v-if="cHandler!==false">
            <div style="display:flex;flex-direction: column;row-gap: 25px;">
                <div style="display: flex;column-gap: 25px;align-items: center;">
                    <h2>{{cHandler.app_name}}: {{cHandler.handler}}</h2>
                    <a class="button smallest nobutton" :href="getHandlerHref()" target="_blank">[`документация`] <i
                            class="fas fa-external-link-alt"></i></a>
                </div>
                <div>
                    <div v-if="cHandler.items.length">
                <span>
                    [`Данный хук используют следующие продукты:`]
                </span>
                        <ul>
                            <li v-for="(item, idx) in cHandler.items" style="padding: 3px;">
                                {{item.name}} <span class="small gray" style="font-family: monospace">{{item.id}}</span>
                            </li>
                        </ul>
                    </div>
                    <span v-else class="gray">[`Не удалось найти продукты использующие этот хук`]</span>
                </div>
            </div>
        </template>
        <template v-else>
            <div class="block double-padded align-center gray">
                <p>
                    <strong>[`Выберите хук в левом столбце`]</strong>
                    <br>
                    <br>
                </p>
            </div>
        </template>
    </template>
    <template v-else>
        <div class="block double-padded align-center gray">
            <p>
                <strong>[`Для просмотра информации об использовании хуков необходимо приобрести</strong><br>
                <strong>Премиум лицензию на плагин Скрытые инструменты и настройки `]</strong>
                <br>
            </p>
        </div>
    </template>
</template>
<template v-else>
    <div class="block double-padded align-center gray">
        <p>
            <strong>[`Отсутствуют данные об использовании хуков`]</strong>
            <br>
            <br>
        </p>

    </div>
</template>
</div>
</div>
{/literal}