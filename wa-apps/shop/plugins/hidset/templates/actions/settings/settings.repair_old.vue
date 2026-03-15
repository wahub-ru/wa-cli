{literal}
<div v-if="li==='repair'">
<div style="margin-top: 50px; margin-bottom: 30px;" class="hidset-table">
    <div class="hidset-row">
        <div class="hidset-cell"
             style="font-size: 1.3em; color: gray; font-weight: bolder; text-align: right; vertical-align: baseline !important;">
            [`Команда`]<br><br></div>
        <div class="hidset-cell" style="font-size: 1.3em; color: gray; font-weight: bolder;padding-left: 20px;">
            [`Описание`]
        </div>
        <div class="hidset-cell" style="font-size: 1.3em; color: gray; font-weight: bolder;padding-left: 20px;"></div>
    </div>
    <template v-for="(c, command) in repairs">
        <div class="hidset-row" style="line-height: 2em;">
            <div class="hidset-cell"
                 style="text-align: right; font-weight: bolder; font-size: 1.1em; font-family: monospace">
                <i class="icon16 star" v-if="c.addon"></i> {{ command }}
            </div>
            <div class="hidset-cell" style="line-height: 1em;max-width: 1000px;" v-if="!c.addon">
                {{c.description}}
                <a :href="'https://support.webasyst.ru/shop-script/20593/data-repair/#' + command" target="_blank"
                   class="small">[`документация`]</a>
            </div>
            <div class="hidset-cell" style="line-height: 1em;max-width: 1000px;" v-if="c.addon" v-html="c.description"></div>
            <div class="hidset-cell">
                <a v-if="checkFormData(c)" @click="runRepairAction(command)" :class="getLinkClass('repair')">[`Выполнить`]</a> <i
                    class="hidset-icon20 loading" v-if="current_action===command"></i>
                <a @click="setFormData(c, command)" v-if="!checkFormData(c)">[`Настроить`]</a>
            </div>
        </div>
        <div class="hidset-row" style="line-height: 2em;" v-if="!checkFormData(c) && repairFormData!==false && repairFormData.action===command">
            <div class="hidset-cell"
                 style="text-align: right; font-weight: bolder; font-size: 1.1em; font-family: monospace"></div>
            <div class="hidset-cell" style="line-height: 1em;max-width: 1000px;">
                <template v-for="(field, idx) in c.formData.fields">
                    <span v-if="field.name" style="margin-right: 5px;margin-left: 5px;">{{field.name}}</span>
                    <template v-if="field.control==='input'">
                        <input :type="field.type" :value="field.value" :class="field.class" v-model="repairFormData.data.fields[idx].value">
                    </template>
                    <template v-if="field.control==='select'">
                        <select v-model="repairFormData.data.fields[idx].value" :class="field.class">
                            <option v-for="(o, v) in repairFormData.data.fields[idx].options" :value="v">{{o}}</option>
                        </select>
                    </template>
                </template>
                <a @click="runRepairAction(command)" style="margin-left: 25px;" :class="getLinkClass('repair')">
                    <i class="hidset-icon20 loading" v-if="current_action===command"></i> [`Выполнить`]
                </a>
                <span class="alert" v-html="c.formData.description"></span>
            </div>
            <div class="hidset-cell">
                <a @click="runRepairAction(command)" :class="getLinkClass('repair')">[`Выполнить`]</a> <i
                    class="hidset-icon20 loading" v-if="current_action===command"></i>
            </div>
        </div>
    </template>
</div>
<span v-if="actionResult!==false" class="alert info" style="margin: 25px; color: black!important;"
      v-html="actionResult"></span>
</div>
{/literal}