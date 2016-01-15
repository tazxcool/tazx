<?php
/**
 * A CDice class to play around with a dice.
 *
 */
class CDice {
 
  /**
   * Properties
   *
   */
    protected $rolls = array();
    private $faces;
    private $last;
    private $sumRound;
 
  /**
   * Constructor
   *
   * @param int $faces the number of faces to use.
   */
    public function __construct($faces=6) {
        $this->faces = $faces;
    }
 
  /**
   * Roll the dice
   *
   */
public function Roll($times) {
    $this->rolls = array();

    for($i = 0; $i < $times; $i++) {
      $this->last = rand(1, $this->faces);
      $this->rolls[] = $this->last;
    }
    return $this->last;
  }
  /**
   * Get the total from the last roll(s).
   *
   */
    public function GetTotal() {
        return array_sum($this->rolls);
    }
  /**
   * Get the average from the last roll(s)
   *
   */
    public function Average() {
        return array_sum($this->rolls)/sizeof($this->rolls);
    }
    /**
     * Get the number of faces.
     *
     */
    public function GetFaces() {
        return $this->faces;
    }
    /**
     * Get the rolls as an array.
     *
     */
    public function GetRollsAsArray() {
        return $this->rolls;
    }
    public function GetLastRoll() {
    return $this->last;
  }
}