<?php

//$recipient = "great_beluluk@yahoo.com";
//$subject = 'PHPMailer test';
//$body = "Percobaan PHPMailer, bukan pake gmail, tapi pake icodeformoney.com";

namespace Trust;

class Mail {

  private static $phpmailerpath = LIBDIR . DS . '_phplib/PHPMailer/PHPMailerAutoload.php';

  const SEND_WITH_GMAIL = 1;
  const SEND_WITH_POSTFIX = 2;
  const SEND_WITH_OTHER_SMTP = 3;

  private static $method = 2;
  private static $senderEmail;
  private static $senderName;
  private static $smtpHost;
  private static $smtpPort;
  private static $smtpUsername; //email
  private static $smtpPassword; //email password
  private static $gmailAcc;
  private static $gmailPass;
  public static $errorInfo;
  public static function setPostfix($sender, $displayName) {
    self::$method = self::SEND_WITH_POSTFIX;
    self::$senderEmail = $sender;
    self::$senderName = $displayName;
  }

  public static function setSMTP($sender, $displayName, $host, $port, $username, $password) {
    self::$method = self::SEND_WITH_OTHER_SMTP;
    self::$senderEmail = $sender;
    self::$senderName = $displayName;
    self::$smtpHost = $host;
    self::$smtpPort = $port;
    self::$smtpUsername = $username;
    self::$smtpPassword = $password;
  }

  public static function setGmail($sender, $pass) {
    self::$method = self::SEND_WITH_GMAIL;
    self::$gmailAcc = $sender;
    self::$gmailPass = $pass;
  }

  public static function sendMail($recipients, $subject, $body, $altBody = '', $attachpaths = []) {
    switch (self::$method) {
      case self::SEND_WITH_GMAIL: return self::sendWithGmail($recipients, $subject, $body, $altBody, $attachpaths);
      case self::SEND_WITH_POSTFIX: return self::sendPostfixMail($recipients, $subject, $body, $altBody, $attachpaths);
      case self::SEND_WITH_OTHER_SMTP: return self::sendSMTPMail($recipients, $subject, $body, $altBody, $attachpaths);
      default: return -1;
    }
  }

  //Using local SMTP Mailserver (postfix)
  public static function sendPostfixMail($recipients, $subject, $body, $altBody = '', $attachpaths = []) {
    date_default_timezone_set('Asia/Jakarta');
    require self::$phpmailerpath;
    $mail = new \PHPMailer;
    $mail->From = self::$senderEmail;
    $mail->FromName = self::$senderName;
    $mail->Subject = $subject;
    $mail->Body = $body;
    $mail->AltBody = $altBody;
    foreach ($recipients as $receiver) $mail->addAddress($receiver);
    foreach ($attachpaths as $attpath) $mail->addAttachment($attpath);
//    $file_to_attach = 'PATH_OF_YOUR_FILE_HERE';
//    $mail->AddAttachment( $file_to_attach , 'NameOfFile.pdf' );
    $mail->isHTML(true);
    if ($mail->Send()) return true;
    self::$errorInfo = $mail->ErrorInfo;
    return false;
  }

  //Using external SMTP Mailserver
  public static function sendSMTPMail($recipients, $subject, $body, $altBody = '', $attachpaths = []) {
    //code comments: https://github.com/PHPMailer/PHPMailer/blob/master/examples/gmail.phps
    date_default_timezone_set('Asia/Jakarta');
    require self::$phpmailerpath;
    $mail = new \PHPMailer;
    $mail->isSMTP();
    $mail->SMTPDebug = ($isDebug) ? 2 : 0;
    $mail->Debugoutput = 'html';
    $mail->Timeout = 15;
    $mail->SMTPSecure = 'tls';
    $mail->SMTPAuth = true;
    $mail->Host = self::$smtpHost;
    $mail->Port = self::$smtpPort;
    $mail->Username = self::$smtpUsername;
    $mail->Password = self::$smtpPassword;
    $mail->setFrom(self::$senderEmail, self::$senderName);
    //$mail->addReplyTo('replyto@example.com', 'First Last');

    foreach ($recipients as $receiver) $mail->addAddress($receiver);
    $mail->Subject = $subject;
    $mail->Body = $body;
    ////Read an HTML message body from an external file, convert referenced images to embedded,
    ////convert HTML into a basic plain-text alternative body
    //$mail->msgHTML(file_get_contents('contents.html'), dirname(__FILE__));
    //Replace the plain text body with one created manually
    if ($altbody) $mail->AltBody = $altbody;
    //send the message, check for errors
    foreach ($attachpaths as $attpath) $mail->addAttachment($attpath);
    $mail->IsHTML(true);
    if ($mail->Send()) return true;
    self::$errorInfo = $mail->ErrorInfo;
    return false;
  }

  public static function sendWithGmail($recipients, $subject, $body, $altbody = '', $attachpaths = []) {
    require self::$phpmailerpath;
    $mail = new \PHPMailer;
    $mail->isSMTP();
    $mail->SMTPDebug = (DEBUG) ? 2 : 0;
    $mail->Debugoutput = 'html';
    $mail->Host = 'smtp.gmail.com';
    // use
    // $mail->Host = gethostbyname('smtp.gmail.com');
    // if your network does not support SMTP over IPv6
    //Set the SMTP port number - 587 for authenticated TLS, a.k.a. RFC4409 SMTP submission
    $mail->Port = 587;
    $mail->SMTPSecure = 'tls';
    //Whether to use SMTP authentication
    $mail->SMTPAuth = true;
    $mail->Username = self::$gmailAcc;
    $mail->Password = self::$gmailPass;
    $mail->setFrom($mail->Username);
    //    $mail->addReplyTo('replyto@example.com', 'First Last');
    foreach ($recipients as $receiver) $mail->addAddress($receiver);
    //Set the subject line
    $mail->Subject = $subject;
    $mail->Body = $body;
    if ($altbody)
      $mail->AltBody = $altbody;
    $mail->IsHTML(true);
    foreach ($attachpaths as $attpath) $mail->addAttachment($attpath);
    if ($mail->Send()) return true;
    self::$errorInfo = $mail->ErrorInfo;
    return false;
  }

}
