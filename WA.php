<?php

header('Content-type: text/xml');

//require_once './DiffEngine.php';
function __autoload($classe) {
    require_once './' . $classe . '.php';
}

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of WA n
 *
 * @author ed
 */
define('NL', ord("\n"));
define('NB', 10);

class WA extends WikipediaReader {

    protected $writer;
    protected $inPage, $inRev;
    protected $namespaces, $ns, $titre, $txt, $txt_size, $fdate, $ldate, $nbRobots, $isRobot, $id_rev, $ip, $id, $username;
    protected $mes_taille, $mes_util, $mes_ins, $mes_mes_ajout_debut, $mes_mes_ajout_fin;
    protected $nb_patch;
    protected $tab_robots, $tab_users;
    
    protected $debut;

    public function __construct() {
        //frwiki-head.xml frwiki-20110409-pages-meta-history.xml
        parent::__construct('frwiki-20110409-pages-meta-history.xml');
        $this->inPage = false;
        $this->inRev = false;
    }

    public function __destruct() {
        parent::__destruct();
    }

    protected function locate($ota, $nta) {//recherche si $ota est inclus dans $nta
        $ok = false;
        $in = false;
        $debut = -1;
        $fin = -1;
        $lota = count($ota);
        $lnta = count($nta);
        if ($lota < $lnta) {
            $oi = 0;
            $ni = 0;
            while ($ni + $lota - $oi <= count($nta) && (!$ok)) {
                if ($ota[$oi] == $nta[$ni]) {
                    if (!$in) {
                        $debut = $ni;
                        $in = true;
                        $fin = $ni;
                    } else {
                        $fin = $ni;
                    }
                    $oi +=1;
                    $ok = ($oi == $lota);
                } elseif ($ota[$oi] != $nta[$ni]) {
                    if ($in) {
                        $debut = -1;
                        $in = false;
                        $fin = -1;
                        $oi = 0;
                    }
                }
                $ni +=1;
            }
        }
        return array($ok, $debut, $fin);
    }

    protected function view_page($writer, $title, $v) {
        $writer->startElement($title);
        $writer->writeAttribute('nb', $v->getKey());
        $page = $v->getValue();
        $writer->writeAttribute('titre', $page['titre']);
        $writer->writeAttribute('rang', $page['rang']);

        $writer->startElement('patchs');
        $writer->writeAttribute('nb_patchs', $page['nb_patchs']);
        if ($page['nb_patchs'] > 0 ) 
            $writer->writeAttribute('nb_patchs_robots', round($page['robots'] * 100 / $page['nb_patchs'], 2) . '%');
        else $writer->writeAttribute('nb_patchs_robots', '0%');
        $writer->endElement();

        $writer->startElement('taille');
        $writer->writeAttribute('finale', $page['taille_finale']);
        $mes_taille = $page['taille'];
        $min = $mes_taille->min();
        $writer->writeAttribute('min', $min[0]);
        $writer->writeAttribute('avg', round($mes_taille->avg_key(), 2));
        $max = $mes_taille->max();
        $writer->writeAttribute('max', $max[0]);
        $writer->endElement();

        $writer->startElement('date');
        $writer->writeAttribute('creation', $page['creation']);
        $writer->writeAttribute('modif', $page['modif']);
        $writer->endElement();

        $writer->endElement(); // de "max"        
    }

    protected function view($writer, $name, $list) {
        $writer->startElement($name);
        $writer->writeAttribute('avg_patch', $list->avg_key());
        $max = $list->max();
        $min = $list->min();
        $writer->writeAttribute('min', $min[0]);
        $writer->writeAttribute('max', $max[0]);

        $maxs = $list->getMaxs();
        foreach ($maxs as $v) {
            $this->view_page($writer, 'max', $v);
        }

        $others = $list->getOthers();
        foreach ($others as $k => $v) {
            $this->view_page($writer, 'random', $v);
        }

        $writer->endElement();
    }

    protected function writeRes() {
        $writer = new XMLWriter();
        $writer->openMemory();
        $writer->setIndent(true);
        $writer->startDocument('1.0', 'utf-8');
        //$writer->writeDTD("liste-pages", null, "livres.dtd");
        $writer->startElement('liste-pages');
        $writer->writeAttribute('nb', $this->nb_pages);
        foreach ($this->namespaces as $ns => $cpt) {
            if ($cpt['nb'] > 0) {
                $writer->startElement('ns');
                $writer->writeAttribute('nom', $ns);
                $writer->writeAttribute('nb', $cpt['nb']);

                $this->view($writer, 'patchs', $cpt['patch']);
                $this->view($writer, 'tailles', $cpt['taille']);
                $this->view($writer, 'robots', $cpt['robot']);
                $this->view($writer, 'users', $cpt['user']);
                $writer->endElement();
            }
        }
        $writer->endElement();
        $writer->endDocument();
        echo $writer->outputMemory();
    }

    protected function openElement($element) {
        switch ($element) {
            case 'mediawiki' :
            case 'siteinfo' : case 'namespaces' :
                $ok = $this->read();
                break;
            case 'page' : $this->debut = microtime(true);
                $ok = $this->read();
                $this->nb_pages += 1;
                $this->txt = '';
                $this->nbRobots = 0;
                $this->tab_robots = array();
                $this->tab_users = array();
                $this->mes_taille = new Mesure('taille', 1);
                break;
            case 'namespace' :
                $ns = $this->readString();
                $this->namespaces[$ns] = array(
                    'nb' => 0,
                    'patch' => new Mesure('patch', NB),
                    'robot' => new Mesure('robot', NB),
                    'user' => new Mesure('user', NB),
                    'taille' => new Mesure('taille',NB)
                );
                $ok = $this->next();
                break;
            case 'title' :
                $this->titre = $this->readString();
                //echo "Traitement de '$this->titre' ";
                $t = explode(':', $this->titre);
                $this->ns = '';
                if (count($t) > 1)
                    if (array_key_exists($t[0], $this->namespaces)) {
                        $this->namespaces[$t[0]]['nb'] += 1;
                        $this->ns = $t[0];
                    } else
                        $this->namespaces['']['nb'] += 1;
                else
                    $this->namespaces['']['nb'] += 1;
                $this->nb_patch = 0;
                $ok = $this->next();
                break;
            case 'revision' :
                $this->nb_patch += 1;
                $this->inRev = true;
                $this->id = null;
                $this->ip = null;
                $this->username = '';
                $ok = $this->read();
                break;
            case 'id' :
                if ($this->inRev)
                    $this->id_rev = $this->readString();
                $ok = $this->next();
                break;
            case 'timestamp':
                if ($this->nb_patch == 1)
                    $this->fdate = $this->readString();
                else
                    $this->ldate = $this->readString();
                $ok = $this->next();
                break;

            case 'contributor' :
                $ok = $this->read();
                break;
            case 'ip' :
                $this->ip = $this->readString();
                $this->isRobot = !$this->isIP($this->ip); //$this->ip == 'script de conversion';
                $ok = $this->next();
                break;
            case 'id' :
                $this->id = $this->readString();
                $ok = $this->next();
                break;
            case 'username' :
                $this->username = $this->readString();
                $ok = $this->next();
                break;

            case 'minor' :
                $ok = $this->next();
                break;
            case 'comment' :
                $comment = $this->readString();
                // Détecter les robots
                $this->isRobot = $this->isRobot($comment);
                $ok = $this->next();
                break;
            case 'text' :
                $last = $this->txt;
                $last_size = $this->txt_size;
                $a = count_chars($this->readString(), 1);
                $this->txt_size = $a[NL] + 1;
                $this->mes_taille->add($this->txt_size, $this->id_rev);
                $ok = $this->next();
                break;
            default : $ok = $this->next();
        }
        return $ok;
    }

    protected function closeElement($element) {
        switch ($element) {
            case 'contributor' :
                $ok = $this->next();
                break;
            case 'page' :
                $page = array(
                    'rang' => $this->nb_pages,
                    'creation' => $this->fdate,
                    'modif' => $this->ldate,
                    'titre' => $this->titre,
                    'taille_finale' => $this->txt_size,
                    'taille' => $this->mes_taille,
                    'robots' => array_sum($this->tab_robots),
                    //'users' => array_sum($this->tab_users),
                    'nb_patchs' => $this->nb_patch
                );

                $this->namespaces[$this->ns]['patch']->add($this->nb_patch, $page);
                $this->namespaces[$this->ns]['robot']->add(count($this->tab_robots), $page);
                $this->namespaces[$this->ns]['user']->add(count($this->tab_users), $page);
                $max_taille = $this->mes_taille->max();
                $this->namespaces[$this->ns]['taille']->add($max_taille[0], $page);

                $duree = round(microtime(true) - $this->debut,2);
                $ok = $this->next();
                /*if ($this->nb_pages % 50000 == 0) {
                    $this->writeRes();
                    echo "\n";
                }*/
                /*echo $duree.' s. - '. memory_get_peak_usage() .
                ":" . memory_get_usage(true) .
                '(' . memory_get_usage(false) . ")\n";*/
                break;
            case 'revision' :
                if ($this->isRobot) {//echo "Robot : $comment\n";
                    if (isset($this->ip))
                        $this->tab_robots[$this->ip] += 1;
                    else
                        $this->tab_robots[$this->id . $this->username] += 1;
                } else {
                    if (isset($this->ip))
                        $this->tab_users[$this->ip] += 1;
                    else
                        $this->tab_users[$this->id . $this->username] += 1;
                }
                $this->inRev = false;
                $ok = $this->next();
                break;
            default : $ok = $this->read();
        }
        return $ok;
    }

    public function run() {
        parent::run();
        $this->writeRes();
    }

    public static function main() {
        $wa = new WA();
        $wa->run();
    }

}

WA::main();

?>
