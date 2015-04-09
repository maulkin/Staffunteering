<?php

require_once('admin-header.inc.php');

function camra_get_details($membership_number)
{
	$url = 'https://api.camra.org.uk/index.php/api/branch/auth_4/format/json';

	$ch = curl_init();
	if (!$ch) {
		return null;
	}

	$key = urlencode (ServerConfig::CAMRA_AUTH_KEY);
	$memno = urlencode (str_pad($membership_number, 6, "0", STR_PAD_LEFT));
	$bypass = urlencode (ServerConfig::CAMRA_AUTH_BYPASS);

	// construct the XML request object
	// setup a POST
	curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, 5);
	curl_setopt ($ch, CURLOPT_URL, $url);
	curl_setopt ($ch, CURLOPT_POST, 1);
	curl_setopt ($ch, CURLOPT_POSTFIELDS, "KEY=$key&memno=$memno&ignore_pass=$bypass");
	curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);

	if (ServerConfig::CAMRA_AUTH_IGNORE_SSL) // Use this if the CS starts failing for whatever reason.
	{
		curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, false);
	}
	else
	{
		curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, true);
		curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 2);
	}

    $result = curl_exec ($ch);
    curl_close ($ch);

	$raw_data = json_decode($result);
	if (!$raw_data || !isset($raw_data->MembershipNumber) || ($raw_data->MembershipNumber != $memno)) {
		return null;
	}

    $proper_data = [
    	'name' => join(' ', [$raw_data->Forename, $raw_data->Surname]),
    	'branch' => $raw_data->BranchName,
    	'address' => join ("\n", [$raw_data->Address1, $raw_data->Address2, $raw_data->Address3, $raw_data->AddressPostcode])
    ];

    if (($raw_data->ContactByEmail == 'Y') && count($raw_data->Emails)) {
    	$proper_data['email'] = $raw_data->Emails[0]->emailAddresss;
    }
    if (count($raw_data->Telephones)) {
    	$proper_data['phone'] = $raw_data->Telephones[0]->Telephone;
    }

    return ($proper_data);
}

if (!isset($_GET['number']) || !preg_match('/^\d+$/', $_GET['number'])) {
	http_response_code(400);
	exit(0);
} else {
	echo json_encode(camra_get_details($_GET['number']));
}
