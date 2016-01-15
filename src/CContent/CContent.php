<?php
/**
 * Handeling content of a blogg.
 *
 */
class CContent
{
    private $db;
    private $sql;
    private $res;
    private $user;
    
    public function __construct($db, $acronym=null) {
        $this->db = $db;
        $this->user = new CUser($acronym); 
    }
    // Retrieve all info from database content that is published
    // return a list of these items with links to view, inactivate and edit 
    // depending if user is logged in.
    public function makeContentList($acronym)
    {
        $items = null;
        $this->sql = <<<EOD
            SELECT *, (published <= NOW()) AS available
            FROM pContent ORDER BY type;
EOD;
        $this->res =  $this->db->ExecuteSelectQueryAndFetchAll($this->sql);
        foreach($this->res AS $key => $val) {
            $erase = $acronym ? "<a href='erase.php?id={$val->id}'>ta bort</a>" : null;    
            $update = $acronym ? "<a href='edit.php?id={$val->id}'>uppdatera</a>" : null;
            $items .= "<li>{$val->type} (" . (!$val->available ? 'inte ' : null) . "publicerad): " . htmlentities($val->title, null, 'UTF-8') . " ({$update} {$erase} <a href='" . $this->getUrlToContent($val) . "'>visa</a>)</li>\n";
        }
        return $items;
    }
    // Retrieve info wheater a content is a blog of a page and return general url for that.
    private function getUrlToContent($content) {
        switch($content->type) {
            case 'page': 
                return "page.php?url={$content->url}"; 
                break;
            case 'post': 
                return "news.php?slug={$content->slug}"; 
                break;
            default: 
                return null; 
                break;
        }
    }
    // Retrieve all info from database content with id as parameter
    public function getInfoById($id) {
        $idInfo = null;
        $sql = 'SELECT * FROM pContent WHERE id = ?';
        $res = $this->db->ExecuteSelectQueryAndFetchAll($sql, array($id));

        if(isset($res[0])) {
            $idInfo = $res[0];
        } else {
            die('Misslyckades: det finns inget innehåll med sådant id.');
        }
        return $idInfo;
    }
    // add new content to database
    public function add($save, $params){
        $output = null;
        if($save) {
            $params[] = $this->user->GetName();
            $sql = '
                INSERT INTO pContent (title, slug, url, data, type, filter, published, category, created, author) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)
            ';
            $res = $this->db->ExecuteQuery($sql, $params);
            if($res) {
                $output = "<span class='green'>Informationen sparades.</span>";
            }
            else {
                $output = "<span class='red'>Informationen sparades EJ.<br><pre></span>" . print_r($this->db->ErrorInfo(), 1) . '</pre>';
            }
        }
        return $output;
    }
    // update content in database
    public function edit($save, $params){
        $output = null;
        if($save) {
            $sql = '
                UPDATE pContent SET
                    title       = ?,
                    slug        = ?,
                    url         = ?,
                    data        = ?,
                    type        = ?,
                    filter      = ?,
                    published   = ?,
                    category    = ?,
                    updated     = NOW()
                WHERE 
                    id = ?
            ';
            $res = $this->db->ExecuteQuery($sql, $params);
            if($res) {
                $output = "<span class='green'>Informationen sparades.</span>";
            }
            else {
                $output = "<span class='red'>Informationen sparades EJ.<br><pre></span>" . print_r($this->db->ErrorInfo(), 1) . '</pre>';
            }
        }
        return $output;
    }
    // delete content from database
    public function delete($id, $erase) {
        $output = null;
        if($erase) {
            $sql = '
                DELETE FROM pContent
                WHERE id = ?
                LIMIT 1;
            ';
            $res = $this->db->ExecuteQuery($sql, array($id));
            if($res) {
                $output = "<span class='green'>Informationen togs bort.</span>";
            }
            else {
                $output = "<span class='red'>Informationen gick EJ att ta bort.<br><pre></span>" . print_r($this->db->ErrorInfo(), 1) . '</pre>';
            }
        }
        return $output;
    }
    
    // set content as inactiv in database
    public function inactivate($id, $erase) {
        $output = null;
        if($erase) {
            $sql = '
                UPDATE pContent SET
                    deleted     = NOW(),
                    published   = null
                WHERE
                    id = ?
            ';
            $res = $this->db->ExecuteQuery($sql, array($id));
            if($res) {
                $output = "<span class='green'>Informationen togs bort.</span>";
            }
            else {
                $output = "<span class='red'>Informationen gick EJ att ta bort.<br><pre></span>" . print_r($this->db->ErrorInfo(), 1) . '</pre>';
            }
        }
        return $output;
    }
    // Retrieve categorylist from database content
    public function getCategories() {
        $sql = 'SELECT DISTINCT category FROM pContent WHERE category IS NOT NULL GROUP BY category DESC';
        $res = $this->db->ExecuteSelectQueryAndFetchAll($sql, array());

        if(!isset($res[0])) {
            die('Misslyckades: det finns inga kategorier');
        }
        $listOfCategories = CContent::getCategoriesAsList($res);
        return $listOfCategories;
    }
    
    // create unordered list of blog categories
    public static function getCategoriesAsList($res){
        $categoryList = '<ul>';
        foreach ($res as $value) {
            $categoryList .= "<li><a href='news.php?category={$value->category}'>{$value->category}</a></li>";
        }
        $categoryList .= '</ul>';
        return $categoryList;
    }
    
    // Create table content i database
    private function createTable() {
        $this->sql = '
            DROP TABLE IF EXISTS pContent;
            CREATE TABLE pContent
            (
                id INT AUTO_INCREMENT PRIMARY KEY NOT NULL,
                slug CHAR(80) UNIQUE,
                url CHAR(80) UNIQUE,

                type CHAR(80),
                title VARCHAR(80),
                data TEXT,
                filter CHAR(80),

                published DATETIME,
                created DATETIME,
                updated DATETIME,
                deleted DATETIME,
                author VARCHAR(80),
                category VARCHAR(30)

            ) ENGINE INNODB CHARACTER SET utf8;
        ';
        $this->db->ExecuteSelectQueryAndFetchAll($this->sql);
    }
    // Create form for editing, adding or viewing data from database
    public function generateForm($title, $slug, $url, $data, $type, $filter, $published, $category, $id, $output, $edit, $readonly=null, $resetButton='reset') {
       
        $buttonValue = ($edit == 'save')?'Spara':'Ta bort ur databas';
        $bbcode = $link = $markdown = $nl2br = $shortcode =  null;
        $filters = explode(",", $filter);
        foreach ($filters as $value) {
            $bbcode = ($value == 'bbcode'? 'selected' : $bbcode);
            $link = ($value == 'link' || $value == 'clickable' ? 'selected' : $link);
            $markdown = ($value == 'markdown'? 'selected' : $markdown);
            $nl2br = ($value == 'nl2br' ? 'selected' : $nl2br);
            $shortcode = ($value == 'shortcode'? 'selected' : $shortcode);
        }
        //if ($filter == null){$nl2br = 'selected';}
        $html = <<<EOD
                <form method=post>
                <output>{$output}</output>
        <fieldset>
        <legend>Uppdatera innehåll</legend>
        <input type='hidden' name='id' value='{$id}' {$readonly}/>
        <p><label>Titel:<br/><input type='text' class='first' name='title' value='{$title}'{$readonly}/></label></p>
        <p><label>Slug:<br/><input type='text' class='first' name='slug' value='{$slug}'{$readonly}/></label></p>
        <p><label>Url:<br/><input type='text' class='first' name='url' value='{$url}'{$readonly}/></label></p>
        <p><label>Text:<br/><textarea name='data' {$readonly}>{$data}</textarea></label></p>
        <p><label>Type:<br/><input type='text' name='type' value='{$type}'{$readonly}/></label></p>
        <p><label>Kategori:<br/><input type='text' name='category' value='{$category}'{$readonly}/></label></p>
        
        <p><label>Filter:<br/><select class='multi' name='filters[]' multiple='multiple' size='5' {$readonly}>
        <option value='bbcode' {$bbcode} {$readonly}>bbcode</option>
        <option value='clickable' {$link} {$readonly}>clickable</option>
        <option value='markdown' {$markdown} {$readonly}>markdown</option>
        <option value='nl2br' {$nl2br} {$readonly}>nl2br</option>
        <option value='shortcode' {$shortcode} >shortcode</option>
        </select></label></p>
        
   
        <p><label>Publiseringsdatum:<br/><input type='text' name='published' value='{$published}'{$readonly}/></label></p>
        <p class=buttons>
            <input type='submit' name='{$edit}' value='{$buttonValue}'/> 
            <input type='{$resetButton}' value='Återställ'/>
            </p>
        <p><a href='news.php'>Visa alla nyheter</a></p>
        
        </fieldset>
    </form>
EOD;
        return $html;
    }
    /**
     * Create a slug of a string, to be used as url.
     *
     * @param string $str the string to format as slug.
     * @returns str the formatted slug. 
     */
    function slugify($str) {
        $str = mb_strtolower(trim($str));
        $str = str_replace(array('å','ä','ö'), array('a','a','o'), $str);
        $str = preg_replace('/[^a-z0-9-]/', '-', $str);
        $str = trim(preg_replace('/-+/', '-', $str), '-');
        return $str;
    }    
}