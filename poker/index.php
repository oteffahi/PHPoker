<?php
	session_start();
	include("classes/GameState.php");
	
	//verifier si l'utilisateur est connecté
	if(!isset($_SESSION["username"])){
	    header("Location: ../index.php");
		exit();
	}
	
	if(isset($_GET["logout"])){
	    session_unset();
	    header("Location: ../index.php");
	}
	
	/*if(isset($_SESSION["gameId"])){
	    header("Location: game.php");
		exit();
	}*/
	
	if($_SERVER["REQUEST_METHOD"] == "POST"){
        $conn = mysqli_connect(/*deleted for obvious reasons*/);
        if(!$conn)
            die('Database error');
		
        if(isset($_POST["join"])){
            if(!empty($_POST["id"]) && preg_match("#^[0-9]+$#", $_POST["id"])){
                include_once("classes/GameState.php");
                $sql = "SELECT * FROM POKERGAME WHERE gameId = ".$_POST["id"];
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
                        header("Location: index.php?error=1");
                	    exit();
                    }
                    else{
                        $game = unserialize($row["gamestate"]);
                        if(!$game->addPlayer($_SESSION["username"])){
                            //should be impossible but still just in case
                            $_SESSION["gameId"] = $_POST["id"];
                            header("Location: waiting.php");
                	        exit();
                        }
                        $x = serialize($game);
                        $sql = "update POKERGAME set gamestate = '$x' where gameId = ".$_POST["id"];
                        $result = mysqli_query($conn, $sql);
                        $_SESSION["gameId"] = $_POST["id"];
                        header("Location: waiting.php");
                	    exit();
                    }
                }
            }
            else{
                header("Location: index.php?error=0");
    	        exit();
            }
        }
        else if(isset($_POST["create"])){
            if(!empty($_POST["chips"]) && !empty($_POST["BB"]) && !empty($_POST["BBEvo"]) && 
            preg_match("#^[0-9]+$#", $_POST["chips"]) && $_POST["chips"] > 0 && 
            preg_match("#^[0-9]+$#", $_POST["BB"]) && $_POST["BB"] > 0 && $_POST["BB"] % 2 == 0 &&
            preg_match("#^[0-9]+$#", $_POST["BBEvo"]) && $_POST["BBEvo"] > 0 && $_POST["BBEvo"] % 2 == 0){
                include_once("classes/GameState.php");
                $game = new GameState($_SESSION["username"], $_POST["chips"], $_POST["BB"], $_POST["BBEvo"]);
                //insert game into database
                $x = serialize($game);
                $sql = "INSERT INTO POKERGAME VALUES(0, '$x', '".$_SESSION["username"]."', null, false)";
                $result = mysqli_query($conn, $sql);
                //get room ID
                $sql = "SELECT LAST_INSERT_ID() as id";
                $result = mysqli_query($conn, $sql);
                $row = mysqli_fetch_assoc($result);
                
                $_SESSION["gameId"] = $row["id"];
                
                header("Location: waiting.php");
                exit();
            }
            else{
                header("Location: index.php?error=0");
                exit();
            }
        }
	}
?>
<!DOCTYPE HTML>
<html>
    <head>
        <meta charset="utf-8">
        <title>Poker</title>
    </head>
    <body>
        <table border=1 cellspacing="0" cellpadding="5" align="right">
            <tr><td aling="center"><a href="index.php?logout=1">logout</a>
        </table>
        <table border=1 cellspacing="0" cellpadding="0" align="center">
            <?php
                if(isset($_GET["error"])){
                    echo 
                    '<tr><td align="center"><font color="red">';
                    switch($_GET["error"]){
                        case 0: //bad inputs
                            echo 'Invalid input data. Please check that all data is numeric';
                            break;
                        case 1: //game does not exist or has already started
                            echo 'Room does not exist or game already started';
                            break;
                    }
                    echo '</font>
                    ';
                }
            ?>
            <tr><td align="center">
                <form action="index.php" method="POST">
                    <input type="hidden" name="join" value="1">
                    <table border=0 cellspacing="0" cellpadding="10" align="center">
                        <tr>
                            <td align="right">Room ID:
                            <td align="center"><input type="text" name="id">
                        <tr>
                        <tr>
                            <td align="center" colspan=2><input type="submit" value='JOIN'>
                    </table>
                </form>
            <tr><td align="center">
                <form action="index.php" method="POST">
                    <input type="hidden" name="create" value="1">
                    <table border=0 cellspacing="0" cellpadding="10" align="center">
                        <tr>
                            <td align="right">Starting Chips:
                            <td align="center"><input type="text" name="chips" placeholder="must be positive">
                        <tr>
                            <td align="right">Big Blind:
                            <td align="center"><input type="text" name="BB" placeholder="must be an even number > 0">
                        <tr>
                            <td align="right">Big Blind increment per round:
                            <td align="center"><input type="text" name="BBEvo" placeholder="must be an even number > 0">
                        <tr>
                            <td align="center" colspan=2><input type="submit" value='CREATE'>
                    </table>
                </form>
        </table>
    </body>
</html>