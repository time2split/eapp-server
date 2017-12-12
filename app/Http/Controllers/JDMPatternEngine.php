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
use Illuminate\Http\Request;

class Infos extends FCInfos
{
    private $db;
    private $Relation;
    public $maxDepth = 1;
    private $asked   = []; //Déjà demandés

    public function __construct( $db, Relation $relation )
    {
        $this->db       = $db;
        $this->Relation = $relation;
    }

    public function canDoRecursion()
    {
        if ( $this->depth >= $this->maxDepth )
            return null;

        if ( $this->calls > 100 )
            return false;

        return true;
    }

    public function getNbMaxResults()
    {
        switch ( $this->depth )
        {
            case 0:
                return 4;
            case 1:
                return 1;
            default:
                return 1;
        }
    }

    public function selectDomain( $domain )
    {
        usort( $domain, function($terma, $termb) {
            return $termb->getWeight() - $terma->getWeight();
        } );
        $nbVal = 0;

        switch ( $this->depth )
        {
            case 0 : $nbVal = 16;
                break;
            case 1 : $nbVal = 10;
                break;
            default : $nbVal = 10;
        }
        $a     = array_merge( array_slice( $domain, 0, $nbVal / 2 ), array_slice( $domain, - $nbVal / 2 ) );
        $randa = $domain;
        shuffle( $randa );
//        $randa = array_slice( $randa, $nbVal / 3 );
//        $a     = array_merge( $a, $randa );
        return array_unique( $a );
    }

    public function computeWeight( $ruleBinded )
    {
        $w      = 0;
        $factor = 1;

        foreach ( $ruleBinded->getHypotheses() as $atom )
        {
            $tmp = $atom->getWeight();
            $neg = $tmp < 0;

            if ( $neg )
                $factor = -1;

            $w += $neg ? -$tmp : $tmp;
        }
        return $w / count( $ruleBinded->getHypotheses() ) * $factor;
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
                {
                    throw new Exception( "Ask for $k => $v already presents" );
                }
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
        {
            throw new Exception( "Unknow ask !" );
        }
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

    public function __invoke( Request $r, string $worda, string $relation, string $wordb )
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

//        $x = "chat";
//        $p = "r_domain";
//        $y = "sport";
//        $p = "r_has_part";
//        $y = "écaille";
//        $x = "plage";
//        $p = 'r_associated';
//        $y = 'sable';
//        $y = 'mur';
//        $p = "r_has_part";
//        $y = "queue";
        $x = $worda;
        $p = $relation;
        $y = $wordb;

        $wp       = $RelationType->getRelation( $p );
        $wx       = $Word->getWord( $x );
        $wy       = $Word->getWord( $y );
        $question = new Term( $wp->_id );
        $question->addAtom( new Atom( 'n1', $wx->_id ), new Atom( 'n2', $wy->_id ) );

        if ( $wx === null || $wy === null || $wp === null )
            return [];

        $info = new Infos( $db, $Relation );
        $ret  = $fchecking->ask( $question, $info );

        if ( $r->query( 'print', false ) )
        {
            ob_start();
            $this->printResult( $ret );
            return ob_get_clean();
        }
        return $ret;
    }

    public function printResult( $ret )
    {
        foreach ( $ret as $res )
        {
            $rule   = $res['rule'];
            $bind   = $res['bind'];
            $result = $res['result'];
            $asks   = $res['asks'] ?? [];

            if ( null !== ( $rule ) )
                $this->makeRulesWithWords( $rule );

            if ( null !== ( $bind ) )
                $this->makeRulesWithWords( $bind );

            var_dump( (string) $rule );
            var_dump( (string) $bind );

            foreach ( $result as $r )
            {
                echo "$r\n";
            }

            foreach ( $asks as $ask )
            {
                foreach ( $ask as $aske )
                {
                    if ( is_array( $aske ) )
                    {
//            var_dump( $aske );
                        echo "<<<\n";
                        $this->printResult( $ask );
                        echo ">>>\n";
                        break;
                    }
                }
            }
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
                $word = $word->n;

//                while ( preg_match( "#[[:digit:]]+#", $word, $matches ) )
//                {
//                    $idw  = $matches[0];
//                    $tmpword = $this->dbWord->getWord( (int) $matches[0] );
//                    $word = preg_replace( "#[[:digit:]]+#", $tmpword, $word );
//                    var_dump($word);
//                    exit;
////                    $this->dbWord->getWord( (int) $matches[0] );
//                }
                $atom->setValue( $word );
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