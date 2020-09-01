<?php
	session_start();
	//verifier si l'utilisateur est déjà connecté
	if(isset($_SESSION["username"])){
		header("Location: /poker");
		exit();
	}
	
	if($_SERVER['REQUEST_METHOD'] != 'POST' || empty($_POST["usr"]) || empty($_POST["pass"])){
		header("Location: index.php?error=1");
		exit();
	}
    //établir la cnx avec la BD
    $conn = mysqli_connect(/*deleted for obvious reasons*/);
    
	if(!$conn) //vérifier si la cnx à réussie
		die('Database error');
    
	//filtrer les caractères d'injections sql
    $_POST["usr"] = mysqli_real_escape_string($conn, $_POST["usr"]);
	
	//vérifier si les username/password sont correctes
	$sql = "SELECT * FROM USERS WHERE username = BINARY '".$_POST['usr']."' and password = '".md5(md5($_POST['pass']))."'";
	$result = mysqli_query($conn, $sql);
	
	if(mysqli_num_rows($result) > 1){
		//si plus d'1 ligne correspondent alors la BD est incohérente ou il s'agit d'une injection sql
		die("Conflict in database");
	}
	else if(mysqli_num_rows($result) == 0){ //si aucune ligne n'est séléctionnée alors mauvais username/password
		header("Location: /index.php?error=1");
		exit();
	}
	$row = mysqli_fetch_assoc($result);
	$_SESSION["username"] = $row["username"];
	
	mysqli_close($conn);
	
	header("Location: /poker");
	exit();
?>