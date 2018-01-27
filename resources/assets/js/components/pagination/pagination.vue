<template>
    <nav v-if="nbPages > 1">
        <ul class="pagination pagination-sm">
            <li v-if="nbPages >= minPageForNextPrev" class="page-item" v-bind:class="{ disabled : currentPage <= 1 }">
                <a class="page-link" href="#"@click.prevent="onEventPrevent" @click="changePage(currentPage-1)"><span class="glyphicon glyphicon-arrow-left"></span></a>
            </li>
            <li v-for="i in nbPages" class="page-item" v-bind:class="{active : i == currentPage}"><a class="page-link" href="#" @click.prevent="onEventPrevent" @click="changePage(i)">{{ i }}</a></li>
            <li v-if="nbPages >= minPageForNextPrev" class="page-item" v-bind:class="{ disabled : currentPage >= nbPages }">
                <a class="page-link" href="#"@click.prevent="onEventPrevent" @click="changePage(currentPage+1)"><span class="glyphicon glyphicon-arrow-right"></span></a>
            </li>
        </ul>
    </nav>
</template>
<script>
    import { HUB } from '../../vue/data.js';
    export default {
        props: {
            total: Number,
            perPage: Number,
            currentPage: {
                type: Number,
                default: 1
            },
            minPageForNextPrev: {
                type: Number,
                default: 2
            }
        },
        computed: {
            nbPages()
            {
                return Math.ceil(this.total / this.perPage);
            }
        },
        created()
        {
        },
        methods: {
            onEventPrevent: HUB.onEventPrevent,
            changePage(page)
            {
                if (page < 1)
                    page = 1;
                else if (page > this.nbPages)
                    page = this.nbPages;

                this.$emit('update:currentPage', page);
            }
        }
    }
    ;
</script>
