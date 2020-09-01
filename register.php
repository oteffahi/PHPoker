<?php
	session_start();
	//verifier si l'utilisateur est déjà connecté
	if(isset($_SESSION["username"])){
		header("Location: /poker");
		exit();
	}
	
	if($_SERVER['REQUEST_METHOD'] != 'POST' || !preg_match("#^[a-zA-Z0-9_\- ]+$#", $_POST["usr"]) 
	    || empty($_POST["pass"])){
	       echo preg_match("#^[a-zA-Z0-9_\- ]+$#", $_POST["usr"]);
		header("Location: index.php?error=2");
		exit();
	}
    //établir la cnx avec la BD
    $conn = mysqli_connect(/*deleted for obvious reasons*/);
    
	if(!$conn) //vérifier si la cnx à réussie
		die('Database error');
    
	//filtrer les caractères d'injections sql just in case
    $_POST["usr"] = mysqli_real_escape_string($conn, $_POST["usr"]);
	
	$username = $_POST["usr"];
	$password = $_POST["pass"];
	
	//vérifier si les username/password sont correctes
	$sql = "INSERT INTO USERS VALUES('$username', md5(md5('$password')))";
	$result = mysqli_query($conn, $sql);
	
	if(!$result){
		header("Location: /index.php?error=2");
		exit();
	}
	
	$_SESSION["username"] = $_POST["usr"];
	
	mysqli_close($conn);
	
	header("Location: /poker");
	exit();
?>