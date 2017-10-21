<?php

namespace App\JDMPatternEngine;

use App\JDMPatternEngine\Atom;

/**
 * Terme JSM de la forme predicate(x,y)
 */
class Term
{
    private $predicate;
    private $atoms = [];
    private $weight;

    public function __construct( $p, int $w = 0, Atom ...$atoms )
    {
        $this->predicate = $p;
        $this->addAtom( ...$atoms );
        $this->setWeight( $w );
    }

    public function __clone()
    {
        foreach ( $this->atoms as &$atom )
        {
            $atom = clone $atom;
        }
    }

    public function getWeight()
    {
        return $this->weight;
    }

    public function setWeight( int $w )
    {
        $this->weight = $w;
    }

    public function getPredicate()
    {
        return $this->predicate;
    }

    public function addAtom( Atom ... $atoms )
    {
        $this->atoms = array_merge( $this->atoms, $atoms );
    }

    public function getAtoms()
    {
        return $this->atoms;
    }

    public function getAtom( int $i = 0 )
    {
        return $this->atoms[$i];
    }

    public function setAtom( int $i = 0, Atom $atom )
    {
        return $this->atoms[$i] = $atom;
    }

    /**
     * Retourne null si 2 variables ; $b->$var si $this variable ; $b != $this sinon
     */
    public function variableMatch( $var, Term $b )
    {
        if ( $b->getPredicate() !== $this->getPredicate() )
            return null;

        $ret = ['x' => null, $y => null];
        $tv  = $this->$var;
        $bv  = $b->$var;

        if ( $tx->isVariable() )
        {
            if ( $by->isVariable() )
                return null;
            else
                return $bv;
        }
        else
        {
            return $bv == $tv;
        }
    }

    static function sameAtoms( Term $a, Term $b )
    {
        $aas = $a->getAtoms();
        $bas = $b->getAtoms();

        if ( count( $aas ) != count( $bas ) )
            return false;

        foreach ( $aas as $k => $aa )
        {
            $ba = $bas[$k];

            if ( $aa != $ba )
                return false;
        }
        return true;
    }

    /**
     * Comparaison sans le poids (weight)
     * @param \App\JDMPatternEngine\Term $a
     * @param \App\JDMPatternEngine\Term $b
     * @return type
     */
    static function sameNature( Term $a, Term $b )
    {
        return $a->getPredicate() === $b->getPredicate() && self::sameAtoms( $a, $b );
    }

    public function getConstants()
    {
        $ret = [];
        foreach ( $this->atoms as $i => $atom )
        {
            if ( $atom->isConstant() )
                $ret[$i] = $atom;
        }
        return $ret;
    }

    public function __toString()
    {
        $ret = "$this->predicate(";
        $tmp = [];

        foreach ( $this->atoms as $atom )
        {
            $tmp[] = (string) $atom;
        }
        $ret .= implode( ',', $tmp );
        $ret .= ")";
        return $ret;
    }
}