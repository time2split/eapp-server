<?php

namespace App;

class Word extends \Moloquent
{
    protected $connection = 'mongodb';
    protected $collection = 'nodes';
    protected $primaryKey = '_id';
    public $timestamps    = false;

//    public function __construct($attrs = [])
//    {
//        parent::__construct($attrs);
//        
//        if (!isset($this->nf))
//            $this->nf = $this->n;
//    }
    
//    static function makes($data)
//    {
//        if(data instanceof self)
//        {
//            if (!isset($this->nf))
//                $this->nf = $this->n;
//        }
//    }

    function getWord($word)
    {
        $k = is_numeric($word) ? '_id' : 'n';
        return $this->where($k, $word)->get()->first();
    }
}