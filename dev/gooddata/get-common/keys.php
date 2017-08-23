<?php
/*  � 2007-2013 eBay Inc., All Rights Reserved */
/* Licensed under CDDL 1.0 -  http://opensource.org/licenses/cddl1.php */

    //show all errors - useful whilst developing
    error_reporting(E_ALL);

    // these keys can be obtained by registering at http://developer.ebay.com
    
    $production         = true;   // toggle to true if going against production
    $compatabilityLevel = 867;    // eBay API version
    
    if ($production) {
        $devID = '6e7512e1-c5a1-4be8-aef5-3b5657c941bf';   // these prod keys are different from sandbox keys
        $appID = 'MojoPart-34e5-49b0-aab3-c8aa62626923';
        $certID = '27093c3f-4efa-45c6-8dab-86cbfca6ddab';
        //set the Server to use (Sandbox or Production)
        $serverUrl = 'https://api.ebay.com/ws/api.dll';      // server URL different for prod and sandbox
        //the token representing the eBay user to assign the call with
        $userToken = 'AgAAAA**AQAAAA**aAAAAA**4Z5DVg**nY+sHZ2PrBmdj6wVnY+sEZ2PrA2dj6wMlIGjDZWApg+dj6x9nY+seQ**LpgBAA**AAMAAA**4x/2Il0DPU+k/YzjR/nWSXqgXz5fNHayp1K+Ic+L6YtRJ0EB3Ibngk2/kpN6bQwlJCt6u7UmFSQXDO3RsOK/EdDvFZNyIxU69yj22CJt3q3k/RxgHgKA/FMOjUsFd20+fLHcJS6GwJkn8Iqoe3DC4RAx7RdbaiQ+5CzkleIxmpeq9oibGnynB7XEEz98te37PDFFDlsA3YMDJ4NgFHkuqIblStuxfA69ymYin7jb0V8usrwheed1CWL6VHs229tjOsDTBeE85iqE7Z2MAufMroQ5WPRg5jjcQWqCeb24Y8pkmt8BCSpjV3EKLNKjyybzMNxHHK3iTqOtHX90jFm1CMBKVvnurn9xgs5T2l6vVvFftLqIdP6c3Df2n4lH9t97c+DT4vcD4nVbSO+RA2ARS/0Hz7FvI2IFvQAIQfvZ4POLdmj7Z/JabZZ9nV1+1N0/05Zr6q9QOzgmXQRrb2CbLULEadKK5Wxce3VjLZxg0jSgueA8bLe+HYU75X8N340dKlaZJMgEcbZa3BuawZ6BzGIF5Xupj5sIcTGVxw1T68ufQuZcLVTKWGFsQGjWq5CYi+rYwnUQ4ykuxp1+dK02cYewgI9T7yIKAr8ItREqbkGTWfHS0V66ooGRAeVvWgVESPTKDRNkLf7IHn+gR/Yxpzd2XAzbkz5Q2tUyjCdqfxnKA3rDFZr5roo7r5ki2fJ5dMVGp0a4JDbc3WetbPnGhHWpRQvLicDJiXn/qn441+2KWdt3m8/uFGHqtGRd+1jj';

// this one expired 2015-11-11 'AgAAAA**AQAAAA**aAAAAA**UmZrUw**nY+sHZ2PrBmdj6wVnY+sEZ2PrA2dj6wMlIGjDZWApg+dj6x9nY+seQ**LpgBAA**AAMAAA**enNlut5Q00ITWRFeq5UyMErcK5R462s2AZFLaDXjlIgQYWaJ769Oi3epnE+1qtpQwMi9o5Gow6nErC9Nuyw50mLf522s/k8xYeYLTRB5igPFbrt6RoR93UhCXWWcJVbqt51UQ9eQWqX5Edaq3kJVz7NraeCXHPlYasy9DxganM+e6p1C252d4bAOiW47Jn8s8l4kbOT5Cy/WH7ez03ugEwfS7g4CJd2+erdRlY7fMq31WfVg0vpouIvGXVepg7RUzUUfpk7PS1BpTU1OMiYKHRy0e041VNSTAmbXU5/CFy7+X+XrncOhKV3k9ZF8khhn4IdtjgXrSZn0ux0dIJomhKpa3ELQsxj4qK9X0qePHEYMBMunezoVhNiLOrSwakckzt2t/zdLIIu+RuLrhc7tyCkz6kboyRfbRlVif7sAfPDOi+1dd1DegvFNP8IAR/HjpUGwghOnT3D/X9zspdZAHr3lPec+RqSERi2fviNevYASF5uzKU9NNt5tSAZmm0/nSgUNOLYSqAk7hfKfDp1jJyoqX5ZqQH4zxWimO809ia73pz/8mfa9KchXAqOIg3fVpDznHk2x0BhY12n4ygIH0W9QeB8Jiwqo5YLcVTIKyBpoZ/oJ2Oct3tEMA1nIxhaVTfoue+dvlvYBlNeQnvw/1u2NOtj0RdSZOkPui+poMWIzgn5nZOn7v/85vIkxnGcXN2xkzKE3Bvm0MZKlzBKYQ5zRbTB3HFaT+mG2Najj1IGopkH4ilXFmfQ/C6EyPeKP'; 

        $paypalEmailAddress= 'mojoautosupply@gmail.com';		
    } else {  
        $devID = '6e7512e1-c5a1-4be8-aef5-3b5657c941bf';         // insert your devID for sandbox
        $appID = 'MojoPart-82e6-4fd7-afc6-c6425b8fe066';   // different from prod keys
        $certID = '15d81c5a-2105-447d-b5ed-d129aee358b7';  // need three 'keys' and one token
        //set the Server to use (Sandbox or Production)
        $serverUrl = 'https://api.sandbox.ebay.com/ws/api.dll';
        // the token representing the eBay user to assign the call with
        // this token is a long string - don't insert new lines - different from prod token
        $userToken = 'YOUR_TOKEN_ABOUT_1000_CHARS'; 
		$paypalEmailAddress = 'SANDBOX_PAYPAL_EMAIL_ADDRESS';		
    }
    
    
?>