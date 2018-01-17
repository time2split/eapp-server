<template>
    <div class="container-fluid">
        <form @submit="onSubmit" @submit.prevent="onSubmitPrevent">
            <search-text id="w1" url="/@word/$/autocomplete" placeholder="Mot 1"></search-text>
            <search-text id="relation" url="/@word/$/rel_autocomplete" :config="search_config" placeholder="Relation"></search-text>
            <search-text id="w2" url="/@word/$/autocomplete" placeholder="Mot 2"></search-text>
            <button type="submit" class="btn btn-primary btn-sm"><span class="glyphicon glyphicon-eye-open"></span> Chercher</button>
            <fieldset>
                <legend>Options</legend>
                <div class="">

                    <div class="row form-row">
                        <div class="form-group col-sm-3">
                            <label>Temps max (s) <input class="form-control input-sm" type="number" v-model="config.time_max" /></label>
                        </div>
                        <div class="form-group col-sm-3">
                            <label>Profondeur max <input class="form-control input-sm" type="number" v-model="config.depth_max" /></label>
                        </div>
                    </div>


                    <div class="row form-row">
                        <small class="form-text text-muted">Les champs suivant associent des valeurs par profondeur dans l'arbre de recherche séparées par des virgules</small>
                    </div>
                    <div class="form-row row">

                        <div class="form-group col-sm-3">
                            <label>Nombre max de résultats <input class="form-control input-sm" type="text" v-model="config.result_max" /></label>
                        </div>
                        <div class="form-group col-sm-3">
                            <label>Cardinalité des domaines <input class="form-control input-sm" type="text" v-model="config.domain_nbValues" /></label>
                        </div>
                        <div class="form-group col-sm-6">
                            <label>Facteur de division <input class="form-control input-sm" type="text" v-model="config.filter_oneResult_divFactor" /></label>
                            <small class="form-text text-muted row">
                                Defini un poids minimal pour qu'un résultat soit accepté.
                                Le poid de la conclusion est divisé par cette valeur et forme une borne inférieure.
                                Les poids des hypothèses doivent être supérieur à cette borne pour que le résultat soit accepté.
                            </small>

                        </div>
                    </div>

                </div>
            </fieldset>
        </form>
        <div class="panel" id="result"></div>
    </div>
</template>
<script>
    /*
     * 
     'time_max'                       => 40,
     'domain_order_rand'              => false,
     'depth_max'                      => 1,
     'filter_oneResult_divFactor'     => [2,3],
     'domain_nbValues'                => [40, 20, 10],
     'result_max'                     => [10],
     'filter_oneResult_divFactor_def' => 4,
     'domain_nbValues_def'            => 4,
     'result_max_def'                 => 1,
     */
    import { HUB } from '../../vue/data.js';
    export default{
        data()
        {
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
                config: {
                    'time_max': 40,
                    'domain_order_rand': false,
                    'depth_max': 1,
                    'filter_oneResult_divFactor': [2, 3],
                    'domain_nbValues': [40, 20, 10],
                    'result_max': [10, 20],
                    'filter_oneResult_divFactor_def': 4,
                    'domain_nbValues_def': 4,
                    'result_max_def': 1,
                },
                httpToken: null
            };
        },
        components: {
            'search-text': require('../form/search.vue')
        },
        beforeCreate()
        {
            var title = HUB.$data.shared.htmlTitle;
            $('title').text(title + ' - Moteur d\'inférence');

            var app = HUB.$data.shared.app;

            if (app.data.pengine === undefined)
            {
                app.data.pengine = {};
                app.data.pengine.config = this.config;
            }
            else {
                this.config = app.data.pengine.config;
            }
        },
        beforeDestroy()
        {
            $('title').text(HUB.$data.shared.htmlTitle);

            if (this.httpToken != null) {
                this.httpToken.cancel();
            }
            app.data.pengine.config = this.config;
        },
        methods: {
            onSubmitPrevent: HUB.onEventPrevent,
            onSubmit()
            {
                var w1 = $('#w1 input').val();
                var w2 = $('#w2 input').val();
                var r = $('#relation input').val();

                if (this.httpToken != null) {
                    this.httpToken.cancel();
                }
                
                if (w1 === '' || w2 === '' || r === '') {
                    $('#result').html('Formulaire incomplet');
                    return;
                }
                $('#result').html(HUB.getImgLoader());

                var params = '';

                for (var cname in this.$data.config)
                {
                    params += '&config[' + cname + ']=' + this.config[cname];
                }

                this.httpToken = HUB.addHttpRequest('/@jdmpattern/' + w1 + '/' + r + '/' + w2 + '?print=1' + params, (response) => {
                    $('#result').html(response.data);
                }, (error) => {
                    $('#result').html(error);
                });
            }
        }
    };
</script>