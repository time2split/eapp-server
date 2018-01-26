<template>
    <form class="form-inline">
        <fieldset class="form-group">
            <div class="form-check form-check-inline">
                <label class="form-check-label">
                    <input class="form-check-input" type="radio" name="sort-type" value="weight" v-model="userConfig.sort_type">Tri par Poids
                </label>
            </div>

            <div class="form-check form-check-inline">
                <label class="form-check-label">
                    <input class="form-check-input" type="radio" name="sort-type" value="alpha" v-model="userConfig.sort_type">Tri par Nom
                </label>
            </div>
        </fieldset>
        <fieldset class="form-group">
            <div class="form-check">
                <label class="form-check-label">
                    <input class="form-check-input" type="checkbox" v-model="userConfig.show.weight">
                    Afficher les poids
                </label>
            </div>
        </fieldset>
    </form>
</div>
</template>
<script>
    import { HUB } from '../vue/data.js';
    export default {
        data()
        {
            return {userConfig: HUB.shared.userConfig};
        }
    }
</script>