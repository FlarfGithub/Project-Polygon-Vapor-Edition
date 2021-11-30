<?php
require $_SERVER['DOCUMENT_ROOT'].'/api/private/core.php'; 
if(isset($_COOKIE['vapor_session']) && session::getSessionData($_COOKIE['vapor_session']))
{	
	session::destroySession($_COOKIE['vapor_session']);
	session::invalidateSession($_COOKIE['vapor_session']);
}

header("Location: /");