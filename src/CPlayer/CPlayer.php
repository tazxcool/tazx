<?php
/**
 * A CDice class to play around with a dice.
 *
 */
class CPlayer {
 
    /**
     * Properties
     *
     */
    private $name;
    private $play;
    private $game;
     
    /**
     * Constructor
     *
     * @param int $faces the number of faces to use.
     */
    public function __construct($name) {
        $this->name = $name;
        $this->play = new CTurn();
        $this->game = new CTurn();
    }
 
    /**
     * Retrieve name
     *
     */
    public function GetName() {
 
        return $this->name;
    }
    /**
     * Retrieve object turn
     *
     */
    public function Getplay() {
 
        return $this->play;
    }
    /**
     * Retrieve object game
     *
     */
    public function GetGame() {
 
        return $this->game;
    }
    
    /**
     * End player
     *
     */
    public function __destruct() {
        //echo "Omgången slut";
    }
    public function saveScore($db, $GamePoints) {
        $sql = "INSERT INTO pHighscore (email, score) VALUES (?, ?)";
        $res = $db->ExecuteSelectQueryAndFetchAll($sql, array($this->name, $GamePoints));

        if(!isset($res[0])) {
            $html = 'Misslyckades: det gick inte att spara resultatet.';
        } else {
            $html = 'Resultatet sparades.';
        }
        
        return $html;
    }
    public static function getScore($db) {
        $sql = "SELECT email, score FROM pHighscore ORDER BY score DESC LIMIT 3";
        $res = $db->ExecuteSelectQueryAndFetchAll($sql, array());

        if(!isset($res[0])) {
            die('Misslyckades: det finns inga resultat att hämta');
        }
        $html = CPlayer::scoreASHTMLTable($res);
        return $html;
    }
    public static function scoreASHTMLTable($res) {
        $html = "<table><caption>HighScore</caption><tr><th>Email</th><th>Poäng</th></tr>";

        foreach($res AS $key => $val) {
            $html .= "<tr><td>{$val->email}</td><td>{$val->score}</td></tr>";
        }
        $html .= "</table>";
        return $html;
    }
}