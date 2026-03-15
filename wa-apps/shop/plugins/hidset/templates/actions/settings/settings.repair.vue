{literal}
<div v-if="li==='repair'" class="custom-p-20">
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
        <div class="hidset-row" style="line-height: 2em; padding-bottom: 10px;">
            <div class="hidset-cell"
                 style="text-align: right; font-weight: bolder; font-family: monospace">
                <i class="icon16 star" v-if="c.addon"></i> {{ command }}
            </div>
            <div class="hidset-cell" style="line-height: 1em;max-width: 1000px;" v-if="!c.addon">
                {{c.description}}
                <a :href="'https://support.webasyst.ru/shop-script/20593/data-repair/#' + command" target="_blank"
                   class="small">[`документация`]</a>
            </div>
            <div class="hidset-cell" style="line-height: 1em;max-width: 1000px;" v-if="c.addon" v-html="c.description"></div>
            <div class="hidset-cell">
                <action-button v-if="checkFormData(c)" @click="runRepairAction(command)" title="[`Выполнить`]" icon="fas fa-play" :action="command" :run="current_action" bClass="smallest outlined"></action-button>
                <action-button v-if="!checkFormData(c)" @click="setFormData(c, command)" title="[`Настроить`]" icon="fas fa-cogs" action="tmp" :run="current_action" bClass="smallest outlined"></action-button>
            </div>
        </div>
        <div class="hidset-row" style="line-height: 2em;" v-if="!checkFormData(c) && repairFormData!==false && repairFormData.action===command">
            <div class="hidset-cell"
                 style="text-align: right; font-weight: bolder; font-size: 1.1em; font-family: monospace"></div>
            <div class="hidset-cell" style="line-height: 1em;max-width: 1000px;">
                <template v-for="(field, idx) in c.formData.fields">
                    <span v-if="field.name" style="margin-right: 5px;margin-left: 5px;">{{field.name}}</span>
                    <template v-if="field.control==='input'">
                        <input :type="field.type" :value="field.value" :class="'small ' + field.class" v-model="repairFormData.data.fields[idx].value" style="margin: 3px;">
                    </template>
                    <template v-if="field.control==='select'">
                        <select v-model="repairFormData.data.fields[idx].value" :class="'small' + field.class" style="margin: 3px;">
                            <option v-for="(o, v) in repairFormData.data.fields[idx].options" :value="v">{{o}}</option>
                        </select>
                    </template>
                </template>
                <action-button @click="runRepairAction(command)" title="[`Выполнить`]" :action="command" :run="current_action" icon="fas fa-play" bClass="smallest outlined"></action-button>

<!--                <a @click="runRepairAction(command)" style="margin-left: 25px;" :class="getLinkClass('repair')">
                    <i class="hidset-icon20 loading" v-if="current_action===command"></i> [`Выполнить1`]
                </a>-->
                <span class="alert" v-html="c.formData.description"></span>
            </div>
            <div class="hidset-cell">

<!--                <a @click="runRepairAction(command)" :class="getLinkClass('repair')">[`Выполнить2`]</a> <i
                    class="hidset-icon20 loading" v-if="current_action===command"></i>-->
            </div>
        </div>
    </template>
</div>
<span v-if="actionResult!==false" class="alert info" style="margin: 25px; color: black!important;"
      v-html="actionResult"></span>
</div>
{/literal}
<script setup>
</script>