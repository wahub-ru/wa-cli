{literal}
<div :class="getButtonClass()" :style="getButtonStyle()">
    <a @click="$emit(buttonClick, $event)">
        <i v-if="checkLoading()" class="icon16 loading"></i>
        <i v-if="run!==action" :class="icon"></i>
        <i v-if="checkResult()" class="icon16 yes"></i>
        <span v-if="title"> {{title}}</span>
    </a>
</div>
{/literal}