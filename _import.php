<?php

include ("./_imp.php");

?>

<html>
<head>
	<meta charset="UTF-8"/>
	<title>Traspàs d'obres d'artinpocket.cat a inpocketart.com</title>
    <!-- <link rel="stylesheet" href="./view/css/estilo.css" type="text/css" media="screen" /> -->
</head>

<body>
	<div id="main">
		<header id="cabecera">
			<h1>Traspàs d'obres d'artinpocket.cat a inpocketart.com</h1>
			<nav id="menu">
			</nav>
		</header>
		<section>
			<article id="cap">
			</article>
			<article id="cos">
				<img src="./logo-artinpocket_web.png" height="30%" width="50%"/>
				</br></br></br></br>
				<form id="formart" name="impexp" action= "<?php echo $_SERVER['PHP_SELF']; ?>" method = "post">
					<input type="submit" name="import" value="Importar"/>
					<input type="submit" name="catDigital" value="Assignar Cat. DIGITAL"/>
 				</form>
			</article>
			<article id="peu">
			</article>
		</section>
		<footer>
		</footer>
    </div>
</body>
</html>		

<?php
if ( isset($_POST["import"]) ) echo (importExport($wpdb) );
if ( isset($_POST["catDigital"]) ) echo (assignaCatDigital($wpdb) );
?>
