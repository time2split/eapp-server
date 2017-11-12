<?php

namespace App\JDMPatternEngine;

class FCInfos
{
    public $depth = 0;
    public $calls = 1;
    public $nbResults = 0;

    public function moreData( $on )
    {
        
    }

    public function canContinue()
    {
        return true;
    }

    public function selectDomain( $domain )
    {
        return $domain;
    }
}