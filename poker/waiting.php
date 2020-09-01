<?php
	session_start();
	include("classes/GameState.php");
	
	//verifier si l'utilisateur est connecté
	if(!isset($_SESSION["username"])){
	    header("Location: ../index.php");
		exit();
	}
	
	if(!isset($_SESSION["gameId"])){
	    header("Location: index.php");
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
        header("Location: index.php?error=1");
        exit();
    }
    else{
        $row = mysqli_fetch_assoc($result);
        if($row["gameStarted"]){
            header("Location: game.php");
            exit();
        }
        $owner = ($_SESSION["username"] == $row["ownername"]);
        if(isset($_GET["start"])){
            //wants to start the game
            if($owner){
                $game = unserialize($row["gamestate"]);
                $game->newRound();
                $x = serialize($game);
                $sql = "UPDATE POKERGAME SET gamestate = '$x' WHERE gameId = ".$_SESSION["gameId"];
                $result = mysqli_query($conn, $sql);
                
                $sql = "UPDATE POKERGAME SET gameStarted = TRUE WHERE gameId = ".$_SESSION["gameId"];
                $result = mysqli_query($conn, $sql);
                
                $time = time();
                $sql = "UPDATE POKERGAME SET lastUpdate = $time WHERE gameId = ".$_SESSION["gameId"];
                $result = mysqli_query($conn, $sql);
                
                header("Location: game.php");
                exit();
            }
        }
        $game = unserialize($row["gamestate"]);
    }
?>
<!DOCTYPE HTML>
<html>
    <head>
        <meta charset="utf-8">
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
        <title>Waiting Room</title>
    </head>
    <body>
        <table border=1 cellspacing="0" cellpadding="5" align="center">
            <tr><td align="center">room ID: <?php echo $_SESSION["gameId"];?>
        </table>
        <br><br>
        <table border=1 cellspacing="0" cellpadding="5" align="center" id="players">
            <tr><td align="center"><b>Players</b>
            <?php
                echo '<tr><td align="center"><b>Loading...</b>
                    ';
            ?>
        </table>
        
        <?php
            if($owner){
                echo
                '<br><br>
                <table border=1 cellspacing="0" cellpadding="5" align="center">
                    <tr><td align="center">
                    <form action="waiting.php" method="GET">
                        <input type="submit" name="start" value="START">
                </table>
                ';
            }
        ?>
        <script src="js/checkState.js"></script>
    </body>
</html>