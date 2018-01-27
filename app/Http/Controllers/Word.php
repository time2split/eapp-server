<?php

namespace App\Http\Controllers;

use App\Word as DBWord;
use App\Relation as DBRelation;
use App\RelationType as DBRelationType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class Word extends Controller
{
    private $dbWord;
    private $dbRelation;
    private $dbRelationType;
    private $relPagination;

    const CACHE_TIME_MIN = 60;

    public function __construct(DBWord $dbWord, DBRelation $dbRelation, DBRelationType $dbRelationType)
    {
        $this->dbWord         = $dbWord;
        $this->dbRelation     = $dbRelation;
        $this->dbRelationType = $dbRelationType;
        $this->relPagination  = config('app.pagination.relations', config('app.pagination.default', 20));
    }

//    public function app(string $word = null, string $relation = null)
//    {
//        if (is_numeric($word)) {
//            $w    = $this->dbWord->find((int) $word);
//            $word = $w->n;
//        }
//        return view('welcome', ['word' => $word, 'word_relation' => $relation, 'app' => null]);
//    }

    private function getWord(string $word)
    {
        $word = urldecode($word);
        return $this->dbWord->getWord($word);
    }

    public function get(string $word)
    {
        $word = urldecode($word);
        $w    = $this->getWord($word);

        if (empty($w))
            return [];

        $wa = $w->toArray();

        if (!isset($wa['nf']))
            $wa['nf'] = $wa['n'];

        $ret = ['word' => $wa];
        return $ret;
    }

    public function getWords(Request $request)
    {
        $words = $request->query('words', '');
        $words = explode(',', $words);
        $ret   = [];

        foreach ($words as $w) {
            $ret[] = $this->get($w)['word'] ?? [];
        }
        return $ret;
    }

    private function getChildsOrParents(string $what, string $word, Request $request)
    {
        $word         = urldecode($word);
        $ret          = [];
        $cacheData    = null;
        $cacheUpdated = false;
        $relations    = explode(',', $request->query('rtid', null));
        $per_page     = $request->query('per_page', $this->relPagination);
        $count        = $request->query('count', null);
        $cacheKey     = null;
        $w            = $this->getWord($word);

        if (empty($w))
            return [];

        if ($count === null)
            $count = false;
        else
            $count = $count !== 'false';

        if ($count) {
            $cacheKey = "JDM:word:$word:count:$what";

            if (Cache::has($cacheKey)) {
                $cacheData = Cache::get($cacheKey, []);
            }
        }

        foreach ($relations as $rel) {

            if (isset($cacheData[$rel])) {
                $ret[$rel] = $cacheData[$rel];
                continue;
            }

            if ($cacheKey !== null)
                $cacheUpdated = true;

            $qrelations = $this->dbRelation->where($what, $w->_id);

            if ($rel !== null)
                $qrelations->where('t', (int) $rel);

            if ($count)
                $ret[$rel] = $qrelations->count();
            else {
                $ret[$rel] = $qrelations->simplePaginate((int) $per_page);
            }
        }

        if (count($relations) === 1)
            $ret = $ret[$rel];

        if ($cacheUpdated)
            Cache::put($cacheKey, $ret, self::CACHE_TIME_MIN);

        return $ret;
    }

    public function getChilds(string $word, Request $request)
    {
        return $this->getChildsOrParents('n1', $word, $request);
    }

    public function getParents(string $word, Request $request)
    {
        return $this->getChildsOrParents('n2', $word, $request);
    }

    public function rel_autocomplete(string $word)
    {
        $words = $this->dbRelationType->where('name', 'like', "$word%")->orderBy('name', 'desc')->orderBy('_id');
        $ret   = $words->get();
        return $ret;
    }

    public function autocomplete(Request $request, string $relation)
    {
        $words = $this->dbWord->where('n', 'like', "$relation%")->orderBy('w', 'desc');

        if (($nb = $request->query('nb', 20)) >= 0) {

            if ($nb === 0)
                return [];

            $words = $words->limit((int) $nb);
        }

        if (($order = $request->query('order', 'nf'))) {
            $words = $words->orderBy($order);
        }
        $ret = $words->get();

        foreach ($ret as &$w)
            $w->makeIt();

        return $ret;
    }

    public function relationTypes(Request $request)
    {
        $get      = $request->query('get');
        $rels     = $this->dbRelationType->all();
        $excluded = $get === 'excluded';

        if ($excluded) {
            $tmp = [];

            foreach ($rels as $r) {
                if (in_array($r->name, ['r_chunk_sujet', 'r_chunck_objet', 'r_flpot']) || $r->info === '' || strpos($r->info, '(interne)') === 0)
                    $tmp[] = $r;
            }
            return $tmp;
        }
        $rels = $rels->toArray();
        usort($rels, function($a, $b) {
            return $a['_id'] - $b['_id'];
        });
        return $rels;
    }
}