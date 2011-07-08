<?php

header('Content-type: text/xml');

ini_set('memory_limit', '2048M');

//phpinfo();

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

    protected $inRevision, $curText, $oldText, $revisionId, $nb_rev;
    protected $mesureLength, $mesureGrowth, $mesureId, $mesureOperation, $mesureIns, $mesureDel, $mesureTypeIns;
    protected $logoot;

    public function __construct($page, $logoot, $options = logootEngine::MODE_NONE) {
        parent::__construct($page);
        $this->logoot = $logoot;
        $this->logoot->setMode($options);
    }

    public function __destruct() {
        parent::__destruct();
    }

    protected function openElement($element) {
        $ok = false;
        switch ($element) {
            case 'page' :
                $this->inRevision = false;
                $this->nb_rev = 0;
                $this->curText = '';
                $this->mesureLength = new Mesure('length_pos');
                $this->mesureGrowth = new Mesure('growth_pos');
                $this->mesureId = new Mesure('new_pos');
                $this->mesureIns = new Mesure('Ins_op');
                $this->mesureDel = new Mesure('Del_op');
                $this->mesureOperation = new Mesure('operation');
                $this->mesureTypeIns = new Mesure('type_ins');
                $ok = $this->read();
                break;
            case 'revision' :
                $this->inRevision = true;
                $this->nb_rev += 1;
                $this->revisionId = $this->getAttribute('id');
                $ok = $this->read();
                break;
            case 'text' :
                $this->oldText = $this->curText;
                $this->curText = $this->readString();
                $patch = $this->logoot->generate($this->oldText, $this->curText);
                $this->mesureOperation->add($patch->size());
                $nb_ins = 0;
                $nb_del = 0;
                foreach ($patch as $op) {
                    if ($op->type() == LogootOperation::INSERT)
                        $nb_ins += 1;
                    else
                        $nb_del +=1;
                }
                $this->mesureIns->add($nb_ins);
                $this->mesureDel->add($nb_del);
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
                if (isset($tab)) {
                    foreach ($tab as $stat) {
                        $this->mesureGrowth->add($stat['growth']);
                        $this->mesureLength->add($stat['max_length']);
                        $this->mesureId->add($stat['nb']);
                        $this->mesureTypeIns->add($stat['pos']);
                    }
                    echo '      ' . $this->mesureGrowth->getXMLAbstract() . "\n";
                    echo '      ' . $this->mesureLength->getXMLAbstract() . "\n";
                    echo '      ' . $this->mesureId->getXMLAbstract() . "\n";
                    echo '      ' . $this->mesureOperation->getXMLAbstract() . "\n";
                    echo '      ' . $this->mesureIns->getXMLAbstract() . "\n";
                    echo '      ' . $this->mesureDel->getXMLAbstract() . "\n";
                    echo '      ' . $this->mesureTypeIns->getXMLAbstract() . "\n";
                }

                list($head, $tail, $new) = $this->logoot->getNbGenerate();
                echo '       <revision nb="' . $this->nb_rev . '" head_opt="' . $head . '" tail_opt="' . $tail . '" new_opt="' . $new . '" ' . "\>\n";

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

    protected $logoot, $mode, $fct;
    protected $liste_pages, $tab_pages;
    protected $rep;

    protected function getPages($patch, $param) {
        if (isset($param['x']))
            foreach ($patch->max as $p) {
                $this->tab_pages[] = $p['titre'];
            }
        if (isset($param['m']))
            foreach ($patch->random as $p) {
                $this->tab_pages[] = $p['titre'];
            }
    }

    protected function selectProp($ns, $param) {
        if (isset($param['patchs']) || isset($param['p'])) {
            foreach ($ns->liste_patchs as $patch)
                $this->getPages($patch, $param);
        }
        if (isset($param['tailles']) || isset($param['t'])) {
            foreach ($ns->liste_tailles as $patch)
                $this->getPages($patch, $param);
        }
        if (isset($param['robots']) || isset($param['r'])) {
            foreach ($ns->liste_robots as $patch)
                $this->getPages($patch, $param);
        }
        if (isset($param['users']) || isset($param['u'])) {
            foreach ($ns->liste_users as $patch)
                $this->getPages($patch, $param);
        }
    }

    public function __construct($rep, $liste, $param) {
        $this->rep = $rep;
        $this->fct = '';
        $this->liste_pages = simplexml_load_file($liste);
        $this->tab_pages = array();
        $this->nb_loaded = 0;
        //collecte des pages à récupérer
        foreach ($this->liste_pages->children() as $ns) {
            if (isset($param['n'])) {
                if (!is_array($param['n'])) {
                    if ($ns['nom'] == $param['n']) {
                        $this->selectProp($ns, $param);
                        $this->fct .= "<ns name='" . $ns['nom'] . "'/>";
                    }
                } else if (in_array($ns['nom'], $param['n'])) {
                    $this->selectProp($ns, $param);
                    $this->fct .= "<ns name='" . $ns['nom'] . "'/>";
                }
            } else {
                echo "tous les ns...";
                $this->selectProp($ns, $param);
            }
        }
        if (isset($param['p']))
            $this->fct .= "<patchs/>";
        if (isset($param['t']))
            $this->fct .= "<tailles/>";
        if (isset($param['r']))
            $this->fct .= "<robots/>";
        if (isset($param['u']))
            $this->fct .= "<users/>";
        if (isset($param['m']))
            $this->fct .= "<random/>";
        if (isset($param['x']))
            $this->fct .= "<max/>";

        $this->tab_pages = array_unique($this->tab_pages);
        $this->mode = logootEngine::MODE_STAT;
        if (isset($param['opt_ht']) || isset($param['ht']) || isset($param['o']))
            $this->mode |= logootEngine::MODE_OPT_INS_HEAD_TAIL;
        if (isset($param['boundary']) || isset($param['b']))
            $this->mode |= logootEngine::MODE_BOUNDARY_INI;
    }

    public function run() {
        date_default_timezone_set('Europe/Paris');
        echo "<?xml version='1.0'?>\n";
        echo "<Etude nb='" . count($this->tab_pages) . "' date='" . date("c") . "' >\n";

        if ($this->mode & logootEngine::MODE_STAT) {
            echo "    <Mode_Stat ";
            if ($this->mode & logootEngine::MODE_BOUNDARY_INI)
                echo "Boundary_classique='on' ";
            if ($this->mode & logootEngine::MODE_OPT_INS_HEAD_TAIL)
                echo "Head_Tail='on'";
            echo "/>\n";

            echo "    " . $this->fct . "\n";
        }
        foreach ($this->tab_pages as $page) {
            $file = $this->rep . '/' . utils::toFileName($page) . '.xml';
            echo "    <Analyse name='$page' file='$file'>\n";
            $this->logoot = manager::getNewEngine(manager::loadModel(0), 3);
            $pr = new PageReader($file, $this->logoot, $this->mode);
            $debut = microtime(true);
            $pr->run();
            $duree = round(microtime(true) - $debut, 2);
            echo "       <duration val='$duree'/>\n";
            echo "    </Analyse>\n";
        }
        echo "</Etude>\n";
    }

    public static function main($param) {
        if (isset($param['d'])
                && isset($param['l'])
                && (isset($param['t'])
                || isset($param['p'])
                || isset($param['r'])
                || isset($param['u']))) {
            if ((!isset($param['x'])) && (!isset($param['m'])))
                $param['x'] = true;
            //var_dump($param);
            $la = new LogootAnalyser($param['d'], $param['l'], $param);
            $la->run();
        } else {
            echo "Erreur de ligne de commande :\n\t php LogootAnalyser.php -d wiki_corpus_repository -l WA_list.xml [options]\n";
            echo "Option sur les espaces de noms :\n";
            echo '-n "namespace" (par défaut tous les espaces sont pris)' . "\n";
            echo "Options sur les propriétés (au moins une) :\n";
            echo "--tailles -t : pour la mesure sur les tailles des pages \n";
            echo "--patchs -p : pour la mesure sur le nombre de patchs \n";
            echo "--robots -r : pour la mesure sur le nombre de robots \n";
            echo "--users -u : pour la mesure sur le nombre d'utilisateurs référencés \n";
            echo "Options d'optimisation :\n";
            echo "--boundary -b : pour mettre en oeuvre les 'boundary' standard \n";
            echo "--opt_ht --ht -o : pour mettre en oeuvre les optimisations d'ajout en fin et début \n";
            echo "Options de type de page :\n";
            echo "-x : les pages 'max' (par défaut) \n";
            echo "-m : les pages 'random' \n";
        }
    }

}

LogootAnalyser::main(getopt(
                "d:l:n:btpruoxm")); //, array("ns::", "tailles", "patchs", "robots", "users", "boundary", "opt_ht")));
?>
