<?php
/*
 Karere
 ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
 This is one of a family of applications providing support
 for realtime communications (e.g., IM) to email users.

 The code in this file has been written by Charles Childers
 and is gifted to the public domain.
 ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
*/

include 'XMPPHP/XMPP.php';


/*
 ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
 Configuration
 ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
*/

$acct   = "";        /* Your Jabber user name              */
$acctPass = "";      /* Jabber password                    */
$sendTo = "";        /* Your email address. Messages will  */
                     /* be sent to this.                   */
$reader = "";        /* Email address for Karere to check  */
                     /* Email sent to this address will be */
                     /* sent to the Jabber server.         */
$readerPass = "";    /* Password for the above account.    */


/*
 ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
 Application starts here
 ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
*/
$conn = new XMPPHP_XMPP('talk.google.com', 5222, $acct, $acctPass, 'xmpphp', 'gmail.com', $printlog=true, $loglevel=XMPPHP_Log::LEVEL_INFO);
$conn->autoSubscribe();

$vcard_request = array();

try
{
  $conn->connect();
  while(!$conn->isDisconnected())
  {
    $payloads = $conn->processUntil(array('message', 'presence', 'end_stream', 'session_start', 'vcard'), 2);
    foreach($payloads as $event)
    {
      $pl = $event[1];
      switch($event[0])
      {
        case 'message':
          $header = "From: Karere (Jabber) <".$reader.">\r\n";
          mail($sendTo, "jabber: " . $pl['from'], $pl['body'], $header);
          echo $pl['from']." said ".$pl['body']."\n";
          break;
        case 'presence':
          print "Presence: {$pl['from']} [{$pl['show']}] {$pl['status']}\n";
          break;
        case 'session_start':
          print "Session Start\n";
          $conn->getRoster();
          $conn->presence("Karere");
          break;
      }
    }

    $mbox = imap_open ("{imap.gmail.com:993/imap/ssl/novalidate-cert}INBOX", $reader, $readerPass) or $nope = "true";
    if ($nope == "true")
    {
      echo "Error: " . imap_last_error() . "\n";
      $nope = "false";
    }
    else
    {
      $headers = @imap_headers($mbox);
      $numEmails = sizeof($headers);

      if ($numEmails > 0)
      {
        echo "Sending messages...\n";
        for($i = 1; $i < $numEmails+1; $i++)
        {
          $mailHeader = @imap_headerinfo($mbox, $i);
          $from = $mailHeader->fromaddress;
          $subject = strip_tags($mailHeader->subject);
          $body = imap_fetchbody($mbox, $i, "1.1");
          if ($body == "")
            $body = imap_fetchbody($mbox, $i, "1");
          $body = ereg_replace("/\n\r|\r\n|\n|\r/", " ", $body);
        $body=substr($body, 0, strpos($body, "------")); //Filter original from reply
        $body=substr($body, 0, strpos($body, "--")); //Filter below signature
        $body=quoted_printable_decode($body);
        $body=str_replace("= ","",$body);
        $body=preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", " ", $body); //This kills blank lines and makes them spaces
        $subject=str_replace("Re: jabber: ","",$subject); //This rewrites the incoming subject so a person can just reply
        echo $subject . ":" . $body . "\n";
        unset($xx2);
          $conn->message("$subject","$body");
          imap_delete($mbox, $i);
        }
      }
      imap_close($mbox);
    }
    sleep(1);

  }
}
catch(XMPPHP_Exception $e)
{
  die($e->getMessage());
}
