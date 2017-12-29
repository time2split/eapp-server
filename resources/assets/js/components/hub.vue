<script>
    const shared = {
        word: null, // Le mot en cours (chaine)
        relation: null, //La relation en cours
        words: {}, //Des mots à rechercher si besoin
        relationTypes: null, //Les types de relations (toute la base de données)
        component: "show-welcome", //Le composant principal utilisé
        htmlTitle: null,
        wordsData: {}, //Infos à stocker sur un mot (relations ...)
        app: {
            direction: null,
            action: null,
            args: null
        },
        config: {
            show_word: {
                per_page: 200,
                max_page: 10,
                min_page_for_counts: 5
            },
            get_words: {
                nb: 1000
            },
            relations: {
                exclude: []
            }
        },
        userConfig: {
            sort_type: 'alpha',
            show: {
                weight: true,
                empty: false,
                noempty: true
            }
        }
    };
    export default {
        data() {
            return {
                wordQ: [],
                default: null,
                shared: shared,
                wordComputed: false, //En train de calculer les mots ?
            };
        },
        created: function ()
        {
            window.onpopstate = this.popstate;
            this.$watch('wordQ', this.getAWord);
            shared.htmlTitle = $('title').text();
        },
        methods: {
            //==================================================================
            //HTML
            //==================================================================
            getImgLoader()
            {
                return '<img src="http://127.0.0.1:8000/images/ajax-loader.gif">';
            },
            //==================================================================
            //HISTORIQUE
            //==================================================================
            popstate(e) {
//                console.log('pop');

                if (e.state) {
//                console.log(e.state);

                    if (e.state.app)
                    {
                        shared.app = e.state.app;
                    } else {
                        shared.word = e.state.word;
                        shared.relation = e.state.relation;
                    }
                }
            },

            //==================================================================
            //SHARED
            //==================================================================
            //
            set(k, v)
            {
                this[k] = v;
            },
            sset(target, k, v)
            {
                this.$set(target, k, v);
            },
            //==================================================================
            addHttpRequest(page, callThen, callCatch = null)
            {
                var token = axios.CancelToken.source();
                axios.get(page, {cancelToken: token.token}).then(callThen).catch(callCatch);
                return token;
            },
            //==================================================================
            changeRelation(relation)
            {
                if (shared.relation == relation)
                    return;

                shared.relation = relation;
                var data = this.$data.shared;
                history.pushState(data, shared.relation, encodeURI("/" + shared.word + '/' + shared.relation));
            },
            changeWord(e)
            {
                var word = $(e.target).find('input').val();
                shared.relation = null;

                if (shared.word == word)
                    return;

                shared.word = word;
                var data = this.$data.shared;
                history.pushState(data, shared.word, encodeURI("/" + shared.word));
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
            getWord(wid) {

                if (shared.words[wid])
                    return shared.words[wid];

                this.askForWord(wid);
                return null;
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