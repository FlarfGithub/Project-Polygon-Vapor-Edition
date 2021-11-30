<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

define("SITE_CONFIG", 
	[
		"database" =>
		[
			"host" => "127.0.0.1",
			"schema" => "vapor",
			"username" => "YOURDBUSER",
			"password" => "YOURDBPASS"
		],

		"site" =>
		[
			"name" => "Project Vapor",
			"name_secondary" => "Vapor",
			"currencyName" => "Diamonds"
		],

		"captcha" =>
		[
			"siteKey" => "undefined",
			"privateKey" => "undefined"
		]
	]);