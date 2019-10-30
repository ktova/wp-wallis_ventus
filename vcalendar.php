<?php
header("Content-Type: text/calendar; charset=utf-8");
header("Content-Disposition: inline; filename=agenda.ics");
?>
BEGIN:VCALENDAR
VERSION:2.0
PRODID:Bernex.net
METHOD:REQUEST
X-WR-CALNAME:Bernex.net
X-APPLE-CALENDAR-COLOR:#001C72
X-WR-TIMEZONE:Europe/Zurich
CALSCALE:GREGORIAN
<?php
	
$args = array (
	"post_type"	=> array("atelier", "ouverture", "demonstration", "conference"),
	"nopaging" => true,
    "post_status" => array("pending", "publish"),
);

$query = new WP_Query($args);

if ($query->have_posts() ) {
	while ($query->have_posts()) {
		$query->the_post();
		get_template_part("vevent", get_post_type($post));
	}
}

wp_reset_postdata();
?>
END:VCALENDAR