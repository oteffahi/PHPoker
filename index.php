<?php
	session_start();
	 
	//verifier si l'utilisateur est déjà connecté
	if(isset($_SESSION["username"])){
	    header("Location: /poker");
		exit();
	}
?>
<!DOCTYPE HTML>
<html>
    <head>
        <meta charset="utf-8">
        <title>Register or Login</title>
    </head>
    <body>
        <table border=1 cellspacing="0" cellpadding="0" align="center">
            <?php
                if(isset($_GET["error"])){
                    echo 
'           <tr><td align="center">
';
                    switch($_GET["error"]){
                        case 1:
                            echo 
'               <font color="red">Wrong username and/or password.</font>
';
                            break;
                        case 2:
                            echo 
'               <font color="red">An error occured. If nothing seems wrong, change username and/or email address.</font>
';
                            break;
                    }
                }
            ?>
            <tr><td align="center">
                <form action="login.php" method="POST">
                    <table border=0 cellspacing="0" cellpadding="10" align="center">
                        <tr>
                            <td align="right">Username:
                            <td align="center"><input type="text" name="usr">
                        <tr>
                            <td align="right">Password:
                            <td align="center"><input type="password" name="pass">
                        <tr>
                            <td align="center" colspan=2><input type="submit" value='LOGIN'>
                    </table>
                </form>
            <tr><td align="center">
                <form action="register.php" method="POST">
                    <table border=0 cellspacing="0" cellpadding="10" align="center">
                        <tr>
                            <td align="right">Username:
                            <td align="center"><input type="text" name="usr">
                        <tr>
                            <td align="right">E-mail address:
                            <td align="center"><input type="text" name="email" disabled>
                        <tr>
                            <td align="right">Password:
                            <td align="center"><input type="password" name="pass">
                        <tr>
                        <tr>
                            <td align="center" colspan=2><input type="submit" value='REGISTER'>
                    </table>
                </form>
        </table>
    </body>
</html>