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
	
	if(!isset($_GET["amount"]) || !preg_match("#^-?[0-9]+$#", $_GET["amount"])){
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
        
        $game = unserialize($row["gamestate"]);
        
        $p = $game->getPlayerByName($_SESSION["username"]);
        
        if($p){
            $myId = $p["position"];
            
            if($game->playerTurn == $myId){
                //do whatever action he asked for
                $game->playerBet($_SESSION["username"], $_GET["amount"]);
                $x = serialize($game);
                $sql = "update POKERGAME set gamestate = '$x' where gameId = ".$_SESSION["gameId"];
                $result = mysqli_query($conn, $sql);
            }
        }
    }
?>