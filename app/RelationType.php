<?php

namespace App;

class RelationType extends \Moloquent
{
    protected $connection = 'mongodb';
    protected $collection = 'relationTypes';
    protected $primaryKey = '_id';
    public $timestamps    = false;
    private $cache        = null;

    public function initCache()
    {
        if ( $this->cache )
            return;

        $this->cache = $this->get()->toArray();
    }

//    public function __construct( array $attributes = array() )
//    {
//        parent::__construct( $attributes );
//    }

    public function getRelation( $rel )
    {
        $this->initCache();

        if ( is_string( $rel ) )
        {
            $ffilter = function($a) use ($rel) {
                return $rel == $a['name'];
            };
        }
        elseif ( is_int( $rel ) )
        {
            $ffilter = function($a) use ($rel) {
                return $rel == $a['_id'];
            };
        }
        else
            return null;

        foreach ( $this->cache as $rel )
        {
            if ( $ffilter( $rel ) )
                return (Object)$rel;
        }
        return null;
    }
}