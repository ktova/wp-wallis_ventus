<?php

error_reporting(E_ALL);
ini_set('display_errors', 'On');

require_once __DIR__ . '/vendor/autoload.php';

header("Pragma: no-cache");
header("Cache-Control: private, no-cache, no-store, max-age=0, must-revalidate, proxy-revalidate");
header("Expires: Tue, 04 Sep 2022 05:32:29 GMT");

// Active le REST aux Custom Post Types : http://v2.wp-api.org/extending/custom-content-types/
require("custom-post-types.php");
require("functions-shortcodes.php");
require("functions-smartbusiness.php");

setlocale(LC_TIME, "fr_FR");

// Menus
register_nav_menu("menu-main", "Zone du menu principal");

// Images
add_theme_support("post-thumbnails");

function format_pretty_french_date($ymd_date) {
	setlocale (LC_ALL, 'fr_FR.utf8', 'fra');
	$date = DateTime::createFromFormat("Ymd", $ymd_date, new DateTimeZone("Europe/Zurich"));
	return strftime("%A %d %B", $date->getTimestamp());
}


// Intercepte l'ajout/modification d'une ouverture et renseigne son champ "Titre" avec la date
function wallis_modifier_ouverture( $data , $postarr ) {
	$field_date_guid = "field_5255c6b8fcd4f";

	if (isset($postarr["acf"]) && ($data["post_type"] == "ouverture")) {
		$data["post_title"] = format_pretty_french_date($postarr["acf"][$field_date_guid]);
	}
	return $data;
}
add_filter("wp_insert_post_data", "wallis_modifier_ouverture", 10, 2);


function wallis_membres_etat_cotisation() {
    $etat_cotisation_paid = false;

    if (in_array(get_field("rang"), array("membre_benevole", "membre_actif", "conseiller_municipal"))) {
        return true;
    }

    $current_membre_id = get_the_ID();
	$parent_membre_id = wp_get_post_parent_id($current_membre_id);
    if ($parent_membre_id != false) {
        global $post;
        $post = get_post($parent_membre_id);
        $parent_membre_etat_cotisation = wallis_membres_etat_cotisation();
        $post = get_post($current_membre_id);
        return $parent_membre_etat_cotisation;
    }

    if (have_rows("operations_financieres")) {
        while (have_rows("operations_financieres")) {
            the_row();
            if (get_sub_field("objet") == "cotisation") {
                var_dump(get_sub_field("date"));
                $payement_date = new DateTime(get_sub_field("date"));
                $date_limite_pour_gratuite_semestre = new DateTime("01 July " . $payement_date->format("Y"));

                if ($payement_date < $date_limite_pour_gratuite_semestre) {
                    $date_de_validite = new DateTime("31 December " . $payement_date->format("Y"));
                } else {
                    $date_de_validite = new DateTime("31 December " . $payement_date->format("Y"));
                    $date_de_validite = $date_de_validite->add(new DateInterval("P1Y"));
                }

                if ($date_de_validite > new DateTime()) {
                    $etat_cotisation_paid = true;
                    break;
                }
            }
        }
    }
    
    return $etat_cotisation_paid;
}

// Ajoute une metabox État de la cotisation.
function wallis_cotisation_membre_hook() {
    add_meta_box("wallis_etat_cotisation_membre", "État de la cotisation", "wallis_cotisation_membre_metabox_content", "membre" );
}
add_action("add_meta_boxes", "wallis_cotisation_membre_hook");

function wallis_cotisation_membre_metabox_content($post) {
    $etat_cotisation_paid = wallis_membres_etat_cotisation();
    ?>
    <span class="etat_cotisation">
        <img src="<?php echo get_template_directory_uri(); ?>/images/etat_cotisation-<?php echo (($etat_cotisation_paid) ? "paid" : "unpaid"); ?>.png" width="50" />
    </span>
    <?php
}



// Affiche l'état de la cotisation dans la liste des membres
function wallis_admin_columns_filters($value, $id, $column ) {

    // Opérations financières
    if ($column instanceof ACA_ACF_Column && $column->get_meta_key() == "operations_financieres") {
        $etat_cotisation_paid = wallis_membres_etat_cotisation();
        return 	"<img src=\"" . get_template_directory_uri() . "/images/etat_cotisation-" . (($etat_cotisation_paid) ? "paid" : "unpaid") . ".png\" height=\"16\"/>";
    }

    // Téléphone
    if ($column instanceof ACA_ACF_Column && $column->get_meta_key() == "telephones") {
        $telephones = "";
        $libphonenumber = \libphonenumber\PhoneNumberUtil::getInstance();

        while(have_rows("telephones")) {
            the_row();

            try {
                $phone_object = $libphonenumber->parse(get_sub_field("telephone"), null);
                // $phone_region = $libphonenumber->getRegionCodeForNumber($phone_object);
                $formatted_phone = $libphonenumber->format($phone_object, \libphonenumber\PhoneNumberFormat::INTERNATIONAL);
            } catch (Exception $exception) {
                $formatted_phone = get_sub_field("telephone");
            }

            $telephones .= "<a class=\"wallis-phone-link\" href=\"#\" data-wallis-phone=\"" . urlencode(get_sub_field("telephone")) . "\">" . $formatted_phone  . "</a><br />";
        }

        return $telephones;
    }

    // Adresse
    if ($column instanceof ACA_ACF_Column && $column->get_meta_key() == "adresse") {
        return  "<a href=\"//maps.apple.com/?daddr=" . urlencode($value . ", " . get_field("code_postal")) .  "\">" . $value . "</a>";
    }



    // Sous-champ de type select dans un champ répéteur
    if ($column instanceof ACA_ACF_Column && $column->get_acf_field_option("type") == "repeater") {
        $field = $column->get_meta_key();
        $sub_field_concatenated = "";
        $sub_field_key = $column->get_option("sub_field");

        while(have_rows($field)) {
            the_row();
            $sub_field = get_sub_field_object($sub_field_key);

            if ($sub_field["type"] == "select") {
                $sub_field_concatenated .= $sub_field["choices"][$sub_field["value"]] . "<br />";
            } else {
                reset_rows();
                return $value;
            }
        }
        return $sub_field_concatenated;
    }

    return $value;
}
add_filter("ac/column/value", "wallis_admin_columns_filters", 11, 5 );

function wallis_set_user_display_options(){
    update_user_option( get_current_user_id(), "edit_membre_per_page", 999 );
    update_user_option( get_current_user_id(), "edit_atelier_per_page", 999 );
    update_user_option( get_current_user_id(), "edit_ouverture_per_page", 999 );
}
add_action("init", "wallis_set_user_display_options");


// Téléphone
function wallis_phone_link_onclick() { ?>
    <script type="text/javascript" >
        jQuery(document).ready(function($) {
            $(".wallis-phone-link").on("click", function (event) {
                event.preventDefault();
                console.log(this);

                var data = {
                    "action": "voice_call",
                    "phone": $(this).data("wallis-phone")
                };

                jQuery.post(ajaxurl, data, function(response) {
                    alert("La numérotation a commencé. Vous allez être mis en relation dans quelques instants. Pour annuler, refusez l'appel.");
                });
            });
        });
    </script> <?php
}
add_action("admin_footer", "wallis_phone_link_onclick");

// Intercepte l'ajout/modification d'un membre et renseigne son champ "Titre" pour que, lorsque on l'utilise dans une "Relation" (comme par exemple les Présences aux ouvertures), son nom s'affiche correctement.
function wallis_modifier_membre($data , $postarr) {
	$field_nom_guid = "field_5255bc573d387";
	$field_prenom_guid = "field_5717cf3d5a934";

	if (isset($postarr["acf"]) && ($data["post_type"] == "membre")) {
		$data["post_title"] = $postarr["acf"][$field_prenom_guid] . " " . $postarr["acf"][$field_nom_guid];
	}

	return $data;
}
add_filter("wp_insert_post_data", "wallis_modifier_membre", 10, 2);

// Ajoute les custom fields de façon propre dans le JSON
function json_api_encode_acf($response)  {
    if (isset($response['posts'])) {
        foreach ($response['posts'] as $post) {
            json_api_add_acf($post);
        }
    }
    else if (isset($response['post'])) {
        json_api_add_acf($response['post']);
    }

    return $response;
}
function json_api_add_acf(&$post) {
    $post->acf = get_fields($post->id);
}
add_filter("json_api_encode", "json_api_encode_acf");


function wallis_format_dates($formation = 0, $repeater_field = "dates", $short = false, $year = false) {
    $formation = get_post($formation);

	if (!have_rows($repeater_field, $formation)) {
		return null;
	}

	$datesdays = $datesmonths = $datesmerged = array();

	while(have_rows($repeater_field, $formation)) {
		the_row();
		$date = get_sub_field("date", false);

		$timeinterval = get_sub_field("periode");
		list($starttime, $endtime) = explode("_", $timeinterval);

		$dtstart = new DateTime($date);
		$dtstart->setTime(intval(substr($starttime, 0, 2)), intval(substr($starttime, -2)));
		$dtend = new DateTime($date);
		$dtend->setTime(intval(substr($endtime, 0, 2)), intval(substr($endtime, -2)));
		$timebracket = strftime("%Hh", $dtstart->getTimestamp()) . "-" . strftime("%Hh", $dtend->getTimestamp());
		$dayoftheweek = strftime((!$short) ? "%A" : "%a", $dtstart->getTimestamp());
		$datesdays[] = trim(strftime("%e", $dtstart->getTimestamp()));
        $datesmonths[] = strftime(" %B", $dtstart->getTimestamp());
        $datesyears[] = strftime(" %Y", $dtstart->getTimestamp());
	}

	// Documentation : ¯\_(ツ)_/¯
	if(count($datesmonths) > 1) {
		$dayoftheweek = $dayoftheweek . "s ";

		for($idx = 0; $idx < count($datesmonths); $idx++) {
			if (isset($datesmonths[$idx+1]) && $datesmonths[$idx] == $datesmonths[$idx+1]) {
				$datesmerged[] = $datesdays[$idx];
			} else {
				$datesmerged[] = $datesdays[$idx] . $datesmonths[$idx];
			}
		}
		$firstsentecepart = array_slice($datesmerged, 0, -2);
		$lastsentencepart = array_slice($datesmerged, -2, 2);
		$sentence = $dayoftheweek . implode($firstsentecepart, ", ") . ", " . implode($lastsentencepart, " et ");
	} else {
		$sentence = $dayoftheweek . " " . $datesdays[0] . $datesmonths[0];
	}

    if ($year) {
        $sentence .= implode("-", array_unique($datesyears));
    }
	$sentence .= ", " . $timebracket;

	return utf8_encode($sentence);
}


function get_field_displayable($field) {
    $field_object = get_field_object($field);
    $value = $field_object["value"];
    return $field_object["choices"][$value];
}

function the_enrollment_link() {
	echo "http://bernex.net/inscription-formation/?formation_id=" . get_the_id();
	return true;
}



function wallis_http_accept_router($template) {
	$accept = isset($_SERVER["HTTP_ACCEPT"]) ? $_SERVER["HTTP_ACCEPT"] : "";

	if ($accept == "text/calendar") {
		$template = locate_template(array("vcalendar.php"));
	}

	return $template;
}
add_filter("template_include", "wallis_http_accept_router");

// Attache la feuille CSS spéciale à l'administration
function wallis_custom_wp_admin_style() {
        wp_register_style("wallis_custom_wp_admin_style", get_template_directory_uri() . "/admin-style.css" );
        wp_enqueue_style("wallis_custom_wp_admin_style");
}
add_action("admin_enqueue_scripts", "wallis_custom_wp_admin_style" );



function implode_repeaters($field, $subfield, $separator) {
	$imploded = array();
	if(have_rows($field)) {
		while (have_rows($field)) {
			the_row();
		    $imploded[] = get_sub_field($subfield);
		}
	}

	return implode($separator, $imploded);
}

// Membres
function wallis_membres_export_bulk_action($bulk_actions) {
    $bulk_actions["export_csv"] = __("Exporter en CSV", "export_csv");
    return $bulk_actions;
}
add_filter("bulk_actions-edit-membre", "wallis_membres_export_bulk_action");

function wallis_membres_bulk_actions_handle($redirect_to, $doaction, $post_ids) {
	setlocale(LC_TIME, "fr_FR");
	header("Content-Type: text/csv; charset=utf-8");
	header("Content-Disposition: attachment; filename=membres_" . date("YmdHi") . ".csv");

	$args = array (
		"post_type"	=> array("membre"),
		"post__in" => $post_ids,
		"nopaging" => true,
		"meta_key" => "nom",
		"orderby" => "meta_value",
		"order" => "ASC",
        'post_status' => array('publish', 'pending', 'inherit', 'trash')
    );

	$query = new WP_Query($args);

	echo "\xEF\xBB\xBF";
	echo "\"sep=,\"\n";
	echo "Numéro de membre,Politesse,Prénom,Nom,Courriel,Téléphone,Adresse,Commune,Cotisation payée\n";

	if ($query->have_posts() ) {
		while ($query->have_posts()) {
			$query->the_post();
			$courriels = implode_repeaters("courriels", "courriel", ", ");
			$telephones = implode_repeaters("telephones", "telephone", ", ");

			$politesse_field = get_field_object("politesse");
			$politesse_value = $politesse_field["value"];
			$politesse = "";
			if ($politesse_value) { $politesse = $politesse_field["choices"][$politesse_value]; }

			$code_postal_field = get_field_object("code_postal");
			$code_postal_value = $code_postal_field["value"];
			$code_postal = "";
			if ($code_postal_value) { $code_postal = $code_postal_field["choices"][$code_postal_value]; }

			echo "\"" . get_the_ID() . "\"";
			echo ",\"" . $politesse . "\"";
			echo ",\"" . trim(get_field("prenom")) . "\"";
			echo ",\"" . trim(get_field("nom")) . "\"";
			echo ",\"" . $courriels . "\"";
			echo ",\"" . $telephones . "\"";
			echo ",\"" . trim(wp_strip_all_tags(get_field("adresse"))) . "\"";
			echo ",\"" . $code_postal . "\"";
			echo ",\"" . ((wallis_membres_etat_cotisation()) ? "Oui" : "") . "\"\n";
		}
	}

	wp_reset_postdata();
}
add_filter("handle_bulk_actions-edit-membre", "wallis_membres_bulk_actions_handle", 10, 3);


// Démonstrations
function wallis_demonstration_export_bulk_action($bulk_actions) {
    $bulk_actions["export_csv"] = __("Exporter en CSV pour InDesign", "export_csv");
    $bulk_actions["renumber"] = __("Renuméroter d'après les dates", "renumber");
    return $bulk_actions;
}
add_filter("bulk_actions-edit-demonstration", "wallis_demonstration_export_bulk_action");

function wallis_demonstration_bulk_actions_handle($redirect_to, $doaction, $post_ids) {
    switch ($doaction) {
        case "export_csv":
            return wallis_demonstration_export_csv_for_indesign($redirect_to, $post_ids);
            break;

        case "renumber":
            return wallis_demonstration_renumber($redirect_to, $post_ids);
            break;

        default:
            break;
    }
}
add_filter("handle_bulk_actions-edit-demonstration", "wallis_demonstration_bulk_actions_handle", 10, 3);

function wallis_demonstration_renumber($redirect_to, $post_ids) {
    setlocale(LC_TIME, "fr_FR");

    $args = array (
        "post_type"	=> array("demonstration"),
        "post__in" => $post_ids,
        "nopaging" => true,
        "meta_query" => array("date" => array("key" => "dates_0_date", "compare" => "EXISTS"), "periode" => array("key" => "dates_0_periode", "compare" => "EXISTS")),
        "orderby" => array("date" => "ASC", "periode" => "ASC"),
        "post_status" => array("pending")
    );

    $query = new WP_Query($args);

    $demonstration_number = 1;
    if ($query->have_posts() ) {
        while ($query->have_posts()) {
            $query->the_post();

            $post = get_post(get_the_ID());

            $new_demonstration_code = "D" . sprintf('%02d', $demonstration_number);
            update_field("code", $new_demonstration_code);
            $demonstration_number += 1;

            // Réinitialise le permalien
            $post->post_name = "";
            wp_update_post($post);
        }
    }

    var_dump($redirect_to);

    return $redirect_to;
}

function wallis_demonstration_export_csv_for_indesign($redirect_to, $post_ids) {
    ob_start();
    setlocale(LC_TIME, "fr_FR");
    header("Content-Type: text/csv; charset=macintosh");
    header("Content-Disposition: attachment; filename=demonstrations_" . date("YmdHi") . ".csv");

    $args = array (
        "post_type"	=> array("demonstration"),
        "post__in" => $post_ids,
        "nopaging" => true,
        "meta_key" => "code",
        "orderby" => "meta_value",
        "order" => "ASC"
    );

    $query = new WP_Query($args);

    echo "Code,Titre,Date,Période,Formateur\n";

    if ($query->have_posts() ) {
        while ($query->have_posts()) {
            $query->the_post();

            echo "\"" . get_field("code") . "\"";
            echo ",\"" . html_entity_decode(get_the_title(), ENT_QUOTES) . "\"";
            echo ",\"" . wallis_format_dates(0, "dates", false, true) . "\"";
            echo ",\"" . get_field("formateur")->post_title . "\"\n";

        }
    }

    wp_reset_postdata();

    // InDesign sur Mac ne fonctionne bien qu'avec l'encodage Macintosh
    echo iconv("UTF-8", "macintosh", ob_get_clean());
}


// Ateliers
function wallis_atelier_bulk_actions($bulk_actions) {
    $bulk_actions["export_csv"] = __("Exporter en CSV pour InDesign", "export_csv");
    $bulk_actions["renumber"] = __("Renuméroter d'après les dates", "renumber");
    return $bulk_actions;
}
add_filter("bulk_actions-edit-atelier", "wallis_atelier_bulk_actions");

function wallis_atelier_bulk_actions_handle($redirect_to, $doaction, $post_ids) {
    switch ($doaction) {
        case "export_csv":
            return wallis_atelier_export_csv_for_indesign($post_ids);
            break;

        case "renumber":
            return wallis_atelier_renumber($redirect_to, $post_ids);
            break;

        default:
            break;
    }
}
add_filter("handle_bulk_actions-edit-atelier", "wallis_atelier_bulk_actions_handle", 10, 3);

function wallis_atelier_renumber($redirect_to, $post_ids) {
    setlocale(LC_TIME, "fr_FR");

    $args = array (
        "post_type"	=> array("atelier"),
        "post__in" => $post_ids,
        "nopaging" => true,
        "meta_query" => array("date" => array("key" => "dates_0_date", "compare" => "EXISTS"), "periode" => array("key" => "dates_0_periode", "compare" => "EXISTS")),
        "orderby" => array("date" => "ASC", "periode" => "ASC"),
        "post_status" => array("pending")
    );

    $query = new WP_Query($args);

    $atelier_number = 1;
    if ($query->have_posts() ) {
        while ($query->have_posts()) {
            $query->the_post();

            $post = get_post(get_the_ID());

            $new_atelier_code = "A" . sprintf('%02d', $atelier_number);
            update_field("code", $new_atelier_code);
            $atelier_number += 1;

            // Réinitialise le permalien
            $post->post_name = "";
            wp_update_post($post);
        }
    }

    return $redirect_to;
}

function wallis_atelier_export_csv_for_indesign($post_ids) {
    ob_start();
    setlocale(LC_TIME, "fr_FR");
    header("Content-Type: text/csv; charset=macintosh");
    header("Content-Disposition: attachment; filename=ateliers_" . date("YmdHi") . ".csv");

    $args = array (
        "post_type"	=> array("atelier"),
        "post__in" => $post_ids,
        "nopaging" => true,
        "meta_key" => "code",
        "orderby" => "meta_value",
        "order" => "ASC"
    );

    $query = new WP_Query($args);


    echo "Code,Titre,Système,Formateur,Date\n";

    if ($query->have_posts() ) {
        while ($query->have_posts()) {
            $query->the_post();

            echo "\"" . get_field("code") . "\"";
            echo ",\"" . html_entity_decode(get_the_title(), ENT_QUOTES) . "\"";

            $atelier_systeme_ids = wp_get_post_terms(get_the_ID(), "atelier_systeme" );
            $atelier_systeme_list = implode(' ', wp_list_pluck($atelier_systeme_ids,"slug"));

            // Mapping vers la police icons8
            $atelier_systeme_slugs = array("android" => "B", "windows" => "C", "ios" => "A", "macos" => "A");
            $atelier_systeme_list = str_replace(array_keys($atelier_systeme_slugs), array_values($atelier_systeme_slugs), $atelier_systeme_list);

            // Enlève les lettres répétées
            $atelier_systeme_list = count_chars($atelier_systeme_list, 3);

            // Ajoute un espace en chaque lettre
            $atelier_systeme_list = trim(implode(" ", str_split($atelier_systeme_list)));

            echo ",\"" . $atelier_systeme_list . "\"";
            echo ",\"" . get_field("formateur")->post_title . "\"";
            echo ",\"" . wallis_format_dates(0, "dates", false, true) . "\"\n";
        }
    }

    wp_reset_postdata();

    // InDesign sur Mac ne fonctionne bien qu'avec l'encodage Macintosh
    echo iconv("UTF-8", "macintosh", ob_get_clean());
}



function modify_list_row_actions( $actions, $post ) {
    if (in_array($post->post_type, ["membre", "demonstration", "atelier"])) {
	    $actions = [];
    }

    return $actions;
}
add_filter( 'post_row_actions', 'modify_list_row_actions', 10, 2);

// Ajoute le numéro de téléphone dans le profil utilisateur
function wallis_user_profile_phone_number($fields) {
    $fields["phone"] = __( "Téléphone" );

    return $fields;

}
add_filter('user_contactmethods', 'wallis_user_profile_phone_number');






function wallis_templating($template, $content) {
    $engine = new \Handlebars\Handlebars;
    $rendered = $engine->render($template, $content);

    return $rendered;
}

function wallis_wp_mail_html_wrap($vars) {
    $template = file_get_contents(__DIR__ . "/emails/standard/content.html");
    $vars["message"] = wallis_templating($template, array("content" => $vars["message"]));
    return $vars;
}
add_filter("wp_mail", "wallis_wp_mail_html_wrap");

function wallis_init_phpmailer($phpmailer) {
    $body = $phpmailer->Body;

    $replacements = array();

    $regex = '/src="(.*?\.png|.*?\.jpg|.*?\.gif)"/';
    while (preg_match($regex, $body, $matches, PREG_OFFSET_CAPTURE)) {
        $match = $matches[1];
        $start = $match[1];
        $url = $match[0];

        $body = substr_replace($body, "cid:" . md5($url), $start, strlen($url));
        $replacements[md5($url)] = get_stylesheet_directory() . "/emails/standard/" . basename($url);
    }

    $regex = '/background="(.*?\.png|.*?\.jpg|.*?\.gif)"/';
    while (preg_match($regex, $body, $matches, PREG_OFFSET_CAPTURE)) {
        $match = $matches[1];
        $start = $match[1];
        $url = $match[0];

        $body = substr_replace($body, "cid:" . md5($url), $start, strlen($url));
        $replacements[md5($url)] = get_stylesheet_directory() . "/emails/standard/" . basename($url);
    }

    foreach ($replacements as $eachreplacementkey => $eachreplacementvalue) {
        $phpmailer->AddEmbeddedImage($eachreplacementvalue, $eachreplacementkey);
    }

    $phpmailer->Body = $body;
    $phpmailer->AltBody = strip_tags($phpmailer->Body);
}
add_action('phpmailer_init', 'wallis_init_phpmailer');


acf_add_options_page(array(
    'page_title' => 'Notifications par courriel',
    'menu_title' => 'Notifications',
    'parent_slug' => 'options-general.php',
));

function wallis_membre_with_email($email) {
    // Cette fonction ne trouvera peut-être pas le membre s'il a plus de deux adresses courriel enregistré dans sa fiche.
    $args = array(
        "posts_per_page"=> -1,
        "post_type"		=> "membre",
        "meta_query"	=> array(
            "relation"		=> "OR",
            array(
                "key"		=> "courriels_0_courriel",
                "value"		=> $email,
                "compare"	=> "="
            ),
            array(
                "key"		=> "courriels_1_courriel",
                "value"		=> $email,
                "compare"	=> "="
            ),
        )
    );

    $query = new WP_Query($args);

    if ($query->have_posts()) {
        $query->the_post();
        return get_the_ID();
    }

    wp_reset_postdata();

    return null;
}



function wallis_show_pending($query) {
    if (is_user_logged_in() && !is_admin()) {
        $query->set("post_status", array("pending", "publish"));
    }
}
add_action("pre_get_posts", "wallis_show_pending");


// Cherche la première formation qui partage le même titre et récupère son contenu
function wallis_reuse_cours_ateliers_description_when_empty($content) {
    if ((strlen($content) == 0) && in_array(get_post_type(), array("atelier", "cours"))) {
        $args = array(
                "post_type" => get_post_type(),
                "posts_per_page"=> 1,
                "title"	=> get_the_title(),
                "meta_query" => array("code" => array("key" => "code", "compare" => "EXISTS")),
                "orderby" => array("code" => "ASC"),
                "post_status" => array("publish", "pending")
        );

        $query = new WP_Query($args);

        $query->the_post();
        $other_content = apply_filters("the_content", get_the_content());

        wp_reset_postdata();

        return $other_content;
    } else {
        return $content;
    }
}
add_filter("the_content", "wallis_reuse_cours_ateliers_description_when_empty");


function wallis_indesign_xml_export() {
    global $wp_rewrite;

    $wp_rewrite->add_external_rule("indesign_xml_export\\.xml", trim(get_stylesheet_directory(), "/") . "/indesign-xml-export.php");
}
add_action("generate_rewrite_rules", "wallis_indesign_xml_export");


function wallis_hide_membres_row_actions($actions, $post)
{
	if ("membre" == $post->post_type) {
		return array();
	}
	return $actions;
}
add_filter("page_row_actions", "wallis_hide_membres_row_actions", 10, 2);
