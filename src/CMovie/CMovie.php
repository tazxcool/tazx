<?php
/**
 * A searchgengine for movies.
 *
 */
class CMovie {
    private $sql;
    private $id;
    private $database;
    private $title;
        
    public function __construct($db, $id, $sql=null) {
        $this->database = $db;
        $this->id = $id;
        if ($sql) {
            $this->sql = $sql;
        } else {
            $this->sql = "SELECT * FROM pVMovie WHERE id = ?";
        }
    }
    // Retrieve all info from database content with id as parameter
    public function getInfoById($id) {
        $idInfo = null;
        $res = $this->database->ExecuteSelectQueryAndFetchAll($this->sql, array($id));

        if(isset($res[0])) {
            $idInfo = $res[0];
        } else {
            die('Misslyckades: det finns inget innehåll med sådant id.');
        }
        return $idInfo;
    }
    /**
     * Get list of movies
     * 
     * 
     */
    public static function getMovieList($db, $amount, $sort = null){
        if($sort == null){
            $sql = "SELECT title, image,id FROM pVMovie ORDER BY id DESC LIMIT $amount";
        } else if($sort == 'last'){
            $sql = <<<EOD
                SELECT pVMovie.title, pVMovie.image, pVMovie.id 
                FROM (
                    SELECT DISTINCT pOrders.idMovie 
                    FROM pOrders 
                    ORDER BY pOrders.id DESC 
                    LIMIT $amount
                    )q 
                JOIN pVMovie 
                ON pVMovie.id = q.idMovie
EOD;
        } else if($sort == 'popular'){
            $sql = <<<EOD
            SELECT pVMovie.title, pVMovie.image, pVMovie.id 
            FROM (
                SELECT pOrders.idMovie, count(pOrders.idMovie) AS Sum 
                FROM pOrders 
                GROUP BY pOrders.idMovie 
                ORDER BY Sum DESC 
                LIMIT 3
                ) q 
            JOIN pVMovie 
            ON pVMovie.id = q.idMovie;
EOD;
        } else {
            die('Misslyckades: det finns inga filmer att hämta');
        }
        $res = $db->ExecuteSelectQueryAndFetchAll($sql, array());

        if(!isset($res[0])) {
            die('Misslyckades: det finns inga filmer att hämta');
        }
        return $res;
    }
    /**
     * Get list of movies as HTML table
     * 
     * 
     */
    public static function getMovieListAsHTMLTable($listOfMovies){
         $output = null;
         foreach ($listOfMovies as $key => $value) {
            $listMovie = $value;
            $output .= "<h2>{$key}</h2><table><tr>" ;
            foreach ($listMovie as $value) {
                $output .= "<td><a href='movie.php?id={$value->id}'><img src='img.php?src={$value->image}&amp;width=80&amp;height=160&amp;crop-to-fit' alt='{$value->title}'/></a></td>";
            }
            $output .= '</tr></table>';
        }
        return $output;
    }
    /**
     * Getters.
     * 
     * 
     */
    public function getId(){
        return $this->id;
    }
    public function getSql(){
        return $this->sql;
    }
    public function getDatabase(){
        return $this->database;
    }
    public function getTitle(){
        return $this->title;
    }
    public function prepareSQL($query = null, $params = null) {
        if (!$query) {
            $query = $this->sql;
        } 
        if (!$params) {
            $params = array($this->id);
        }
        $res = $this->database->ExecuteSelectQueryAndFetchAll($query, $params);
        $this->title = isset($res[0]->title) ? $res[0]->title : null;
        //$data = $this->generatePage($res);
        return $res;
    }
    public function generatePage($res, $acronym){
        $data = null;
        $editLink = $acronym ? "<p><a class='small' href='admin_movie_edit.php?id={$this->id}'>Uppdatera filmens information</a></p>" : null; 
        if (!isset($res[0]->id)){
            $data = "<strong>Film för valet " . htmlentities($this->id) . " saknas</strong>";
        } else {
            $data = <<<EOD
                <table class='center'><tr>
                    <td><img src='img.php?src={$res[0]->image}&amp;width=160&amp;height=240&amp;crop-to-fit' alt='{$res[0]->title}'/><p class='price'>Pris: {$res[0]->price} kr<p></td>
                    <td>{$res[0]->plot}</td>
                    <td><iframe width='340' height='255' src='{$res[0]->trailer}'></iframe></td>
                    </tr></table>
                    
                    <table class='movieinfo'><tr>
                    <th>År</th>
                    <th>Regissör</th>
                    <th>Språk</th>
                    <th>Text</th>
                    <th>Längd</th>
                    <th>Upplösning</th>
                    <th>Format</th>
                    <th colspan=2>Kategori</th>
                    
                </tr><tr>
                    <td>{$res[0]->year}</td>
                    <td>{$res[0]->director}</td>
                    <td>{$res[0]->speech}</td>
                    <td>{$res[0]->subtext}</td>
                    <td>{$res[0]->length} min</td>
                    <td>{$res[0]->quality}</td>
                    <td>{$res[0]->format}</td>
                    <td>{$res[0]->genre}</td>
                    <td><a href='{$res[0]->imdb}' target='_blank'>Imdb.com</a></td>
                </tr></table>{$editLink}
EOD;
        }
        return $data;
    }
    
     public function edit($save, $params, $genre){
        $output = null;
        if($save) {
            $sql = '
                UPDATE pMovie SET
                    title       = ?,
                    image        = ?,
                    plot         = ?,
                    trailer        = ?,
                    imdb        = ?,
                    year      = ?,
                    director   = ?,
                    speech    = ?,
                    subtext    = ?,
                    length    = ?,
                    quality    = ?,
                    format    = ?,
                    price    = ?
                WHERE 
                    id = ?
            ';
            
            $res = $this->database->ExecuteQuery($sql, $params);
            if($res) {
                $sql = '
                    DELETE 
                    FROM pMovie2genre
                    WHERE idMovie = ?
                ';
                $id = ($params[sizeof($params)-1]);
                
                $res = $this->database->ExecuteQuery($sql, array($id));
                
                $sql = '
                    INSERT INTO pMovie2genre (idMovie,idGenre)
                    VALUES (?, ?) 
                ';
                $genres = explode(",", $genre);
                foreach ($genres as $value) {
                    $params = array($id, $value);
                    $res = $this->database->ExecuteQuery($sql, $params);
                }
                if($res){
                    $output = "<span class='green'>Informationen sparades.</span>";
                } else {
                    $output = 'Viss information sparades.';
                }
            }
            else {
                $output = "<span class='red'>Informationen sparades EJ.<br><pre></span>" . print_r($this->database->ErrorInfo(), 1) . '</pre>';
            }
        }
        return $output;
    }
    // add new content to database
    public function add($save, $params, $genre){
        $output = null;
        if($save) {
            $sql = '
                INSERT INTO pMovie (title, image, plot, trailer, imdb, year, director, speech, subtext, length, quality, format, price) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ';
            $res = $this->database->ExecuteQuery($sql, $params);
            if($res) {
                $sql = '
                    SELECT id 
                    FROM pMovie
                    ORDER BY id DESC
                    LIMIT 1;
                ';
                $res2 = $this->database->ExecuteSelectQueryAndFetchAll($sql, $params);
        
                if(isset($res2[0])) {
                    foreach($res2 as $c) {
                        $this->id = $c->id;
                    }
                    $sql = '
                        INSERT INTO pMovie2genre (idMovie, idGenre)
                        VALUES (?, ?) 
                    ';
                    $genres = explode(",", $genre);
                    foreach ($genres as $value) {
                        $params = array($this->id, $value);
                        $res3 = $this->database->ExecuteQuery($sql, $params);
                    }
                    if($res3) {
                        $output = "<span class='green'>Informationen sparades.</span>";
                    } else {
                        $output = 'Viss information sparades.';
                    }
                
                } else {
                     $output = 'Viss information sparades.';
                }
            }
            else {
                $output = "<span class='red'>Informationen sparades EJ.<br><pre></span>" . print_r($this->database->ErrorInfo(), 1) . '</pre>';
            }
        }
        return $output;
    }
    // delete movie from database
    public function delete($id, $erase) {
        $output = null;
        if($erase) {
            $sql = '
                DELETE FROM pMovie2genre
                WHERE idMovie = ?;
            ';
            $res = $this->database->ExecuteQuery($sql, array($id));
            if($res) {
                $sql = '
                    DELETE FROM pOrders
                    WHERE idMovie = ?;
                ';
                $res = $this->database->ExecuteQuery($sql, array($id));
                if ($res) {
                    $sql = '
                        DELETE FROM pMovie
                        WHERE id = ?
                        LIMIT 1;
                    ';
                    $res = $this->database->ExecuteQuery($sql, array($id));
                    if ($res) {    
                        $output = "<span class='green'>Informationen togs bort.</span>";
                    } else { 
                    $output = 'Viss information togs bort (orders och genres).';
                }
                } else { 
                    $output = 'Viss information togs bort (genres).';
                }
            }
            else {
                $output = "<span class='red'>Informationen gick EJ att ta bort.<br><pre></span>" . print_r($this->db->ErrorInfo(), 1) . '</pre>';
            }
        }
        return $output;
    }
    
    public function generateEditForm($title, $image, $plot, $trailer, $imdb, $year, $director, $speech, $subtext, $length, $quality, $format, $genre, $price, $id, $output, $edit, $readonly=null, $resetButton='reset') {
       
        $buttonValue = ($edit == 'saveMovie')?'Spara':'Ta bort ur databas';
        $adventure = $family = $fantasy = $horror = $drama = $sciFi = $action = $comedy = $musical = $romance = $thriller = $sport = $mystery = $animation = $biography = $crime = null;
        $genres = explode(",", $genre);
        foreach ($genres as $value) {
            $adventure = ($value == 'Adventure'? 'selected' : $adventure);
            $family = ($value == 'Family' ? 'selected' : $family);
            $fantasy = ($value == 'Fantasy'? 'selected' : $fantasy);
            $horror = ($value == 'Horror' ? 'selected' : $horror);
            $drama = ($value == 'Drama' ? 'selected' : $drama);
            $sciFi = ($value == 'Sci-Fi' ? 'selected' : $sciFi);
            $action = ($value == 'Action' ? 'selected' : $action);
            $comedy = ($value == 'Comedy' ? 'selected' : $comedy);
            $musical = ($value == 'Musical' ? 'selected' : $musical);
            $romance = ($value == 'Romance' ? 'selected' : $romance);
            $thriller = ($value == 'Thriller' ? 'selected' : $thriller);
            $sport = ($value == 'Sport'? 'selected' : $sport);
            $mystery = ($value == 'Mystery'? 'selected' : $mystery);
            $animation = ($value == 'Animation'? 'selected' : $animation);
            $biography = ($value == 'Biography'? 'selected' : $biography);
            $crime = ($value == 'Crime'? 'selected' : $crime);
        }

        $html = <<<EOD
                <form method=post>
                <output>{$output}</output>
        <fieldset>
        <legend>Uppdatera innehåll</legend>
        <input type='hidden' name='id' value='{$id}' {$readonly}/>
        <p><label>Titel:<br/><input type='text' class='first' name='title' value='{$title}'{$readonly}/></label></p>
        <p><label>Bild:<br/><input type='text' class='first' name='image' value='{$image}'{$readonly}/></label></p>
        <p><label>Länk (Trailer):<br/><input type='text' class='first' name='trailer' value='{$trailer}'{$readonly}/></label></p>
        <p><label>Länk (IMDB.com):<br/><input type='text' class='first' name='imdb' value='{$imdb}'{$readonly}/></label></p>
        <p><label>Handling:<br/><textarea name='plot' {$readonly}>{$plot}</textarea></label></p>
        <p><label>Inspelningsår:<br/><input type='text' name='year' value='{$year}'{$readonly} required/></label></p>
        <p><label>Regissör:<br/><input type='text' name='director' value='{$director}'{$readonly}/></label></p>
        
        <p><label>Kategori:<br/><select class='multi' name='genres[]' multiple='multiple' size='5' {$readonly}>
        <option value='1' {$adventure} {$readonly}>Adventure</option>
        <option value='2' {$family} {$readonly}>Family</option>
        <option value='3' {$fantasy} {$readonly}>Fantasy</option>
        <option value='4' {$horror} {$readonly}>Horror</option>
        <option value='5' {$drama} {$readonly}>Drama</option>
        <option value='6' {$sciFi} {$readonly}>Sci-Fi</option>
        <option value='7' {$action} {$readonly}>Action</option>
        <option value='8' {$comedy} {$readonly}>Comedy</option>
        <option value='9' {$musical} {$readonly}>Musical</option>
        <option value='10' {$romance} {$readonly}>Romance</option>
        <option value='11' {$thriller} {$readonly}>Thriller</option>
        <option value='12' {$sport} {$readonly}>Sport</option>  
        <option value='13' {$mystery} {$readonly}>Mystery</option>
        <option value='14' {$animation} {$readonly}>Animation</option>
        <option value='15' {$biography} {$readonly}>Biography</option>
        <option value='16' {$crime} {$readonly}>Crime</option>
        </select></label></p>
        

        <p><label>Längd (min):<br/><input type='text' name='length' value='{$length}'{$readonly}/></label></p>
        <p><label>Språk:<br/><input type='text' name='speech' value='{$speech}'{$readonly}/></label></p>
        <p><label>Undertext:<br/><input type='text' name='subtext' value='{$subtext}'{$readonly}/></label></p>
        <p><label>Upplösning:<br/><input type='text' name='quality' value='{$quality}'{$readonly}/></label></p>
        <p><label>Format:<br/><input type='text' name='format' value='{$format}'{$readonly}/></label></p>
        <p><label>Pris:<br/><input type='text' name='price' value='{$price}'{$readonly}/></label></p>
        
        <p class=buttons>
            <input type='submit' name='{$edit}' value='{$buttonValue}'/> 
            <input type='{$resetButton}' value='Återställ'/>
            </p>
        <p><a href='movies.php'>Visa alla</a></p>
        
        </fieldset>
    </form>
EOD;
        return $html;
    }
    public function createFormUploadImage($uploadedMessage){
        $data = <<<EOD
            <form action="process/admin_upload_image_process.php" method="post" enctype="multipart/form-data" id="upload"></form>
                <fieldset>
                    <legend><strong>Välj bild</strong></legend>
                    <p><input type="file" name="myFile" accept="image/jpeg,image/png,image/gif" class="button uploadFirst" form="upload"></p>
                    <p><input type="submit" value="Ladda upp" class="button uploadSecond floatNone" form="upload"></p>
                    {$uploadedMessage}
                </fieldset>
EOD;
        $_SESSION['uploadMessage'] = null;            
        return $data;
    }
}