actionButton = {
    props: ['title', 'icon', 'run', 'action', 'bclass', 'btype', 'result'],
    data: function () {
        return { buttonClick: 'bclick'};
    },
    template: {$actionButton},
    methods: {
        getIconClass: function () {
            return 'icon16 ' + this.icon;
        },
        checkLoading: function () {
            if (this.action === false) return false;
            return (this.action === this.run && this.result !== true);
        },
        checkResult: function() {
            return this.action === this.run && this.result === true;
        },
        getButtonClass: function () {
            let buttonClass = {if $wa->whichUI() == '1.3'}'hidset-button'{else}'button small'{/if};
            if (this.bclass !== undefined) {
                buttonClass += ' ' + this.bclass;
            }
            if (this.run !== false && this.result !== true) {
                buttonClass += ' disabled light-gray';
            }
            return buttonClass;
        },
        getButtonStyle: function () {
            let bStyle = '';
            {if $wa->whichUI()!='1.3'}
            if (this.btype === 'undo') bStyle += ' background: var(--gray);';
            if (this.btype === 'trash') bStyle += ' background: var(--red);';
            {/if}
            return bStyle;
        }
    }
};