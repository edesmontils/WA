<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of WikipediaReader
 *
 * @author ed
 */
class WikipediaReader extends XMLReader {

    protected $nb_pages;

    public function __construct($wikifile) {
        $this->open($wikifile);
        $this->nb_pages = 0;
    }

    public function __destruct() {
        $this->close();
        //parent::__destruct();
    }

    // readString() est normalement présente dans XMLReader mais pas toujours...
    // Cette fonction n'est disponible que si PHP est compilé à l'aide de la
    // librarie libxml 20620 ou ultérieure.
    //(Cf. http://www.php.net/manual/fr/xmlreader.readstring.php)

    function readString() {
        $node = $this->expand();
        return $node->textContent;
    }

    protected function isRobot($comment) {
        return preg_match("/^robot /i", $comment) ||
        preg_match("/^bot /i", $comment) ||
        preg_match("/^Med - bot /i", $comment) ||
        preg_match("/ bot /i", $comment) ||
        preg_match("/ robot /i", $comment);
    }

    protected function isIP($ip) {
        return utils::isIP($ip);
    }

    protected function openElement($element) {
        return $this->next();
    }

    protected function closeElement($element) {
        return $this->next();
    }

    public function run() {
        $ok = $this->read();
        while ($ok) {
            if ($this->nodeType === XMLReader::ELEMENT) {
                $ok = $this->openElement($this->name);
            } elseif ($this->nodeType === XMLReader::END_ELEMENT) {
                $ok = $this->closeElement($this->name);
            }
            else
                $ok = $this->next();
        }
    }

}

?>
