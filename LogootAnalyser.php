<?php

header('Content-type: text/xml');

ini_set('memory_limit', '2048M');

//phpinfo();

function __autoload($classe) {
    require_once './logootComponent/' . $classe . '.php';
}

$debug = false;

require_once './logoot_lib/utils.php';

require_once './logootComponent/logootEnv.php';
require_once './logootModel/manager.php';
require_once './logootModel/boModel.php';
require_once './logootModel/boModelPlus.php';
require_once './logootComponent/DiffEngine.php';
require_once './logootComponent/Math/BigInteger.php';

require_once './WikipediaReader.php';
require_once './Mesure.php';

function wfDebugLog($type, $message) {
    global $debug;
    if ($debug)
        echo '-- Debug Log --> Type : ' . $type . "\n\t" . $message . "\n";
}

class PageReader extends WikipediaReader {

    protected $inRevision, $curText, $oldText, $revisionId;
    protected $nb_rev;
    public $mesureLength, $mesureGrowth, $mesureId, $mesureOperation,
    $mesureIns, $mesureDel, $mesureTypeIns, $mesureTps, $mesureMem;
    protected $logoot;
    protected $mem;

    public function __construct($page, $logoot, $options = logootEngine::MODE_NONE) {
        parent::__construct($page);
        $this->logoot = $logoot;
        $this->logoot->setMode($options);
    }

    public function __destruct() {
        parent::__destruct();
    }

    public function getNb_rev() {
        return $this->nb_rev;
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
                $this->mesureTps = new Mesure('tps');
                //$this->mesureMem = new Mesure('memory');
                $ok = $this->read();
                //$this->mem = memory_get_usage(false);
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
                $debut = microtime(true);
                //$mem = memory_get_usage(false);
                $patch = $this->logoot->generate($this->oldText, $this->curText);
                $duree = microtime(true) - $debut;
                //$mem = (memory_get_peak_usage(false)-$mem)/ 1024 / 1024;
                $this->mesureTps->add($duree);
                $this->mesureOperation->add($patch->size());
                //$this->mesureMem->add($mem);//memory_get_usage()-memory_get_usage(false));
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
                }
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

    protected $logoot, $mode, $fct, $digit;
    protected $liste_pages, $tab_pages;
    protected $rep, $boundary, $bound_factor;

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
                $this->fct .= "<ns/>";
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
        
        $env = logootEnv::getInstance();
        
        if (isset($param['i'])) {
            if ($param['i'] > 0)
                $this->digit = (integer) $param['i'];
            else
                $this->digit = 2;
            $env->setDigit($this->digit);
        }

        $this->tab_pages = array_unique($this->tab_pages);
        $this->mode = logootEngine::MODE_STAT;
        if (isset($param['o']))
            $this->mode |= logootEngine::MODE_OPT_INS_HEAD_TAIL;
        if (isset($param['b'])) {
            $this->mode |= logootEngine::MODE_BOUNDARY_INI;
            if ($param['b'] > 0)
                $this->boundary = (integer) $param['b'];
            else
                $this->boundary = logootEngine::getDefaultBoundary();
        } else
            $this->boundary = logootEngine::getDefaultBoundary();
        if (isset($param['a']) && isset($param['b'])) {
            $this->mode |= logootEngine::MODE_BOUNDARY_OPT;
            if ($param['a'] > 0) {
                $this->bound_factor = (integer) $param['a'];
            }else
                $this->bound_factor = 3;
        }
    }

    public function run() {
        date_default_timezone_set('Europe/Paris');
        
        
        
        echo "<Etude nb='" . count($this->tab_pages) . "' date='" . date("c") . "' >\n";
        $env = logootEnv::getInstance();
        
        echo "    <logoot digit='".$env->getDigit()."' int_max='".$env->getInt_max()."' mode='".$env->getMode()."'/>\n";
        
        if ($this->mode & logootEngine::MODE_STAT) {
            echo "    <Mode_Stat ";
            if ($this->mode & logootEngine::MODE_BOUNDARY_OPT) {
                echo "Boundary='avancé' Boundary_val='" . $this->boundary . "' Boundary_fact='" . $this->bound_factor . "' ";
            } else if ($this->mode & logootEngine::MODE_BOUNDARY_INI) {
                echo "Boundary='standard' Boundary_val='" . $this->boundary . "' ";
            }
            if ($this->mode & logootEngine::MODE_OPT_INS_HEAD_TAIL)
                echo "Head_Tail='on'";
            echo "/>\n";

            echo "    " . $this->fct . "\n";
        }
        foreach ($this->tab_pages as $page) {
            $file = $this->rep . '/' . utils::toFileName($page) . '.xml';
            echo "    <Analyse name='$page' file='$file'>\n";
            $this->logoot = manager::getNewEngine(manager::loadModel(0), 3);

            if ($this->mode & logootEngine::MODE_BOUNDARY_INI) {
                $this->logoot->setBoundary($this->boundary);
            }

            if ($this->mode & logootEngine::MODE_BOUNDARY_OPT) {
                $this->logoot->setBoundary_modulator($this->bound_factor);
            }

            $pr = new PageReader($file, $this->logoot, $this->mode);
            $debut = microtime(true);
            $pr->run();
            $duree = round(microtime(true) - $debut, 2);
            echo "       <process duration='$duree' avg_duration='" . $pr->mesureTps->avg_key() . "' ";
            echo "memory_get_peak_usage='" . round(memory_get_peak_usage(false) / 1024 / 1024, 2) . '/' . round(memory_get_peak_usage(true) / 1024 / 1024, 2) .
            "' memory_get_usage='" . round(memory_get_usage(false) / 1024 / 1024, 2) .
            '/' . round(memory_get_usage(true) / 1024 / 1024, 2) . "'/>\n";
            echo '      ' . $pr->mesureGrowth->getXMLAbstract() . "\n";
            echo '      ' . $pr->mesureLength->getXMLAbstract() . "\n";
            echo '      ' . $pr->mesureId->getXMLAbstract() . "\n";
            echo '      ' . $pr->mesureOperation->getXMLAbstract() . "\n";
            echo '      ' . $pr->mesureIns->getXMLAbstract() . "\n";
            echo '      ' . $pr->mesureDel->getXMLAbstract() . "\n";
            echo '      ' . $pr->mesureTypeIns->getXMLAbstract() . "\n";
            echo '      ' . $pr->mesureTps->getXMLAbstract() . "\n";
            //echo '      ' . $pr->mesureMem->getXMLAbstract() . "\n";

            list($head, $tail, $new) = $this->logoot->getNbGenerate();
            echo '       <revision nb="' . $pr->getNb_rev() . '" head_opt="' . $head . '" tail_opt="' . $tail . '" new_opt="' . $new . '" ' . "/>\n";
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
            echo "-t : pour la mesure sur les tailles des pages \n";
            echo "-p : pour la mesure sur le nombre de patchs \n";
            echo "-r : pour la mesure sur le nombre de robots \n";
            echo "-u : pour la mesure sur le nombre d'utilisateurs référencés \n";
            echo "Options d'optimisation :\n";
            echo "-b val : pour mettre en oeuvre les 'boundary' standard \n";
            echo "-a val : pour mettre en oeuvre les 'boundary' avancés (si -b présent)\n";
            echo "-o : pour mettre en oeuvre les optimisations d'ajout en création, en fin et début \n";
            echo "-i val : pour spécifier la longueur des identifiants (2 par défaut)\n";
            echo "Options de type de page :\n";
            echo "-x : les pages 'max' (par défaut) \n";
            echo "-m : les pages 'random' \n";
        }
    }

}

LogootAnalyser::main(getopt(
                "d:l:n:b:a:i:tpruoxm")); //, array("ns::", "tailles", "patchs", "robots", "users", "boundary", "opt_ht")));

//echo PHP_INT_SIZE.'.'.PHP_INT_MAX.' '. strlen(PHP_INT_MAX ); // 8.9223372036854775807

?>
