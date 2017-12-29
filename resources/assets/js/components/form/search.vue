<template>
    <span>
        <input type="search" class="input-sm form-control" v-bind:placeholder="placeholder">
    </span>
</template>
<script>
    require('easy-autocomplete');
    export default {
        props: {
            url: String,
            config: {},
            fun_click: null,
            placeholder: {
                default: 'Recherche'
            }
        },
        mounted()
        {
            this.initAutocomplete();
        },
        methods: {
            initAutocomplete: function (e) {

                if (this.url == null)
                    return;
                var component = this;
                var form = $(this.$el);
                var input = form.find("input");
                var default_config = {
                    getValue: 'n',
                    requestDelay: 300,
                    url: function (word) {
                        return component.url.replace('$', word);
                    },
                    list: {
                        maxNumberOfElements: 10,
                        onClickEvent(e) {

                            if (this.fun_click)
                            {
                                var val = input.val();
                                this.fun_click(val, this);
                            }
                        }
                    },
                    template: {
                        type: "custom",
                        method(value, item)
                        {
                            var value = item.nf ? item.nf : item.n;
                            return value + ' <span class="badge badge-info">' + item.w + '</span>';
                        }
                    }
                };
                var config = Object.assign(default_config, this.config);
                input.easyAutocomplete(config);
            }
        }
    }
    ;
</script>
