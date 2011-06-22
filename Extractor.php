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

    protected $writer, $uri;
    protected $liste_pages, $tab_pages, $tab_rang, $nb_loaded;
    protected $debut, $next, $fin;
    protected $page_title, $page_id, $user_id, $username, $user_ip, $revision_id,
    $revision_timestamp, $comment, $isMinor, $text;
    protected $inPage, $inRevision, $inContributor, $toSave;

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
            foreach ($ns->liste_patchs as $patch) {
                foreach ($patch->max as $p) {
                    $this->tab_pages[] = $p['titre'];
                    $this->tab_rang[] = $p['rang'];
                }
                foreach ($patch->random as $p) {
                    $this->tab_pages[] = $p['titre'];
                    $this->tab_rang[] = $p['rang'];
                }
            }
            foreach ($ns->liste_tailles as $patch) {
                foreach ($patch->max as $p) {
                    $this->tab_pages[] = $p['titre'];
                    $this->tab_rang[] = $p['rang'];
                }
                foreach ($patch->random as $p) {
                    $this->tab_pages[] = $p['titre'];
                    $this->tab_rang[] = $p['rang'];
                }
            }
            foreach ($ns->liste_robots as $patch) {
                foreach ($patch->max as $p) {
                    $this->tab_pages[] = $p['titre'];
                    $this->tab_rang[] = $p['rang'];
                } foreach ($patch->random as $p) {
                    $this->tab_pages[] = $p['titre'];
                    $this->tab_rang[] = $p['rang'];
                }
            }
            foreach ($ns->liste_users as $patch) {
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
                $this->inPage = false;
                $this->inRevision = false;
                $this->inContributor = false;
                $ok = $this->read();
                break;
            case 'siteinfo' : case 'namespaces' :
                $ok = $this->next();
                break;
            case 'page' :
                $this->inPage = true;
                $this->nb_pages += 1;
                if ($this->nb_pages == $this->next) {
                    $this->debut = microtime(true);
                    $this->writer = new XMLWriter();
                    $this->toSave = true;
                    $ok = $this->read();
                } else {
                    $this->toSave = false;
                    $ok = $this->next();
                }
                break;
            case 'title' :
                if ($this->toSave) {
                    $this->page_title = $this->readString();
                    echo "Récupération de : $this->page_title\n";
                    $this->uri = 'files/' . $this->toFileName($this->page_title) . '.xml';
                    //$this->writer->openMemory();
                    $this->writer->openURI($this->uri);
                    $this->writer->setIndent(true);
                    $this->writer->startDocument('1.0', 'UTF-8');
                    $this->writer->writeDTD("page", null, "../page_mediawiki.dtd");
                    $this->writer->startElement('page');
                    $this->writer->writeAttribute('title', $this->page_title);
                }
                $ok = $this->next();
                break;
            case 'id' :
                if ($this->toSave) {
                    $id = $this->readString();
                    if ($this->inPage) {
                        if ($this->inRevision)
                            if ($this->inContributor)
                                $this->user_id = $id;
                            else
                                $this->revision_id = $id;
                        else
                            $this->page_id = $id;
                        $this->writer->writeAttribute('id', 'id' . $id);
                    }
                }
                $ok = $this->next();
                break;
            case 'revision' :
                $this->inRevision = true;
                if ($this->toSave) {
                    $this->writer->startElement('revision');
                }
                $ok = $this->read();
                break;
            case 'timestamp' :
                if ($this->toSave) {
                    $this->writer->writeAttribute('timestamp', $this->readString());
                }
                $ok = $this->next();
                break;
            case 'contributor' :
                $this->inContributor = true;
                if ($this->toSave) {
                    $this->writer->startElement('contributor');
                }
                $ok = $this->read();
                break;
            case 'username' :
                if ($this->toSave) {
                    $this->writer->writeAttribute('name', $this->readString());
                }
                $ok = $this->next();
                break;
            case 'ip' :
                if ($this->toSave) {
                    $this->writer->writeAttribute('ip', $this->readString());
                }
                $ok = $this->next();
                break;
            case 'comment' :
                if ($this->toSave) {
                    $this->writer->writeElement('comment', $this->readString());
                }
                $ok = $this->next();
                break;
            case 'text' :
                if ($this->toSave) {
                    $this->writer->writeElement('text', $this->readString());
                }
                $ok = $this->next();
                break;
            default : $ok = $this->next();
        }
        return $ok && (!$this->fin);
    }

    protected function closeElement($element) {
        switch ($element) {
            case 'page' :
                if ($this->toSave) {
                    $this->writer->endElement(); //page
                    $this->writer->endDocument();
                    //echo "\n=====\n" . $this->writer->outputMemory();
                    $this->writer->flush();
                    $this->toSave = false;
                    if (count($this->tab_rang) > 0)
                        $this->next = array_shift($this->tab_rang);
                    else
                        $this->fin = true;
                    $duree = round(microtime(true) - $this->debut, 2);
                    echo $this->nb_pages . " " . $this->uri . " mémorisée\n";
                    echo $duree . ' s. - ' . memory_get_peak_usage() .
                    ":" . memory_get_usage(true) .
                    '(' . memory_get_usage(false) . ")\n";
                }
                $this->inPage = false;
                $ok = $this->next();
                break;
            case 'revision' :
                $this->inRevision = false;
                if ($this->toSave) {
                    $this->writer->endElement();
                }
                $ok = $this->next();
                break;
            case 'contributor' :
                $this->inContributor = false;
                if ($this->toSave) {
                    $this->writer->endElement();
                }
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
?>