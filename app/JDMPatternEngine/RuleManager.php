<?php

namespace App\JDMPatternEngine;

use App\JDMPatternEngine\Rule;
use App\JDMPatternEngine\Term;

class RuleManager
{
    private $rules = [];

    public function addRule( Rule ...$rules )
    {
        $this->rules = array_merge( $this->rules, $rules );
    }

    public function getRules()
    {
        return $this->rules;
    }

    public function getRulesWithConclusion( $pred )
    {
        $ret = [];

        if ( $pred instanceof Term )
            $pred = $pred->getPredicate();

        foreach ( $this->rules as $rule )
        {
            $conclusion = $rule->getConclusion();

            if ( $conclusion->getPredicate() == $pred )
                $ret[] = $rule;
        }
        return $ret;
    }
}