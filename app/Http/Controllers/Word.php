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

    public function app( string $word = null, string $relation = null )
    {
        if ( is_numeric( $word ) )
        {
            $w    = $this->dbWord->find( (int) $word );
            $word = $w->n;
        }
        return view( 'welcome', ['word' => $word, 'word_relation' => $relation] );
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

        if ( !isset( $wa['nf'] ) )
            $wa['nf'] = $wa['n'];

//        $name = $wa['n'];
//        preg_match_all( "#>(\d+)#", $name, $matches );
//
//        if ( empty( $matches[1] ) )
//        {
//            $wa['N'] = $name;
//        }
//        else
//        {
//            $replacements = [];
//
//            foreach ( $matches[1] as $match )
//            {
//                $tmp = $this->get( $match );
//
//                if ( empty( $tmp ) )
//                    $tmp = null;
//                else
//                    $tmp = $tmp['word']['N'];
//
//                $replacements[] = $tmp;
//            }
//            $wa['N'] = preg_replace( array_fill( 0, 2, "#(\d+)#" ), $replacements, $name );
//        }
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

    private function getChildsOrParents( string $what, string $word, Request $request )
    {
        $w        = $this->getWord( $word );
        $rel      = $request->query( 'rtid', null );
        $per_page = $request->query( 'per_page', $this->relPagination );
        $count    = $request->query( 'count', null );

        if ( $count === null )
            $count = false;
        else
            $count = $count !== 'false';

        if ( empty( $w ) )
            return [];

        $relations = $this->dbRelation->where( $what, $w->_id );

        if ( $rel !== null )
            $relations->where( 't', (int) $rel );

        if ( $count )
            return $relations->count();
        else
            return $relations->simplePaginate( (int) $per_page );
    }

    public function getChilds( string $word, Request $request )
    {
        return $this->getChildsOrParents( 'n1', $word, $request );
    }

    public function getParents( string $word, Request $request )
    {
        return $this->getChildsOrParents( 'n2', $word, $request );
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
                if ( in_array( $r->name, ['r_chunk_sujet', 'r_chunck_objet', 'r_flpot'] ) || $r->info === '' || strpos( $r->info, '(interne)' ) === 0 )
                    $tmp[] = $r;
            }
            return $tmp;
        }
        return $rels;
    }
}