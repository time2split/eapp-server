<template>
    <div class="container-fluid">
        <form @submit="onSubmit" @submit.prevent="onSubmitPrevent">
            <search-text id="w1" url="/@word/$/autocomplete" placeholder="Mot 1"></search-text>
            <search-text id="relation" url="/@word/$/rel_autocomplete" :config="search_config" placeholder="Relation"></search-text>
            <search-text id="w2" url="/@word/$/autocomplete" placeholder="Mot 2"></search-text>
            <button type="submit" class="btn btn-primary btn-sm"><span class="glyphicon glyphicon-eye-open"></span> Chercher</button>
        </form>
        <div class="panel" id="result"></div>
    </div>
</template>
<script>
    import { HUB } from '../../vue/data.js';
    export default{
        data() {
            return {
                search_config: {
                    getValue: 'name',
                    template: {
                        type: "custom",
                        method(value)
                        {
                            return value;
                        }
                    }
                },
                httpToken: null
            }
        },
        components: {
            'search-text': require('../form/search.vue')
        },
        created()
        {
            HUB.$data.shared.app = {
                direction: '@app:service',
                action: 'jdmpattern'
            };
            history.pushState({app: HUB.$data.shared.app}, null, '/@app:service/jdmpattern');
        },
        methods: {
            onSubmitPrevent(e)
            {
                e.preventDefault();
            },
            onSubmit(e)
            {
//                console.log($('#w1 input').val());
                var w1 = $('#w1 input').val();
                var w2 = $('#w2 input').val();
                var r = $('#relation input').val();

                if (this.httpToken != null){
                    this.httpToken.cancel();
                }
                $('#result').html(HUB.getImgLoader());

                this.httpToken = HUB.addHttpRequest('/@jdmpattern/' + w1 + '/' + r + '/' + w2 + '?print=1', (response) => {
//                    this.relationTypes = response.data;
//                    console.log(response.data);
                    $('#result').html(response.data);
                }, (error) => {
                    $('#result').html(error);
                });
            }
        }
    };
</script>