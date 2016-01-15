<?php 
class CHTMLTable { 
    private $html = null;
    private $rows; 
    private $max; 
    private $hits; 
    private $page; 
    
    public function __construct() {} 
    
    /**
     * Get a htmltable created and returned.
     * 
     * @return string with html.
     */
    public function generateHTMLTable ($db, $res, $movieSearch, $hits, $page, $acronym) { 
        $query = "SELECT COUNT(id) AS rows FROM User;";
        $result = $db->ExecuteSelectQueryAndFetchAll($query, $params);
        $rows = isset($result[0]->rows) ? $result[0]->rows : null;
        $max = ceil($rows / $hits);
        $hitsPerPage = $this->getHitsPerPage(array(2, 4, 8), $hits);
        $navigatePage = $this->getPageNavigation($hits, $page, $max);
        $editHeader = $acronym ? "<th></th><th></th>" : null; 
        $html = "<div class='rows'>{$rows} träffar. {$hitsPerPage}</div><table><tr><th></th><th>Titel " . $this->orderby('title') . "</th><th>År " . $this->orderby('year') . "</th><th>Karegori</th><th>Pris " . $this->orderby('price') . "</th></tr>";

        foreach($res AS $key => $val) {
            $genre = $this->seperateGenres($val->genre, $hits, $page, $movieSearch);
            $edit = $acronym ? "<td><a href='admin_movie_edit.php?id={$val->id}'><img src='img.php?src=img/edit.png&amp;width=20&amp;height=20&amp;crop-to-fit' alt='Uppdatera'></a></td><td><a href='admin_movie_delete.php?id={$val->id}'><img src='img.php?src=img/delete.png&amp;width=20&amp;height=20&amp;crop-to-fit' alt='Ta bort'></a></td>" : null; 
            
            $html .= "<tr><td><a href='movie.php?id={$val->id}'><img src='img.php?src={$val->image}&amp;width=60&amp;height=90&amp;crop-to-fit' alt='{$val->title}'/></a></td><td><a href='movie.php?id={$val->id}'>{$val->title}</a></td><td>{$val->year}</td><td>{$genre}</td><td>{$val->price} kr</td>{$edit}</tr>";
        }
        $html .= "</table><div class='pages'>{$navigatePage}</div>";
        return $html;
    }
    /**
     * Function to remove comma from string and make links for every value
     *
     * @param string $column the name of the database column to sort by
     * @return string with links to order by column.
     */
    Private function seperateGenres($genre, $hits, $page, $movieSearch){
        $genres = explode(",", $genre);
        $data = null;
        foreach($genres AS $key => $val){
            $data .= "<a href='?genre={$val}&amp;title={$movieSearch->getTitle()}&amp;year1={$movieSearch->getFromYear()}&amp;year2={$movieSearch->getToYear()}&amp;hits={$hits}&amp;page=1&amp;submit=Sök'>{$val}</a> ";
        }
        return $data;
    }
    /**
     * Function to create links for sorting
     *
     * @param string $column the name of the database column to sort by
     * @return string with links to order by column.
     */
    private function orderby($column) {
        $nav  = "<a href='" . $this->getQueryString(array('orderby'=>$column, 'order'=>'asc')) . "'>&darr;</a>";
        $nav .= "<a href='" . $this->getQueryString(array('orderby'=>$column, 'order'=>'desc')) . "'>&uarr;</a>";
        return "<span class='orderby'>" . $nav . "</span>";
    }
    
    /**
     * Create navigation among pages.
     *
     * @param integer $hits per page.
     * @param integer $page current page.
     * @param integer $max number of pages. 
     * @param integer $min is the first page number, usually 0 or 1. 
     * @return string as a link to this page.
     */
    private function getPageNavigation($hits, $page, $max, $min=1) {
        $nav  = ($page != $min) ? "<a href='" . $this->getQueryString(array('page' => $min)) . "'>&lt;&lt;</a> " : '&lt;&lt; ';
        $nav .= ($page > $min) ? "<a href='" . $this->getQueryString(array('page' => ($page > $min ? $page - 1 : $min) )) . "'>&lt;</a> " : '&lt; ';

        for($i=$min; $i<=$max; $i++) {
            if($page == $i) {
                $nav .= "$i ";
            }
            else {
                $nav .= "<a href='" . $this->getQueryString(array('page' => $i)) . "'>$i</a> ";
            }
        }

        $nav .= ($page < $max) ? "<a href='" . $this->getQueryString(array('page' => ($page < $max ? $page + 1 : $max) )) . "'>&gt;</a> " : '&gt; ';
        $nav .= ($page != $max) ? "<a href='" . $this->getQueryString(array('page' => $max)) . "'>&gt;&gt;</a> " : '&gt;&gt; ';
        return $nav;
    }
    /**
    * Create links for hits per page.
    *
    * @param array $hits a list of hits-options to display.
    * @param array $current value.
    * @return string as a link to this page.
    */
    private function getHitsPerPage($hits, $current=null) {
    $nav = "Träffar per sida: ";
    foreach($hits AS $val) {
        if($current == $val) {
            $nav .= "$val ";
        }
        else {
            $nav .= "<a href='" . $this->getQueryString(array('hits' => $val)) . "'>$val</a> ";
        }
    }  
    return $nav;
    }
    
    /**
     * Use the current querystring as base, modify it according to $options and return the modified query string.
     *
     * @param array $options to set/change.
     * @param string $prepend this to the resulting query string
     * @return string with an updated query string.
     */
    private function getQueryString($options=array(), $prepend='?') {
    // parse query string into array
    $query = array();
    parse_str($_SERVER['QUERY_STRING'], $query);

    // Modify the existing query string with new options
    $query = array_merge($query, $options);

    // Return the modified querystring
    return $prepend . htmlentities(http_build_query($query));
    }

}
        