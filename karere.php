<?
/*
 ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
 Karere
 ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
 IRC-via-email

 This code was written by Charles Childers and is gifted to
 the public domain.
 ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
*/



// define your variables
$host = "irc.freenode.net";
$port=6667;
$nick="_crc_";
$ident="Karere Client";
$readbuffer="";
$realname = "Karere";

$acct = "post@gmail.com";
$pass = "password";
$to = "recieve@gmail.com"

/* Keep the script from timing out */
set_time_limit(0);


/* Open a connection to the irc channel */
$fp = fsockopen($host, $port, $erno, $errstr, 30);


/* Display an error if there is a problem connecting */
if (!$fp)
{
  echo $errstr." (".$errno.")<br />\n";
}
else
{
  /* Success! Now we can log in */
  fwrite($fp, "NICK ".$nick."\r\n");
  fwrite($fp, "USER ".$ident." ".$host." bla :".$realname."\r\n");
  fwrite($fp, "JOIN :#retro\r\n");
  fwrite($fp, "JOIN :#forth\r\n");
  fwrite($fp, "JOIN :#keow\r\n");
  fwrite($fp, "JOIN :##forth\r\n");

  /* And finally we get the server response loop */
  while (!feof($fp))
  {
    /* Read input (up to 2k) from the server and display it */
    $line =  fgets($fp, 2048);
    echo $line;

    /* Now break the input into pieces we can format better */
    preg_match('/:(.*)!/m', $line, $returns);
    $who = str_replace(" ", "", $returns[1]);
    $where = strpos($line, "PRIVMSG");
    $parts = explode(" ", $line);

    /* If we found a PRIVMSG, send it as an email */
    if ($where != FALSE)
    {
      /* A little more processing */
      $line = substr($line, $where);
      $where = strpos($line, ":");
      $msg = substr($line, $where);

      /* And send it on */
      $header = "From: Karere <atua9812g@gmail.com>\r\n"; //optional headerfields
      mail($to, $parts[2], $who . " in " . $parts[2] . " said " . $msg, $header);
    }


    /* Ok, here we log into the gmail server */
    $mbox = imap_open ("{imap.gmail.com:993/imap/ssl/novalidate-cert}INBOX", $acct, $pass) or die("can't connect: " . imap_last_error());

    /* Check for messages */
    $headers = @imap_headers($mbox);
    $numEmails = sizeof($headers);

    /* If we have messages, loop through them */
    if ($numEmails > 0)
    {
      for($i = 1; $i < $numEmails+1; $i++)
      {
        /* Extract the headers */
        $mailHeader = @imap_headerinfo($mbox, $i);
        $from = $mailHeader->fromaddress;
        $subject = strip_tags($mailHeader->subject);
        $body = imap_fetchbody($mbox, $i, "1.1");
        if ($body == "")
          $body = imap_fetchbody($mbox, $i, "1");

        /* Write the message to the proper channel */
        fwrite($fp, "PRIVMSG ".$subject." :".$body."\r\n");

        /* Delete the message */
        imap_delete($mbox, $i);
      }
    }
    /* Disconnect from the gmail server */

    imap_close($mbox);

    /* Respond to PING requests */
    $line = explode(":ping ", $line);
    if ($line[1])
      fwrite($fp, "PONG ".$line[1]."\r\n");
  }
  fclose($fp);
}
