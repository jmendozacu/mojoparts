<?php
    
    define("ENV_PRODUCTION", 'production');
    define("ENV_SANDBOX", 'sandbox');
    
    function getBulkDataExchangeServiceEndpoint($environment)
    {
	    if ( $environment == ENV_PRODUCTION ) {
	        $endpoint = 'https://webservices.ebay.com/BulkDataExchangeService';    
	    }
	    elseif ( $environment == ENV_SANDBOX ) {  
	    	$endpoint = 'https://webservices.sandbox.ebay.com/BulkDataExchangeService';              
	    }
	    else {
	    	die("Invalid Environment: $environment");  
	    }
	    
	    return $endpoint;
    }
    
    function getFileTransferServiceEndpoint($environment)
    {
    	if ( $environment == ENV_PRODUCTION ) {
	        $endpoint = 'https://storage.ebay.com/FileTransferService';    
	    }
	    elseif ( $environment == ENV_SANDBOX ) {  
	    	$endpoint = 'https://storage.sandbox.ebay.com/FileTransferService';              
	    }
	    else {
	    	die("Invalid Environment: $environment");    
	    }
	    
	    return $endpoint;
    }
    
    function getSecurityToken($environment)
    {
	    if ( $environment === ENV_PRODUCTION ) {
	        $securityToken = 'AgAAAA**AQAAAA**aAAAAA**t8WVWQ**nY+sHZ2PrBmdj6wVnY+sEZ2PrA2dj6wMlIGjDZWApg+dj6x9nY+seQ**LpgBAA**AAMAAA**PSU9d2EjU6KPixPwhViKsyVCcvietmWdaRXABxb1lpyG5q47vqX5hE1XmJJB+DWJB1sKL/EgPRTbZ2pkW5DTvm51gTtAX4S25RkVkur1Ts/CfDAlWpQNkpAmarDYlG8ox9leioCJcqeWdPQm6B3MVcvpmmWK1Zu9dCO2SMPYXCc/qkRG+wD0hed83Vs8kDatpCBkC9zwME9yGlKUfoyuHGy+1xZeHbPLY2RSFCykg8ceN29cDliFd822xOESQSbB+nsW09uoUBRCQ7LysUqm9IIiuMe3xK4L7frMrinnR3LdcL5BEh2i3LZkl6eO02V5Bg8j/CCcHd5xEIogaIzPAUOMUYDOQ6JEUa2bO69R/0esvb4aR7QihZmAWsjLQlYBlZ5BNUme7spr3e+Sdx9f+AEszAyAQ6YbSqpKImRc93XmqUscmc4MKoKcOg5uuEgZ8QU3KKEVxqnxQcrNsTnLxM1v/q1k4ZcFjMq07Z2ublCBfGBYxBvZJv+dAEna4xWQKhcd0esZS8fvDknxZF1mky1woieNdvo43q3nYxcGo4QXv91Idk6T25DLvUH0b8HIKq5tEvhwbp98jMeTy/ApOGTLoEXKckSqf/jHB2RqbhPZRZsbxACXfO3rqOStiQSXMzUzCXpovupov+awa5IiGyPpAL3ZaeJwsTmwpuTjQvAold9IxLytPaBF3XX6pb9SGK2kHPV3ktlkNwff6XqydWl2DxwMZcnN8TrGO2OEOBH8IsQnEsBQXlygemP6Klli';
	    }
	    elseif ( $environment === ENV_SANDBOX ) {  
	        $securityToken = 'PASTE_YOUR_SANDBOX_TOKEN_HERE';                 
	    }
	    else {
	    	die("Invalid Environment: $environment");   
	    }
	    
	    return $securityToken;
    }

?>