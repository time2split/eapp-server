<?php

namespace App\Http\Controllers;

use App\Word as DBWord;
use App\Relation as DBRelation;
use App\RelationType as DBRelationType;
use Illuminate\Http\Request;

class Word extends Controller
{
    private $dbWord;
    private $dbRelation;
    private $dbRelationType;
    private $relPagination;

    public function __construct( DBWord $dbWord, DBRelation $dbRelation, DBRelationType $dbRelationType )
    {
        $this->dbWord         = $dbWord;
        $this->dbRelation     = $dbRelation;
        $this->dbRelationType = $dbRelationType;
        $this->relPagination  = config( 'app.pagination.relations', config( 'app.pagination.default', 20 ) );
    }

    public function app( string $word = null )
    {
        return view( 'welcome', ['word' => $word] );
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

    public function getWords( Request $request )
    {
        $words = $request->query( 'words', '' );
        $words = explode( ',', $words );
        $ret   = [];

        foreach ( $words as $w )
        {
            $ret[] = $this->get( $w )['word'] ?? [];
        }
        return $ret;
    }

    public function getChilds( string $word, Request $request )
    {
        $w        = $this->getWord( $word );
        $rel      = $request->query( 'rtid', null );
        $per_page = $request->query( 'per_page', $this->relPagination );

        if ( empty( $w ) )
            return [];

        $relations = $this->dbRelation->where( 'n1', $w->_id );

        if ( $rel !== null )
            $relations->where( 't', (int) $rel );

        return $relations->simplePaginate( (int) $per_page );
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
        $words = $this->dbWord->where( 'n', 'like', "$word%" )->orderBy( 'w', 'desc' )->orderBy( 'n' )->limit( 20 );
        return $words->get();
    }

    public function relationTypes( Request $request )
    {
        $get      = $request->query( 'get' );
        $rels     = $this->dbRelationType->all();
        $excluded = $get === 'excluded';

        if ( $excluded )
        {
            $tmp = [];

            foreach ( $rels as $r )
            {
                if ( in_array( $r->name, ['r_associated','r_chunk_sujet', 'r_chunck_objet', 'r_flpot'] ) || $r->info === '' || strpos( $r->info, '(interne)' ) === 0 )
                    $tmp[] = $r;
            }
            return $tmp;
        }
        return $rels;
    }
}