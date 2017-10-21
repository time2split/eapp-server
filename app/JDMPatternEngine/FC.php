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
        $info->moreData( $conclusion );
        $direct = $this->directAsk( $conclusion );

        if ( $direct !== null )
            return ['rule' => null, 'result' => $direct];

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
                $catom       = $conclusion->getAtom( $k );
                $name        = $atom->getName();
                $val         = $conclusion->getAtom( $k )->getValue();
                $bind[$name] = $val;
            }
            $asks       = [];
            $finded     = true;
            $ruleBinded = clone $rule;
            $ruleBinded->bind( $bind );

            foreach ( $ruleBinded->getHypotheses() as $hterm )
            {
                var_dump( (string) $hterm );
//                foreac(h ( $hypos->getAtoms() as $hterm )
                {
                    $info->calls++;
                    $info->depth++;
                    $ret = $this->ask( $hterm, $info );
                    $info->depth--;

                    if ( empty( $ret ) )
                    {
                        $finded = false;
                        break;
                    }
                    $asks[] = $ret['result'];
                }
            }

            if ( $finded )
            {
                $vars  = $ruleBinded->getVariables();
                $cvars = count( $vars );

                if ( $cvars == 1 )
                {
                    $var    = array_pop( $vars );
                    $min    = null;
                    $minpos = null;

                    //Calcul du min
                    foreach ( $asks as $k => $a )
                    {
                        $c = count( $a );

                        if ( $min === null || $min > $c )
                        {
                            $min    = $c;
                            $minpos = $k;
                        }
                    }

                    foreach($asks[$minpos] as $a)
                    {
                        $var->setValue($a);
                    }
                }
                else
                    throw new Exception( "Sais pas faire avec $cvars variables!" );
//Pour le moment 1 variable 2 hypos
//Vérifier en bindant



                echo "yep !\n";
            }
        }
        return null;
    }
}