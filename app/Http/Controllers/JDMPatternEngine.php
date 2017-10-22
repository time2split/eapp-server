<?php

namespace App\Http\Controllers;

use App\JDMPatternEngine\FC;
use App\JDMPatternEngine\FCInfos;
//use App\JDMPatternEngine\RuleManager;
use App\JDMPatternEngine\RuleManagerFactory_file;
use App\JDMPatternEngine\Database;
use App\JDMPatternEngine\Rule;
use App\JDMPatternEngine\Atom;
use App\JDMPatternEngine\Term;
use App\Word;
use App\Relation;
use App\RelationType;

class Infos extends FCInfos
{
    private $db;
    private $Relation;
    private $maxDepth = 2;
    private $asked    = []; //Déjà demandés

    public function __construct( $db, Relation $relation )
    {
        $this->db       = $db;
        $this->Relation = $relation;
    }

    private function moreRelationsAskFor( $demand )
    {
        if ( is_array( $demand ) )
        {
            $q     = $this->Relation;
            $asked = [];

            foreach ( $demand as $k => $v )
            {
                if ( isset( $asked[$k] ) && $asked[$k] == $v )
                    throw new Exception( "Ask for $k => $v already presents" );

                $asked[$k] = $v;
                $q         = $q->where( $k, $v );
            }
            $relations = $q->get();
            return $relations;
        }
        elseif ( $demand instanceof Term )
        {
            $tmpAsked = array_filter( $this->asked, function($e) use($demand) {
                return $e->getPredicate() === $demand->getPredicate() && $e->variableMatch( $demand );
            } );

            if ( !empty( $tmpAsked ) )
            {
//                var_dump( "ALREADY ASKED !!!!" );
//                var_dump( $demand );
//                var_dump( $tmpAsked );
                return [];
            }
            $this->asked[] = clone $demand;
            $constants     = $demand->getConstants();
            $corresp       = ['n1', 'n2'];
            $demand        = [];

            foreach ( $constants as $pos => $const )
            {
                $demand[$corresp[$pos]] = $const->getValue();
            }
            return $this->moreRelationsAskFor( $demand );
        }
        else
            throw new Exception( "Unknow ask !" );
    }

    private function relations2Terms( $relations )
    {
        $terms = [];
        foreach ( $relations as $rel )
        {
            $term    = new Term( $rel->t, $rel->w, new Atom( 'n1', $rel->n1 ), new Atom( 'n2', $rel->n2 ) );
            $terms[] = $term;
        }
        return $terms;
    }

    public function moreData( $on )
    {
        if ( $this->depth > $this->maxDepth )
        {
            return null;
        }

        if ( is_array( $on ) )
        {
            foreach ( $on as $val )
            {
                $this->moreData( $val );
            }
        }
        elseif ( $on instanceof Term )
        {
            $relations = $this->moreRelationsAskFor( $on );
            $terms     = $this->relations2Terms( $relations );
            $this->db->addTerm( ...$terms );
            return true;
        }
        elseif ( $on instanceof Rule )
        {
            //Seules les hypothèses nous intéresse : la conclusion doit être bindée
            $this->moreData( $on->getHypotheses() );
        }
        return null;
    }
}

class JDMPatternEngine extends Controller
{
    private $dbWord;
    private $dbRelation;
    private $dbRelationType;

    public function __construct( Word $dbWord, Relation $dbRelation, RelationType $dbRelationType )
    {
        $this->dbWord         = $dbWord;
        $this->dbRelation     = $dbRelation;
        $this->dbRelationType = $dbRelationType;
    }

    public function __invoke()
    {
        $ret          = null;
        $Word         = $this->dbWord;
        $Relation     = $this->dbRelation;
        ;
        $RelationType = $this->dbRelationType;

        $data     = file( '/home/zuri/works/UM/HMIN302 - E-Applications/server/storage/rules.txt' );
        $rfactory = new RuleManagerFactory_file( $data, $RelationType );
        $rules    = $rfactory->new_();

        $db        = new Database();
        $fchecking = new FC( $rules, $db );

        $x = "chat";
        $p = "r_domain";
        $y = "sport";

        $p = "r_has_part";
        $y = "écaille";

//        $p = "r_has_part";
//        $y = "queue";

        $wp       = $RelationType->getRelation( $p );
        $wx       = $Word->where( 'n', $x )->first();
        $wy       = $Word->where( 'n', $y )->first();
        $question = new Term( $wp->_id );
        $question->addAtom( new Atom( 'n1', $wx->_id ), new Atom( 'n2', $wy->_id ) );

        if ( $wx === null || $wy === null || $wp === null )
            return null;

        $info = new Infos( $db, $Relation );
        $ret  = $fchecking->ask( $question, $info );

        echo "RESULT \n";

        foreach ( $ret as $res )
        {
            $rule = $res['rule'];
            $bind = $res['bind'];
            $this->makeRulesWithWords( $rule );
            $this->makeRulesWithWords( $bind );
//            foreach()
            var_dump( (string) $rule );
            var_dump( (string) $bind );
        }
    }

    private function makeTermsWithWords( Term ...$terms )
    {
        foreach ( $terms as $term )
        {
            $pred = $term->getPredicate();
            $tmp  = $this->dbRelationType->getRelation( $pred );

            if ( $tmp !== null )
            {
                $term->setPredicate( $tmp->name );
            }

            foreach ( $term->getAtoms() as $atom )
            {
                if ( !$atom->isConstant() )
                    continue;

                $word = $this->dbWord->getWord( $atom->getValue() );
                $atom->setValue( $word->n );
            }
        }
    }

    private function makeRulesWithWords( Rule ...$rules )
    {
        foreach ( $rules as $rule )
        {
            $this->makeTermsWithWords( ...$rule->getAllTerms() );
        }
    }
}