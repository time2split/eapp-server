<script>
    export default {
        data() {
            return {
                word: null,
                default: null,
                relationTypes: null,
                component: "show-welcome",
                config: {
                    show_word: {
                        per_page: 2000,
                        max_page: 2
                    },
                    get_words: {
                        nb: 1000
                    },
                    relations: {
                        exclude: []
                    }
                }
            };
        },
        created: function ()
        {
            window.onpopstate = this.popstate;
        },
        methods: {
            popstate(e) {

                if (e.state) {
                    this.word = e.state.word;
                }
            },
            changeWord(word) {

                if (this.word == word)
                    return;
                this.word = word;
                
                var data = this.$data;
                history.pushState(data, this.word, "/" + this.word);
            }
        }
    };
</script>