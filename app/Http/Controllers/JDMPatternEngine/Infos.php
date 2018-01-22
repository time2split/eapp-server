<?php

namespace App\Http\Controllers\JDMPatternEngine;

use App\JDMPatternEngine\FCInfos;
use App\Relation;
//use App\JDMPatternEngine\FC;
//use App\JDMPatternEngine\RuleManagerFactory_file;
use App\JDMPatternEngine\Database;
use App\JDMPatternEngine\Rule;
use App\JDMPatternEngine\Atom;
use App\JDMPatternEngine\Term;
use Illuminate\Support\Facades\Cache;
use App\Word;

class Infos extends FCInfos
{
    const CONFIG_DEF = [
        'time_max'                       => 40,
        'domain_order_rand'              => false,
        'depth_max'                      => 1,
        'filter_oneResult_divFactor'     => [2, 3],
        'domain_nbValues'                => [40, 20, 10],
        'result_max'                     => [10],
        'filter_oneResult_divFactor_def' => 4,
        'domain_nbValues_def'            => 4,
        'result_max_def'                 => 1,
    ];

    private $startTime;
    private $db;
    private $Relation;
    private $asked = []; //Déjà demandés
    private $excludeWords;

    public function __construct(Database $db, Relation $relation, Word $dbWord, $config = [])
    {
        $this->db       = $db;
        $this->Relation = $relation;

        if (Cache::has('JDM:Infos:excluded')) {
            $this->excludeWords = Cache::get('JDM:Infos:excluded');
        }
        else {
            $tmp                = $dbWord->select('_id')->where('n', 'like', "_%")->get();
            $this->excludeWords = array_column($tmp->toArray(), '_id');
            Cache::set('JDM:Infos:excluded', $this->excludeWords, 10);
        }
        $this->startTime = time();
        $this->config    = array_merge(self::CONFIG_DEF, (array) $config);

        foreach ($this->config as $k => &$v) {
            $this->{'conf_' . $k} = & $v;
        }
    }

    public function canDoRecursion()
    {
        if ($this->depth >= $this->conf_depth_max)
            return null;

//        if ($this->calls > 100)
//            return false;

        return true;
    }

    public function canIterate()
    {
        if (time() - $this->startTime > $this->conf_time_max)
            return false;

        return true;
    }

//    public function minWeight()
//    {
//        return $this->conf_result_max[$this->depth];
//    }

    public function getNbMaxResults()
    {
        return $this->conf_result_max[$this->depth] ?? $this->conf_result_max_def;
    }

    public function filterOneResult($res)
    {
        $resWeight = $res['result']->getWeight();
        $inf       = $resWeight / ($this->conf_filter_oneResult_divFactor[$this->depth] ?? $this->conf_filter_oneResult_divFactor_def);

        foreach ($res['bind']->getHypotheses() as $term) {
            $w = $term->getWeight();

            if ($w < $inf) {
                return false;
            }
        }
        return true;
    }

    private function filterTerm($term, /* $minWeight = null, */ $excludeWords = null)
    {
        $atoms = $term->getAtoms();
        $w     = $term->getWeight();

        if ($excludeWords === null) {
            $excludeWords = $this->excludeWords;
        }
//        if ($minWeight === null) {
//            $minWeight = $this->minWeight();
//        }
        return !in_array($atoms[0]->getValue(), $excludeWords) && !in_array($atoms[1]->getValue(), $excludeWords)
        //&& ($w >= 0 && $w >= $minWeight) || $w < 0
        ;
    }

    private function filterDomain($domain)
    {
        $excludeWords = $this->excludeWords;
//        $minWeight    = $this->minWeight();
        $me           = $this;

        return array_filter($domain, function($item) use($me, /* $minWeight, */ $excludeWords) {
            return $this->filterTerm($item[0], $excludeWords);
        });
    }

    private function getValuesOfDomain($domain)
    {
        return array_map(function($e) {
            return $e[0]->getAtoms()[$e['varPos']]->getValue();
        }, $domain);
    }

    public function selectDomain($domain)
    {
        /*
         * Ajout de l'information varPos pour chaque entrée
         */
        foreach ($domain as &$dom) {
            $varPos        = $dom['varPos'];
            $dom['domain'] = array_map(function($e) use ($varPos) {
                return ['varPos' => $varPos, $e];
            }, $dom['domain']);
        }

        //Intersection si derniere profondeur atteinte
        if ($this->depth >= $this->conf_depth_max) {
            $tmp = [];

            foreach ($domain as $dom) {
                $tmp[] = $this->getValuesOfDomain($dom['domain']);
            }
            
            if (count($tmp) > 1)
                $tmp    = array_intersect(...$tmp);
            
            $domain = array_intersect_key($domain[0]['domain'], $tmp);
        }
        else {
            $domain = array_merge(...array_column($domain, 'domain'));
        }
        $domain = $this->filterDomain($domain);

        usort($domain, function($terma, $termb) {
            return $termb[0]->getWeight() - $terma[0]->getWeight();
        });
        $nbVal = $this->conf_domain_nbValues[$this->depth] ?? $this->conf_domain_nbValues_def;

        //Dédoublonnage
        $tmp    = $this->getValuesOfDomain($domain);
        $tmp    = array_unique($tmp);
        $domain = array_intersect_key($domain, $tmp);


        $positive = array_filter($domain, function($e) {
            return $e[0]->getWeight() >= 0;
        });
        $negative = array_filter($domain, function($e) {
            return $e[0]->getWeight() < 0;
        });
        $cpos = count($positive);
        $cneg = count($negative);
        $mid  = floor($nbVal / 2.0);

        if ($cpos >= $mid && $cneg >= $mid) {
            $cpos = ceil($nbVal / 2.0);
            $cneg = floor($nbVal / 2.0);
        }
        elseif ($cpos < $mid) {
            $cneg = $nbVal - $cpos;
        }
        elseif ($cneg < $mid) {
            $cpos = $nbVal - $cneg;
        }
        $a = array_merge(array_slice($positive, 0, $cpos), array_slice($negative, - $cneg));

        if ($this->conf_domain_order_rand) {
            shuffle($a);
        }
        $a = array_map(function($e) {
            return $e[0]->getAtoms()[$e['varPos']]->getValue();
        }, $a);
        return $a;
    }

    /**
     * Moyenne géométrique
     * 
     * @param iterable $ruleBinded
     * @return int
     */
    public function computeWeight($ruleBinded)
    {
        $w      = 1;
        $factor = 1;

        foreach ($ruleBinded->getHypotheses() as $atom) {
            $tmp = $atom->getWeight();
            $neg = $tmp < 0;

            if ($neg)
                $factor = -1;

            $w *= $neg ? -$tmp : $tmp;
        }
        return (int) pow($w, 1 / count($ruleBinded->getHypotheses())) * $factor;
    }

    /**
     * Retourne les relations depuis mongodb
     * @param type $demand
     * @return type
     * @throws Exception
     */
    private function moreRelationsAskFor($demand)
    {
        if (is_array($demand)) {
            $q     = $this->Relation;
            $asked = [];

            foreach ($demand as $k => $v) {
                if (isset($asked[$k]) && $asked[$k] == $v) {
                    throw new Exception("Ask for $k => $v already presents");
                }
                $asked[$k] = $v;
                $q         = $q->where($k, $v);
            }
            $relations = $q->get();
            return $relations;
        }
        elseif ($demand instanceof Term) {
            $tmpAsked = array_filter($this->asked, function($e) use($demand) {
                return $e->getPredicate() === $demand->getPredicate() && $e->variableMatch($demand);
            });

            if (!empty($tmpAsked)) {
                return [];
            }
            $this->asked[] = clone $demand;
            $constants     = $demand->getConstants();
            $corresp       = ['n1', 'n2'];
            $demand        = [];

            foreach ($constants as $pos => $const) {
                $demand[$corresp[$pos]] = $const->getValue();
            }
            return $this->moreRelationsAskFor($demand);
        }
        else {
            throw new Exception("Unknow ask !");
        }
    }

    private function relations2Terms($relations)
    {
        $terms = [];
        foreach ($relations as $rel) {
            $term    = new Term($rel->t, $rel->w, new Atom('n1', $rel->n1), new Atom('n2', $rel->n2));
            $terms[] = $term;
        }
        return $terms;
    }

    public function moreData($on)
    {
        if ($this->depth > $this->conf_depth_max) {
            return null;
        }

        if (is_array($on)) {
            foreach ($on as $val) {
                $this->moreData($val);
            }
        }
        elseif ($on instanceof Term) {
            $relations = $this->moreRelationsAskFor($on);
            
            if(empty($relations))
                return null;
            
            $terms     = $this->relations2Terms($relations);
            $this->db->addTerm(...$terms);
            return true;
        }
        elseif ($on instanceof Rule) {
            //Seules les hypothèses nous intéresse : la conclusion doit être bindée
            $this->moreData($on->getHypotheses());
        }
        return null;
    }
}