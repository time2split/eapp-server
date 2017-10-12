<script>
    const shared = {
        word: null,
        words: {},
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
    export default {
        data() {
            return {
                wordComputed: false,
                wordQ: [],
                default: null,
                shared: shared
            };
        },
        created: function ()
        {
            window.onpopstate = this.popstate;
            this.$watch('wordQ', this.getAWord);
        },
        methods: {
            popstate(e) {

                if (e.state) {
                    shared.word = e.state.word;
                }
            },
            changeWord(word) {

                if (shared.word == word)
                    return;
                
                shared.word = word;
                var data = this.$data.shared;
                history.pushState(data, this.word, encodeURI("/" + this.word));
            },
            addHttpRequest(page, callThen, callCatch = null)
            {
                axios.get(page).then(callThen).catch(callCatch);
            },
            askForWord: function (wid)
            {
                if (!shared.words[wid] && this.wordQ.indexOf(wid) == -1)
                    this.wordQ.push(wid);
            },
            getAWord: function ()
            {
                if (this.wordComputed)
                    return;

                this.wordComputed = true;
                this.fillWords();
            },
            fillWords: function ()
            {
                var nb = shared.config.get_words.nb;
                var wids = [];

                for (var i = 0; i < nb && this.wordQ.length; i++)
                {
                    wids.push(this.wordQ.pop());
                }
                var url = '/@get/words?words=' + wids.join(',');

                this.addHttpRequest(url, (response) => {
                    var words = response.data;

                    for (var word of words)
                    {
                        var wid = word._id;
                        this.$set(shared.words, wid, word);
                    }

                    if (this.wordQ.length == 0)
                        this.wordComputed = false;
                    else
                        this.fillWords();
                });
            },
        }
    };
</script>