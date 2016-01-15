<?php
/**
 * Handeling content of a page.
 *
 */
class CPage {
    private $title;
    private $textFilter;
    private $id;

    public function __construct() {
        $this->textFilter = new CTextFilter();
    }
        
    // Retrieve all info from database content that is published and of type page
    // with match to parameter url
    // Save title AND id and return data
    public function getPage($db, $url) {
        $c = null;
        $sql = "
            SELECT *
            FROM pContent
            WHERE
                type = 'page' AND
                url = ? AND
                published <= NOW();
            ";
        $res = $db->ExecuteSelectQueryAndFetchAll($sql, array($url));

        if(isset($res[0])) {
            $c = $res[0];
            $this->id = $res[0]->id; 
        }
        else {
            die('Misslyckades: det finns inget innehåll.');
        }
        // Sanitize content before using it.
        $this->title  = htmlentities($c->title, null, 'UTF-8');
        $data   = $this->textFilter->doFilter(htmlentities($c->data, null, 'UTF-8'), $c->filter);
        return $data;
    }
     // Retrieve totle
    public function getTitle() {
        return $this->title;
    }
     // Retrieve id
    public function getId() {
        return $this->id;
    }
    // Retrieve all url from database as a list of links.
    public function getPageLinks($db) {
        $c = null;
        $sql = "
            SELECT *
            FROM pContent
            WHERE
                type = 'page' AND
                published <= NOW();
            ";
        $res = $db->ExecuteSelectQueryAndFetchAll($sql);
        $html = "<h1>Bloggsidor</h1>";
        if(isset($res[0])) {
            foreach ($res AS $key => $value) {
                $html .= "<p><a href='page.php?url={$value->url}'>" . ucfirst($value->url) . "</a></p>";
            }
            return $html;
        }
        else {
            die('Misslyckades: det finns inget innehåll.');
        }
    }
}
