<script>
    const shared = {
//        word: null, // Le mot en cours (chaine)
//        relation: null, //La relation en cours
        words: {}, //Des mots à rechercher si besoin
        relationTypes: null, //Les types de relations (toute la base de données)
//        component: "show-welcome", //Le composant principal utilisé
        htmlTitle: null,
        wordsData: {}, //Infos à stocker sur un mot (relations ...)
        app: {
            direction: null,
            action: null,
            data: {},
            path: [],
            isNew: false
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
        data()
        {
            return {
                wordQ: [],
                default: null,
                shared: shared,
                wordComputed: false, //En train de calculer les mots ?
                url: {
                    first: {},
                    current: {}
                }
            };
        },
        created: function ()
        {
            window.onpopstate = this.popstate;
            this.$watch('wordQ', this.getAWord);
            shared.htmlTitle = $('title').text();

            var url = this.urlMakeCurent();
            Object.assign(this.url.first, url); //clone
            Object.assign(this.url.current, url); //clone
        },
        methods: {
            urlMakeCurent()
            {
                var path = window.location.pathname.split('/');
                var vars = {};

                window.location.search.replace(/[?&]+([^=&]+)=?([^&]*)?/gi,
                        (m, key, value) =>
                {
                    vars[key] = value !== undefined ? value : '';
                }
                );
                return {
                    path: path,
                    args: vars,
                    string: window.location.toString()
                };
            },
            getUrl(first = false)
            {
                if (first)
                    return this.url.first;

                var winlocale = window.location.toString();
                var current = this.url.current.string;

                if (current !== winlocale)
                    Object.assign(this.url.current, this.urlMakeCurent());

                return this.url.current;
            },
            //==================================================================
            //HTML
            //==================================================================

            getImgLoader()
            {
                return '<img src="/images/ajax-loader.gif">';
            },
            onEventPrevent(e)
            {
                e.preventDefault();
            },
            //==================================================================
            //HISTORIQUE
            //==================================================================

            popstate(e)
            {
                if (e.state) {
                    shared.app = e.state.app;
                }
                else {
                    shared.app = null;
                }
            },
            //==================================================================
            //SHARED
            //==================================================================

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

//            changeRelation(relation)
//            {
//                if (shared.relation == relation)
//                    return;
//                shared.app = {
//                    direction: '@app:site',
//                    action: 'show-word',
//                    data: {
//                        word: shared.word,
//                        relation: relation,
//                    },
//                }
//            },
//            changeWord(word)
//            {
////                console.log("changeWord()");
//
//                if (shared.word == word)
//                    return false;
////                console.log("changeWord(" + word + ")");
//                shared.word = word;
//                shared.app = {
//                    direction: '@app:site',
//                    action: 'show-word',
//                    data: {
//                        word: word,
//                        relation: null,
//                    },
//                    isNew: true
//                };
//                return true;
//            },
            //==================================================================
            // CHARGEMENT DES MOTS
            //==================================================================

            askForWord(wid)
            {
                if (!shared.words[wid] && this.wordQ.indexOf(wid) == -1)
                    this.wordQ.push(wid);
            },
            getAWord()
            {
                if (this.wordComputed)
                    return;
                this.wordComputed = true;
                this.fillWords();
            },
            getWord(wid)
            {
                if (shared.words[wid])
                    return shared.words[wid];

                this.askForWord(wid);
                return null;
            },
            fillWords()
            {
                var nb = shared.config.get_words.nb;
                var wids = [];

                for (var i = 0; i < nb && this.wordQ.length; i++) {
                    wids.push(this.wordQ.pop());
                }
                var url = '/@get/words?words=' + wids.join(',');
                this.addHttpRequest(url, (response) => {
                    var words = response.data;

                    for (var word of words) {
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