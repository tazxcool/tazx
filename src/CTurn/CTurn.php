<?php
/**
 * A CDice class to play around with a dice.
 *
 */
class CTurn {
 
    /**
     * Properties
     *
     */
    private $totalPoint;
    private $gamepoints;
 
    /**
     * Constructor
     *
     * @param int $faces the number of faces to use.
     */
    public function __construct() {
        $this->totalPoint = 0;
        $this->gamepoints = 1000;
    }
 
    /**
     * Add dice value to turns total point
     *
     */
    public function AddToTotalPoint($value) {
 
        $this->totalPoint += $value;
    }
    /**
     * Retrieve turns total point
     *
     */
    public function GetTotalPoint() {
 
        return $this->totalPoint;
    }
    public function SetTotalPoint($value) {
         $this->totalPoint = $value;
    }
    
    public function changeGamepoints($value) {
        $this->gamepoints -= $value; 
    }
    public function getGamePoints() {
        return $this->gamepoints;
    }

    /**
     * End turn
     *
     */
    public function __destruct() {
        //echo "Omgången slut";
    }
    public function SwitchPlayer($players) {
        if (isset($_SESSION['player'])){
            ($_SESSION['player'] >= sizeof($players))? $_SESSION['player'] = 0 : $_SESSION['player'];
        } else {
            $_SESSION['player'] = 0;
        }
        $currentPlayer = $_SESSION['player'];
        return $currentPlayer;
    }
}