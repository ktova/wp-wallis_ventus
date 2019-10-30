<?php

$periodes = array();
$periodes_object = get_field_object("periodes");
foreach ($periodes_object["value"] as $periode) {
    $periodes[] = $periodes_object["choices"][$periode];
}

?>
<a class="opening-link w-inline-block" href="#">
    <div class="opening-container">
        <div class="left opening-badge"><?php the_title(); ?></div>
        <div class="opening-badge right"><?php echo implode("<br>", $periodes); ?></div>
    </div>
</a>