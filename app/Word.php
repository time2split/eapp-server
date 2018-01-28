<?php

namespace App;

class Word extends \Moloquent
{
    protected $connection = 'mongodb';
    protected $collection = 'nodes';
    protected $primaryKey = '_id';
    public $timestamps    = false;

    public function makeIt()
    {
        if (!isset($this->nf))
            $this->nf = $this->n;
    }

    function getWord($word)
    {
        if (is_numeric($word))
            $w = $this->find((int) $word);
        elseif (preg_match('#^::>(.+)>(.+)$#', $word, $matches)) {
            $w = $this->where('n', $word)->first();
        }
        elseif (preg_match('#^(.+)>(.+)$#', $word, $matches) && !ctype_digit($matches[2])) {
            $raff = $this->getWord($matches[2]);
            return $this->getWord($matches[1] . '>' . $raff->_id);
        }
        else
            $w = $this->where('n', $word)->first();

        $this->makeIt();
        return $w;
    }
}