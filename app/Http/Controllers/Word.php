<?php

namespace App\Http\Controllers;

use App\Word as DBWord;
use App\Relation as DBRelation;
use Illuminate\Http\Request;

class Word extends Controller
{
    private $dbWord;
    private $dbRelation;
    private $relPagination;

    public function __construct( DBWord $dbWord, DBRelation $dbRelation )
    {
        $this->dbWord        = $dbWord;
        $this->dbRelation    = $dbRelation;
        $this->relPagination = config( 'app.pagination.relations', config( 'app.pagination.default', 20 ) );
    }

    private function getWord( string $word )
    {
        if ( is_numeric( $word ) )
            $w = $this->dbWord->find( (int) $word );
        else
            $w = $this->dbWord->where( 'n', $word )->first();
        return $w;
    }

    public function get( string $word )
    {
        $w = $this->getWord( $word );

        if ( empty( $w ) )
            return [];

        $wa = $w->toArray();

        $ret = ['word' => $wa];
        return $ret;
    }

    public function getChilds( string $word, Request $request )
    {
        $w = $this->getWord( $word );

        if ( empty( $w ) )
            return [];

        $relations = $this->dbRelation->where( 'n1', $w->_id );
        return $relations->simplePaginate( $this->relPagination );
    }

    public function getParents( string $word )
    {
        $w = $this->getWord( $word );

        if ( empty( $w ) )
            return [];

        $relations = $this->dbRelation->where( 'n2', $w->_id );
        return $relations->simplePaginate( $this->relPagination );
    }

    public function autocomplete( string $word )
    {
        $words = $this->dbWord->where( 'n', 'like', "$word%" )->orderBy('w','desc')->orderBy('n')->limit(100);
        return $words->pluck('n');
    }
}