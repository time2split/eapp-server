<?php

namespace App\Http\Controllers;

use App\JDMPatternEngine\FC;
use App\JDMPatternEngine\RuleManagerFactory_file;
use App\JDMPatternEngine\Database;
use App\JDMPatternEngine\Rule;
use App\JDMPatternEngine\Atom;
use App\JDMPatternEngine\Term;
use App\Word;
use App\Relation;
use App\RelationType;
use Illuminate\Http\Request;
use App\Http\Controllers\JDMPatternEngine\Infos;
use \Illuminate\Support\Facades\Storage;

class JDMPatternEngine extends Controller
{
    private $dbWord;
    private $dbRelation;
    private $dbRelationType;

    private function makeConfig($config)
    {
        $config['filter_oneResult_divFactor_def'] = $config['filter_oneResult_divFactor'][count($config['filter_oneResult_divFactor']) - 1];
        $config['domain_nbValues_def']            = $config['domain_nbValues'][count($config['domain_nbValues']) - 1];
        $config['result_max_def']                 = $config['result_max'][count($config['result_max']) - 1];
        $config['filter_oneResult_divFactor']     = explode(',', $config['filter_oneResult_divFactor']);
        $config['domain_nbValues']                = explode(',', $config['domain_nbValues']);
        $config['result_max']                     = explode(',', $config['result_max']);
        $config['domain_order_rand']              = (bool) $config['domain_order_rand'];
        $config['time_max']                       = (int) $config['time_max'];
        return $config;
    }

    public function __construct(Word $dbWord, Relation $dbRelation, RelationType $dbRelationType)
    {
        $this->dbWord         = $dbWord;
        $this->dbRelation     = $dbRelation;
        $this->dbRelationType = $dbRelationType;
    }

    public function __invoke(Request $r, string $worda, string $relation, string $wordb)
    {
        $ret          = null;
        $Word         = $this->dbWord;
        $Relation     = $this->dbRelation;
        $RelationType = $this->dbRelationType;

        $data     = file(Storage::getDriver()->getAdapter()->applyPathPrefix('rules.txt'));
        $rfactory = new RuleManagerFactory_file($data, $RelationType);
        $rules    = $rfactory->new_();

        $config = $r->query('config', []);
        $config = $this->makeConfig($config);

        $db        = new Database();
        $fchecking = new FC($rules, $db);

        $x = $worda;
        $p = $relation;
        $y = $wordb;

        $wp       = $RelationType->getRelation($p);
        $wx       = $Word->getWord($x);
        $wy       = $Word->getWord($y);
        $question = new Term($wp->_id);
        $question->addAtom(new Atom('n1', $wx->_id), new Atom('n2', $wy->_id));

        if ($wx === null || $wy === null || $wp === null)
            return [];

        $info = new Infos($db, $Relation, $this->dbWord, $config);
        $ret  = $fchecking->ask($question, $info);

        if ($r->query('makeWords', true)) {
            $this->resultMakeWords($ret);
        }

        if ($r->query('castString', true)) {
            $fwalk = function (&$v) {

                if (is_object($v)) {
                    $v = (string) $v;
                }
            };
            array_walk_recursive($ret, $fwalk);
        }

        if ($r->query('print', false)) {
            return view('JDM-print', ['data' => $ret]);
        }
        return $ret;
    }

    public function resultMakeWords(&$ret)
    {
        if (!is_array($ret)) {
            return;
        }

        foreach ($ret as &$res) {
            $rule   = &$res['rule'];
            $bind   = &$res['bind'];
            $result = &$res['result'];
            $asks   = &$res['asks'];

            if (null !== $rule)
                $this->makeRulesWithWords($rule);

            if (null !== $bind)
                $this->makeRulesWithWords($bind);

            if (null !== $result)
                $this->makeTermsWithWords($result);

            $this->resultMakeWords($asks);
        }
    }

    private function makeTermsWithWords(Term ...$terms)
    {
        foreach ($terms as $term) {
            $pred = $term->getPredicate();
            $tmp  = $this->dbRelationType->getRelation($pred);

            if ($tmp !== null) {
                $term->setPredicate($tmp->name);
            }

            foreach ($term->getAtoms() as $atom) {

                if (!$atom->isConstant())
                    continue;
                $wid = $atom->getValue();

//Terme deja remplacé
                if (is_string($wid)) {
                    continue;
                }
                $word = $this->dbWord->getWord($wid)->toArray();
                $word = $word['nf'] ?? $word['n'];

//                while ( preg_match( "#[[:digit:]]+#", $word, $matches ) )
//                {
//                    $idw  = $matches[0];
//                    $tmpword = $this->dbWord->getWord( (int) $matches[0] );
//                    $word = preg_replace( "#[[:digit:]]+#", $tmpword, $word );
//                    var_dump($word);
//                    exit;
////                    $this->dbWord->getWord( (int) $matches[0] );
//                }
                $atom->setValue($word);
            }
        }
    }

    private function makeRulesWithWords(Rule ...$rules)
    {
        foreach ($rules as $rule) {
            $this->makeTermsWithWords(...$rule->getAllTerms());
        }
    }
}