<?php

class RegisterSuccessEmail extends Email {

   protected $from = '';  // setting a blank from address uses the site's default administrator email
   protected $subject = '';
   protected $ss_template = 'RegisterSuccessEmail';

   function __construct() {
      parent::__construct();
   }

   public function sendMail($from, $fromName, $to, $data, $subject, $body) {

      $email = Object::create('RegisterSuccessEmail');
      $email->populateTemplate($data);
      $email->setFrom($from);
      $email->fromName = $fromName;
      $email->setTo($to);
      $email->setSubject($subject);
      $email->setBody($body);
      $email->send();
   }

}
