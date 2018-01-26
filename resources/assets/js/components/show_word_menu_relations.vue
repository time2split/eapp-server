<template>
    <div class="right col-sm-3 col-sm-push-9">
        <nav id="menu-relations">
            <a href="#" @click="selectAll" @click.prevent="onEventPrevent">Tout selectionner</a><br />
            <small>click+Ctrl pour sélection multiple</small>

            <form class="form-inline">
                <label class="form-check-label"><input class="form-check-input" type="checkbox" v-model="config.showEmpty" /> Afficher si vide</label>
            </form>
            <ul class="nav nav-pills nav-stacked">
                <li v-bind:class="{ active : selectedRelations[relation._id] }" v-for="relation in showRelations" v-if="showable(relation)" v-bind:id="'rel' + relation._id" >

                    <a href="#" @click.exact="changeRelation($event,relation)" @click.ctrl.exact="addRelation($event,relation)" @click.prevent="onEventPrevent">
                        {{ relation._id }}
                        <span v-if="relation.nom_etendu">
                            {{ relation.nom_etendu }}
                        </span>
                        <span v-else>
                            {{ relation.name }}
                        </span>
                        <span v-if="relationsInfos[relation._id].ALL.count === null" class="label label-info pull-right">
                            ?
                        </span>
                        <span v-else-if="relationsInfos[relation._id].ALL.count >= 0" class="label label-success pull-right">
                            <show-count v-bind:count="relationsInfos[relation._id].ALL.count"></show-count>
                        </span>
                        <span v-else class="label label-warning pull-right">
                            <show-count v-bind:count="relationsInfos[relation._id].ALL.nb"></show-count>
                        </span>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
</template>
<script>
    import { HUB } from '../vue/data.js';
    export default{
        props: ['showRelations', 'relationsInfos'],
        components: {
            'show-count': require('./show_count.vue')
        },
        data()
        {
            return {
                config: {
                    showEmpty: false
                },
                selectedRelations: {}
            };
        },
        methods: {
            onEventPrevent: HUB.onEventPrevent,
            changeRelation(event, relation)
            {
                this.selectedRelations = {};
                this.addRelation(event, relation);
            },
            addRelation(event, relation)
            {
                this.selectedRelations[relation._id] = relation;
                this.$emit('changeRelations', this.selectedRelations);
            },
            selectAll()
            {
                this.selectedRelations = {};

                for (var rel of this.showRelations) {
                    var relinfos = this.relationsInfos[rel._id];
                    
                    if (relinfos.ALL.count === null || relinfos.ALL.count > 0)
                        this.selectedRelations[rel._id] = rel;
                }
                this.$emit('changeRelations', this.selectedRelations);
            },
            showable(relation)
            {
                var config = this.config;
                var rel = this.relationsInfos[relation._id];

                return rel.ALL.count === null || config.showEmpty || rel.ALL.count > 0;
            }
        }
    };
</script>