<?php

$periodes = get_field("periodes");
$date = get_field("date", false, false);
		
foreach($periodes as $eachperiode) {
	list($starttime, $endtime) = explode("_", $eachperiode);
	
	$dtstart = new DateTime($date);
	$dtstart->setTime(intval(substr($starttime, 0, 2)), intval(substr($starttime, -2)));
	$dtend = new DateTime($date);
	$dtend->setTime(intval(substr($endtime, 0, 2)), intval(substr($endtime, -2)));
?>
BEGIN:VEVENT
DTSTAMP:<?php echo date("Ymd\THis"); echo "\n"; ?>
DTSTART:<?php echo $dtstart->format("Ymd\THis"); echo "\n"; ?>
DTEND:<?php echo $dtend->format("Ymd\THis"); echo "\n"; ?>
X-APPLE-TRAVEL-ADVISORY-BEHAVIOR:AUTOMATIC
LOCATION:Association Bernex.net\nChemin du Signal 21\, 1233 Bernex\, Suisse
X-APPLE-STRUCTURED-LOCATION;VALUE=URI;X-APPLE-RADIUS=13.51679061164669;X-TITLE="Association Bernex.net\nChemin du Signal 21, 1233 Bernex, Suisse":geo:46.173425,6.071462
UID:<?php echo md5(the_id() + rand() * 1000); ?>@bernex.net
SUMMARY:Ouverture
DESCRIPTION:
END:VEVENT
<?php
	}
?>