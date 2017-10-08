<?php

namespace App;

class Word extends \Moloquent
{
    protected $connection = 'mongodb';
    protected $collection = 'nodes';
    protected $primaryKey = '_id';
    public $timestamps    = false;

}