<script>
    const shared = {
        words: {}, //Des mots à rechercher si besoin
        relationTypes: null, //Les types de relations (toute la base de données)
        htmlTitle: null,
        wordsData: {}, //Infos à stocker sur un mot (relations ...)
        /*
         * Page actuelle
         */
        app: {
            direction: null,
            action: null,
            data: {},
            path: [],
            isNew: false
        },
        config: {
            show_word: {
                per_page: 500, //Page lors d'un chargement ajax
//                max_page: 10,
//                min_page_for_counts: 10
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
                noempty: true,
                wordPerPage: 500 //Page dans l'affichage
            }
        }
    };
    export default {
        data()
        {
            return {
                wordTime: 0, //Intervalle (ms) entre les demande des mots
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
//            this.$watch('wordQ', this.getAWord);
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
            // CHARGEMENT DES MOTS
            //==================================================================

            askForWord(wid)
            {
                if (!shared.words[wid] && this.wordQ.indexOf(wid) == -1) {
                    this.wordQ.push(wid);
                    this.getAWord();
                }
            },
            getAWord()
            {
//                if (this.wordTime === null) {
//                    this.wordTime = Date.now();
//                    setTimeout(this.getAWord, 100);
//                }

                if (this.wordComputed)
                    return;

                this.wordComputed = true;
                setTimeout(this.fillWords, this.wordTime);
//                this.fillWords();
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

                for (var i = 0; i < nb && i < this.wordQ.length; i++) {
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
                    else {
//                        this.fillWords();
                        setTimeout(this.fillWords, this.wordTime);
                    }
                });
            },
        }
    };
</script>