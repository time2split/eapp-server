<?php

namespace App;

class Relation extends \Moloquent
{
    protected $connection = 'mongodb';
    protected $collection = 'relations';
    protected $primaryKey = '_id';
    public $timestamps    = false;

}