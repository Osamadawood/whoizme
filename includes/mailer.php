<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function send_app_mail(array $cfg, string $to, string $subject, string $html): bool {
  $autoload = __DIR__ . '/../vendor/autoload.php';
  if (!file_exists($autoload)) return false; // حماية لو Composer مش موجود
  require_once $autoload;

  $mail = new PHPMailer(true);
  try {
    $smtp = $cfg['mail']['smtp'] ?? [];
    $mail->isSMTP();
    $mail->Host       = $smtp['host'] ?? '';
    $mail->SMTPAuth   = true;
    $mail->Username   = $smtp['username'] ?? '';
    $mail->Password   = $smtp['password'] ?? '';
    $mail->Port       = (int)($smtp['port'] ?? 0);
    if (!empty($smtp['secure'])) $mail->SMTPSecure = $smtp['secure'];

    $fromEmail = $cfg['mail']['from_email'] ?? 'no-reply@example.com';
    $fromName  = $cfg['mail']['from_name']  ?? 'App';
    $mail->setFrom($fromEmail, $fromName);
    $mail->addAddress($to);

    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body    = $html;

    return $mail->send();
  } catch (Exception $e) {
    return false;
  }
}