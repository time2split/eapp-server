<?php

namespace App\JDMPatternEngine;

use App\JDMPatternEngine\Database;
use App\JDMPatternEngine\RuleManager;
use App\JDMPatternEngine\Term;
use App\JDMPatternEngine\FCInfos;
use Exception;

/**
 * Chaînage avant
 */
class FC
{
    private $rules;
    private $db;

    public function __construct(RuleManager $rules, Database $db)
    {
        $this->rules = $rules;
        $this->db    = $db;
    }

    private function searchDomains($variables)
    {
        
    }

    public function directAsk(Term $conclusion)
    {
        $search = $this->db->matchingTerms($conclusion);

        if ($search !== false) {
            return $search;
        }
        return null;
    }

    private function getQueryOrder($freeVariables, $hterms)
    {
        $htermsStats = new \SplObjectStorage;

//        var_dump( $freeVariables );
//            $this->searchDomains( $freeVariables );
        //init de $htupleStats

        $fgetOneVarTerms = function() use ($hterms, $htermsStats) {
            return array_filter($hterms, function($hterm) use ($htermsStats) {
                $stats = $htermsStats[$hterm];
                return count($hterm->getAtoms()) - $stats['free'] == 1;
            });
        };

//        foreach ($freeVariables as $var)
        {

            foreach ($hterms as $term) {
                $nbFreeVariables = 0;

                foreach ($term->getAtoms() as $atom) {
                    if ($atom->isVariable())
                        $nbFreeVariables++;
                }
                $htermsStats[$term] = ['free' => $nbFreeVariables, /* 'term' => $term, */ 'domain' => []];
            }
        }

        //Caclul de queryOrder
        $queryOrder = [];

        while (true) {
            $oneVarTerms = $fgetOneVarTerms();
            $queryOrder  = array_merge($queryOrder, $oneVarTerms);
            $hterms      = array_diff($hterms, $oneVarTerms);

            if (empty($freeVariables))
                break;

            foreach ($oneVarTerms as $onevterm) {
                $var = $onevterm->getVariables();
                $var = array_pop($var);

                foreach ($hterms as $k => $hterm) {

                    if (!$hterm->hasVariable($var->getName()))
                        continue;
                    $stats               = $htermsStats[$hterm];
                    $stats['free'] --;
                    $htermsStats[$hterm] = $stats;

                    if ($stats['free'] == 0) {
//                            if ( !in_array( $hterm, $queryOrder ) )
                        $queryOrder[] = $hterm;
                        unset($hterms[$k]);
                    }
                }
            }
            break;
        }
        if (!empty($hterms))
            throw new Exception("Erreur dans la règle $ruleBinded, impossible de détecter le domaine de toutes les variables");

        return $queryOrder;
    }

    private function getDomains($queryOrder, FCInfos $info)
    {
        $domains = [];

        foreach ($queryOrder as $hterm) {
            $vars    = $hterm->getVariables();
            $cte     = $hterm->getConstants();
            $mustAsk = false;

            foreach ($vars as $pos => $var) {
                $varName = $var->getName();

                if (isset($domains[$varName])) {
                    continue;
                }
                $mustAsk = true;
                //Impossible qu'il y ai plus de 1 variables
                break;
            }

            if (!$mustAsk)
                continue;

            $info->moreData($hterm);
            $res = $this->directAsk($hterm);

            if (empty($res))
                return null;

            $res = $info->filterDomain($res);
            $res = $info->selectDomain($res);

            $vals = array_map(function($e) use ($pos) {
                return $e->getAtoms()[$pos]->getValue();
            }, $res);

            //Ordonne les clés
            $domains[$varName] = array_values($vals);
        }
        return $domains;
    }

    private function nextBind($varOrder, $domains, &$data)
    {
        $bind = null;

        if ($data === null) {
            $bind = [];

            foreach ($varOrder as $varName => $var) {
                $domain = $domains[$varName];

                if (empty($domain)) {
                    return null;
                }
                $val            = $domain[0];
                $data[]         = ['current' => 0, 'last' => count($domain) - 1, 'varName' => $varName];
                $bind[$varName] = $val;
            }
        }
        else {
            $i     = count($data) - 1;
            $cdata = & $data[$i];

            if ($cdata['current'] == $cdata['last']) {
                $bind = [];
                do {
                    if ($i === 0)
                        return null;

                    $varName          = $cdata['varName'];
                    $domain           = $domains[$varName];
                    $val              = $domain[0];
                    $bind[$varName]   = $val;
                    $cdata['current'] = 0;
                    $i--;
                    $cdata            = & $data[$i];
                }
                while ($cdata['current'] == $cdata['last']);
            }
            $varName        = $cdata['varName'];
            $pos            = ++$cdata['current'];
            $domain         = $domains[$varName];
            $val            = $domain[$pos];
            $bind[$varName] = $val;
        }
        return $bind;
    }

    public function ask(Term $conclusion, FCInfos $info)
    {
        $ret    = [];
        $info->moreData($conclusion);
        $direct = $this->directAsk($conclusion);

        if ($direct !== null) {
            if (!isset($direct[0])) {
                var_dump($direct);
                exit;
            }
            $ret[] = ['rule' => null, 'bind' => null, 'result' => $direct[0], 'asks' => []];
        }
        return array_merge($ret, $this->ask_($conclusion, $info));
    }

    private function ask_(Term $conclusion, FCInfos $info, $excludedWords = [])
    {
        $ret         = [];
        $rules       = $this->rules;
        $applicables = $rules->getRulesWithConclusion($conclusion);

        /*
         * Atomes
         *  ne devant pas apparaitre comme destination dans un terme
         * Limité au atomes pire depart -> arrivé
         */
        $excludedWords[] = $conclusion->getAtom(0)->getValue();
        $excludedWords[] = $conclusion->getAtom(1)->getValue();
//        
        //Règles applicables
        foreach ($applicables as $rule) {
            $rconcl = $rule->getConclusion();
            $bind   = [];

            //RÉCUPÉRATION DES CONSTANTES À BINDER
            foreach ($rconcl->getAtoms() as $k => $atom) {
                $name        = $atom->getName();
                $val         = $conclusion->getAtom($k)->getValue();
                $bind[$name] = $val;
            }
//            $asks       = [];
            $ruleBinded = clone $rule;
            $ruleBinded->bind($bind);

            //Calcul de l'ordre de traitement de la requête
            $freeVariables = $ruleBinded->getVariables();
            $hterms        = $ruleBinded->getHypotheses();
            $queryOrder    = $this->getQueryOrder($freeVariables, $hterms);
            $domains       = $this->getDomains($queryOrder, $info);

            if (empty($domains))
                goto end;

            $oneVariableTerm = array_filter($hterms, function($hterm) {
                return count($hterm->getVariables()) == 1;
            });
            $oneVariableTerm = array_keys($oneVariableTerm);

            //Recupération de l'ordre des variables pour binder
            $varOrder = [];

            foreach ($queryOrder as $k => $hterm) {
                $vars    = $hterm->getVariables();
                $newVars = [];

                foreach ($vars as $var) {
                    $varName = $var->getName();

                    if (!array_key_exists($varName, $varOrder))
                        $newVars[$varName] = $var;
                }
                if (!empty($newVars))
                    $varOrder = array_merge($varOrder, $newVars);
            }
            
            $data = null;
            
            /**
             * boucle principale
             */
            while (true) {

                if (!$info->canIterate())
                    goto end;

                $bind = $this->nextBind($varOrder, $domains, $data);

                if ($bind === null)
                    break;

                $ruleBinded->bind($bind);

                /*
                 * On vérifie que les arrivées sont valides
                 * On évite les cycles sur la règle
                 */
                $hypos   = $ruleBinded->getHypotheses();
                $hypos_c = count($hypos);

                if ($hypos_c >= 2) {
                    $words                = [];
                    $localExcludedWords   = $excludedWords;
                    $localExcludedWords[] = $conclusion->getAtom(0)->getValue();

                    for ($i = 0; $i < $hypos_c - 1; $i++) {
                        $words[] = $hypos[$i]->getAtom(1)->getValue();
                    }
                    /*
                     * On supprime le dernier mot car c'est la destination finale
                     * Elle est présente dans excluded words
                     */
                    $c = count($words);

                    for ($i = 0; $i < $c; $i ++) {
                        $word = $words[$i];

                        if (in_array($word, $localExcludedWords)) {
//                        var_dump($word);
//                        var_dump($localExcludedWords);
                            continue 2;
                        }
                        $localExcludedWords[] = $word;
                    }
                }
//                unset($atoms);
//                unset($localExcludedAtom);
//                $localExcludedWords = $excludedWords;

                $asks = [];

                foreach ($ruleBinded->getHypotheses() as $k => $hterm) {
                    $isOneTerm = in_array($k, $oneVariableTerm);

//                    if (!$isOneTerm)
                    {
                        $info->moreData($hterm);
                    }
                    $ask = $this->directAsk($hterm, $info);
                    /*
                     * Appel récursif
                     */
                    if ($ask === null) {
                        
                        if (/*!$isOneTerm && */$info->canDoRecursion()) {
                            $info->depth++;
                            $info->calls++;
                            $ask = $this->ask_($hterm, $info, $localExcludedWords);
                            $info->depth--;
                        }

                        if (empty($ask)) {
//                            var_dump('tt');
                            continue 2;
                        }
                        array_filter($ask, [$info, 'filterOneResult']);

                        if (empty($ask)) {
//                            var_dump('bb');
                            continue 2;
                        }
                        //TODO: meilleur calcul
                        $weight = $ask[0]['result']->getWeight();
                        $asks   = array_merge($asks, $ask);
                    }
                    /**
                     * direct ask
                     */
                    else {
                        $ask    = $ask[0];
                        $weight = $ask->getWeight();
                        $asks[] = ['rule' => null, 'bind' => null, 'result' => $ask, 'asks' => []];
                    }
                    $hterm->setWeight($weight);
                }
                $tmpr = clone $ruleBinded;
                $tmpr->getConclusion()->setWeight($info->computeWeight($tmpr));
                $tmp  = ['rule' => $rule, 'bind' => $tmpr, 'result' => $tmpr->getConclusion(), 'asks' => $asks];

                if (!$info->filterOneResult($tmp))
                    continue;

                $ret[] = $tmp;

                if ($info->depth == 0)
                    $info->nbResults++;

                if (count($ret) >= $info->getNbMaxResults())
                    break;
            }
        }
        end:
        return $ret;
    }
}