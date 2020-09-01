<?php
    class Card{
        private static $ranks = array("A", "2", "3", "4", "5", "6", "7", "8", "9", "10", "J", "Q", "K");
        private static $suits = array("Diamonds", "Clubs", "Hearts", "Spades");
        public $rank;
        public $suit;

        /*public static function customCard($rank, $suit){
            $no = array();
            $instance = new self($no);
            $instance->rank = $rank;
            $instance->suit = $suit;
            return $instance;
        }*/

        public function __construct(&$tab){
            do{
                $randomRank = random_int(0, 12); //sizeof($ranks)-1
                $randomSuit = random_int(0, 3); //sizeof($suits)-1
                $this->rank = Card::$ranks[$randomRank];
                $this->suit = Card::$suits[$randomSuit];
                $found = false;
                foreach ($tab as $card){
                    if($this->equals($card)){
                        $found = true;
                        break;
                    }
                }
            }while($found);
            $tab[] = $this;
        }

        public function equals($c){
            return($c->rank == $this->rank && $c->suit == $this->suit);
        }

        public static function getRanks(){
            return Card::ranks;
        }
    }
?>