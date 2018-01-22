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

//    private function searchDomains($variables)
//    {
//        
//    }

    public function directAsk(Term $conclusion)
    {
        $search = $this->db->matchingTerms($conclusion);

        if ($search !== false) {
            return $search;
        }
        return null;
    }

    private function getQVOrder($hterms)
    {
        $htermsStats = new \SplObjectStorage;

        $fgetOneVarTerms = function($hterms, $htermsStats) {
            return array_filter($hterms, function($hterm) use ($htermsStats) {
                $stats = $htermsStats[$hterm];
                return $stats['free'] == 1;
            });
        };

        /*
         * Init des stats
         */
        foreach ($hterms as $term) {
            $nbFreeVariables = 0;

            foreach ($term->getAtoms() as $atom) {

                if ($atom->isVariable())
                    $nbFreeVariables++;
            }
            $htermsStats[$term] = ['free' => $nbFreeVariables];
        }

        //Caclul de queryOrder
        $allVariables = [];
        $orders       = ['variables' => [], 'query' => []];
        $i            = -1;

        while (true) {
            $i++;
            $oneVarTerms = $fgetOneVarTerms($hterms, $htermsStats);

            if (empty($oneVarTerms)) {
                throw new Exception("Erreur dans l'ordonnancement de le requête");
            }
            $hterms                  = array_diff($hterms, $oneVarTerms);
            $orders['variables'][$i] = [];
            $orders['query'][$i]     = array_values($oneVarTerms);
            $currentVariables        = [];

            /*
             * Récupération des variables trouvées
             */
            foreach ($oneVarTerms as $hterm) {
                unset($htermsStats[$hterm]);

                /*
                 * On récupère la variable libre du terme
                 */
                foreach ($hterm->getVariables() as $var) {

                    // Toutes les autres variables sont dans $allVariables
                    if (!in_array($var->getName(), $allVariables)) {
                        $vname = $var->getName();

                        if (!isset($currentVariables[$vname])) {
                            $currentVariables[$vname] = $var;
                        }
                        break;
                    }
                }
            }

            foreach ($currentVariables as $vname => $hterm) {
                $allVariables[]                  = $vname;
                $orders['variables'][$i][$vname] = $hterm;

                /**
                 * MAJ des stats
                 */
                foreach ($hterms as $k => $hterm) {
                    $stats = $htermsStats[$hterm];

                    if ($hterm->hasVariable($vname)) {
                        $stats['free'] --;

                        if ($stats['free'] == 0) {
                            unset($hterms[$k]);
                            unset($htermsStats[$hterm]);
                        }
                        else
                            $htermsStats[$hterm] = $stats;
                    }
                }
            }

            if (empty($hterms))
                break;
        }
        return $orders;
    }
    /*
     * On se base sur les hterms dont les valeurs sont fixés par la requête,
     * autrement dit la profondeur 0 de $varsOrder
     */

    private function getDomains($orders, FCInfos $info)
    {
        $varsOrder  = $orders['variables'];
        $queryOrder = $orders['query'];
        $domains    = [];

        //Check variables libres

        if (count($varsOrder) > 1) {
            $vars = implode(',', array_keys(array_merge(...array_slice($varsOrder, 1))));
            throw new Exception("Les variables $vars sont sans contraintes, trop de combinaisons possibles");
        }

        foreach ($queryOrder[0] as $hterm) {
            $vars    = $hterm->getVariables();
            $varPos  = array_keys($vars)[0];
            $var     = $vars[$varPos];
            $varName = $var->getName();

            $info->moreData($hterm);
            $res = $this->directAsk($hterm);

            if (empty($res))
                return null;

            $vals = $res;

            if (!isset($domains[$varName]))
                $domains[$varName] = [];

            $domains[$varName][] = ['varPos' => $varPos, 'domain' => $vals];
        }

        foreach ($domains as $varName => $domain) {
            $domains[$varName] = $info->selectDomain($domain);
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
            return $ret;
        }
//        return array_merge($ret, $this->ask_($conclusion, $info));
        return $this->ask_($conclusion, $info);
    }

    private function ask_(Term $conclusion, FCInfos $info, $excludedWords = [])
    {
        $ret         = [];
        $rules       = $this->rules;
        $applicables = $rules->getRulesWithConclusion($conclusion);

        /*
         * Atomes
         *  ne devant pas apparaitre comme destination dans un terme
         * Limité au atomes paire depart -> arrivé
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
            $ruleBinded = clone $rule;
            $ruleBinded->bind($bind);

            //Calcul de l'ordre de traitement de la requête
            $hterms     = $ruleBinded->getHypotheses();
            $orders     = $this->getQVOrder($hterms);
            $varsOrder  = array_merge(...$orders['variables']);
            $queryOrder = array_merge(...$orders['query']);
            $domains    = $this->getDomains($orders, $info);

            if (empty($domains))
                goto end;

            $oneVariableTerm = array_filter($hterms, function($hterm) {
                return count($hterm->getVariables()) == 1;
            });
            $oneVariableTerm = array_keys($oneVariableTerm);

            //Recupération de l'ordre des variables pour binder
            $varOrder = [];

            foreach ($varsOrder as $vname => $varAtom) {
                $varOrder[$vname] = $varAtom;
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
                 * TODO: le mettre dans une fonction de $infos
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
                            continue 2;
                        }
                        $localExcludedWords[] = $word;
                    }
                }
                $asks = [];

                foreach ($ruleBinded->getHypotheses() as $k => $hterm) {
                    $isOneTerm = in_array($k, $oneVariableTerm);

                    $info->moreData($hterm);
                    $ask = $this->directAsk($hterm, $info);
                    /*
                     * Appel récursif
                     */
                    if ($ask === null) {

                        if (/* !$isOneTerm && */$info->canDoRecursion()) {
                            $info->depth++;
                            $info->calls++;
                            $ask = $this->ask_($hterm, $info, $localExcludedWords);
                            $info->depth--;
                        }

                        if (empty($ask)) {
                            continue 2;
                        }
                        array_filter($ask, [$info, 'filterOneResult']);

                        if (empty($ask)) {
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