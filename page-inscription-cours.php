<?php

require_once __DIR__ . '/vendor/autoload.php';

function wallis_recaptcha_script()
{
    echo "<script src='https://www.google.com/recaptcha/api.js'></script>\n";
}
add_action('wp_head', 'wallis_recaptcha_script');

function wallis_templating($template, $content)
{
    $engine = new \Handlebars\Handlebars;
    $rendered = $engine->render($template, $content);

    return $rendered;
}

function wallis_membre_with_email($email)
{
    // TODO: Cette fonction ne trouvera peut-être pas le membre s'il a plus de deux adresses courriel enregistré dans sa fiche.
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

$member_not_found = false;

if (isset($_POST["confirm"])) {
    $formation = get_post(intval($_POST["formation_id"]));
    $courriel = sanitize_text_field($_POST["email"]);
    $member_id = wallis_membre_with_email($courriel);

    if (count(get_field("inscriptions", $formation)) >= 6) {
    } elseif ($member_id !== null) {
        $inscrits = get_field("inscriptions", $formation);
        if (!is_array($inscrits)) {
            $inscrits = array();
        }
        array_push($inscrits, get_post($member_id));
        update_field("inscriptions", $inscrits, $formation);

        $prenom = get_field("prenom", $member_id);
        $nom = get_field("nom", $member_id);

        // Envoi du courriel au membre
        $content_member_template = get_field("inscription_cours_atelier", "options");
        $content_member = wallis_templating($content_member_template, array(
            "titre" => get_the_title($formation),
            "dates" => get_the_date("d/m/Y", $formation),
        ));
        wp_mail($courriel, "Inscription à une formation", $content_member, array("From: Bernex.net <info@bernex.net>"));

        // Envoi du courriel au secrétariat
        $content_admin_template = get_field("admin_inscription_cours_atelier", "options");
        $content_admin = wallis_templating($content_admin_template, array(
            "membre_id" => $member_id,
            "prenom" => $prenom,
            "nom" => $nom,
            "courriel" => $courriel,
            "code" => get_field("code", $formation),
            "titre" => get_the_title($formation),
            "edit-post-link" => get_edit_post_link($formation),
        ));
        wp_mail("inscriptions@bernex.net", "Inscription à " . get_field("code", $formation), $content_admin, array("From: Bernex.net <info@bernex.net>"));
		wp_mail("teva@2gik.ch", "Inscription à " . get_field("code", $formation), $content_admin, array("From: Bernex.net <info@bernex.net>"));


        wp_redirect("http://bernex.net/merci-de-votre-inscription/");
        exit;
    } else {
        $member_not_found = true;
    }
}

$formation_id = "";

if (!isset($_GET["formation_id"])) {
    if (!isset($_POST["formation_id"])) {
        wp_redirect("http://bernex.net/cours-ateliers/");
        echo "La formation est introuvable.";
        exit();
    } else {
        $formation = get_post(intval($_POST["formation_id"]));
        $formation_id = $_POST["formation_id"];
        if (!in_array(get_post_type($formation), array("cours", "atelier"))) {
            wp_redirect("http://bernex.net/cours-ateliers/");
            echo "La formation est introuvable.";
            exit();
        }
    }
} else {
    $formation = get_post(intval($_GET["formation_id"]));
    $formation_id = $_GET["formation_id"];
    if (!in_array(get_post_type($formation), array("cours", "atelier"))) {
        wp_redirect("http://bernex.net/cours-ateliers/");
        echo "La formation est introuvable.";
        exit();
    }
}

if (isset($_POST["action"]) && ($_POST["action"] == "check-email")) {
    $courriel = sanitize_text_field($_POST["email"]);

    echo json_encode(array("shouldShowExtraFields" => (wallis_membre_with_email($courriel) === null)));
    exit;
}

get_header();
?>
    <style>
        .extra-fields, .confirm-buttons {
            display: block;
        }
		.half, .in-page-block-wrapper {
			background-color: #fff !important;
            padding:30px;
		}
    </style>
    <div class="half in-page-block-wrapper">
        <h1><?php echo get_the_title(); ?></h1>
        <h2><?php echo get_field("code", $formation);echo '&nbsp'; echo get_the_title($formation); ?></h2>
        <span class="lesson-subtitle"><h4 style="color:#001d6e"><?php echo get_the_date("d/m/Y H:i", $formation); ?></h4></span>
        <p>&nbsp;</p>
		<p><?php echo get_field("infos", $formation); ?></p>
        <p><?php the_post(); the_content(); ?></p>
        <div>
            <?php if ($member_not_found) {?>
            <div>
                <strong>Veuillez nous excuser, mais ce courriel n'est relié à aucun membre. Merci de bien vouloir réessayer avec un courriel différent, et de nous contacter si vous avez besoin d'aide.</strong>
            </div>
            <?php }?>
            <?php if (count(get_field("inscriptions", $formation)) >= 6) {?>
            <div>
                <strong>Les inscriptions à ce cours sont fermées (le nombre de personnes pouvant être inscrites a été atteint)</strong>
            </div>
            <?php } else { ?>
            <form action="/inscription-cours/?submit" id="enrollment" name="enrollment-form" method="post">
                <input type="hidden" name="formation_id" value="<?php echo $formation_id; ?>"/>

                <label for="email">Courriel</label>
                <input class="w-input" id="email" maxlength="256" name="email" required="required" type="email" />
                <br />

                <div class="confirm-buttons">
                    <div class="g-recaptcha" data-sitekey="6Leq1bcUAAAAAJQxpA4vOD0m91FB91ET_-5uOziX" data-callback="enableFormSubmission"></div>
                    <p>&nbsp;</p>
                    <input class="book-course w-button" type="submit" name="confirm" id="confirm" value="Confirmer l&#39;inscription" style="display: block;"/>
                </div>
            </form>
            <?php } ?>
        </div>
        <p>&nbsp;</p>
        <p>&nbsp;</p>
    </div>
    <script>

        function enableFormSubmission() {
            $("#confirm").css("display", "block");
        }

    </script>
<?php get_footer(); ?>