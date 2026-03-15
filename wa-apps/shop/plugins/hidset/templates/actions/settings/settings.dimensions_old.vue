{literal}
<span class="alert" v-if="premium!==true && li==='dimensions'" style="max-width: 1000px;"><i class="icon16 status-red"></i> Возможность редактирования существующих мер и добавления новых доступна при наличии Премиум-лицензии плагина!</span>
<div v-if="li==='dimensions'" style="margin: 30px;">
<template v-if="dimEdit===false">
    <div>
        <select v-model="currentDim" class="large" v-if="newDim===false">
            <option v-for="(data, dim) in dimensions" :value="dim">{{data.name}}</option>
        </select>
        <template v-if="newDim!==false">
            id <input v-model="newDim.id" class="short"> Название <input v-model="newDim.name" placeholder="[`Укажите название меры`]"> base_unit <input v-model="newDim.base_unit" class="short">
            &nbsp;&nbsp;&nbsp;&nbsp;<a @click="addDim()" :class="getLinkClass('newDim')">добавить</a>&nbsp;&nbsp;&nbsp;[`или`]&nbsp;&nbsp;&nbsp;<a @click="newDim=false">[`отмена`]</a>
        </template>
        <template v-else>
            <action-button v-if="premium===true" @click="newDim={'id':'', 'name': '', 'base_unit': ''}" icon="icon16 plus" title="[`Добавить новую меру`]" :run="false" style="float: right; margin-right: 500px; margin-left: 150px; width: fit-content;"></action-button>
        </template>
    </div>
</template>
<template v-if="currentDim !== false && newDim===false">
    <template v-if="dimEdit!==false">
        <span class="alert danger" style="max-width: 1000px;">
            <table style="margin: 0px;">
                    <tr>
                        <td><i class="hidset-icon48 danger"></i></td>
                        <td style="padding-left: 20px; font-weight: bold">
                            ПОЛЬЗОВАТЕЛЬ, БУДЬ ВНИМАТЕЛЕН!<br>
                            Данный раздел изменяет лишь значения в файле dimensions.php. Существующие значения всех характеристик товаров останутся неизменными!
                        </td>
                    </tr>
                </table>
        </span>
        <table id="hidset-dimensions" class="mono">
            <tr class="dimension values">
                <td style="font-weight: bold"><span style="font-weight: normal !important;">id:</span> {{currentDim}}</td>
                <td class="right">name:</td>
                <td colspan="3">{{dimEdit.name}}</td>
            </tr>
            <tr class="values">
                <td></td>
                <td class="right">base_unit:</td>
                <td>
                    {{dimEdit.base_unit}}
                </td>
                <td></td>
                <td></td>
            </tr>
            <tr class="values">
                <td></td>
                <td class="right">units:</td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            <template v-for="(udata, unit) in dimEdit.units">
                <tr class="values units">
                    <td style="text-align: right;"><a @click="delUnit(unit)"><i class="icon16 minus-bw"></i></a></td>
                    <td class="right">{{unit}}</td>
                    <td>name</td>
                    <td class="small"><input class="short" v-model="udata.name"></td>
                    <td></td>
                </tr>
                <tr class="values">
                    <td></td>
                    <td></td>
                    <td>multiplier</td>
                    <td class="small">
                        <input type="number" class="short" v-model="udata.multiplier" min="0" style="min-width: 80px !important;width: 80px !important;">
                    </td>
                    <td></td>
                </tr>
            </template>
        </table>
        <a v-if="newUnit===false" class="small" @click="newUnit={'unit':'', 'name': 'Название', 'multiplier': 1}"><i class="icon16 add"></i> добавить</a>
        <div v-if="newUnit!==false">
            <table>
                <tr>
                    <td style="text-align: right">
                        <span class="small">[`Единица`]</span>
                    </td>
                    <td style="padding-left: 3px;"><input class="small short" v-model="newUnit.unit"></td>
                    <td>
                    </td>
                </tr>
                <tr>
                    <td style="padding-top:3px;text-align: right"><span class="small">[`Название`]</span></td>
                    <td style="padding-top:3px;padding-left: 3px;"><input class="small" v-model="newUnit.name"></td>
                    <td style="padding-top:3px;padding-left: 20px;">
                        <a :class="getLinkClass('addUnit')" @click="addUnit()">[`добавить`]</a>
                        <a @click="newUnit=false" style="margin-left: 25px;">[`отмена`]</a>
                    </td>
                </tr>
                <tr>
                    <td style="padding-top:3px;text-align: right"><span class="small">[`Коэффициент`]</span></td>
                    <td style="padding-top:3px;padding-left: 3px;">
                        <input class="small short" type="number" v-model="newUnit.multiplier"></td>
                    <td></td>
                </tr>
            </table>
        </div>
    </template>
    <template v-else-if="currentDim !== false">
        <table id="hidset-dimensions" class="mono">
            <tr class="dimension values">
                <td style="font-weight: bold"><span style="font-weight: normal !important;">id:</span> {{currentDim}}</td>
                <td class="right">name:</td>
                <td colspan="3">{{dimensions[currentDim].name}}</td>
            </tr>
            <tr class="values">
                <td></td>
                <td class="right">base_unit:</td>
                <td>{{dimensions[currentDim].base_unit}}</td>
                <td></td>
                <td></td>
            </tr>
            <tr class="values">
                <td></td>
                <td class="right">units:</td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            <template v-for="(udata, unit) in dimensions[currentDim].units">
                <tr class="values units">
                    <td></td>
                    <td></td>
                    <td class="right">{{unit}}</td>
                    <td>name</td>
                    <td>{{udata.name}}</td>
                </tr>
                <tr class="values">
                    <td></td>
                    <td></td>
                    <td></td>
                    <td>multiplier</td>
                    <td>{{udata.multiplier}}</td>
                </tr>
            </template>
        </table>
    </template>
    <div class="hidset-footer">
        <template v-if="dimEdit===false">
            <template v-if="premium===true">
                <action-button @click="onEditDim(currentDim)" title="[`Изменить`]" icon="icon16 edit" run=false></action-button>
                <action-button @click="restoreDefaultDim()" icon="icon16 update" title="Восстановить значения по умолчанию" :run="runActions" style="margin-left: 35px;"></action-button>
                <action-button @click="delDim()" icon="icon16 trash" title="[`Удалить`]" :run="runActions"></action-button>
            </template>
        </template>
        <template v-else-if="newUnit===false">
            <action-button @click="saveDim()" :disabled="!dimEdit.units.hasOwnProperty(dimEdit.base_unit)" title="Сохранить" icon="icon16 disk" :run="runActions"></action-button>
            <action-button @click="currentDim='length';dimEdit=false;" title="[`Отмена`]" icon="icon16 update" action="tmp" :run="runAction"></action-button>
        </template>
    </div>
</template>
</div>
{/literal}