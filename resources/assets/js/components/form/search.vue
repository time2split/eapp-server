<template>

    <!--<div class="form-group">-->
    <span>
        <input type="search" class="input-sm form-control" v-bind:placeholder="placeholder">
        <!--        <button type="submit" class="btn btn-primary btn-sm"><span class="glyphicon glyphicon-eye-open"></span> Chercher</button>-->
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
