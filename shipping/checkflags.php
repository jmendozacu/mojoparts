<?php


$mBox = imap_open('{imap.gmail.com:993/imap/ssl/novalidate-cert}@PFGTracking','admin@mojoparts.com','Lo7jDEi8');

$messages = imap_search($mBox, 'BODY "993317911622810"');
foreach($messages as $mid){
	print_r(imap_fetch_overview($mBox, $mid));
}

