<?php
/* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
   Karere
   ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
   IRC-via-email

   This code was written by Charles Childers and is gifted to
   the public domain.
   ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */
require_once 'mbox.php';


/* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
   Configuration
   ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */
$mailbox = '/var/mail/karere';
$msg     = '/home/karere/htdocs/messgage.txt';



/* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
   Code begins
   ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */
$filter = array("<", ">");
$mbox = new Mail_Mbox($mailbox);
$mbox->open();

for ($n = 0; $n < $mbox->size(); $n++)
{
  $type = "none";
  $message = $mbox->get($n);

  preg_match('/Return-path: (.*)$/m', $message, $returns);
  $who = str_replace($filter, "", $returns[1]);

  $a = preg_match('/Subject: (.*)$/m', $message, $matches);
  $subject = $matches[1];
  if ($a == 1)
  {
    $type = "chat";
    $h = fopen($msg, "w");
    fwrite($h, $subject);
    fclose($h);
  }
  $mbox->remove($n);
}

$mbox->close();
?>
