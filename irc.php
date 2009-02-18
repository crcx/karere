<?
/* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
   Karere
   ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
   IRC-via-email

   This code was written by Charles Childers and is gifted to
   the public domain.
   ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */


/* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
   Configuration
   ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */
$message = "/path/to/message.txt";
$to      = "address@domain";

$host = "irc.freenode.net";
$port=6667;
$nick="karere";
$ident="Karere";
$chan="#karere";
$readbuffer="";
$realname = "Karere IRC-via-email";



/* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
   Code begins
   ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */
$last = "";
$now = "";

// open a socket connection to the IRC server
$fp = fsockopen($host, $port, $erno, $errstr, 30);

// print the error if ther eis no connection
if (!$fp)
{
  echo $errstr." (".$errno.")<br />\n";
}
else
{
  // write data through the socket to join the channel
  fwrite($fp, "NICK ".$nick."\r\n");
  fwrite($fp, "USER ".$ident." ".$host." bla :".$realname."\r\n");
  fwrite($fp, "JOIN :".$chan."\r\n");

  // loop through each line to look for ping
  while (!feof($fp))
  {
    $line =  fgets($fp, 512);
    preg_match('/:(.*)!/m', $line, $returns);
    $who = str_replace(" ", "", $returns[1]);
    $where = strpos($line, "PRIVMSG");

    if ($where != FALSE)
    {
      $line = substr($line, $where);
      $where = strpos($line, ":");
      $msg = substr($line, $where);
      mail($to, $chan, $who . " " . $msg, $header);
    }

    $last = $now;
    $now = file_get_contents($message);
    if ($now != $last)
    {
      fwrite($fp, "PRIVMSG ".$chan." :".$now."\r\n");
    }

    /* Respond to PING requests */
    $line = explode(":ping ", $line);
    if ($line[1])
    {
      fwrite($fp, "PONG ".$line[1]."\r\n");
    }

  }
  fclose($fp);
}
?>
