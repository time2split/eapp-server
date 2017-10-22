<?php

namespace App;

class Word extends \Moloquent
{
    protected $connection = 'mongodb';
    protected $collection = 'nodes';
    protected $primaryKey = '_id';
    public $timestamps    = false;

    function getWord( $word )
    {
        $k = is_numeric($word) ? '_id' : 'n';
        return $this->where( $k, $word )->get()->first();
    }
}