<?php
	session_start();
	include("classes/GameState.php");
	
	//verifier si l'utilisateur est connecté
	if(!isset($_SESSION["username"])){
		exit("not allowed");
	}
	
	if(!isset($_SESSION["gameId"])){
		exit("not allowed");
	}
	
	if(!isset($_GET["id"]) || !preg_match("#^-?[0-9]+$#", $_GET["id"])){
	    exit("missing data");
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
        
        //$owner = ($_SESSION["username"] == $row["ownername"]);
        $game = unserialize($row["gamestate"]);
        if($_GET["id"] != $game->globalStateId){
            if($row["gameStarted"]){
                exit("started");
            }
            echo json_encode($game);
        }
        exit();
    }
?>