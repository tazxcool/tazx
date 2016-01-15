<?php
/**
 * Handeling content of a page.
 *
 */
class CBlog {
    private $textFilter;
    private $html;
    private $data;
    private $title;
    
    public function __construct() {
        $this->textFilter = new CTextFilter();
    }
    public function getLimitAmountOfBlogs($db, $acronym, $amount) {
        $sql = "
                SELECT *
                FROM pContent
                WHERE
                    type = 'post' AND
                    published <= NOW()
                ORDER BY published DESC
                LIMIT $amount
                ;
            "; 
        $this->html = $this->getBlog(null, null, $db, $acronym, $sql);
        return $this->html;
    }
    // Retrieve all info from database content that is published and of type post
    // Return as HTML-code
    public function getBlog($slug, $category, $db, $acronym, $sql=null) {
        $params = $category ? array($category) : array($slug);
        if($slug) {
            $partSql = 'slug = ?';
        } else if ($category){
            $partSql = 'category = ?';
        } else {
            $partSql = '1';
        }
        if (!$sql){
            $sql = "
                SELECT *
                FROM pContent
                WHERE
                    type = 'post' AND
                    $partSql AND
                    published <= NOW()
                ORDER BY updated DESC
                ;
            ";
        }
        $res = $db->ExecuteSelectQueryAndFetchAll($sql, $params);
        
        if(isset($res[0])) {
            foreach($res as $c) {
                // Sanitize content before using it.
                $this->title  = htmlentities($c->title, null, 'UTF-8');
                if($slug) {
                    $this->data = $this->textFilter->doFilter(htmlentities($c->data, null, 'UTF-8'), $c->filter);
                } else {
                    $this->data = $this->displayShort($c);
                }
                $editLink = $acronym ? "<a class='small' href='edit.php?id={$c->id}'>Uppdatera posten</a>" : null;
                $this->html .= <<<EOD
                    <section>
                        <article>
                        <header class="bottom-margin">
                            <h1><a href='news.php?slug={$c->slug}'>{$this->title}</a></h1><small>{$c->published} - {$c->author}<br>Kategori: <a href='news.php?category={$c->category}'>{$c->category}</a></small>
                        </header>
                            {$this->data}
                        <footer>
                        {$editLink}
                        </footer>
                        </article>
                    </section>
EOD;
                }
            } else if($slug) {
            $this->html = "Det fanns inte en sådan bloggpost.";
        } else {
            $this->html = "Det fanns inga bloggposter.";
        }
        return $this->html;
    }
     public function getTitle() {
        return $this->title;
    }
    private function getFirstParagraph($text) { 
        $start = strpos($text, '<p>'); 
        $end = strpos($text, '</p>', $start); 
        return substr($text, $start, $end-$start+4); 
    } 
    public function displayShort($row) { 
	$filter = $row->filter . ',markdown'; 
	$_data = htmlentities($row->data); 
	$data = $this->getFirstParagraph($this->textFilter->doFilter($_data, $filter)); 
	$html = $data; 
        $html .= "<a href='news.php?slug={$row->slug}'> Läs mer >></a>"; 
        return $html; 
    } 

}