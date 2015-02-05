<?php

require_once('server-config.inc.php');

function camra_authenticate ($membership_number, $password, &$error_code, &$debug=false)
{
	$url = 'https://api.camra.org.uk/index.php/api/auth/beerengine/format/json';

	// create a CURL channel to call the authentication service
	$ch = curl_init();
	if (!$ch)
	{
		$error_code = 3;
		return null;
	}

	$key = urlencode (ServerConfig::CAMRA_AUTH_KEY);
	$memno = urlencode (str_pad($membership_number, 6, "0", STR_PAD_LEFT));
	$pass = urlencode ($password);

	// construct the XML request object
	// setup a POST
	curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, 5);
	curl_setopt ($ch, CURLOPT_URL, $url);
	curl_setopt ($ch, CURLOPT_POST, 1);
	curl_setopt ($ch, CURLOPT_POSTFIELDS, "KEY=$key&memno=$memno&pass=$pass");
	curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);

	if (1) // Use this if the CS starts failing for whatever reason.
	{
		curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, false);
	}
	else
	{
		curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, true);
		curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt ($ch, CURLOPT_CAINFO, getcwd() . "/priv/certs/ca/GeoTrustGlobalCA.crt");
	}

	// execute the POST method
	$result_data = curl_exec ($ch);
	$error_code = curl_errno ($ch);
	if ($error_code)
	{
		if ($debug)
			$debug = "POST failed:\n" . curl_error($ch);
		curl_close ($ch);
		return null;
	}
	curl_close ($ch);

	$data = json_decode ($result_data);

	// It appears that the service is working OK, so failures beyond here are real authentication errors.
	$error_code = 0;

	if (!isset ($data->Forename) || !isset ($data->Surname) ||
			!isset ($data->Branch) ||
			!isset ($data->MembershipNumber)) {
		if ($debug)
			$debug = "Auth failed:\n" . $result_data . "\n";

		return null;
	}

	return $data;
}


