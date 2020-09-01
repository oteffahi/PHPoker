<?php
    include_once("Card.php");

    class Player{
        public $username;
        public $hand; //Card[2]
        public $chips;
        public $currentBet;
        public $folded;
        
        private $allin;
        private $hasPlayed;

        /*public static function customPlayer($hand){
            $no = array();
            $instance = new self("CUSTOM", 1500);
            $instance->hand = $hand;
            return $instance;
        }*/

        public function __construct($username, $startChips){
            $this->username = $username;
            $this->chips = $startChips;
            $this->currentBet = 0;
            $this->folded = false;
            $this->allin = false;
            $this->hasPlayed = false;
        }

        public function newRound(&$tab, $bet){
            $this->hand = array(new Card($tab), new Card($tab));
            $this->currentBet = 0;
            $this->folded = false;
            $this->allin = false;
            $this->bet($bet);
            $this->hasPlayed = false;
        }
        
        /*public function call($amount){
            $this->bet($amount - $this->currentBet);
            $this->hasPlayed = true;
            return $this->currentBet;
        }*/
        
        public function bet($amount){
            if($amount >= $this->chips){
                $this->allin();
            }
            else{
                $this->chips -= $amount;
                $this->currentBet += $amount;
            }
            $this->hasPlayed = true;
            return $this->currentBet;
        }

        public function allin(){
            $this->currentBet += $this->chips;
            $this->chips = 0;
            $this->allin = true;
        }

        public function hasLost(){
            return ($this->chips == 0 && $this->folded);
        }

        public function isAllIn(){
            return $this->allin;
        }

        public function cannotPlay(){
            return $this->allin || $this->folded || $this->hasPlayed;
        }

        public function setHasPlayed($bool){
            $this->hasPlayed = $bool;
        }

        public function fold(){
            $this->folded = true;
            $this->hand = null;
        }

        public function setLost(){
            $this->folded = true;
            //$this->hand = null;
        }
    }
?>