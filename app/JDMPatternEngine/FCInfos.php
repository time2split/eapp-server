<?php

namespace App\JDMPatternEngine;

class FCInfos
{
    public $depth     = 0;
    public $calls     = 1;
    public $nbResults = 0;

    public function moreData($on)
    {
        
    }

    public function selectDomain($domain)
    {
        return $domain;
    }

    public function canIterate()
    {
        return true;
    }

    public function canDoRecursion()
    {
        return true;
    }

//    public function filterTerm($term)
//    {
//        return true;
//    }

//    public function filterDomain($domain)
//    {
//        return $domain;
//    }

    public function computeWeight($ruleBinded)
    {
        return 0;
    }

    public function getNbMaxResults()
    {
        return 10;
    }

    public function filterOneResult($res)
    {
        return true;
    }
}