<?php

namespace App\JDMPatternEngine;

class Atom
{
    private $name;
    private $value = null;

    public function __construct( $name = null, $value = null )
    {
        $this->name = $name;
        $this->setValue( $value );
    }

    public function setValue( $val )
    {
        $this->value = $val;
    }

    public function isConstant()
    {
        return $this->value !== null;
    }

    public function isVariable()
    {
        return $this->value === null;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getValue()
    {
        return $this->value;
    }

    static public function sameName( Atom $a, Atom $b )
    {
        return $a->getName() == $b->getName();
    }

    static public function sameValue( Atom $a, Atom $b )
    {
        return $a->getValue() == $b->getValue();
    }
}