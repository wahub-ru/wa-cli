{literal}
<div v-if="li==='free'" style="margin: 30px;">
<ul class="tabs" id="hidset-free-ul" style="white-space:normal;height:auto">
    <li :class="getLiFreeClass('settings')">
        <a @click="liFree='settings'" href="javascript:void(0)"><i class="icon16 ss visibility"></i>[`Основные настройки`]</a>
    </li>
    <li :class="getLiFreeClass('icons')">
        <a @click="liFree='icons'" href="javascript:void(0)"><i class="icon16 ss flag-checkers"></i>[`Настройки icons`]</a>
    </li>
    <li :class="getLiFreeClass('library')">
        <a @click="liFree='library'" href="javascript:void(0)"><i class="icon16 image"></i>[`Библиотека icons`]</a>
    </li>
</ul>
<div class="tab-content">
    <template v-if="liFree==='settings' && li==='free'">
        <div class="hidset-table" style="margin: 20px;">
            <div class="hidset-row">
                <div class="hidset-cell" style="font-weight: bolder;color: gray;text-align: right">[`Название параметра`]</div>
                <div class="hidset-cell" style="font-weight: bolder;color: gray;">[`Значение`]</div>
                <div class="hidset-cell" style="font-weight: bolder;color: gray;">[`Краткое описание`]</div>
            </div>
            <div class="hidset-row" v-for="(data, set) in sets">
                <template v-if="data.type!=='icons'">
                    <div class="hidset-cell bolder right" style="vertical-align: middle">{{set}}</div>
                    <template v-if="data.type === 'int'">
                        <div class="hidset-cell"><input type="number" class="short" v-model="settings[set]" min="0">
                        </div>
                    </template>
                    <template v-else-if="data.type==='select'">
                        <div class="hidset-cell">
                            <select v-model="settings[set]">
                                <option v-for="(option, idx) in data.options">{{option}}</option>
                            </select>
                        </div>
                    </template>
                    <template v-else-if="data.type==='array'">
                        <div class="hidset-cell">
                            <table class="small" style="margin-bottom: 0px !important;">
                                <tr v-for="(el, idx) in data.options">
                                    <td style="text-align: right;">{{el.name}}&nbsp;</td>
                                    <td>
                                        <input type="number" class="short" v-model="settings[set][el.name]">
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </template>
                    <template v-else>
                        <div class="hidset-cell"><span class="gray">[`Неподдерживаемый тип настройки`]</span></div>
                    </template>
                    <div class="hidset-cell small">{{data.desc}}</div>
                </template>
            </div>
            <template v-if="pluginSets.length>0" v-for="(data, idx) in pluginSets">
                <div class="hidset-row">
                    <div class="hidset-cell bolder right gray" style="padding-top: 25px; padding-bottom: 15px;">[`Настройки
                        плагина `]{{data.name}}
                    </div>
                    <div class="hidset-cell"></div>
                    <div class="hidset-cell"></div>
                </div>
                <div class="hidset-row" v-for="(pdata, pset) in data.sets">
                    <div class="hidset-cell bolder right">{{pset}}</div>
                    <div class="hidset-cell" v-if="pdata.type==='int'">
                        <input type="number" class="short" v-model="pluginSets[idx].config[pset]">
                    </div>
                    <div class="hidset-cell" v-else>
                        <span>[`Неподдерживаемый тип настройки`]</span>
                    </div>
                    <div class="hidset-cell">{{pdata.desc}}</div>
                </div>
            </template>
        </div>
        <div class="hidset-footer" style="vertical-align: middle">
            <template v-if="defaultValues!==false">
    <span class="alert info" style="color: black">
        <input id="hidset-default-shop_hidset" type="radio" name="defaultValue" v-model="defaultValues"
               value="shop_hidset"> <label for="hidset-default-shop_hidset"> [`Только скрытые настройки Shop-Script доступные в плагине "Скрытые настройки"`]</label><br>
        <input id="hidset-default-shop" type="radio" name="defaultValue" v-model="defaultValues"
               v-model="defaultValues" value="shop"> <label for="hidset-default-shop"> [`Все скрытые настройки Shop-Script`]</label><br>
        <span v-if="pluginSets.length>0"><input id="hidset-default-plugins" type="radio" name="defaultValue" v-model="defaultValues" value="plugins"> <label for="hidset-default-plugins"> [`Только скрытые настройки плагинов`]</label><br></span>
        <span v-if="pluginSets.length>0"><input id="hidset-default-all" type="radio" name="defaultValue" v-model="defaultValues" value="all"> <label
                for="hidset-default-all"> [`Все скрытые настройки`]</label></span>
    </span>
                <action-button @click="restoreDefaultSettings()" icon="icon16 ss transfer-bw" title="[`Восстановить`]" :run="runActions"></action-button>
                <action-button @click="defaultValues=false" icon="icon16 rotate-left" title="[`Отмена`]" run=false></action-button>
            </template>
            <action-button @click="saveSettings('base')" v-if="defaultValues===false" title="[`Сохранить`]" action="saveSettings" icon="icon16 disk" :run="runActions"></action-button>
            <action-button @click="defaultValues='shop_hidset'" v-if="defaultValues===false" title="[`Восстановить значения по-умолчанию...`]" icon="icon16 update" action="tmp" :run="runActions" style="margin-left: 70px;"></action-button>
        </div>
    </template>
    <template v-if="liFree==='library' && li==='free'">
        <div style="margin: 40px;">
            <template v-for="(icons, type) in css">
                <div style="max-width: 1200px;">
                    <h3 style="margin: 15px;">{{type}}</h3>
                    <div v-for="(icon, idx) in icons" style="width: 170px; margin: 3px; display: inline-block;">
                        <i :class="getIconClass(icon)"></i> <a @click="copyToClipboard(icon)" class="icon">{{icon}}</a>
                    </div>
                </div>
            </template>
        </div>
    </template>
</div>
</div>
{/literal}