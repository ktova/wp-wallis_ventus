<?php

while(have_rows("dates")) {
	the_row();
	$date = get_sub_field("date", false, false);
	$timeinterval = get_sub_field("periode");
	list($starttime, $endtime) = explode("_", $timeinterval);
	
	
	$dtstart = new DateTime($date);
	$dtstart->setTime(intval(substr($starttime, 0, 2)), intval(substr($starttime, -2)));
	$dtend = new DateTime($date);
	$dtend->setTime(intval(substr($endtime, 0, 2)), intval(substr($endtime, -2)));
	
	$inscriptions = get_field("inscriptions");
	$liste_inscriptions = "";
	if (is_array($inscriptions)) {		
		foreach ($inscriptions as $inscrit) {
			$liste_inscriptions .= "– " . $inscrit->post_title . "\\n";
		}
	} else {
		$liste_inscriptions = "Aucune inscription.\n";
	}

?>
BEGIN:VEVENT
DTSTAMP:<?php echo date("Ymd\THis"); echo "\n"; ?>
DTSTART:<?php echo $dtstart->format("Ymd\THis"); echo "\n"; ?>
DTEND:<?php echo $dtend->format("Ymd\THis"); echo "\n"; ?>
X-APPLE-TRAVEL-ADVISORY-BEHAVIOR:AUTOMATIC
LOCATION:Association Bernex.net\nChemin du Signal 21\, 1233 Bernex\, Suisse
X-APPLE-STRUCTURED-LOCATION;VALUE=URI;X-APPLE-RADIUS=13.51679061164669;X-TITLE="Association Bernex.net\nChemin du Signal 21, 1233 Bernex, Suisse":geo:46.173425,6.071462
UID:<?php echo md5(the_id() + rand() * 1000); ?>@bernex.net
SUMMARY:<?php echo get_field("code"); ?> : <?php echo html_entity_decode(get_the_title(), ENT_QUOTES); ?> (<?php $formateur = get_field("formateur"); echo $formateur->post_title; ?>)
DESCRIPTION:<?php echo $liste_inscriptions; ?> 
END:VEVENT
<?php
	}
?>
