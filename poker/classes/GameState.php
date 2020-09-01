<?php
    include_once("HandStrength.php");

    class GameState{
        public $players;
        public $tableCards;
        public $pot;
        public $playerTurn;
        public $currentMaxBet;
        public $globalStateId;
        public $log;

        private $playedCards;
        
        private $initBB;
        private $bigBindEvolution;
        
        private $BBPlayer;
        private $SBPlayer;
        private $virtualBB;

        private $nextState;
        private $roundState;
        /*round states:
            1: pre-flop
            2: flop
            3: turn
            4: river
            timestamp (big value): showdown
        */

        public function __construct($ownerName, $startingChips, $bigBind, $bigBindEvolution){
            $this->players = array();
            
            $this->tableCards = array();
            $this->pot = 0;
            
            $this->playedCards = array();
            
            $this->BBPlayer = 0;
            $this->SBPlayer = 0;
            $this->currentMaxBet = 0;

            $this->initBB = $bigBind;
            $this->bigBindEvolution = $bigBindEvolution;
            
            $this->players[] = new Player($ownerName, $startingChips);
            
            $this->nextState = false;
            $this->globalStateId = -1;
        }

        public function addPlayer($username){
            if(!$this->getPlayerByName($username)){
                $startingChips = $this->players[0]->chips;
                $this->players[] = new Player($username, $startingChips);
                $this->globalStateId--;
                return true;
            }
            return false;
        }

        private function noWinnerYet(){
            $nbplayers = 0;
            foreach($this->players as $player){
                if(!$player->hasLost()){
                    $nbplayers++;
                    if($nbplayers == 2)
                        return true;
                }
            }
            return false;
        }

        private function playersRemain(){
            $nbplayers = 0;
            foreach($this->players as $player){
                if(!$player->folded){
                    $nbplayers++;
                    if($nbplayers == 2)
                        return true;
                }
            }
            return false;
        }

        public function getPlayerByName($username){
            foreach($this->players as $pos => $player){
                if($player->username == $username){
                    return array("position" => $pos, "player" => $player);
                }
            }
            return null;
        }

        /**
         * YandereDev would be proud of this.
         * You are welcome to try to make it look nicer.
         */
        public function playerBet($username, $amount){
            $player = $this->getPlayerByName($username);
            if($player && $player["position"] == $this->playerTurn){
                $nbPlayers = sizeof($this->players);
                //---I will deny writing the mess of if statements below---
                if($amount < 0){
                    //player folded
                    $player["player"]->fold();
                    $this->log[] = $player["player"]->username." folds.";
                    if($this->virtualBB == $this->playerTurn){
                        //must find new virtual BB
                        do{
                            $this->virtualBB--;
                            if($this->virtualBB < 0){
                                $this->virtualBB = $nbPlayers - 1;
                            }
                        }while($this->players[$this->virtualBB]->folded);
                    }
                }
                else{
                    //check, raise or allin
                    $bet = $amount;
                    $amount = $player["player"]->bet($amount);
                    if($amount < $this->currentMaxBet){
                        //it's a call by default
                        $player["player"]->bet($this->currentMaxBet - $amount);
                        if(!$player["player"]->isAllIn()){
                            $this->log[] = $player["player"]->username." calls.";
                        }
                        else{
                            $this->log[] = $player["player"]->username." goes All In.";
                        }
                    }
                    else if($amount > $this->currentMaxBet){
                        //it's a raise
                        if(!$player["player"]->isAllIn()){
                            $this->log[] = $player["player"]->username." raises to $amount$.";
                        }
                        else{
                            $this->log[] = $player["player"]->username." goes All In.";
                        }

                        $this->currentMaxBet = $amount;
                        $this->virtualBB = $this->playerTurn;

                        //everyone who played has to play again
                        foreach($this->players as $pos => $player){
                            if(!$player->folded && $pos != $this->virtualBB){
                                $player->setHasPlayed(false);
                            }
                        }
                    }
                    else if($bet == 0){
                        $this->log[] = $player["player"]->username." checks.";
                    }
                    else{
                        if(!$player["player"]->isAllIn()){
                            $this->log[] = $player["player"]->username." calls.";
                        }
                        else{
                            $this->log[] = $player["player"]->username." goes All In.";
                        }
                    }
                }
                //---I will deny writing the mess of if statements above---
                if($this->playersRemain()){
                    //search for next player
                    do{
                        $this->playerTurn = ($this->playerTurn + 1) % $nbPlayers;
                    }while($this->players[$this->playerTurn]->cannotPlay() && $this->playerTurn != $this->virtualBB);

                    if($this->playerTurn == $this->virtualBB && $this->players[$this->playerTurn]->cannotPlay()){
                        //game is ready to move to next state
                        $this->playerTurn = -1;
                        $this->nextState = true;
                    }
                }
                else{
                    //all players folded except one which is virtualBB
                    $this->playerTurn = -1;
                    $this->nextState = true;
                    $this->roundState = -1;
                }
                $this->globalStateId++;
            }
        }

        public function moveToNextState(){
            //check if state is ready to be updated
            if($this->nextState){
                //move to next state
                switch($this->roundState){
                    case -1:
                        //all players have folded except one
                        $this->winnerByFold();
                        $this->globalStateId++;
                        break;
                    case 1:
                        //moving to flop
                        $this->nextState = false;
                        $this->flop();
                        $this->globalStateId++;
                        break;
                    case 2:
                        //moving to turn
                        $this->nextState = false;
                        $this->turnOrRiver();
                        $this->globalStateId++;
                        break;
                    case 3:
                        //moving to river
                        $this->nextState = false;
                        $this->turnOrRiver();
                        $this->globalStateId++;
                        break;
                    case 4:
                        //moving to showdown, must evaluate handstrength and pick winner(s)
                        $this->nextState = false;
                        $this->showdown();
                        $this->globalStateId++;
                        break;
                    default:
                        //wait till timestamp is old by 8s
                        if(time() - $this->roundState > 8){
                            $this->nextState = false;
                            $this->newRound();
                            $this->globalStateId++;
                            return true;
                        }
                        return false;
                        break;
                }
                return true;
            }
            return false;
        }

        private function updateBinds(){
            //update BB value, and also BB and SB pointers before start of new round
            //condition: more than one player remain
            
            $nbPlayers = sizeof($this->players);

            if($this->BBPlayer != $this->SBPlayer){ //if not first round
                $this->initBB += $this->bigBindEvolution;

                $this->SBPlayer = $this->BBPlayer;
                while($this->players[$this->SBPlayer]->hasLost()){
                    $this->SBPlayer = ($this->SBPlayer + 1) % $nbPlayers;
                }
    
                $this->BBPlayer = $this->SBPlayer;
                do{
                    $this->BBPlayer = ($this->BBPlayer + 1) % $nbPlayers;
                }while($this->players[$this->BBPlayer]->hasLost());
            }
            else{ //it's first round let's pick random consecutive big bind and small bind
                $this->SBPlayer = random_int(0, $nbPlayers-1);
                $this->BBPlayer = ($this->SBPlayer + 1) % $nbPlayers;
            }
            $this->currentMaxBet = $this->initBB;
        }

        public function newRound(){
            $this->log = array();
            $this->pot = 0;
            if($this->noWinnerYet()){
                $this->playedCards = array();
                $this->tableCards = array();
                $this->updateBinds();
                foreach($this->players as $pos => $player){
                    if(!$player->hasLost()){
                        $bet = 0;
                        if($pos == $this->BBPlayer)
                            $bet = $this->currentMaxBet;
                        else if($pos == $this->SBPlayer)
                            $bet = intdiv($this->currentMaxBet, 2);
                        $player->newRound($this->playedCards, $bet);
                    }
                    else{
                        $player->hand = null;
                    }
                }
                //find first player to go
                $nbPlayers = sizeof($this->players);
                $this->playerTurn = $this->BBPlayer;

                do{
                    $this->playerTurn = ($this->playerTurn + 1) % $nbPlayers;
                }while($this->players[$this->playerTurn]->cannotPlay() && $this->playerTurn != $this->BBPlayer);
                
                if($this->playerTurn == $this->BBPlayer && $this->players[$this->playerTurn]->cannotPlay()){
                    //all players are in allin due to blinds
                    $this->playerTurn = -1;
                    $this->nextState = true;
                }
                
                $this->virtualBB = $this->BBPlayer;

                $this->roundState = 1;
                $this->globalStateId = 0;
            }
        }

        private function flop(){
            foreach($this->players as $player){
                $player->setHasPlayed(false);
                if($player->currentBet > 0){
                    $this->pot += $player->currentBet;
                    $player->currentBet = 0;
                }
            }
            //reset max bet
            $this->currentMaxBet = 0;

            //find next player to go
            $nbPlayers = sizeof($this->players);

            $this->playerTurn = $this->virtualBB;
            
            do{
                $this->playerTurn = ($this->playerTurn + 1) % $nbPlayers;
            }while($this->players[$this->playerTurn]->cannotPlay() && $this->playerTurn != $this->virtualBB);

            //EDIT: reading this on September 1st. I have no idea why I have two seperate conditions that do the same thing
            // I am scared of modifying this and breaking something
            if($this->playerTurn == $this->virtualBB){
                //no one can play, so game is ready to move to next state
                $this->playerTurn = -1;
                $this->nextState = true;
            }
            else if($this->advanceCuzAllIn()){
                //game is ready to move to next state
                $this->playerTurn = -1;
                $this->nextState = true;
            }

            //discard top card
            $discard = new Card($this->playedCards);
            //draw 3 cards
            $this->tableCards[] = new Card($this->playedCards);
            $this->tableCards[] = new Card($this->playedCards);
            $this->tableCards[] = new Card($this->playedCards);

            $this->roundState = 2;
        }
        
        private function turnOrRiver(){
            foreach($this->players as $player){
                $player->setHasPlayed(false);
                if($player->currentBet > 0){
                    $this->pot += $player->currentBet;
                    $player->currentBet = 0;
                }
            }
            //reset max bet
            $this->currentMaxBet = 0;

            //find next player to go
            $nbPlayers = sizeof($this->players);

            $this->playerTurn = $this->virtualBB;
            
            do{
                $this->playerTurn = ($this->playerTurn + 1) % $nbPlayers;
            }while($this->players[$this->playerTurn]->cannotPlay() && $this->playerTurn != $this->virtualBB);
            
            //EDIT: reading this on September 1st. I have no idea why I have two seperate conditions that do the same thing
            // I am scared of modifying this and breaking something
            if($this->playerTurn == $this->virtualBB){
                //no one can play, so game is ready to move to next state
                $this->playerTurn = -1;
                $this->nextState = true;
            }
            else if($this->advanceCuzAllIn()){
                //game is ready to move to next state
                $this->playerTurn = -1;
                $this->nextState = true;
            }
            
            //discard top card
            $discard = new Card($this->playedCards);
            //draw 1 cards
            $this->tableCards[] = new Card($this->playedCards);

            $this->roundState++;
        }

        private function showdown(){
            foreach($this->players as $player){
                if($player->currentBet > 0){
                    $this->pot += $player->currentBet;
                    $player->currentBet = 0;
                }
            }

            $winners = HandStrength::generateResults($this->players, $this->tableCards);
            $this->sharePotBetweenWinners($winners["winners"], $winners["strength"]);
            foreach($this->players as $player){
                if($player->chips == 0 && !$player->folded){
                    $player->setLost(); //player is out of the game
                    $this->log[] = $player->username." is out of the game.";
                }
            }
            $this->roundState = time();
            $this->nextState = true;
        }

        private function winnerByFold(){
            foreach($this->players as $player){
                if($player->currentBet > 0){
                    $this->pot += $player->currentBet;
                    $player->currentBet = 0;
                }
            }
            
            $this->sharePotBetweenWinners(array($this->virtualBB), -1);
            foreach($this->players as $player){
                if($player->chips == 0 && !$player->folded){
                    $player->setLost(); //player is out of the game
                    $this->log[] = $player->username." is out of the game.";
                }
            }
            $this->roundState = time();
            $this->nextState = true;
        }

        /**
         * No limit on all-in gains. You can all in with 1$ and win 1000$.
         */
        private function sharePotBetweenWinners($winners, $strength){
            $amount = intdiv($this->pot, sizeof($winners));
            $rest = $this->pot % sizeof($winners);

            if($strength < 0){ //won by default cuz all players folded
                $this->log[] = $this->players[$winners[0]]->username." wins $amount$.";
                $this->players[$winners[0]]->chips += $amount;
            }
            else{
                foreach($winners as $index){
                    $hand = HandStrength::$hands[$strength];
                    $this->log[] = $this->players[$index]->username." wins $amount$ with $hand.";
                    $this->players[$index]->chips += $amount;
                    if($rest > 0){
                        $this->players[$index]->chips++;
                        $rest--;
                    }
                }
            }
        }
        
        public function getRoundState(){
            return $this->roundState;
        }
        
        private function advanceCuzAllIn(){
            //I can hear my complexity teacher cry.
            //I will deny writing this if confronted btw.
            $allIn = false;
            foreach($this->players as $player){
                if($player->isAllIn()){
                    $allIn = true;
                    break;
                }
            }
            if(!$allIn)
                return false;
            
            $cpt = 0;
            foreach($this->players as $player){
                if(!$player->cannotPlay()){
                    $cpt++;
                    if($cpt > 1){
                        return false;
                    }
                }
            }
            return true;
        }
    }
?>