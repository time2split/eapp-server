<template>
    <section v-if="relations" class="show-relation">
        <h1>{{ relation.name }}</h1>
        <ul class="list-inline">
            <li class="list-inline-item" v-for="rel in relations">
            <a-word :relation="rel" :words="words"></a-word>
            </li>
        </ul>
    </section>
</template>
<script>
    export default{
        props: ['relation', 'relations', 'words'],
        components: {
            'a-word': require('./a_word.vue')
        }
    }
</script>