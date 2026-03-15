{literal}
<template v-if="liFree==='icons' && li==='free'">
    <action-button @click="saveSettings('icons')" title="[`Сохранить`]" icon="save" :run="runActions" style="margin-bottom: 20px;"></action-button>
    <action-button @click="restoreDefaultSettings('icons')" title="Восстановить значения по-умолчанию" icon="reload" :run="false"></action-button>
    <div style="margin: 40px;">
        <table class="zebra">
            <tr>
                <td><strong>order_state_icons</strong></td>
                <td><strong>order_action_icons</strong></td>
                <td><strong>customers_filter_icons</strong></td>
                <td><strong>type_icons</strong></td>
            </tr>
            <tr>
                <td class="grey">Доступные иконки для статусов заказов</td>
                <td class="grey">Доступные иконки для действий c заказами</td>
                <td class="grey">Иконки для сохраненных фильтров покупателей</td>
                <td class="grey">Иконки для типов товаров</td>
            </tr>
            <template v-for="(tmp, idx) in getIconSettings()">
                <tr>
                    <template v-for="(set, tidx) in ['order_state_icons', 'order_action_icons', 'customers_filter_icons', 'type_icons']">
                        <td class="hidset-icons">
                            <template v-if="settings[set][idx]!==undefined">
                            <span>
                                <i :class="getIconClass('ss ' + settings[set][idx])" v-if="set === 'order_state_icons'"></i>
                                <i :class="getIconClass(settings[set][idx])" v-else></i>
                                &nbsp;{{settings[set][idx]}}</span>
                                <a @click="delIcon(set, idx)" class="small" style="float: right; margin-right:150px;"><i class="icon16 no"></i></a>
                            </template>
                            <template v-else-if="settings[set].length===idx">
                                <a @click="onAddIcon=set" class="small" v-if="onAddIcon!==set"><i class="icon10 plus-bw"></i> [`добавить`]</a>
                                <span class="small" v-else>
                                    <input class="short" v-model="newIcon">&nbsp;&nbsp;&nbsp;
                                    <a @click="settings[set].push(newIcon);newIcon='';onAddIcon=false;" :class="getLinkClass('addIcon')" style="display: inline">[`добавить`]</a>
                                </span>
                            </template>
                        </td>
                    </template>
                </tr>
            </template>
            <tr>
                <td v-for="(set, tidx) in ['order_state_icons', 'order_action_icons', 'customers_filter_icons', 'type_icons']">
                    <template v-if="settings[set].length===getIconSettings().length">
                        <a @click="onAddIcon=set" class="small" v-if="onAddIcon!==set"><i class="icon10 plus-bw"></i> [`добавить`]</a>
                        <span class="small" v-else>
                                    <input class="short" v-model="newIcon">&nbsp;&nbsp;&nbsp;
                                    <a @click="settings[set].push(newIcon);newIcon='';onAddIcon=false;" :class="getLinkClass('addIcon')" style="display: inline">[`добавить`]</a>
                                </span>
                    </template>
                </td>
            </tr>
        </table>
    </div>
</template>
{/literal}