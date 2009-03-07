<?
/* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
   Karere
   ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
   IRC-via-email

   This code was written by Charles Childers and is gifted to
   the public domain.
   ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */

$msg = '/path/to/message';
$email = 'name@gmail.com';
$pass = 'password';

$mbox = imap_open ("{imap.gmail.com:993/imap/ssl}INBOX", $email, $pass)
  or die("can't connect: " . imap_last_error());

$headers = @imap_headers($mbox) or die("Couldn't get emails");

$numEmails = sizeof($headers);

echo $numEmails;

if ($numEmails > 0)
{
  for($i = 1; $i < $numEmails+1; $i++)
  {
    $mailHeader = @imap_headerinfo($mbox, $i);
    $from = $mailHeader->fromaddress;
    $subject = strip_tags($mailHeader->subject);
    $date = $mailHeader->date;

    $h = fopen($msg, "w");
    fwrite($h, $subject);
    fclose($h);

    imap_delete($mbox, $i);
  }
}
imap_close($mbox);
?>
