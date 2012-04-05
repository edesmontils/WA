<?php

header('Content-type: text/xml');
require_once './logoot_lib/utils.php';
require_once './logootComponent/DiffEngine.php';
require_once './WikipediaReader.php';

function __autoload($classe) {
    require_once './logootComponent/' . $classe . '.php';
}

//$debug = true;

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

    protected $writer, $uri, $rep;
    protected $liste_pages, $tab_pages, $tab_rang, $tab_ns, $nb_loaded;
    protected $debut, $next, $fin;
    protected $page_title, $page_id, $user_id, $username, $user_ip, $revision_id,
            $revision_timestamp, $comment, $isMinor, $text;
    protected $inPage, $inRevision, $inContributor, $toSave;
    //protected $logoot;
            protected $page, $namespaces;

    public function __construct($rep, $wiki, $liste, $p) {
        parent::__construct($wiki);
        $this->rep = $rep;
        $this->liste_pages = simplexml_load_file($liste);
        $this->fin = false;
        $this->tab_pages = array();
        $this->nb_loaded = 0;
        $this->page = $p;

        if (!is_dir($rep)) {
            mkdir($rep);
        }

        //collecte des pages à récupérer
        foreach ($this->liste_pages->children() as $ns) {
            echo "Récup de " . $ns['nom'] . "\n";
            $this->namespaces[] = $ns['nom'];

            $dir_ns = ($ns['nom'] == '' ? $this->rep . '/default' : $this->rep . '/' . utils::toFileName($ns['nom']));
            if (!is_dir($dir_ns)) {
                mkdir($dir_ns);
            }

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
        $this->tab_pages = array_unique($this->tab_pages);
        $this->tab_rang = array_unique($this->tab_rang);
        natsort($this->tab_rang);
        echo "Récupération de " . count($this->tab_pages) . " page(s)\n";
    }

    public function __destruct() {
        parent::__destruct();
    }

    protected function openElement($element) {
        $ok = false;
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
                $this->text = '';
                break;
            case 'title' :
                if ($this->toSave) {
                    $this->page_title = $this->readString();
                    echo "==> Récupération de : $this->page_title\n";

                    $t = explode(':', $this->page_title);
                    if (count($t) > 1)
                        if (in_array($t[0], $this->namespaces)) {
                            $ns = $t[0];
                        } else
                            $ns = NULL;
                    else
                        $ns = NULL;
                    $dir_ns = $this->rep . (isset($ns) ? '/' . utils::toFileName($ns) : '/default');
                    if (isset($ns)) unset($t[0]);
                    $file_name = utils::toFileName(implode(':', $t));
                    $this->uri = $dir_ns . '/' . $file_name . '.xml';
                    if (strlen($this->uri) > 255) {
                        echo "** replace (too long) ". $this->uri;
                        $file_name = substr($file_name, 0, 250);
                        $this->uri = $dir_ns . '/' . $file_name . '.xml';
                        echo " by " . $this->uri . "\n" ;
                    } 
                    
                    if (file_exists($this->uri)) {
                        echo "** replace (exists) ". $this->uri;
                        $this->uri = $dir_ns . '/page_' . $this->nb_pages . '.xml';
                        echo " by " . $this->uri . "\n" ;
                    }

                    //$this->writer->openMemory();
                    $this->writer->openURI($this->uri);
                    $this->writer->setIndent(true);
                    $this->writer->startDocument('1.0', 'UTF-8');
                    $this->writer->writeDTD("page", null, "http://www.desmontils.net/ns/WA/page_mediawiki.dtd");
                    $this->writer->startElement('page');
                    $titre = utils::toAttribute($this->page_title);
                    $this->writer->writeAttribute('title', $titre);
                }
                $ok = $this->next();
                break;
            case 'id' :
                if ($this->toSave) {
                    $id = $this->readString();
                    if ($this->inPage) {
                        if ($this->inRevision)
                            if ($this->inContributor) {
                                $this->user_id = $id;
                                $this->writer->writeAttribute('id', 'u' . $id);
                            } else {
                                $this->revision_id = $id;
                                $this->writer->writeAttribute('id', 'r' . $id);
                            } else {
                            $this->page_id = $id;
                            $this->writer->writeAttribute('id', 'p' . $id);
                        }
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
                    $ip = $this->readString();
                    if ($this->isIP($ip))
                        $this->writer->writeAttribute('ip', $ip);
                    else
                        $this->writer->writeAttribute('name', $ip);
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
                    //$this->writer->startElement('text');
                    //$this->writer->writeAttribute('xml:space', 'preserve');
                    $old = $this->text;
                    $this->text = $this->readString();
                    //$this->writer->text($this->text);
                    //$this->writer->endElement();
                    $this->writer->startElement('change-list');
                    $this->generate($old, $this->text);
                    $this->writer->endElement();
                }
                $ok = $this->next();
                break;
            default : $ok = $this->next();
        }
        return $ok && (!$this->fin);
    }

    protected function closeElement($element) {
        $ok = false;
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
                $this->logoot = NULL;
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
        if (isset($param['w']) && isset($param['l']) && isset($param['d'])) {
            $ex = new Extractor($param['d'], $param['w'], $param['l'], (isset($param['p']) ? $param['p'] : NULL));
            $ex->run();
        }
        else
            echo "php Extractor.php -d rep -w wiki_dump.xml [-p page_name] -l WA_list.xml\n";
    }

    /**
     * Calculate the diff between two texts
     * Returns a list of operations applied on this blobinfo(document model)
     * For each operation (insert or delete), an operation object is created
     * an applied via the 'integrateBlob' function call. These objects are
     *  stored in an array and returned for further implementations.
     *
     * NB: the direct implementation is necessary because the generation of
     * a new position (LogootPosition) is based on the positions of the model
     * (BlobInfo) and so we have to update (immediat integration) this model after
     * each operation (that we get from the difference engine)
     * @global <Object> $wgContLang
     * @param <String> $oldtext
     * @param <String> $newtext
     * @return <array> list of logootOperation
     */
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
                //echo $ota[$oi] . '/' . $nta[$ni] . '/' . ($in ? 'in' : 'out') . "\n";
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
                //echo $ni . "::" . count($nta) . "::" . ($ok ? "ok" : "!ok") . '/' . ($in ? 'in' : 'out') . "\n";
            }
        }
        return array($ok, $debut, $fin);
    }

    /*
     * Méthode de création des opérations
     */

    protected function generate_del_line($line_nb, $line_txt) {
        $this->writer->startElement('delete');
        $this->writer->writeAttribute('line', $line_nb);
        $this->writer->writeAttribute('txt', $line_txt);
        $this->writer->endElement();
    }

    protected function generate_ins_line($line_nb, $line_txt) {
        $this->writer->startElement('insert');
        $this->writer->writeAttribute('line', $line_nb);
        $this->writer->writeAttribute('txt', $line_txt);
        $this->writer->endElement();
    }

    protected function generate_ins_text($line_nb, $txt) {
        $nb = count($txt);
        for ($i = 0; $i < $nb; $i++) {
            $this->generate_ins_line($line_nb + $i, $txt[$i]);
        }
        return $nb;
    }

    /*
     * génère les opérations effectuées entre $oldText et $newText..
     */

    public function generate($oldText, $newText) {
        /* explode into lines */
        $ota = explode("\n", $oldText);
        $nta = explode("\n", $newText);

        if ((count($ota) == 1) && ($ota[0] == "")) {// c'est un nouveau document
            unset($ota[0]);
            $this->generate_ins_text(1, $nta);
        } else {
            list($trouve, $deb, $fin) = $this->locate($ota, $nta);
            if ($trouve) {//il y a eu un ajout de texte au début et/ou à la fin uniquement
                if ($deb > 0) {
                    $delta = $this->generate_ins_text(1, array_slice($nta, 0, $deb));
                } else
                    $delta = 0;
                if ($fin + 1 < count($nta)) {
                    $this->generate_ins_text(count($ota) + 1 + $delta, array_slice($nta, $fin + 1, count($nta) - ($fin + 1)));
                }
            } else {
                $counter = 0;
                if ((count($ota) == 1) && ($ota[0] == "")) // c'est un nouveau document
                    unset($ota[0]);
                $diffs = new Diff1($ota, $nta);
                /* convert 4 operations into 2 operations */
                foreach ($diffs->edits as $operation) {
                    switch ($operation->type) {
                        case "add":
                            $adds = $operation->closing;
                            ksort($adds, SORT_NUMERIC);
                            foreach ($adds as $lineNb => $linetxt) {
                                $this->generate_ins_line($lineNb, $linetxt);
                                $counter += 1;
                            }
                            break;
                        case "delete":
                            foreach ($operation->orig as $lineNb => $linetxt) {
                                $this->generate_del_line($lineNb + $counter, $linetxt);
                                $counter -= 1;
                            }
                            break;
                        case "change":
                            $this->writer->startElement('change');
                            foreach ($operation->orig as $lineNb => $linetxt) {
                                $this->generate_del_line($lineNb + $counter, $linetxt);
                                $counter -= 1;
                            }
                            $adds1 = $operation->closing;
                            ksort($adds1, SORT_NUMERIC);
                            foreach ($adds1 as $lineNb => $linetxt) {
                                $this->generate_ins_line($lineNb, $linetxt);
                                $counter += 1;
                            }
                            $this->writer->endElement();
                            break;
                        case "copy": break;
                        default :;
                    }
                }
            }
        }
    }

}

Extractor::main(getopt("w:l:d:"));

/* $env = logootEnv::getInstance();
  var_dump($env);
  var_dump(LogootId::IdMax()); */

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