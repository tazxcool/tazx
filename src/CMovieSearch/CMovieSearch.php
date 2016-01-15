<?php
/**
 * A searchengine for movies.
 *
 */
class CMovieSearch {
    private $sql;
    private $title;
    private $fromYear;
    private $toYear;
    const TEMPFROMYEAR = 1;
    const TEMPTOYEAR = 3000;
    const TITLE = '%';
    const GENRE = '%';
    private $database;
    private $hits;
    private $page;
    private $orderby;
    private $order;
    private $genre;
    
    public function __construct($db, $title, $fromYear, $toYear, $hits, $page, $orderby, $order, $genre, $sql = null) {
        $this->database = $db;
        $this->hits = $hits;
        $this->page = $page;
        $this->orderby = $orderby;
        $this->order = $order;
        if ($genre) {
        $this->genre = "%" . $genre . "%";
        }
        if ($title) {
        $this->title = $title;
        }
        if ($fromYear) {
            $this->fromYear = $fromYear;
        }
        if ($toYear) {
        $this->toYear = $toYear;
        }
        if ($sql) {
            $this->sql = $sql;
        } else {
            $this->sql = "SELECT * FROM pVMovie WHERE Title LIKE ? AND Year BETWEEN ? AND ? AND Genre LIKE ? ORDER BY $orderby $order LIMIT $hits OFFSET " . (($page - 1) * $hits) . ";";
        }
    }
    /**
     * Getters.
     * 
     * 
     */
    public function getTitle(){
        return $this->title;
    }
    public function getFromYear(){
        return $this->fromYear;
    }
    public function getToYear(){
        return $this->toYear;
    }
    /**
     * Fetch query to database end return the result.
     * 
     * @return string with html.
     */
    public function prepareSQL($query = null, $params = null) {
        if (!$query) {
            $query = $this->sql;
        } 
        if (!$params) {
            $params = array(isset($this->title)?$this->title:CMovieSearch::TITLE, isset($this->fromYear)?$this->fromYear:CMovieSearch::TEMPFROMYEAR, isset($this->toYear)?$this->toYear:CMovieSearch::TEMPTOYEAR, isset($this->genre)?$this->genre:CMovieSearch::GENRE);
        }
        $res = $this->database->ExecuteSelectQueryAndFetchAll($query, $params);
        return $res;
    }
    /**
     * Get a htmlform generated for searchcriterias.
     * 
     * @return string with html.
     */
    
    /**
     * Get list of moviescategories
     * 
     * 
     */
    public static function getMovieCategories($db){
        $sql = "SELECT name FROM pGenre";
        $res = $db->ExecuteSelectQueryAndFetchAll($sql, array());

        if(!isset($res[0])) {
            die('Misslyckades: det finns inga kategorier');
        }
        $unorderedList = CMovieSearch::getMovieCategoriesAsList($res);
        return $unorderedList;
    }
    
    // create unordered list of movie categories
    public static function getMovieCategoriesAsList($res){
        $categoryMovieList = '<ul>';
        foreach ($res as $value) {
            $categoryMovieList .= "<li><a href='movies.php?genre={$value->name}'>{$value->name}</a></li>";
        }
        $categoryMovieList .= '</ul>';
        return $categoryMovieList;
        }
    
    public static function getMovieList($db, $amount, $sort){
        $sql = "SELECT name FROM pGenre";
        $res = $db->ExecuteSelectQueryAndFetchAll($sql, array());

        if(!isset($res[0])) {
            die('Misslyckades: det finns inga kategorier');
        }
        return $res;
    }
    public function generateForm() {
        $htmlForm = <<<EOD
    
        <form>
            <fieldset>
            <legend>Sök</legend>
            <input type=hidden name=hits value='{$this->hits}'/>
            <input type=hidden name=page value='1'/>
            <p><label>Titel (delsträng, använd % som *): 
                <input type='search' name='title' value='{$this->title}'/></label>
            </p>
            <p><label>Skapad mellan åren: 
                <input type='text' name='year1' value='{$this->fromYear}'/></label>
                - 
                <label><input type='text' name='year2' value='{$this->toYear}'/></label>
            </p>
            <p><input type='submit' name='submit' value='Sök'/></p>
            <p><a href='?'>Visa alla</a></p>
            </fieldset>
        </form>
EOD;
        return $htmlForm;
    }
    public function generateSearchForm() {
        $htmlForm = <<<EOD
    
        <form action='movies.php'>
            <input type=hidden name=hits value='{$this->hits}'/>
            <input type=hidden name=page value='1'/>
            <p><label class='navbarLabel'>Titel: 
                <input class='navbarSearch' type='search' name='title' value='{$this->title}' placeholder='Använd % som *'/></label>
            <input class='navbarButton' type='submit' name='submit' value='Sök'/></p>
        </form>
EOD;
        return $htmlForm;
    }
}