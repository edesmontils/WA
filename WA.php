<?php

header('Content-type: text/xml');
ini_set('memory_limit', '2048M');
require_once './logoot_lib/utils.php';

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
    protected $inPage, $inRev, $inContrib;
    protected $namespaces, $ns, $titre, $txt, $txt_size, $fdate, $ldate,
    $nbRobots, $isRobot, $id_rev, $ip, $id, $username, $page_id;
    protected $mes_taille, $mes_util, $mes_ip, $mes_car,
    $mes_ins, $mes_mes_ajout_debut, $mes_mes_ajout_fin;
    protected $nb_patch, $weight;
    protected $tab_robots, $tab_users, $tab_ip;
    protected $debut;
    protected $nb_mem;

    public function __construct($wiki, $nb = NB) {
        //frwiki-head.xml frwiki-20110409-pages-meta-history.xml
        //parent::__construct('frwiki-20110409-pages-meta-history.xml');
        parent::__construct($wiki);
        date_default_timezone_set('Europe/Paris');
        $this->inPage = false;
        $this->inRev = false;
        $this->inContrib = false;
        $this->nb_mem = $nb;
    }

    public function __destruct() {
        parent::__destruct();
    }

    protected function view_page($writer, $title, $v) {
        $writer->startElement($title);
        $writer->writeAttribute('nb', $v->getKey());
        $page = $v->getValue();
        $writer->writeAttribute('titre', $page['titre']);
        $writer->writeAttribute('rang', $page['rang']);
        $writer->writeAttribute('id', 'p' . $page['id']);

        $writer->startElement('patchs');
        $writer->writeAttribute('nb_patchs', $page['nb_patchs']);
        if ($page['nb_patchs'] > 0)
            $writer->writeAttribute('nb_patchs_robots', round($page['robots'] * 100 / $page['nb_patchs'], 2) . '%');
        else
            $writer->writeAttribute('nb_patchs_robots', '0%');
        $writer->endElement();

        $writer->startElement('tailles');
        $writer->writeAttribute('finale', $page['taille_finale']);
        $mes_taille = $page['taille'];
        $min = $mes_taille->min();
        $writer->writeAttribute('min', $min[0]);
        $writer->writeAttribute('avg', round($mes_taille->avg_key(), 2));
        $max = $mes_taille->max();
        $writer->writeAttribute('max', $max[0]);
        $writer->endElement();

        $writer->startElement('poids');
        $writer->writeAttribute('final', $page['volume_final']);
        $mes_taille = $page['volume'];
        $min = $mes_taille->min();
        $writer->writeAttribute('min', $min[0]);
        $writer->writeAttribute('avg', round($mes_taille->avg_key(), 2));
        $max = $mes_taille->max();
        $writer->writeAttribute('max', $max[0]);
        $writer->endElement();

        $writer->startElement('users');
        $writer->writeAttribute('patch_id', $page['users']);
        $writer->writeAttribute('patch_ip', $page['ip']);
        $writer->writeAttribute('patch_robots', $page['robots']);
        $writer->writeAttribute('id', $page['uusers']);
        $writer->writeAttribute('ip', $page['uip']);
        $writer->writeAttribute('robots', $page['urobots']);
        $writer->endElement();

        $writer->startElement('dates');
        $writer->writeAttribute('creation', $page['creation']);
        $writer->writeAttribute('modif', $page['modif']);

        /*try {
            $d1 = new DateTime($page['creation']);
            $d2 = new DateTime($page['modif']);
            $iv = $d2->diff($d1);
            //$writer->writeAttribute('age', $iv->format("%y an(s) %m mois %d jour(s)"));
            $writer->writeAttribute('age', $iv->format("P%YY%MM%DDT%HH%IM%SS"));
        } catch (Exception $e) {
            echo 'Exception reçue : ', $e->getMessage(), "\n";
        }*/

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
        $writer->writeDTD("liste_pages", null, "http://edamiral.hd.free.fr/ns/explore.dtd");
        $writer->startElement('liste_pages');
        $writer->writeAttribute('nb', $this->nb_pages);
        $writer->writeAttribute('file', $this->file);

        foreach ($this->namespaces as $ns => $cpt) {
            if ($cpt['nb'] > 0) {
                $writer->startElement('ns');
                $writer->writeAttribute('nom', $ns);
                $writer->writeAttribute('nb', $cpt['nb']);

                $this->view($writer, 'liste_patchs', $cpt['patch']);
                $this->view($writer, 'liste_tailles', $cpt['taille']);
                $this->view($writer, 'liste_poids', $cpt['volume']);
                
                $this->view($writer, 'liste_max_tailles', $cpt['max_taille']);
                $this->view($writer, 'liste_max_poids', $cpt['max_volume']);
                
                $this->view($writer, 'liste_robots', $cpt['robot']);
                $this->view($writer, 'liste_users', $cpt['user']);
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
                $this->tab_ip = array();
                $this->mes_taille = new Mesure('taille', 1);
                $this->mes_car = new Mesure('volume', 1);
                $this->inPage = true;
                break;
            case 'namespace' :
                $ns = $this->readString();
                $this->namespaces[$ns] = array(
                    'nb' => 0,
                    'patch' => new Mesure('patch', $this->nb_mem),
                    'robot' => new Mesure('robot', $this->nb_mem),
                    'user' => new Mesure('user', $this->nb_mem),
                    'ip' => new Mesure('ip', $this->nb_mem),
                    'taille' => new Mesure('taille', $this->nb_mem),
                    'volume' => new Mesure('volume', $this->nb_mem),
                    'max_taille' => new Mesure('max_taille', $this->nb_mem),
                    'max_volume' => new Mesure('max_volume', $this->nb_mem)
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
                if ($this->inContrib)
                    $this->id = $this->readString();
                else if ($this->inRev)
                    $this->id_rev = $this->readString();
                else
                    $this->page_id = $this->readString();
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
                $this->inContrib = true;
                $ok = $this->read();
                break;
            case 'ip' :
                $this->ip = $this->readString();
                $this->isRobot = !$this->isIP($this->ip); //$this->ip == 'script de conversion';
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
                $this->txt = $this->readString();
                $a = count_chars($this->txt, 1);
                $this->txt_size = $a[NL] + 1;
                $this->mes_taille->add($this->txt_size, $this->id_rev);
                $this->weight = strlen($this->txt);
                $this->mes_car->add($this->weight, $this->id_rev);
                $ok = $this->next();
                break;
            default : $ok = $this->next();
        }
        return $ok;
    }

    protected function closeElement($element) {
        switch ($element) {
            case 'contributor' :
                $this->inContrib = false;
                $ok = $this->next();
                break;
            case 'page' :
                $page = array(
                    'id' => $this->page_id,
                    'rang' => $this->nb_pages,
                    'creation' => $this->fdate,
                    'modif' => $this->ldate,
                    'titre' => $this->titre,
                    'taille_finale' => $this->txt_size,
                    'taille' => $this->mes_taille,
                    'volume' => $this->mes_car,
                    'volume_final' => $this->weight,
                    'robots' => array_sum($this->tab_robots),
                    'users' => array_sum($this->tab_users),
                    'ip' => array_sum($this->tab_ip),
                    'urobots' => count($this->tab_robots),
                    'uusers' => count($this->tab_users),
                    'uip' => count($this->tab_ip),
                    'nb_patchs' => $this->nb_patch
                );

                $this->namespaces[$this->ns]['patch']->add($this->nb_patch, $page);
                $this->namespaces[$this->ns]['robot']->add(count($this->tab_robots), $page);
                $this->namespaces[$this->ns]['user']->add(count($this->tab_users), $page);
                $this->namespaces[$this->ns]['ip']->add(count($this->tab_ip), $page);
                $max_taille = $this->mes_taille->max();
                $this->namespaces[$this->ns]['max_taille']->add($max_taille[0], $page);
                $this->namespaces[$this->ns]['taille']->add($this->txt_size, $page);
                $max_volume = $this->mes_car->max();
                $this->namespaces[$this->ns]['max_volume']->add($max_volume[0], $page);
                $this->namespaces[$this->ns]['volume']->add($this->weight, $page);

                $duree = round(microtime(true) - $this->debut, 2);
                $this->inPage = false;
                $ok = $this->next();
                /* if ($this->nb_pages % 50000 == 0) {
                  $this->writeRes();
                  echo "\n";
                  } */
                /* echo $duree.' s. - '. memory_get_peak_usage() .
                  ":" . memory_get_usage(true) .
                  '(' . memory_get_usage(false) . ")\n"; */
                break;
            case 'revision' :
                if ($this->isRobot) {//echo "Robot : $comment\n";
                    if (isset($this->ip))
                        $this->tab_robots[$this->ip] += 1;
                    else
                        $this->tab_robots[$this->id . $this->username] += 1;
                } else {
                    if (isset($this->ip))
                        $this->tab_ip[$this->ip] += 1;
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

    public static function main($param) {
        //var_dump( $param);
        if (isset($param['w'])) {
            if (isset($param['n']))
                $wa = new WA($param['w'], $param['n']);
            else
                $wa = new WA($param['w']);
            $wa->run();
        }
        else
            echo "php WA.php -w wiki_dump.xml [-n nb_of_res]\n";
    }

}

WA::main(getopt("w:n:"));
?>
