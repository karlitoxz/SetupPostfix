<?php
// Incluir la librería PHPMailer
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';
require 'PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Crea una nueva instancia de PHPMailer
$mail = new PHPMailer();

// Configura el servidor SMTP
$mail->isSMTP();
$mail->Host = '127.0.0.1';
$mail->Port = 25;

//Evitar error SSL o TLS
$mail->SMTPOptions = array(
'ssl' => array(
    'verify_peer' => false,
    'verify_peer_name' => false,
    'allow_self_signed' => true
));


$mail->SMTPAuth = false; // Si el servidor SMTP requiere autenticación, cambia esto a true
$mail->SMTPSecure = 'tsl'; // Si el servidor SMTP requiere conexión segura (TLS/SSL), cambia esto a 'tls' o 'ssl'

//Debug
$mail->SMTPDebug = true;
$mail->SMTPDebug = 4;

//De:
$to = 'info@segurihack.lat';
$toNombre = 'Juan Romero Segurihack';

//Para
$para = 'test-hqoe384y1@srv1.mail-tester.com';
$paraNombre = 'Juan Romero Destino';

// Configura el remitente y el destinatario
$mail->setFrom($to, $toNombre);
$mail->addAddress($para, $paraNombre);

// Configura el contenido del correo
$mail->Subject = 'Prueba de envio de correo desde PHP servidor' . $mail->Host ;
$mail->Body = 'Este es un correo de prueba enviado desde PHP usando PHPMailer.';

// Envía el correo y verifica si hubo errores
if ($mail->send()) {
    echo 'Correo enviado correctamente.';
} else {
    echo 'Hubo un error al enviar el correo: ' . $mail->ErrorInfo;
}
