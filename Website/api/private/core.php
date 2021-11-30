<?php
require 'config.php';
require 'PageBuilder.php';
require $_SERVER['DOCUMENT_ROOT'].'/api/private/vendors/Parsedown.php';



try
{
	$pdo = new PDO('mysql:host='.SITE_CONFIG["database"]["host"].';dbname='.SITE_CONFIG["database"]["schema"], SITE_CONFIG["database"]["username"], SITE_CONFIG["database"]["password"]);
	$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}
catch(PDOException $e)
{
	die("critical error: failed to connect to database");
}

class api
{
	static function respond($status, $success, $message)
	{
		die(json_encode(["status" => $status, "success" => $success, "message" => $message]));
	}


	static function requireLogin()
	{
		if(!SESSION){ self::respond(400, false, "Not logged in"); }
		if(!isset($_SERVER['HTTP_X_VAPOR_CSRF'])){ self::respond(400, false, "CSRF token not set"); }
		if($_SERVER['HTTP_X_VAPOR_CSRF'] != SESSION["csrfToken"]){ self::respond(400, false, "Invalid CSRF token"); }
	}
}

class general
{
	static function time_elapsed($datetime, $full = false, $ending = true) //https://stackoverflow.com/questions/1416697/converting-timestamp-to-time-ago-in-php-e-g-1-day-ago-2-days-ago
	{
		if($datetime == "@"){ return "-"; }
	    $now = new DateTime;
	    $ago = new DateTime($datetime);
	    $diff = $now->diff($ago);

	    $diff->w = floor($diff->d / 7);
	    $diff->d -= $diff->w * 7;

	    $string = array(
	        'y' => 'year',
	        'm' => 'month',
	        'w' => 'week',
	        'd' => 'day',
	        'h' => 'hour',
	        'i' => 'minute',
	        's' => 'second',
	    );
	    foreach ($string as $k => &$v) 
	    {
	        if ($diff->$k) { $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : ''); } 
	        else { unset($string[$k]); }
	    }

	    if (!$full) $string = array_slice($string, 0, 1);
	    if($ending){ return $string ? implode(', ', $string) . ' ago' : 'just now'; }
	    return implode(', ', $string);
	}

	static function getIpInfo($ip)
	{
		return json_decode(file_get_contents("http://api.ipgeolocationapi.com/geolocate/$ip"));
	}

	static function getServerMemoryUsage() 
	{
        // Get total physical memory (this is in bytes)
        exec("wmic ComputerSystem get TotalPhysicalMemory", $outputTotalPhysicalMemory);

        // Get free physical memory (this is in kibibytes!)
        exec("wmic OS get FreePhysicalMemory", $outputFreePhysicalMemory);

        // Find total value
        foreach ($outputTotalPhysicalMemory as $line) 
        {
            if ($line && preg_match("/^[0-9]+\$/", $line)) 
            {
                $memoryTotal = $line;
                break;
            }
        }

        // Find free value
        foreach ($outputFreePhysicalMemory as $line) 
        {
            if ($line && preg_match("/^[0-9]+\$/", $line)) 
            {
                $memoryFree = $line;
                $memoryFree *= 1024;  // convert from kibibytes to bytes
                break;
            }
        }

        return (object)["total" => $memoryTotal, "free" => $memoryFree];
    }

    static function getNiceFileSize($bytes, $binaryPrefix = false) 
    {
        if ($binaryPrefix) 
        {
            $unit=array('B','KiB','MiB','GiB','TiB','PiB');
            if (!$bytes) return '0 ' . $unit[0];
            return round($bytes/pow(1024,($i=floor(log($bytes,1024)))),2) .' '. (isset($unit[$i]) ? $unit[$i] : 'B');
        } 
        else 
        {
            $unit=array('B','KB','MB','GB','TB','PB');
            if (!$bytes) return '0 ' . $unit[0];
            return round($bytes/pow(1000,($i=floor(log($bytes,1000)))),2) .' '. (isset($unit[$i]) ? $unit[$i] : 'B');
        }
    }

    static function getFolderSize($path)
    {
	    $obj = new COM('scripting.filesystemobject');
	    if (is_object($obj))
	    {
	        $ref = $obj->getfolder($path);
	        return $ref->size;
	    }
	    else
	    {
	        return 'Failed to create COM Object';
	    }
    }

    static function replaceVars($string)
    {
    	return str_replace("%site_name_secondary%", SITE_CONFIG["site"]["name_secondary"], str_replace("%site_name%", SITE_CONFIG["site"]["name"], $string) );
    }
}

class users
{
	static function getUserNameFromUid($userId)
	{
		global $pdo;

		$query = $pdo->prepare("SELECT username FROM users WHERE id = :userid");
		$query->bindParam(":userid", $userId, PDO::PARAM_INT);
		$query->execute();

		return $query->fetchColumn();
	}

	static function getUidFromUserName($userName)
	{
		global $pdo;

		$query = $pdo->prepare("SELECT id FROM users WHERE username = :username");
		$query->bindParam(":username", $userName, PDO::PARAM_STR);
		$query->execute();

		return $query->fetchColumn();
	}

	static function getUserInfoFromUid($userId)
	{
		global $pdo;

		$query = $pdo->prepare("SELECT * FROM users WHERE id = :userid");
		$query->bindParam(":userid", $userId, PDO::PARAM_INT);
		$query->execute();

		return $query->fetch(PDO::FETCH_OBJ);
	}
    
	static function getUserInfoFromUserName($username)
	{
		global $pdo;

		$query = $pdo->prepare("SELECT * FROM users WHERE username = :username");
		$query->bindParam(":username", $username, PDO::PARAM_STR);
		$query->execute();

		return $query->fetch(PDO::FETCH_OBJ);
	}

	static function getUserAvatar($userId)
	{
		return "https://projvap.cf/thumbnail/error";
	}

	static function checkIfFriends($userId1, $userId2, $status = false)
	{
		global $pdo;

		if($status === false)
		{
			$query = $pdo->prepare("SELECT * FROM friends WHERE :uid1 IN (requesterId, receiverId) AND :uid2 IN (requesterId, receiverId) AND NOT status = 2");
		}
		else
		{
			$query = $pdo->prepare("SELECT * FROM friends WHERE :uid1 IN (requesterId, receiverId) AND :uid2 IN (requesterId, receiverId) AND status = :status");
			$query->bindParam(":status", $status, PDO::PARAM_INT);
		}

		$query->bindParam(":uid1", $userId1, PDO::PARAM_INT);
		$query->bindParam(":uid2", $userId2, PDO::PARAM_INT);
		$query->execute();

		return $query->fetch(PDO::FETCH_OBJ);
	}

		static function getFriendCount($userId)
	{
		global $pdo;

		$query = $pdo->prepare("SELECT COUNT(*) FROM friends WHERE :uid IN (requesterId, receiverId) AND status = 1");
		$query->bindParam(":uid", $userId, PDO::PARAM_INT);
		$query->execute();

		return $query->fetchColumn();
	}

	static function getFriendRequestCount($userId)
	{
		global $pdo;

		$query = $pdo->prepare("SELECT COUNT(*) FROM friends WHERE :uid = receiverId AND status = 0");
		$query->bindParam(":uid", $userId, PDO::PARAM_INT);
		$query->execute();

		return $query->fetchColumn();
	}


	static function updatePing()
	{
		global $pdo;
		if(!SESSION){ return false; }

		$userId = SESSION["userId"];
		$sessionkey = $_COOKIE['vapor_session'];

		$query = $pdo->prepare("UPDATE users SET lastonline = UNIX_TIMESTAMP() WHERE id = :id");
		$query->bindParam(":id", $userId, PDO::PARAM_INT);

		if(!$sessionkey){ return $query->execute(); }
		
		$sessquery = $pdo->prepare("UPDATE sessions SET lastonline = UNIX_TIMESTAMP() WHERE sessionKey = :key");
		$sessquery->bindParam(":key", $sessionkey, PDO::PARAM_STR);

		return $sessquery->execute() && $query->execute();
	}

	static function updateCurrencyStipend()
	{
		global $pdo;
		if(!SESSION){ return false; }

		$userId = SESSION["userId"];
		if(SESSION["nextCurrencyStipend"] > time()){ return true; } //not yet

		$query = $pdo->prepare("UPDATE users SET currency = currency + 10, nextCurrencyStipend = UNIX_TIMESTAMP()+86400 WHERE id = :uid");
		$query->bindParam(":uid", $userId, PDO::PARAM_INT);
		return $query->execute();
	}

	static function getOnlineStatus($userId)
	{
		global $pdo;

		$response = ["online" => false, "text" => false];

		$query = $pdo->prepare("SELECT lastonline FROM users WHERE id = :id");
		$query->bindParam(":id", $userId, PDO::PARAM_INT);
		$query->execute();

		$time = $query->fetchColumn();

		if(!$query->rowCount()){ return $response; }
		if($time+30 > time()){ $response["online"] = true; $response["text"] = "Website"; }
		else{ $response["text"] = ($time + 604800) > time() ? general::time_elapsed('@'.$time) : date('j/n/Y', $time);  }
		// \a\t g:i:s A
		return $response;
	}

	static function getUsersOnline()
	{
		global $pdo;

		$query = $pdo->query("SELECT COUNT(*) FROM users WHERE lastonline+35 > UNIX_TIMESTAMP()");
		$query->execute();
		return $query->fetchColumn();
	}

	static function requireLogin()
	{
		if(!SESSION){ die(header("Location: /login?ReturnUrl=".urlencode($_SERVER['REQUEST_URI']))); }
	}

	static function requireLoggedOut()
	{
		if(SESSION){ die(header("Location: /home")); }
	}

	static function getUserModeration($userId)
	{
		global $pdo;

		$query = $pdo->prepare("SELECT * FROM bans WHERE userId = :id AND NOT isDismissed ORDER BY id DESC LIMIT 1");
		$query->bindParam(":id", $userId, PDO::PARAM_INT);
		$query->execute();

		return $query->fetch(PDO::FETCH_OBJ);
	}

}


class session //most of the session code here comes from my old roblonium code; it works surprisingly well
{
	static function createSession($userId)
	{
		global $pdo;

		keygen:
		$sessionkey = bin2hex(random_bytes(128)); 

		$query = $pdo->prepare("SELECT COUNT(*) FROM sessions WHERE sessionKey = :sesskey");
		$query->bindParam(":sesskey", $sessionkey, PDO::PARAM_STR);
		$query->execute();

		if($query->fetchColumn()){ goto keygen; } //if a session with the same key already exists then repeat key generation process

		$csrf = bin2hex(random_bytes(32));

		$create = $pdo->prepare("INSERT INTO sessions (`sessionKey`, `userAgent`, `userId`, `loginIp`, `created`, `lastonline`, `csrf`) VALUES (:sesskey, :useragent, :userid, :ip, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), :csrf)");
		$create->bindParam(":sesskey", $sessionkey, PDO::PARAM_STR);
		$create->bindParam(":useragent", $_SERVER['HTTP_USER_AGENT'], PDO::PARAM_STR);
		$create->bindParam(":userid", $userId, PDO::PARAM_INT);
		$create->bindParam(":ip", $_SERVER['REMOTE_ADDR'], PDO::PARAM_STR);
		$create->bindParam(":csrf", $csrf, PDO::PARAM_STR);
		$create->execute();

		setcookie("vapor_session", $sessionkey, time()+(157700000*3), "/"); //expires in 5 years
	}

	static function destroySession($sesskey)
	{
		global $pdo;

		$query = $pdo->prepare("UPDATE sessions SET valid = 0 WHERE sessionKey = :sesskey");
		$query->bindParam(":sesskey", $sesskey, PDO::PARAM_STR);
		return $query->execute();
	}

	static function invalidateSession($sesskey)
	{
		setcookie("vapor_session", $sesskey, 1, "/");
		die(header("Refresh: 0"));
	}

	static function getSessionData($sessionkey, $strict = true) 
	{
		global $pdo;
		
		$query = $pdo->prepare("SELECT * FROM sessions WHERE sessionKey = :sesskey AND lastonline+432000 > UNIX_TIMESTAMP()");
		$query->bindParam(":sesskey", $sessionkey, PDO::PARAM_STR);
		$query->execute();
		if(!$query->rowCount()){ return false; }
		$row = $query->fetch(PDO::FETCH_OBJ);

		if($row->created+(157700000*3) < time()){ return false; }
		if($strict && $row->userAgent != $_SERVER['HTTP_USER_AGENT']){ return false; }
		if($row->loginIp != $_SERVER['REMOTE_ADDR']){ return false; }
		//these last two checks in particular should help to stop potential cookie stealing attacks

		return $row;
	}
}

if(isset($_COOKIE['vapor_session']))
{	
	$session = session::getSessionData($_COOKIE['vapor_session']);
	if($session) 
	{
		$userInfo = users::getUserInfoFromUid($session->userId);

		define('SESSION', 
			[
				"userName" => $userInfo->username, 
				"userId" => $userInfo->id, 
				"friendRequests" => users::getFriendRequestCount($userInfo->id),
				"status" => $userInfo->status,
				"currency" => $userInfo->currency, 
				"nextCurrencyStipend" => $userInfo->nextCurrencyStipend,
				"csrfToken" => $session->csrf
			]);

		if(users::getUserModeration(SESSION["userId"]) && !isset($bypassModeration))
		{
			die(header("Location: /moderation"));
		}
		else
		{
			users::updatePing();
			users::updateCurrencyStipend();
		}
	}
	else 
	{
		session::destroySession($_COOKIE['vapor_session']);
		session::invalidateSession($_COOKIE['vapor_session']);
		define('SESSION', false);
	}
}
else 
{
	define('SESSION', false);
}