<?php


function mailer_send($to, $subject, $body)
{
	return mailer_send_multiple([$to], $subject, $body);
}

function mailer_send_multiple($to_list, $subject, $body)
{
	if (count($to_list) == 0)
		return;

	$mail = new PHPMailer();

	$mail->isSMTP();
	$mail->SMTPAuth = true;
	$mail->SMTPSecure = ServerConfig::MAIL_SECURE;
	$mail->Host = ServerConfig::MAIL_HOST;
	$mail->Port = ServerConfig::MAIL_PORT;
	$mail->Username = ServerConfig::MAIL_USER;
	$mail->Password = ServerConfig::MAIL_PASSWORD;
	$mail->setFrom(ServerConfig::MAIL_FROM, ServerConfig::MAIL_FROM_NAME);
	$mail->XMailer = ServerConfig::MAIL_XMAILER;

	$mail->CharSet = 'utf-8';
	$mail->isHTML = false;

	$mail->Subject = $subject;
	$mail->Body = $body;

	if (count($to_list) == 1) {
		$mail->addAddress($to_list[0]);
	} else {
		$mail->addAddress(ServerConfig::MAIL_FROM, ServerConfig::MAIL_FROM_NAME);
		foreach ($to_list as $to) {
			$mail->addBcc($to);
		}
	}

	if (!$mail->send()) {
		trigger_error("Failed to send email:" . $mail->ErrorInfo, E_USER_WARNING);
		return false;
	}
	return true;
}
