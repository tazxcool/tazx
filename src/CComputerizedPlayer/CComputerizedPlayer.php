<?php
/**
 * A CDice class to play around with a dice.
 *
 */
class CComputerizedPlayer extends CPlayer{
 
    /**
     * Properties
     *
     */
    
    /**
     * Constructor
     *
     * @param int $faces the number of faces to use.
     */
    public function __construct() {
           parent::__construct('Datorn');
    }
 
    /**
     * roll dices
     *
     */
    public function RollDices($dice, $turn, $game) {
        $value = 0;
        //continue to roll until face is one och total turn point is 20 or more.
        while ($value != 1 && $turn->GetTotalPoint() < 20 && ($turn->GetTotalPoint() + $game->GetTotalPoint()) < 100) {
            $value = $dice->Roll(1);
            if ($value != 1) {
                $turn->AddToTotalPoint($value);
            }
            $arrayOfValues[] = $value;
        } //if total value of 20 or more has been reached
        if ($value != 1) {
            $extrahtml = "<br>Datorn valde att stanna";
            $game->AddToTotalPoint($turn->GetTotalPoint());
            $extrahtml .= "<p>Summan av alla tärningsslag denna omgång blev: ";
            $extrahtml .= $turn->GetTotalPoint();
            $extrahtml .= "</p>";
        } else { //if value 1 is rolled
            $extrahtml = "<br>Datorn slog en 1:a och förlorade omgångens alla poäng"; 
        }
        //possibility of stop and save current turn points should NOT be true
        $_SESSION['stopable'] = false;
        $extrahtml .= "<p>Datorn slog följande slag: ";
        //print all values one by one
        foreach ($arrayOfValues as $value) {
            $extrahtml .= "$value, ";
        }
        //reset turn point
        $turn->SetTotalPoint(0);
        return $extrahtml;
    }
    
    
    /**
     * End player
     *
     */
    public function __destruct() {
        //echo "Omgången slut";
    }
}