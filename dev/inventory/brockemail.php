<?php
include 'config.php';
error_reporting(E_ALL);
require_once dirname(__FILE__).'/excel_reader2.php';

$mBox = imap_open('{imap.gmail.com:993/imap/ssl/novalidate-cert}INBOX', $email_user, $email_pwd);
if ($mBox === false) throw new Exception('Unable to connect to mailbox. '.imap_last_error());

$messages = imap_search($mBox, 'UNSEEN SUBJECT "'.'Brock Supply Stock
Availability Report'.'"');

if($messages == false) echo "not found :(".PHP_EOL;
else {
   foreach ($messages as $muid) {
        $overview = imap_fetch_overview($mBox,$muid,0);
        $message = imap_fetchbody($mBox,$muid,1);
        echo $message;
    }
}

?>
