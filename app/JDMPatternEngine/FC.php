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

    public function __construct( RuleManager $rules, Database $db )
    {
        $this->rules = $rules;
        $this->db    = $db;
    }

    private function searchDomains( $variables )
    {
        
    }

    public function directAsk( Term $conclusion )
    {
        $search = $this->db->matchingTerms( $conclusion );

        if ( $search !== false )
        {
            return $search;
        }
        return null;
    }

    private function getQueryOrder( $freeVariables, $hterms )
    {
        $htermsStats = new \SplObjectStorage;

//        var_dump( $freeVariables );
//            $this->searchDomains( $freeVariables );
        //init de $htupleStats

        $fgetOneVarTerms = function() use ($hterms, $htermsStats) {
            return array_filter( $hterms, function($hterm) use ($htermsStats) {
                $stats = $htermsStats[$hterm];
                return count( $stats['term']->getAtoms() ) - $stats['free'] == 1;
            } );
        };

        foreach ( $freeVariables as $var )
        {
            foreach ( $hterms as $term )
            {
                $nbFreeVariables = 0;

                foreach ( $term->getAtoms() as $atom )
                {
                    if ( $atom->isVariable() )
                        $nbFreeVariables++;
                }
                $htermsStats[$term] = ['free' => $nbFreeVariables, 'term' => $term, 'domain' => []];
            }
        }

        $fupdatehtermsStats = function(&$htermsStats, $oneVarTerms) {
            
        };
        //Caclul de queryOrder
        $queryOrder = [];

        while ( true )
        {
            $oneVarTerms = $fgetOneVarTerms();
            $queryOrder  = array_merge( $queryOrder, $oneVarTerms );
            $hterms      = array_diff( $hterms, $oneVarTerms );

            if ( empty( $freeVariables ) )
                break;

            foreach ( $oneVarTerms as $onevterm )
            {
                $var = $onevterm->getVariables();
                $var = array_pop( $var );

                foreach ( $hterms as $k => $hterm )
                {
                    if ( !$hterm->hasVariable( $var->getName() ) )
                        continue;
                    $stats               = $htermsStats[$hterm];
                    $stats['free'] --;
                    $htermsStats[$hterm] = $stats;

                    if ( $stats['free'] == 0 )
                    {
//                            if ( !in_array( $hterm, $queryOrder ) )
                        $queryOrder[] = $hterm;
                        unset( $hterms[$k] );
                    }
                }
            }
            break;
        }
        if ( !empty( $hterms ) )
            throw new Exception( "Erreur dans la règle $ruleBinded, impossible de détecter le domaine de toutes les variables" );

        return $queryOrder;
    }

    private function getDomains( $queryOrder, FCInfos $info )
    {
        $domains = [];

        foreach ( $queryOrder as $hterm )
        {
            $vars    = $hterm->getVariables();
            $cte     = $hterm->getConstants();
            $mustAsk = false;

            foreach ( $vars as $pos => $var )
            {
                $varName = $var->getName();

                if ( isset( $domains[$varName] ) )
                {
                    continue;
                }
                $mustAsk = true;
                //Impossible qu'il y ai plus de 1 variables
                break;
            }

            if ( !$mustAsk )
                continue;

            $info->moreData( $hterm );
            $res = $this->directAsk( $hterm );

            if ( empty( $res ) )
                return null;

            $res  = $info->selectDomain( $res );
            $vals = array_map( function($e) use ($pos) {
                return $e->getAtoms()[$pos]->getValue();
            }, $res );
            //Ordonne les clés
            $domains[$varName] = array_values( $vals );
        }
        return $domains;
    }

    private function nextBind( $varOrder, $domains, &$data )
    {
        $bind = null;

        if ( $data === null )
        {
            $bind = [];

            foreach ( $varOrder as $varName => $var )
            {
                $domain         = $domains[$varName];
                $val            = $domain[0];
                $data[]         = ['current' => 0, 'last' => count( $domain ) - 1, 'varName' => $varName];
                $bind[$varName] = $val;
            }
        }
        else
        {
            $i     = count( $data ) - 1;
            $cdata = & $data[$i];

            if ( $cdata['current'] == $cdata['last'] )
            {
                $bind = [];
                do
                {
                    if ( $i === 0 )
                        return null;

                    $varName          = $cdata['varName'];
                    $domain           = $domains[$varName];
                    $val              = $domain[0];
                    $bind[$varName]   = $val;
                    $cdata['current'] = 0;
                    $i--;
                    $cdata            = & $data[$i];
                }
                while ( $cdata['current'] == $cdata['last'] );
            }
            $varName        = $cdata['varName'];
            $pos            = ++$cdata['current'];
            $domain         = $domains[$varName];
            $val            = $domain[$pos];
            $bind[$varName] = $val;
        }
        return $bind;
    }

    public function ask( Term $conclusion, FCInfos $info )
    {
        $ret    = [];
        $info->moreData( $conclusion );
        $direct = $this->directAsk( $conclusion );

        if ( $direct !== null )
        {
            $ret[] = ['rule' => null, 'bind' => null, 'result' => $direct];
//            return $ret;
        }
        return $this->ask_( $conclusion, $info, $ret );
    }

    private function ask_( Term $conclusion, FCInfos $info, $ret = [] )
    {
        $rules       = $this->rules;
        $applicables = $rules->getRulesWithConclusion( $conclusion );

        //Règles applicables
        foreach ( $applicables as $rule )
        {
//            var_dump( 'R:' . $rule );
            $rconcl = $rule->getConclusion();
            $bind   = [];

            //RÉCUPÉRATION DES CONSTANTES À BINDER
            foreach ( $rconcl->getAtoms() as $k => $atom )
            {
                $name        = $atom->getName();
                $val         = $conclusion->getAtom( $k )->getValue();
                $bind[$name] = $val;
            }
            $asks       = [];
            $ruleBinded = clone $rule;
            $ruleBinded->bind( $bind );

            //Calcul de l'ordre de traitement de la requête
            $freeVariables = $ruleBinded->getVariables();
            $hterms        = $ruleBinded->getHypotheses();
            $queryOrder    = $this->getQueryOrder( $freeVariables, $hterms );
            $domains       = $this->getDomains( $queryOrder, $info );

            if ( empty( $domains ) )
                goto end;

            $oneVariableTerm = array_filter( $hterms, function($hterm) {
                return count( $hterm->getVariables() ) == 1;
            } );
            $oneVariableTerm = array_keys( $oneVariableTerm );

            //Recupération de l'ordre des variables pour binder
            $varOrder = [];

            foreach ( $queryOrder as $k => $hterm )
            {
                $vars    = $hterm->getVariables();
                $newVars = [];

                foreach ( $vars as $var )
                {
                    $varName = $var->getName();

                    if ( !array_key_exists( $varName, $varOrder ) )
                        $newVars[$varName] = $var;
                }
                if ( !empty( $newVars ) )
                    $varOrder = array_merge( $varOrder, $newVars );
            }

            while ( true )
            {
//                if ( $info->canContinue() === false )
//                    goto end;

                $bind = $this->nextBind( $varOrder, $domains, $data );

                if ( $bind === null )
                    break;

                $ruleBinded->bind( $bind );
                $asks = [];

                foreach ( $ruleBinded->getHypotheses() as $k => $hterm )
                {
                    $isOneTerm = in_array( $k, $oneVariableTerm );

                    if ( !$isOneTerm )
                        $info->moreData( $hterm );

                    $ask = $this->directAsk( $hterm, $info );
//
                    if ( $ask === null )
                    {
                        if ( !$isOneTerm && $info->canDoRecursion() )
                        {
                            $info->depth++;
                            $info->calls++;
                            $ask = $this->ask_( $hterm, $info );
                            $info->depth--;
                        }

                        if ( $ask == null )
                            continue 2;

//                        var_dump($ask[0]['result']);
                        $weight = $ask[0]['result']->getWeight();
                    }
                    else
                    {
                        $ask    = array_values( $ask );
                        $weight = $ask[0]->getWeight();
                    }
                    $hterm->setWeight( $weight );
                    $asks[] = $ask;
                }

//                if ( empty( $asks ) )
//                    continue;

                $tmpr  = clone $ruleBinded;
                $tmpr->getConclusion()->setWeight($info->computeWeight($tmpr));
                $ret[] = ['rule' => $rule, 'bind' => $tmpr, 'result' => $tmpr->getConclusion(), 'asks' => $asks];

                if ( $info->depth == 0 )
                    $info->nbResults++;

                if ( count( $ret ) >= $info->getNbMaxResults() )
                    break;
            }
//            return $ret;
//            return $asks;
//            exit;
//            //On demande pour chaque hypothèse si elle est présente dans la DB
//            foreach ( $queryOrder as $hterm )
//            {
//                $info->calls++;
//                $info->depth++;
//                $ask = $this->ask( $hterm, $info );
//                $info->depth--;
//
//                if ( empty( $ask ) )
//                {
//                    goto end;
//                }
//                $asks[] = $ask[0]['result'];
//            }
//            $vars  = $ruleBinded->getVariables();
//            $cvars = count( $vars );
//
//            if ( $cvars == 0 )
//            {
//                $ret[] = ['rule' => $rule, 'bind' => $ruleBinded, 'result' => [$conclusion]];
//                goto end;
//            }
//            $hypos = $ruleBinded->getHypotheses();
//            $binds = [];
//
//            foreach ( $vars as $vpos => $var )
//            {
//                $binds[$vpos] = [];
//
//                $matchingTerms = array_filter( $hypos, function($e) use ($var) {
//                    return !empty( $e->getAtomPos( $var ) );
//                } );
//
//                //On cherche le min dans asks
//                foreach ( $matchingTerms as $pos => $mterm )
//                {
//                    $c = count( $asks[$pos] );
//
//                    if ( !isset( $min ) || $c < $min )
//                    {
//                        $min    = $c;
//                        $minpos = $pos;
//                    }
//                }
//                $term    = $matchingTerms[$minpos];
//                $posAtom = $term->getAtomPos( $var )[0];
//
//                //On construit l'ensemble des bindages
//                foreach ( $asks[$minpos] as $bind )
//                {
//                    $val            = $bind->getAtom( $posAtom )->getValue();
//                    $binds[$vpos][] = $val;
//                }
//                $binds[$vpos] = array_unique( $binds[$vpos], SORT_NUMERIC );
//            }
//            var_dump( $binds );
//
//            //On binde et on teste
//            $bindVarPos = [];
//            $bindPos    = [];
//            $bindCounts = [];
//
//            foreach ( $binds as $pos => $bind )
//            {
//                $bindCounts[] = count( $bind );
//                $bindPos[]    = 0;
//                $bindVarPos[] = $pos;
//            }
//            $i = $cvars - 1;
//
//            foreach ( $vars as $pos => $var )
//            {
//                $var->setValue( $binds[$pos][0] );
//            }
//
//            while ( true )
//            {
//                $bindPos[$i] ++;
//
//                //Fin des combinaisons courrantes
//                if ( $bindPos[$i] == $bindCounts[$i] )
//                {
//                    do
//                    {
//                        $bindPos[$i] = 0;
//
//                        //Fin
//                        if ( $i === 0 )
//                            break 2;
//
//                        $var = $vars[$bindVarPos[$i]];
//                        $var->setValue( $binds[$bindVarPos[$i]][$bindPos[$i]] );
//                        $i--;
//                        $bindPos[$i] ++;
//                        $var = $vars[$bindVarPos[$i]];
//                        $var->setValue( $binds[$bindVarPos[$i]][$bindPos[$i]] );
//                    }
//                    while ( $bindPos[$i] == $bindCounts[$i] );
//
//                    $i = $cvars - 1;
//                }
//                else
//                {
//                    $var = $vars[$bindVarPos[$i]];
//                    $var->setValue( $binds[$bindVarPos[$i]][$bindPos[$i]] );
//                }
//                $directAsks = [];
//
//                //Vérification
//                foreach ( $ruleBinded->getHypotheses() as $hterm )
//                {
//                    $tmp = $this->directAsk( $hterm );
//
//                    if ( $tmp === null )
//                    {
//                        continue 2;
//                    }
//                    $directAsks[] = $tmp;
//                }
//                exit;
//
//                //Construction du résultat
//                $retBind = clone $ruleBinded;
//                $wBind   = 0;
//
//                //Calcul du poids
//                foreach ( $retBind->getHypotheses() as $k => $hterm )
//                {
//                    $tmpa = $this->db->matchingTerms( $hterm );
//                    $tmp  = array_pop( $tmpa );
//
//                    //Normalement impossible
//                    if ( $tmp !== null )
//                    {
//                        $h     = $retBind->getHypothesis( $k );
//                        $h->setWeight( $tmp->getWeight() );
//                        $wBind += $tmp->getWeight();
//                    }
//                }
//                $retBind->getConclusion()->setWeight( $wBind );
//                $conclBind = $retBind->getConclusion();
//
//                $ret[] = ['rule' => $rule, 'bind' => $retBind, 'result' => [$conclBind]];
//                $this->db->addTerm( $conclBind );
//            }
        }
        end:
        return $ret;
    }
}