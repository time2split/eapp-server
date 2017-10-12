<?php

namespace App;

class RelationType extends \Moloquent
{
    protected $connection = 'mongodb';
    protected $collection = 'relationTypes';
    protected $primaryKey = '_id';
    public $timestamps    = false;

}