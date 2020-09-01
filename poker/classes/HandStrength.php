<?php
    include_once("Player.php");

    class HandStrength{
        private static $ranks = array("2"=>1, "3"=>2, "4"=>3, "5"=>4, "6"=>5, "7"=>6, "8"=>7, "9"=>8, "10"=>9, "J"=>10, "Q"=>11, "K"=>12, "A"=>13);
        public static $hands = array("Highest Card", "One Pair", "Two Pairs", "Three of a Kind", "Straight", 
                                        "Flush", "Full House", "Four of a Kind", "Straight Flush", "Royal Flush");

        private $hand;
        private $suitDivision;

        public $handStrength;
        public $element1;
        public $element2;
        public $kickers;

        public static function generateResults($players, $tableCards/*, &$log*/){
            $highestHand = 0;

            $handStrengths = array();
            foreach($players as $player){
                if(!$player->folded){
                    $handStrengths[] = new HandStrength($player->hand, $tableCards, $highestHand);
                }
                else{
                    $handStrengths[] = null;
                }
            }

            //get winner(s)
            $winners = array();
            foreach($players as $pos => $player){
                if($handStrengths[$pos] && $handStrengths[$pos]->handStrength == $highestHand){
                    $winners[] = $pos;
                }
            }
            if(sizeof($winners) > 1){ //we have a tie
                $winners = HandStrength::tieBreaker($handStrengths, $winners);
            }
            return array("winners"=>$winners, "strength"=>$highestHand);
        }

        private static function tieBreaker($handStrengths, $winners){
            if($handStrengths[$winners[0]]->handStrength == 9){ //royal flush is always a tie
                return $winners;
            }

            $realWinners = array();
            $realWinners[] = $winners[0];

            $size = sizeof($winners);

            for($i=1; $i<$size; $i++){
                if($handStrengths[$winners[$i]]->element1 && $handStrengths[$winners[$i]]->element1 != $handStrengths[$realWinners[0]]->element1){
                    $currentMaxStrength = HandStrength::$ranks[$handStrengths[$realWinners[0]]->element1];
                    $challengerStrength = HandStrength::$ranks[$handStrengths[$winners[$i]]->element1];

                    if($challengerStrength > $currentMaxStrength){
                        $realWinners = array($winners[$i]); //the challenger is stronger than all the current winners
                    }
                }
                else if($handStrengths[$winners[$i]]->element2 && $handStrengths[$winners[$i]]->element2 != $handStrengths[$realWinners[0]]->element2){
                    $currentMaxStrength = HandStrength::$ranks[$handStrengths[$realWinners[0]]->element2];
                    $challengerStrength = HandStrength::$ranks[$handStrengths[$winners[$i]]->element2];

                    if($challengerStrength > $currentMaxStrength){
                        $realWinners = array($winners[$i]); //the challenger is stronger than all the current winners
                    }
                }
                else if($handStrengths[$winners[$i]]->kickers){
                    $currentMaxKickers = $handStrengths[$realWinners[0]]->kickers;
                    $challengerKickers = $handStrengths[$winners[$i]]->kickers;

                    $ksize = sizeof($challengerKickers);
                    for($j=$ksize-1; $j>=0; $j--){
                        $currentMaxStrength = HandStrength::$ranks[$currentMaxKickers[$j]];
                        $challengerStrength = HandStrength::$ranks[$challengerKickers[$j]];
                        if($challengerStrength > $currentMaxStrength){
                            $realWinners = array($winners[$i]); //the challenger is stronger than all the current winners
                            break;
                        }
                        else if($challengerStrength < $currentMaxStrength){ //if one challenger kicker loses then the challenger is weaker
                            break;
                        }
                    }
                    if($j < 0){ //if all kickers are equal
                        $realWinners[] = $winners[$i];
                    }
                }
                else{
                    $realWinners[] = $winners[$i];
                }
            }
            return $realWinners;
        }

        public function __construct($hand, $table, &$highestHand){
            $this->hand = array_merge($hand, $table);

            usort($this->hand, array($this, "compareCards"));
            
            foreach($this->hand as $card){
                $this->suitDivision[$card->suit][] = $card;
            }
            $this->generateHandStrength($highestHand);
        }

        private function compareCards($c1, $c2){
            return HandStrength::$ranks[$c1->rank] - HandStrength::$ranks[$c2->rank];
        }

        public function generateHandStrength(&$highestHand){
            $functions = array("isRoyalFlush", "isStraightFlush", "isFourOfAKind", "isFullHouse", "isFlush", 
                               "isStraight", "isThreeOfAKind", "isTwoPair", "isPair", "isHighCard");
            
            $cpt = 9;
            foreach($functions as $call){
                if($cpt < $highestHand){
                    $this->hand = null;
                    $this->suitDivision = null;
                    $this->handStrength = 0;
                    return;
                }
                if(call_user_func(array($this, $call))){
                    if($cpt > $highestHand){
                        $highestHand = $cpt;
                    }
                    $this->hand = null;
                    $this->suitDivision = null;
                    return;
                }
                $cpt--;
            }
        }

        private function isRoyalFlush(){
            foreach($this->suitDivision as $suit){
                $size = sizeof($suit);
                if($size >= 5){
                    if($suit[$size-1]->rank == "A" && $suit[$size-2]->rank == "K" && 
                       $suit[$size-3]->rank == "Q" && $suit[$size-4]->rank == "J" && $suit[$size-5]->rank == "10" ){
                           $this->handStrength = 9;
                           return true;
                    }
                    return false;
                }
            }
            return false;
        }

        private function isStraightFlush(){
            foreach($this->suitDivision as $suit){
                $size = sizeof($suit);
                if($size >= 5){
                    $cards = $this->toLowAcesOrder($suit);

                    $max = HandStrength::$ranks[$cards[$size-1]->rank];
                    $cpt = 0;
                    for($i=$size-2; $i>=0; $i--){
                        $current = HandStrength::$ranks[$cards[$i]->rank];
                        if($cards[$i]->rank == "A"){
                            $current = 0;
                        }
                        if($current == $max-1){
                            $cpt++;
                            $max--;
                        }
                        else{
                            $cpt = 0;
                            $max = $current;
                        }
                        
                        if($cpt == 4){
                            $this->element1 = $cards[$i+4]->rank;
                            $this->handStrength = 8;
                            return true;
                        }
                    }
                    return false;
                }
            }
            return false;
        }

        private function isFourOfAKind(){
            $cards = $this->hand;
            $size = sizeof($cards);

            for($i=$size-1; $i>2; $i--){
                if($cards[$i]->rank == $cards[$i-1]->rank){
                    if($cards[$i]->rank == $cards[$i-2]->rank){
                        if($cards[$i]->rank == $cards[$i-3]->rank){
                            $this->kickers = array();
                            if($i+1 < $size){ //if highest card of the hand is not part of the four of a kind
                                $this->kickers[] = $cards[$size-1]->rank; //the kicker is the highest card of the hand
                            }
                            else{
                                $this->kickers[] = $cards[$i-4]->rank; //the kicker is the next card below the four of a kind
                            }
                            $this->element1 = $cards[$i]->rank;
                            $this->handStrength = 7;
                            return true;
                        }
                        else{
                            $i-=2; //oPtImIsAtIoN
                        }
                    }
                    else{
                        $i--; //oPtImIsAtIoN
                    }
                }
            }
            return false;
        }

        private function isFullHouse(){
            $cards = $this->hand;
            $size = sizeof($cards);

            $threePos = null;
            $pairPos = null;
            for($i=$size-1; $i>1; $i--){
                if(!$threePos && $cards[$i]->rank == $cards[$i-1]->rank && $cards[$i]->rank == $cards[$i-2]->rank){
                    $threePos = $i;
                    $i-=2; //OpTiMi
                }
                else if(!$pairPos && $cards[$i]->rank == $cards[$i-1]->rank){
                    $pairPos = $i;
                    $i--; //SaTiOn
                }
            }
            if($threePos && !$pairPos && $threePos > 2 && $cards[1]->rank == $cards[0]->rank){
                $pairPos = 1;
            }
            if($threePos && $pairPos){
                $this->element1 = $cards[$threePos]->rank;
                $this->element2 = $cards[$pairPos]->rank;
                $this->handStrength = 6;
                return true;
            }
            return false;
        }

        private function isFlush(){
            foreach($this->suitDivision as $suit){
                $size = sizeof($suit);
                if($size >= 5){
                    $this->kickers = array();
                    for($i = $size-5; $i<$size; $i++){
                        $this->kickers[] = $suit[$i]->rank;
                    }
                    $this->handStrength = 5;
                    return true;
                }
            }
            return false;
        }

        private function isStraight(){
            $cards = $this->toLowAcesOrder($this->hand);
            $size = sizeof($cards);

            if($cards[0]->rank == "A" && $cards[$size-1]->rank == "K"){
                $cards[] = $cards[0]; //kinda illegal, but this array is disapearing soon anyway
                $size++;
            }
            $max = HandStrength::$ranks[$cards[$size-1]->rank];
            $posMax = $size-1;
            $cpt = 0;
            for($i=$size-2; $i>=0; $i--){
                $current = HandStrength::$ranks[$cards[$i]->rank];
                if($max < 13 && $cards[$i]->rank == "A"){ //convert ace to low ace if not "10JQKA"
                    $current = 0;
                }
                if($current == $max-1){
                    $cpt++;
                    $max--;
                }
                else if($current != $max){
                    $cpt = 0;
                    $max = $current;
                    $posMax = $i;
                }
                
                if($cpt == 4){
                    $this->element1 = $cards[$posMax]->rank;
                    $this->handStrength = 4;
                    return true;
                }
            }
            return false;
        }

        private function isThreeOfAKind(){
            $cards = $this->hand;
            $size = sizeof($cards);

            for($i=$size-1; $i>1; $i--){
                if($cards[$i]->rank == $cards[$i-1]->rank){
                    if($cards[$i]->rank == $cards[$i-2]->rank){
                        $this->kickers = array();
                        $remainingHighCards = $size - $i - 1;
                        if($remainingHighCards >= 2){
                            $this->kickers[] = $cards[$size-2]->rank;
                            $this->kickers[] = $cards[$size-1]->rank;
                        }
                        else if($remainingHighCards == 1){
                            $this->kickers[] = $cards[$i-3]->rank;
                            $this->kickers[] = $cards[$size-1]->rank;
                        }
                        else{
                            $this->kickers[] = $cards[$i-4]->rank;
                            $this->kickers[] = $cards[$i-3]->rank;
                        }
                        $this->element1 = $cards[$i]->rank;
                        $this->handStrength = 3;
                        return true;
                    }
                    else{
                        $i-=1; //oPtImIsAtIoN
                    }
                }
            }
            return false;
        }

        private function isTwoPair(){
            $cards = $this->hand;
            $size = sizeof($cards);

            $pair1 = null;
            $pair2 = null;
            for($i=$size-1; $i>0; $i--){
                if($cards[$i]->rank == $cards[$i-1]->rank){
                    if(!$pair1){
                        $pair1 = $i;
                        $i--; //OpTiMi
                    }
                    else{
                        $pair2 = $i;
                        $i--; //SaTiOn
                    }
                }
                if($pair1 && $pair2){
                    $this->kickers = array();
                    $remainingHighCards = $size - $pair1 - 1;
                    if($remainingHighCards > 0){
                        $this->kickers[] = $cards[$size-1]->rank;
                    }
                    else{
                        $cardsInBetween = $pair1 - $pair2 - 2;
                        if($cardsInBetween > 0){
                            $this->kickers[] = $cards[$pair1-2]->rank;
                        }
                        else{
                            $this->kickers[] = $cards[$pair2-2]->rank;
                        }
                    }
                    $this->element1 = $cards[$pair1]->rank;
                    $this->element2 = $cards[$pair2]->rank;
                    $this->handStrength = 2;
                    return true;
                }
            }
            return false;
        }

        private function isPair(){
            $cards = $this->hand;
            $size = sizeof($cards);

            for($i=$size-1; $i>0; $i--){
                if($cards[$i]->rank == $cards[$i-1]->rank){
                    //time for some kickers, my favorite part of this class -_-
                    $this->kickers = array();
                    $remainingHighCards = $size - $i - 1;
                    if($remainingHighCards > 2){
                        $this->kickers[] = $cards[$size-3]->rank;
                        $this->kickers[] = $cards[$size-2]->rank;
                        $this->kickers[] = $cards[$size-1]->rank;
                    }
                    else if($remainingHighCards > 1){
                        $this->kickers[] = $cards[$i-2]->rank;
                        $this->kickers[] = $cards[$size-2]->rank;
                        $this->kickers[] = $cards[$size-1]->rank;
                    }
                    else if($remainingHighCards == 1){
                        $this->kickers[] = $cards[$i-3]->rank;
                        $this->kickers[] = $cards[$i-2]->rank;
                        $this->kickers[] = $cards[$size-1]->rank;
                    }
                    else{
                        $this->kickers[] = $cards[$i-4]->rank;
                        $this->kickers[] = $cards[$i-3]->rank;
                        $this->kickers[] = $cards[$i-2]->rank;
                    }
                    $this->element1 = $cards[$i]->rank;
                    $this->handStrength = 1;
                    return true;
                }
            }
            return false;
        }

        private function isHighCard(){
            $cards = $this->hand;
            $size = sizeof($cards);

            $this->element1 = $cards[$size-1]->rank;
            $this->kickers = array();

            for($i=5; $i>1; $i--){
                $this->kickers[] = $cards[$size-$i]->rank;
            }
            $this->handStrength = 0;
            return true;
        }

        private function toLowAcesOrder($cards){
            $flipped = array();
            $size = sizeof($cards);
            
            $i=$size-1;
            while($cards[$i]->rank == "A"){
                $flipped[] = $cards[$i];
                $i--;
            }

            if($i==$size-1){
                return $cards;
            }

            for($j=0; $j<=$i; $j++){
                $flipped[] = $cards[$j];
            }
            
            return $flipped;
        }
    }
?>