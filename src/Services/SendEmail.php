<?php

/**
 * User: Dev_Lee
 * Date: 06/29/2023 - Time: 6:00 AM
 * Updated: 10/03/2023 - Time: 11:54 PM
 */

namespace Devlee\WakerORM\Services;

/**
 * @author  Ankain Lesly <leeleslyank@gmail.com>
 * @package  Waker-ORM
 */

class SendEmail
{
  private String $logo = '';
  private string $main_email = '';

  private String $email_send_to;
  private String $email_subject;
  private String $email_body;
  private String $email_headers = '';

  // private String $email_body = null;

  public function __construct($custom_header = null)
  {
    $this->logo = $_ENV['APP_URL'] . "/static/media/app_logo.png";
    $this->main_email = $_ENV['EMAIL_ADDRESS'];

    $this->email_body = '<html> <head> <title>{{subject}}</title> <style> .mail_btn { text-decoration: none;padding: 0.5em 1em; border-radius:10px; background-color: #3838de; color: #fff; display: block; margin: 10px; text-align: center; } </style> </head> <body> <div class="container"> {{header}} <div class="content"  style="padding: 10px;">{{content}}</div> </div> </body></html>';

    if ($custom_header) {
      $this->email_body = str_replace('{{header}}', $custom_header, $this->email_body);;
    } else {
      // TODO: Design the header
      $header = '
        <div class="header" style="color: #fff; display: flex; align-items: center; justify-content: space-between; padding: 12px; background-color: #351c15;">
          <img src="' . $this->logo . '" alt="Logo">
          <h4>' . $_ENV['APP_NAME'] ?? "Service" . '</h4>
        </div>';


      $this->email_body = str_replace('{{header}}', $header, $this->email_body);;;
    }
  }

  // Set Email Parameters
  public function setEmail($to, $subject, $body, $from = null)
  {
    $this->email_headers .=  "To: Client <$to> \r\n";
    $this->email_send_to = $to;
    $this->email_subject = $subject;

    $this->email_body = str_replace('{{subject}}', $subject, $this->email_body);
    $this->email_body = str_replace('{{content}}', $body, $this->email_body);

    $this->setHeaders($from);
  }

  // Set Email Headers
  public function setHeaders($sender_email)
  {
    if ($sender_email) {
      $headers = "From: " . $_ENV['APP_NAME'] ?? "Web Services" . " <$sender_email> \r\n";
    } else {
      $headers = "From: " . $_ENV['APP_NAME'] ?? "Web Services" . " <$this->main_email> \r\n";
    }

    $headers .= "Cc: Our Customer Service <$this->main_email> \r\n";
    $headers .= "MIME-Version: 1.0 \r\n";
    $headers .= "Content-type: text/html; charset=UTF-8 \r\n";
    $headers .= "Reply-To: $this->main_email \r\n ";
    $this->email_headers = $headers;
  }

  public function send()
  {
    // echo 'sending...';
    // $result = mail('to', 'subject', 'message', 'headers');
    $result = mail($this->email_send_to, $this->email_subject, $this->email_body, $this->email_headers);
  }
}
