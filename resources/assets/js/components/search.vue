<template>
    <form class="navbar-form navbar-right inline-form" v-on:submit="submit" v-on:submit.prevent="onSubmit">
        <div class="form-group">
            <input v-on:mouseover.once="initAutocomplete" type="search" class="input-sm form-control" placeholder="Recherche">
            <button type="submit" class="btn btn-primary btn-sm"><span class="glyphicon glyphicon-eye-open"></span> Chercher</button>
        </div>
    </form>
</template>
<script>
    export default {
        props: {
            url: String
        },
        methods: {
            onSubmit: function (e)
            {
                e.preventDefault();
            },
            submit: function (e = null)
            {
                var word = $(this.$el).find('input').val();
                this.$emit('submit', word);
            }
            ,
            initAutocomplete: function (e) {
                
                if (this.url == null)
                    return;

                require('easy-autocomplete');

                var form = $(this.$el);
                var input = form.find("input");
                var button = form.find("button");
                var component = this;

                input.easyAutocomplete({
                    url: function (word) {
                        return component.url.replace('$', word);
                    },
                    list: {
                        maxNumberOfElements: 10,
                        onClickEvent: function (e)
                        {
                            button.click();
                        }
                    },
                    getValue: 'n',
                    template: {
                        type: "custom",
                        method: function (value, item)
                        {
                            return value + ' <span class="badge badge-info">' + item.w + '</span>';
                        }
                    }
                });
            }
        }
    }
    ;
</script>