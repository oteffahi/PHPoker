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
        if(!$row["gameStarted"]){
            header("Location: waiting.php");
            exit();
        }
        $game = unserialize($row["gamestate"]);
        $p = $game->getPlayerByName($_SESSION["username"]);
        
        $myId = -100;
        if($p){
            $myId = $p["position"];
        }
    }
?>
<!DOCTYPE HTML>
<html>
    <head>
        <meta charset="utf-8">
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
        <title>Poker Room</title>
    </head>
    <body>
        <table border=1 cellspacing="0" cellpadding="5" align="center">
            <tr><td align="center">room ID: <?php echo $_SESSION["gameId"];?>
        </table>
        <br>
        
        <table border=1 cellspacing="0" cellpadding="5" align="center" height=100px>
            <tr><td align="center" valing="center" id="table1" width=60px>
                <td align="center" valing="center" id="table2" width=60px>
                <td align="center" valing="center" id="table3" width=60px>
                <td align="center" valing="center" id="table4" width=60px>
                <td align="center" valing="center" id="table5" width=60px>
        </table>
        <br>
        
        <table border=1 cellspacing="0" cellpadding="5" align="center" width="60px">
            <tr><td align="center" id="pot">0$
        </table>
        <br>
        
        <table border=0 cellspacing="0" cellpadding="5" align="center" width=100%>
            <tr>
            <?php
                foreach($game->players as $pos => $player){
                    echo
'           <td><table border=1 cellspacing="0" cellpadding="5" align="center">
                <tr><td align="center" colspan=2 id="bet'.$pos.'">0$
                <tr><td align="center" colspan=2>'.$player->username.'
                <tr><td align="center" colspan=2 id="chips'.$pos.'">0$
                <tr><td align="center" id="card'.$pos.'1" height=20px width=100px>
                    <td align="center" id="card'.$pos.'2" height=20px width=100px>
            </table>
';
                }
            ?>
        </table>
        <br>
        <table border=1 cellspacing="0" cellpadding="5" align="right" width="40%" height=110px>
            <tr><td align="left" valign="top">
                <div style="overflow:scroll; overflow-x:hidden; width:100%; height:110px" id="log">
                </div>
        </table>
        <table border=1 cellspacing="0" cellpadding="5" align="left" width="40%" height=110px>
            <tr><td align="center" colspan=2>
                <button id="call">call/check</button>
                <button id="fold">fold</button>
            <tr><td align="center">
                <input type="text" id="betValue">
                <button id="bet">bet</button>
        </table>
        
        <script>
            var myId = <?php echo $myId;?>;
        </script>
        <script src="js/game.js"></script>
    </body>
</html>