<?php
	session_start();
	include("classes/GameState.php");
	
	//verifier si l'utilisateur est connecté
	if(!isset($_SESSION["username"])){
		exit();
	}
	
	if(!isset($_SESSION["gameId"])){
		exit();
	}
	
	if(!isset($_GET["id"]) || !preg_match("#^-?[0-9]+$#", $_GET["id"])){
	    exit();
	}
	
	$conn = mysqli_connect(/*deleted for obvious reasons*/);
    if(!$conn)
        die('Database error');
    
    $sql = "SELECT * FROM POKERGAME WHERE gameId = ".$_SESSION["gameId"];
    $result = mysqli_query($conn, $sql);
    if(mysqli_num_rows($result) > 1){
        die("Conflict in database");
    }
    else if(mysqli_num_rows($result) == 0){
        exit("error");
    }
    else{
        $row = mysqli_fetch_assoc($result);
        
        if(!$row["gameStarted"]){
            exit();
        }
        $owner = ($_SESSION["username"] == $row["ownername"]);
        
        $game = unserialize($row["gamestate"]);
        if($owner){
            //update time if necessary
            if(time() - $row["lastUpdate"] > 5){
                $sql = "update POKERGAME set lastUpdate = ".time()." where gameId = ".$_SESSION["gameId"];
                $result = mysqli_query($conn, $sql);
            }
            //advance to next state if possible
            if($game->moveToNextState()){
                $x = serialize($game);
                $sql = "update POKERGAME set gamestate = '$x' where gameId = ".$_SESSION["gameId"];
                $result = mysqli_query($conn, $sql);
            }
        }
        else if(time() - $row["lastUpdate"] > 10){ //if owner disconnected for 10s
            //replace owner
            $sql = "update POKERGAME set lastUpdate = ".time()." where gameId = ".$_SESSION["gameId"];
            $result = mysqli_query($conn, $sql);
            
            $sql = "update POKERGAME set ownername = '".$_SESSION["username"]."' where gameId = ".$_SESSION["gameId"];
            $result = mysqli_query($conn, $sql);
        }
        
        if($_GET["id"] != $game->globalStateId){
            if($game->getRoundState() <= 4){ //if not showdown
                //hide all players hands except current player
                foreach($game->players as $player){
                    if($player->username != $_SESSION["username"]){
                        $player->hand = null;
                    }
                }
            }
            echo json_encode($game);
        }
        exit();
    }
?>