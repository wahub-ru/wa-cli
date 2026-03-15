<span class="alert" v-if="premium!==true && li==='cli'" style="max-width: 1000px;">
<i class="icon16 status-red"></i> Возможность использования заданий cron доступна при наличии Премиум-лицензии плагина!
</span>
<div class="block double-padded" style="max-width: 900px;" v-if="li==='cli'">
<template v-if="isCloud===true">
    <span style="display: block; margin-top: 10px;">В приложении Облако выберите приложение <strong>Shop-Script</strong> и укажите необходимую команду из перечня ниже</span>
    <span style="display: block; margin-top: 10px;">Подробнее о настройке заданий cron в облаке Webasyst вы можете <a href="https://support.webasyst.ru/8372/webasyst-cloud-cron/" target="_blank">прочитать в статье</a></span>
</template>
<template v-else>
    <span style="display: block; margin-top: 10px;">
        Для выполнения задания добавьте в ваш планировщик заданий команду вида:
    </span>
    <span class="alert cli-command">
        [путь до интерпретатора php] {$wa_path}/cli.php shop hidsetPlugin -task КОМАНДА
    </span>
    <span style="display: block; margin-top: 10px;">
        Путь до интерпретатора php, а так же информацию о том, как создавать задания для cron,
        вы можете прочитать по <a href="http://www.webasyst.ru/developers/docs/tips/cron/" target="_blank">этой ссылке</a> и/или уточнить у вашего хостинг-провайдера
    </span>
</template>
<div style="margin-top: 30px;">
    <strong style=" margin-top: 40px;">[`Доступные в Премиум-версии плагина команды планировщика задач`]</strong>
</div>
{literal}
<template v-for="(cli, idx) in clis">
    <div :class="getCliTaskClass(idx)">
        <i class="icon16 star" style="position: absolute;" v-if="cli.addon"></i>
        <span class="alert cli-command">{{cli.command}}&nbsp;<template v-if="cli.addon && cli.params.length>0" v-for="(param, idx) in cli.params"><span style="color: dimgrey">&nbsp;{{param}}</span></template> </span>
        <div style="margin-top: 10px; line-height: 140%" v-html="cli.description"></div>
    </div>
</template>
</div>
{/literal}