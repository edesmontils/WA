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
 * Description of Extractor
 *
 * @author ed
 */
class Extractor extends WikipediaReader {

    protected $writer;
    protected $liste_pages, $tab_pages, $tab_rang, $nb_loaded;
    protected $debut, $next, $fin;
    protected $page_title, $page_id, $user_id, $username, $user_ip, $revisioon_id,
    $revision_timestamp, $comment, $isMinor, $text;

    public function __construct($wiki, $liste) {
        parent::__construct($wiki);

        $this->liste_pages = simplexml_load_file($liste);
        $this->fin = false;
        $this->tab_pages = array();
        $this->tab_pages = array();
        $this->nb_loaded = 0;
        //collecte des pages à récupérer
        foreach ($this->liste_pages->children() as $ns) {
            echo "Récup de " . $ns['nom'] . "\n";
            foreach ($ns->patchs as $patch) {
                foreach ($patch->max as $p) {
                    $this->tab_pages[] = $p['titre'];
                    $this->tab_rang[] = $p['rang'];
                }
                foreach ($patch->random as $p) {
                    $this->tab_pages[] = $p['titre'];
                    $this->tab_rang[] = $p['rang'];
                }
            }
            foreach ($ns->tailles as $patch) {
                foreach ($patch->max as $p) {
                    $this->tab_pages[] = $p['titre'];
                    $this->tab_rang[] = $p['rang'];
                }
                foreach ($patch->random as $p) {
                    $this->tab_pages[] = $p['titre'];
                    $this->tab_rang[] = $p['rang'];
                }
            }
            foreach ($ns->robots as $patch) {
                foreach ($patch->max as $p) {
                    $this->tab_pages[] = $p['titre'];
                    $this->tab_rang[] = $p['rang'];
                } foreach ($patch->random as $p) {
                    $this->tab_pages[] = $p['titre'];
                    $this->tab_rang[] = $p['rang'];
                }
            }
            foreach ($ns->users as $patch) {
                foreach ($patch->max as $p) {
                    $this->tab_pages[] = $p['titre'];
                    $this->tab_rang[] = $p['rang'];
                } foreach ($patch->random as $p) {
                    $this->tab_pages[] = $p['titre'];
                    $this->tab_rang[] = $p['rang'];
                }
            }
        }
        $this->tab_pages = array_unique($this->tab_pages); //TODO : suppression des doublons
        $this->tab_rang = array_unique($this->tab_rang);
        natsort($this->tab_rang);
        echo "Récupération de " . count($this->tab_pages) . " page(s)\n";
    }

    protected function toFileName($title) {
        return str_replace(
                array(" ", "(", ")", "'", "/", ":"), array("_und_", "_ope_", "_clo_", "_cot_", "_slh_", "_ns_"), $title);
    }

    public function __destruct() {
        parent::__destruct();
    }

    protected function openElement($element) {
        switch ($element) {
            case 'mediawiki' :
                $this->next = array_shift($this->tab_rang);
                $ok = $this->read();
                break;
            case 'siteinfo' : case 'namespaces' :
                $ok = $this->next();
                break;
            case 'page' :
                $this->nb_pages += 1;
                if ($this->nb_pages == $this->next) {
                    /* $this->debut = microtime(true);
                      $sxml = new SimpleXMLElement($this->readOuterXml());
                      $duree = round(microtime(true) - $this->debut, 2);
                      echo $duree . ' s. - ' . memory_get_peak_usage() .
                      ":" . memory_get_usage(true) .
                      '(' . memory_get_usage(false) . ")\n";
                      //$dom = $this->expand();
                      $titre = (String) $sxml->title;
                      if (in_array($titre, $this->tab_pages)) {
                      $filename = 'files/' . $this->toFileName($titre) . '.xml';
                      echo $this->nb_pages . " " . $filename . " mémorisée\n";
                      $sxml->asXML($filename);
                      } else
                      echo "$this->nb_pages $titre passée\n";
                      if (count($this->tab_rang) > 0)
                      $this->next = array_shift($this->tab_rang);
                      else
                      $this->fin = true; */
                    $this->debut = microtime(true);
                    $this->writer = new XMLWriter();
                    $this->writer->setIndent(true);
                    $this->writer->startDocument('1.0', 'UTF-8');
                }
                $ok = $this->read();
                break;
            case 'title' :
                $this->page_title = $this->readString();
                $uri = 'files/' . $this->toFileName($this->page_title) . '.xml';
                $this->writer->openUri($uri);
                $this->writer->writeElement('page');
                $this->writer->writeElement('title', $this->page_title);
                break;
            default : $ok = $this->next();
        }
        return $ok && (!$this->fin);
    }

    protected function closeElement($element) {
        switch ($element) {
            case 'page' :

                $this->writer->endElement(); //page
                $duree = round(microtime(true) - $this->debut, 2);
                echo $duree . ' s. - ' . memory_get_peak_usage() .
                ":" . memory_get_usage(true) .
                '(' . memory_get_usage(false) . ")\n";
                $ok = $this->next();
                break;
            default : $ok = $this->next();
        }
        return $ok;
    }

    public static function main($param) {
        //frwiki-head.xml frwiki-20110409-pages-meta-history.xml
        if (isset($param['w']) && isset($param['l'])) {
            $ex = new Extractor($param['w'], $param['l']);
            $ex->run();
        }
        else
            echo "php Extractor.php -w wiki_dump.xml -l WA_list.xml\n";
    }

}

Extractor::main(getopt("w:l:"));
?>