<?php

namespace App\JDMPatternEngine;

use App\JDMPatternEngine\Database;
use App\JDMPatternEngine\RuleManager;
use App\JDMPatternEngine\Term;
use App\JDMPatternEngine\FCInfos;

//use Exception;

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

    public function directAsk( Term $conclusion )
    {
        $search = $this->db->matchingTerms( $conclusion );

        if ( $search !== false )
        {
            return $search;
        }
        return null;
    }

    public function ask( Term $conclusion, FCInfos $info )
    {
        $ret    = [];
        $info->moreData( $conclusion );
        $direct = $this->directAsk( $conclusion );

        if ( $direct !== null )
        {
            $ret[] = ['rule' => null, 'bind' => null, 'result' => $direct];
            goto end;
        }
        $rules       = $this->rules;
        $applicables = $rules->getRulesWithConclusion( $conclusion );

        //Règles applicables
        foreach ( $applicables as $rule )
        {
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

            //On demande pour chaque hypothèse si elle est présente dans la DB
            foreach ( $ruleBinded->getHypotheses() as $hterm )
            {
                $info->calls++;
                $info->depth++;
                $ask = $this->ask( $hterm, $info );
                $info->depth--;

                if ( empty( $ask ) )
                {
                    goto end;
                }
                $asks[] = $ask[0]['result'];
            }
            $vars  = $ruleBinded->getVariables();
            $cvars = count( $vars );

            if ( $cvars == 0 )
            {
                $ret[] = ['rule' => $rule, 'bind' => $ruleBinded, 'result' => [$conclusion]];
                goto end;
            }
            $hypos = $ruleBinded->getHypotheses();
            $binds = [];

            foreach ( $vars as $vpos => $var )
            {
                $binds[$vpos] = [];

                $matchingTerms = array_filter( $hypos, function($e) use ($var) {
                    return !empty( $e->getAtomPos( $var ) );
                } );

                //On cherche le min dans asks
                foreach ( $matchingTerms as $pos => $mterm )
                {
                    $c = count( $asks[$pos] );

                    if ( !isset( $min ) || $c < $min )
                    {
                        $min    = $c;
                        $minpos = $pos;
                    }
                }
                $term    = $matchingTerms[$minpos];
                $posAtom = $term->getAtomPos( $var )[0];

                //On construit l'ensemble des bindages
                foreach ( $asks[$minpos] as $bind )
                {
                    $val            = $bind->getAtom( $posAtom )->getValue();
                    $binds[$vpos][] = $val;
                }
                $binds[$vpos] = array_unique( $binds[$vpos], SORT_NUMERIC );
            }

            //On binde et on teste
            $bindVarPos = [];
            $bindPos    = [];
            $bindCounts = [];

            foreach ( $binds as $pos => $bind )
            {
                $bindCounts[] = count( $bind );
                $bindPos[]    = 0;
                $bindVarPos[] = $pos;
            }
            $i = $cvars - 1;

            foreach ( $vars as $pos => $var )
            {
                $var->setValue( $binds[$pos][0] );
            }

            while ( true )
            {
                $bindPos[$i] ++;

                //Fin des combinaisons courrantes
                if ( $bindPos[$i] == $bindCounts[$i] )
                {
                    do
                    {
                        $bindPos[$i] = 0;

                        //Fin
                        if ( $i === 0 )
                            break 2;

                        $var = $vars[$bindVarPos[$i]];
                        $var->setValue( $binds[$bindVarPos[$i]][$bindPos[$i]] );
                        $i--;
                        $bindPos[$i] ++;
                        $var = $vars[$bindVarPos[$i]];
                        $var->setValue( $binds[$bindVarPos[$i]][$bindPos[$i]] );
                    }
                    while ( $bindPos[$i] == $bindCounts[$i] );

                    $i = $cvars - 1;
                }
                else
                {
                    $var = $vars[$bindVarPos[$i]];
                    $var->setValue( $binds[$bindVarPos[$i]][$bindPos[$i]] );
                }
                $directAsks = [];

                //Vérification
                foreach ( $ruleBinded->getHypotheses() as $hterm )
                {
                    $tmp = $this->directAsk( $hterm );

                    if ( $tmp === null )
                    {
                        continue 2;
                    }
                    $directAsks[] = $tmp;
                }
                //Construction du résultat
                $retBind = clone $ruleBinded;
                $wBind   = 0;

                //Calcul du poids
                foreach ( $retBind->getHypotheses() as $k => $hterm )
                {
                    $tmpa = $this->db->matchingTerms( $hterm );
                    $tmp  = array_pop( $tmpa );

                    //Normalement impossible
                    if ( $tmp !== null )
                    {
                        $h     = $retBind->getHypothesis( $k );
                        $h->setWeight( $tmp->getWeight() );
                        $wBind += $tmp->getWeight();
                    }
                }
                $retBind->getConclusion()->setWeight( $wBind );
                $conclBind = $retBind->getConclusion();

                $ret[] = ['rule' => $rule, 'bind' => $retBind, 'result' => [$conclBind]];
                $this->db->addTerm( $conclBind );
            }
        }
        end:
        return $ret;
    }
}