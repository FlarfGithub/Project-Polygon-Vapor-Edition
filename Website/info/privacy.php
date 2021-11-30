<?php 
$bypassModeration = true;
require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php';
pageBuilder::$pageConfig["title"] = "Terms Of Service";
pageBuilder::buildHeader();
?>

<b style="color:black;"> Vapor TOS (Privacy Policy)</b>

<div style="color:black;">1. When playing Vapor you must agree with us logging ip's (we are doing it as a moderation purpose).<div>

<div style="color:black;">2. Passwords are not plain text, they are hashed with a proper hashing algorithm which is bcrypt<div>

<div style="color:black;">3. If you do not want your data on Vapor anymore you can dm Flarf#0039 with proof that you are the owner of the account and we can sort things out.<div>

<div style="color:black;">4. If the servers ever get hacked (or breached whatever you call it) we will always truthfully say what was affected<br>

<div style="color:black;">5. If you are hosting the game by yourself instead of using our dedicated server you must understeand that exploiters can get your ip, so the recommended thing as a game hoster is using a vpn that supports port fowarding.<div>

<b style="color:black;">Thank you for reading the TOS (Privacy Policy) and we hope that you enjoy the game!</b>

<?php pageBuilder::buildFooter(); ?>
