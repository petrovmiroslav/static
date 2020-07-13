<?php

	use PHPMailer\PHPMailer\PHPMailer;
	use PHPMailer\PHPMailer\SMTP;
	use PHPMailer\PHPMailer\Exception;

	require 'PHPMailer/src/Exception.php';
	require 'PHPMailer/src/PHPMailer.php';
	require 'PHPMailer/src/SMTP.php';

	require_once $_SERVER['DOCUMENT_ROOT']."\\cnf\\cnf.php";

	$formData = [
		'error' => []
	];

	if (isset($_POST['submit']) || isset($_POST['hidden'])) {
		if (isset($_POST['personName'])) {
			personNameHandler($formData);
		}
		if (isset($_POST['phoneNumber'])) {
			phoneNumberHandler($formData);
		}
		if (isset($_POST['email'])) {
			emailHandler($formData);
		}
		if (isset($_POST['info'])) {
			infoHandler($formData);
		}
		if (isset($_FILES['file'])) {
			fileHandler($formData);
		}
		$formData['POST'] = $_POST;
		$formData['FILES'] = $_FILES;
		$formData['SCRIPT'] = $_SERVER;
		$formData['PSWRD'] = $gml;
		
		sendEmail($formData, $gml);
		
		sendJSON($formData);
	} else { 
		//echo 'HIDDEN-';
	}

	function sendJSON(&$formData) {
		echo json_encode($formData, JSON_UNESCAPED_UNICODE);
	}

	function personNameHandler (&$formData) {
		$formData['personName'] = trim(filter_input(INPUT_POST, 'personName', FILTER_SANITIZE_FULL_SPECIAL_CHARS));

		if(mb_strlen($formData['personName']) == 0) {
			array_push($formData['error'], 'nameIsNull');
		} else {
			if(mb_strlen($formData['personName']) <= 2) {
				array_push($formData['error'], 'nameIsTooShort');
			}
			if(mb_strlen($formData['personName']) >= 500) {
				array_push($formData['error'], 'nameIsTooLong');
			}
		}
	}

	function phoneNumberHandler (&$formData) {
		$formData['phoneNumber'] = filter_input(INPUT_POST, 'phoneNumber', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		$phoneNumberArr = preg_split('//', $formData['phoneNumber'], -1, PREG_SPLIT_NO_EMPTY);
		$phoneNumberArr = array_filter($phoneNumberArr, function($k) {
			    return preg_match("/\d/", $k);
			});
		$formData['phoneNumber'] = implode('', $phoneNumberArr);
		if (!filter_var($formData['phoneNumber'], 
						FILTER_VALIDATE_REGEXP,
						['options' => ['regexp' => "/^\d{11,20}$/"]
						]
						)) {

				if(mb_strlen($formData['phoneNumber']) < 11) {
					array_push($formData['error'], 'phoneNumberIsTooShort');
				} else {
					if(mb_strlen($formData['phoneNumber']) > 20) {
						array_push($formData['error'], 'phoneNumberIsTooLong');
					} else {
						array_push($formData['error'], 'Incorrect phoneNumber');
					}
				}

		} else {

		}
	}

	function emailHandler (&$formData) {
		$formData['email'] = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

		if (!filter_var(trim($formData['email']), FILTER_VALIDATE_EMAIL)) {
			array_push($formData['error'], 'Incorrect email');

		} else {
			$formData['email'] = trim($formData['email']);
		}
	}

	function infoHandler (&$formData) {
		$formData['info'] = trim(filter_input(INPUT_POST, 'info', FILTER_SANITIZE_FULL_SPECIAL_CHARS));

		if(mb_strlen($formData['info']) == 0) {
			array_push($formData['error'], 'infoIsNull');
		} else {
			if(mb_strlen($formData['info']) <= 2) {
				array_push($formData['error'], 'infoIsTooShort');
			}
			if(mb_strlen($formData['info']) >= 2000) {
				array_push($formData['error'], 'infoIsTooLong');
			}
		}
	}

	function fileHandler (&$formData) {
		$data = $_FILES['file'];
		$tmp = $data['tmp_name'];
		if (is_uploaded_file($tmp)) {
			$formData['file'] = $_FILES['file'];
			if (preg_match('/.exe$/', $data['name'], $p)) {
				array_push($formData['error'], 'fileIsExe');
			} else {
				if ($data['size'] > 10485760) {
					array_push($formData['error'], 'fileIsTooBig');
				} else {
					$formData['fileName'] = $data['name'];
					$formData['filePath'] = $tmp;
				}
			}
		} else {
			if ($data['error'] == 1) {
				array_push($formData['error'], 'fileIsTooBig');
			} else {
				array_push($formData['error'], 'fileIsNotUploads');
			}
		}
	}

	function sendEmail(&$formData, $gml) {
		
		$mailData = [];
		if ($formData['personName']) {
			$mailData['personName'] = $formData['personName'];
		} else {
			$mailData['personName'] = false;
		}
		if ($formData['phoneNumber']) {
			$mailData['phoneNumber'] = $formData['phoneNumber'];
		} else {
			$mailData['phoneNumber'] = false;
		}
		if ($formData['email']) {
			$mailData['email'] = $formData['email'];
		} else {
			$mailData['email'] = false;
		}
		if ($formData['info']) {
			$mailData['info'] = $formData['info'];
		} else {
			$mailData['info'] = false;
		}
		if ($formData['info']) {
			$mailData['info'] = $formData['info'];
		} else {
			$mailData['info'] = false;
		}
		if ($formData['filePath']) {

			$file = "C:\\Users\\mir19\\AppData\\Local\\Temp\\uploadFiles\\".$formData['fileName'];
				/*ОТНОСИТЕЛЬНЫЙ ПУТЬ В ПАПКУ СКРИПТА $file = $_SERVER['DOCUMENT_ROOT']."\\php\\uploadFiles\\".$formData['fileName'];*/

			if (move_uploaded_file($formData['filePath'], $file)) {
				$mailData['file'] = $file;
			} else {
				$mailData['file'] = false;
			}
		} else {
			$mailData['file'] = false;
		}


		$mail = new PHPMailer;

		$mail->isSMTP();
		// SMTP::DEBUG_OFF = off (for production use)
		// SMTP::DEBUG_CLIENT = client messages
		// SMTP::DEBUG_SERVER = client and server messages
		$mail->SMTPDebug = SMTP::DEBUG_OFF;
		$mail->Host = 'smtp.gmail.com';
		$mail->Port = 587;
		//Set the encryption mechanism to use - STARTTLS or SMTPS
		$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
		$mail->SMTPAuth = true;
		$mail->SMTPKeepAlive = true; // SMTP connection will not close after each email sent, reduces SMTP overhead
		$mail->Username = 'petrovproweb.ultimatefreehost@gmail.com';
		$mail->Password = $gml;


		$mail->setFrom('from@example.com', 'First Last');//вставить настоящий gmail
		$mail->addReplyTo('replyto@example.com', 'RepFirst RepLast');//вставить gmail куда отвечать!!!!!!!
		$mail->addAddress('mir1991bg@yandex.ru', $mailData['personName']);
		$mail->Subject = 'PHPMailer GMail SMTP test';
		$mail->isHTML(true);
		$mail->Body    = 'This is the HTML message body <b>in bold!</b> <img src="cid:my-photo" alt="my-photo">';
		$mail->AltBody = 'This is a plain-text message body';
		$mail->addEmbeddedImage($_SERVER['DOCUMENT_ROOT'].'\img\IMG_9951.JPG', 'my-photo');
		$mail->addAttachment($mailData['file']);

		function tryToSendMail(&$mail) {
			try {
		        $mail->send();
		        //echo 'Message sent!';
		        
		    } catch (Exception $e) {
		    	//echo 'Mailer Error: '. $mail->ErrorInfo;
		    	array_push($formData['error'], 'mailIsNotSend');
		        $mail->getSMTPInstance()->reset();
		    }
		}
		tryToSendMail($mail);
		$mail->clearAddresses();
    	$mail->addAddress($mail->Username, $mailData['personName']);
    	tryToSendMail($mail);

		
		/*if (!$mail->send()) {
		    echo 'Mailer Error: '. $mail->ErrorInfo;
		} else {
		    echo 'Message sent!';
		}*/


	}
	
	//echo json_encode($formData, JSON_UNESCAPED_UNICODE);
/*
?>
<!DOCTYPE html>
<html lang="ru">
<head>
	<title>Фильтрация пользовательского ввода</title>
<meta charset='utf-8'>
</head>
<body>
<form method="POST">
	<?// изменить на EMAIL и вставить в JS validate; ?>
<input type="text" name="email" value="<?= $email?>"><br />
<input type="hidden" name="validate" value="validate">
<input type="submit" name="submit" value="Фильтровать">
</form>
<?= $email; ?>
</body>
</html>
*/

