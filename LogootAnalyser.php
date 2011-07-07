<?php

header('Content-type: text/plain');

function __autoload($classe) {
    require_once './logootComponent/' . $classe . '.php';
}

$debug = false;

require_once './logootModel/manager.php';
require_once './logootModel/boModel.php';
require_once './logootModel/boModelPlus.php';
require_once './logootComponent/DiffEngine.php';
require_once './logootComponent/Math/BigInteger.php';
require_once './utils.php';
require_once './WikipediaReader.php';
require_once './Mesure.php';

if (!defined('DIGIT')) {
    define('DIGIT', 2);
}

if (!defined('INT_MAX')) {
    define('INT_MAX', (integer) pow(10, DIGIT));
}

if (!defined('INT_MIN')) {
    define('INT_MIN', 0);
}

if (!defined('BASE')) {
    define('BASE', (integer) (INT_MAX - INT_MIN));
}

if (!defined('CLOCK_MAX')) {
    define('CLOCK_MAX', "100000000000000000000000");
}

if (!defined('CLOCK_MIN')) {
    define('CLOCK_MIN', "0");
}

if (!defined('SESSION_MAX')) {
    define('SESSION_MAX', "FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF"); //.CLOCK_MAX);
    //050F550EB44F6DE53333AE460EE85396
}

if (!defined('SESSION_MIN')) {
    define('SESSION_MIN', "0");
}

if (!defined('BOUNDARY')) {
    define('BOUNDARY', (integer) pow(10, DIGIT / 2));
}

if (!defined('LOGOOTMODE')) {
    define('LOGOOTMODE', 'STD');
    //define('LOGOOTMODE', 'PLS');
}

function wfDebugLog($type, $message) {
    global $debug;
    if ($debug)
        echo '-- Debug Log --> Type : ' . $type . "\n\t" . $message . "\n";
}

class PageReader extends WikipediaReader {

    protected $inRevision, $curText, $oldText;
    protected $mesureLength, $mesureGrowth, $mesureId, $mesureOperation, $mesureIns, $mesureDel;
    protected $logoot;

    public function __construct($page, $logoot) {
        parent::__construct($page);
        $this->logoot = $logoot;
    }

    public function __destruct() {
        parent::__destruct();
    }

    protected function openElement($element) {
        $ok = false;
        switch ($element) {
            case 'page' :
                $this->inRevision = false;
                $this->curText = '';
                $this->mesureLength = new Mesure('length_pos', 10);
                $this->mesureGrowth = new Mesure('growth_pos', 10);
                $this->mesureId = new Mesure('new_pos', 10);
                $this->mesureIns = new Mesure('Ins_op', 10);
                $this->mesureDel = new Mesure('Del_op', 10);
                $this->mesureOperation = new Mesure('operation', 10);
                $this->logoot->setMode(logootEngine::MODE_STAT 
                        | logootEngine::MODE_BOUNDARY_INI
                        );
                $ok = $this->read();
                break;
            case 'revision' :
                $this->inRevision = true;
                $ok = $this->read();
                break;
            case 'text' :
                $this->oldText = $this->curText;
                $this->curText = $this->readString();
                $patch = $this->logoot->generate($this->oldText, $this->curText);
                $this->mesureOperation->add($patch->size(), null);
                $nb_ins = 0;
                $nb_del = 0;
                foreach ($patch as $op) {
                    if ($op->type() == LogootOperation::INSERT)
                        $nb_ins += 1;
                    else
                        $nb_del +=1;
                }
                $this->mesureIns->add($nb_ins, null);
                $this->mesureDel->add($nb_del, null);
                $ok = $this->next();
                break;
            default : $ok = $this->next();
        }
        return $ok;
    }

    protected function closeElement($element) {
        $ok = false;
        switch ($element) {
            case 'revision' :
                $this->inRevision = false;
                $ok = $this->next();
                break;
            case 'page' :
                $tab = $this->logoot->getTabStat();
                foreach ($tab as $stat) {
                    $this->mesureGrowth->add($stat['growth'], null);
                    $this->mesureLength->add($stat['max_length'], null);
                    $this->mesureId->add($stat['nb'], null);
                }
                echo $this->mesureGrowth->getXMLAbstract() . "\n";
                echo $this->mesureLength->getXMLAbstract() . "\n";
                echo $this->mesureId->getXMLAbstract() . "\n";
                echo $this->mesureOperation->getXMLAbstract() . "\n";
                echo $this->mesureIns->getXMLAbstract() . "\n";
                echo $this->mesureDel->getXMLAbstract() . "\n";
                $ok = $this->next();
                break;
            default : $ok = $this->next();
        }
        return $ok;
    }

}

/**
 * Description of LogootAnalyser
 *
 * @author emmanueldesmontils
 */
class LogootAnalyser {

    protected $logoot;
    protected $liste_pages;
    protected $rep;

    public function __construct($rep, $liste) {
        $this->rep = $rep;
        $this->logoot = manager::getNewEngine(manager::loadModel(0), 3);
        //$this->liste_pages = simplexml_load_file($liste);
    }

    public function run() {
        $file = $this->rep . '/Algèbre_und_générale' . '.xml';
        $pr = new PageReader($file, $this->logoot);
        $pr->run();
    }

    public static function main($param) {
        if (isset($param['d']) && isset($param['l'])) {
            $la = new LogootAnalyser($param['d'], $param['l']);
            $la->run();
        }
        else
            echo "php LogootAnalyser.php -d wiki_corpus_repository -l WA_list.xml\n";
    }

}

LogootAnalyser::main(getopt("d:l:"));
?>
