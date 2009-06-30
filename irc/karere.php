<?
/*
 ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
 Karere
 ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
 This is one of a family of applications providing support
 for realtime communications (e.g., IM) to email users.

 The code in this file has been written by Charles Childers
 and is gifted to the public domain.
 ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
*/


/*
 ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
 Configuration
 ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
*/

$nick   = "";        /* Your nick for IRC                  */
$real   = "";        /* Your real name for IRC             */
$sendTo = "";        /* Your email address. Messages will  */
                     /* be sent to this.                   */
$reader = "";        /* Email address for Karere to check  */
                     /* Email sent to this address will be */
                     /* sent to the IRC server.            */
$readerPass = "";    /* Password for the above address     */
$channel    = "";    /* The channel you want to join.      */


/*
 ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
 Optional Configuration
 The defaults should be ok for most people
 ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
*/

$host = "irc.freenode.net";
$port=6667;
$ident="Karere Client";


/*
 ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
 You shouldn't need to touch anything below this point
 ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
*/
$readbuffer="";
$send = "yes";
$nope = "false";
$start = time();
set_time_limit(0);

/* Connect to the IRC server */
$fp = fsockopen($host, $port, $erno, $errstr, 30);


/* Report an error and die if there is a problem */
if (!$fp)
{
  echo $errstr." (".$errno.")<br />\n";
  exit(1);
}

/* No problems? Log into the server and join channels */
fwrite($fp, "NICK ".$nick."\r\n");
fwrite($fp, "USER ".$ident." ".$host." bla :".$real."\r\n");
fwrite($fp, "JOIN :".$channel."\r\n");

/* Set the socket to non-blocking. This improves performance. */
stream_set_blocking($fp, 0);


/* Now we enter the actual client. */
while (!feof($fp))
{
  /* Get the current number of seconds since we started running. */
  /* We'll use this later, to help keep from straining the IMAP server */
  $diff = time() - $start;

  /* Read a line of up to 1k from the server */
  $line = fgets($fp, 1024);

  /* Since we don't block for input, the read could return an empty */
  /* string. We only need to process input if we actually read anything. */
  if ($line != "")
  {
    echo $line;  /* For logging purposes */

    /* We use a few techniques here to break the input into something more */
    /* managable. */
    preg_match('/:(.*)!/m', $line, $returns);
    $who = str_replace(" ", "", $returns[1]);
    $where = strpos($line, "PRIVMSG");
    $parts = explode(" ", $line);

    /* If the string contains PRIVMSG, we need to reformat and send it to */
    /* the user. */
    if ($where != FALSE)
    {
      $line = substr($line, $where);
      $where = strpos($line, ":");
      $msg = substr($line, $where);
      $header = "From: Karere <".$reader.">\r\n";
      if ($send == "yes")
        mail($sendTo, $parts[2], $who . " in " . $parts[2] . " said " . $msg, $header);
    }

    /* The server will send PING requests on occasion.   */
    /* We respond to those here to ensure that we aren't */
    /* kicked off the server.  */
    $line = explode(":ping ", $line);
    if ($line[1])
    {
      fwrite($fp, "PONG ".$line[1]."\r\n");
    }
  }

  /* Back to that time check. Every 60 seconds we check for messages from our user. */
  if ($diff % 60 == 0)
  {
    $mbox = imap_open ("{imap.gmail.com:993/imap/ssl/novalidate-cert}INBOX", $reader, $readerPass) or $nope = "true";

    /* If we couldn't connect, report the error, otherwise check for messages. */
    if ($nope == "true")
    {
      echo "Error: " . imap_last_error() . "\n";
      $nope = "false";
    }
    else
    {
      echo "Check for messages...\n";
      $headers = @imap_headers($mbox);
      $numEmails = sizeof($headers);

      /* If we have messages, loop through them. */
      if ($numEmails > 0)
      {
        echo "Sending messages...\n";
        for($i = 1; $i < $numEmails+1; $i++)
        {
          $mailHeader = @imap_headerinfo($mbox, $i);
          $from = $mailHeader->fromaddress;
          $subject = strip_tags($mailHeader->subject);

          /* We extract part 1 or 1.1 of the message to get plain text. */
          $body = imap_fetchbody($mbox, $i, "1.1");
          if ($body == "")
            $body = imap_fetchbody($mbox, $i, "1");

          /* And convert end of line to spaces. */
          $body = ereg_replace("/\n\r|\r\n|\n|\r/", " ", $body);

          /* Send the message to the channel */
          echo $subject . "\n" . $body . "\n";

          if ($subject != "@off" && $subject != "&on")
            fwrite($fp, "PRIVMSG ".$subject." :".$body."\r\n");
          if ($subject == "@off")
          {
            $send = "no";
          }
          if ($subject == "@on")
          {
            $send = "yes";
          }
          imap_delete($mbox, $i);
        }
      }
      imap_close($mbox);

      /* This makes us sleep a bit after sending messages. It prevents multiple logins */
      /* to the IMAP server and helps us keep CPU load down too. */
      sleep(1);
    }
  }
  /* This forces us to sleep a bit to keep CPU load down. */
  sleep(1);
}
fclose($fp);
?>
