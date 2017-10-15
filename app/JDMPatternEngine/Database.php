<?php

namespace App\JDMPatternEngine;

use App\JDMPatternEngine\Term;
use App\JDMPatternEngine\Variable;

class Database
{
    private $predicates = [];

    public function addTerm( Term ...$preds )
    {
        $this->predicates = array_merge( $this->predicates, $preds );
    }

//    public function searchTerm( Term $pred )
//    {
//        $f = function($a) use ($pred) {
//            return Term::sameNature( $pred, $a );
//        };
//        $res = array_filter( $this->predicates, $f );
//
//        if ( empty( $res ) )
//            return false;
//
//        return array_pop( $res );
//    }

    public function matchTerm( Term $pattern )
    {
        $x = $pattern->getAtom(0);
        $y = $pattern->getAtom(1);

        if ( $x->isVariable() && $y->isVariable() )
        {
            $pred = $pattern->getPredicate();

            $f = function($a) use ($pred) {
                return $pred = $a->getPredicate();
            };
        }
        elseif ( $x->isVariable() )
        {
            $pred = $pattern->getPredicate();
            $y    = $pattern->getAtom(1);

            $f = function($a) use ($pred, $y) {
                return $pred = $a->getPredicate() && $y == $a->getAtom(1);
            };
        }
        elseif ( $y->isVariable() )
        {
            $pred = $pattern->getPredicate();
            $x    = $pattern->getAtom(0);

            $f = function($a) use ($pred, $x) {
                return $pred = $a->getPredicate() && $x == $a->getAtom(0);
            };
        }
        else
        {
            $f = function($a) use ($pattern) {
                return Term::sameNature( $pattern, $a );
            };
        }
        $res = array_filter( $this->predicates, $f );

        if ( empty( $res ) )
            return false;

        return array_pop( $res );
    }
}