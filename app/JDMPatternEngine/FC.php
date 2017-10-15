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
        $search = $this->db->matchTerm( $conclusion );

        if ( $search !== false )
        {
            return $search;
        }
        return null;
    }

    public function ask( Term $conclusion, FCInfos $info )
    {
        $res = $info->moreData( $conclusion );

//        if ( $res === false )
//        {
//            throw new Exception( "Impossible de remplir la base de connaissances" );
//        }
        $direct = $this->directAsk( $conclusion );

        if ( $direct !== null )
            return [$direct];

        $rules = $this->rules;

        $applicables = $rules->getRulesWithConclusion( $conclusion );

        //Règles applicables
        foreach ( $applicables as $rule )
        {
            $rconcl = $rule->getConclusion();
            $bind   = [];

            foreach ( $rconcl->getAtoms() as $k => $atom )
            {
                $catom       = $conclusion->getAtom( $k );
                $name        = $atom->getName();
                $val         = $conclusion->getAtom($k)->getValue();
                $bind[$name] = $val;
            }
            $ruleBinded = clone $rule;
            $ruleBinded->bind( $bind );
            $info->moreData( $rbinded );
//
//            foreach ( $rule->getHypothesis() as $hrule )
//            {
////                $res = $this->ask();
//
//                if ( $res === true )
//                {
//                    
//                }
//                // Trouvé négatif
//                elseif ( $res === false )
//                {
//                    //TODO
//                    break;
//                }
//                // Ne sait pas
//                else
//                {
//                    break;
//                }
//            }
//            //hypotheses verifiées
//            if ( $res )
//            {
//                echo 'YES !!';
//            }
        }
        return null;
    }
}