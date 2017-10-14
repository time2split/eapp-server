<template>
    <div>
        TRI
        <div class="radio">
            <label><input type="radio" name="sort-type" value="weight" v-model="userConfig.sort_type">Poids</label>
        </div>
        <div class="radio">
            <label><input type="radio" name="sort-type" value="alpha" v-model="userConfig.sort_type">Nom</label>
        </div>
        <div class="form-group">
            Aperçu
            <label><input type="checkbox" v-model="userConfig.show.weight">Afficher les poids</label>
            <div v-if="shared.relation === null">
                <label><input type="checkbox" v-model="userConfig.show.empty">Afficher les relations vide</label>
                <label><input type="checkbox" v-model="userConfig.show.noempty">Afficher les relations pleines</label>
            </div>
        </div>
    </div>
</template>
<script>
    import { HUB } from '../vue/data.js';
    export default {
        data() {
            return {userConfig: HUB.shared.userConfig, shared: HUB.shared};
        }
    }
</script>