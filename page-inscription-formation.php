<?php


function wallis_recaptcha_script() {
    echo "<script src='https://www.google.com/recaptcha/api.js'></script>\n";
}
add_action('wp_head', 'wallis_recaptcha_script');


if (!isset($_GET["formation_id"])) {
    wp_redirect("http://bernex.net/cours-ateliers/");
    exit();
} else {
    $formation = get_post(intval($_GET["formation_id"]));

    if (!in_array(get_post_type($formation), array("cours", "atelier"))) {
        wp_redirect("http://bernex.net/cours-ateliers/");
        exit();
    }
}

if (isset($_POST["action"]) && ($_POST["action"] == "check-email")) {
    $courriel = sanitize_text_field($_POST["email"]);

    echo json_encode(array("shouldShowExtraFields" => (wallis_membre_with_email($courriel) === null)));
    exit;
}

if (isset($_POST["confirm"])) {
    $formation = get_post(intval($_GET["formation_id"]));
    $courriel = sanitize_text_field($_POST["email"]);
    $member_id = wallis_membre_with_email($courriel);

    $prenom = sanitize_text_field($_POST["firstname"]);
    $nom = sanitize_text_field($_POST["lastname"]);

    if ($member_id !== null) {
        $inscrits = get_field("inscriptions", $formation);
        if (!is_array($inscrits)) { $inscrits = array(); }
        array_push($inscrits, get_post($member_id));
        update_field("inscriptions", $inscrits, $formation);

        $prenom = get_field("prenom", $member_id);
        $nom = get_field("nom", $member_id);
    }

    // Envoi du courriel au membre
    $content_member_template = get_field("inscription_cours_atelier", "options");
    $content_member = wallis_templating($content_member_template, array(
        "titre" => get_the_title($formation),
        "dates" => wallis_format_dates($formation),
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
    wp_mail("inscription@bernex.net", "Inscription à " . get_field("code", $formation), $content_admin, array("From: Bernex.net <info@bernex.net>"));

    wp_redirect("http://bernex.net/merci-de-votre-inscription/");
    exit;
}

get_header();
?>
    <style>
        .extra-fields, .confirm-buttons {
            display: none;
        }
    </style>
    <div class="half in-page-block-wrapper">
        <h1><?php echo get_the_title(); ?></h1>
        <p>&nbsp;</p>
        <h2><?php echo get_the_title($formation); ?></h2>
        <span class="lesson-subtitle"><?php echo wallis_format_dates($formation); ?></span>
        <p>&nbsp;</p>
        <p>&nbsp;</p>
        <p><?php the_post(); the_content(); ?></p>
        <div>
            <form action="/404" id="enrollment" name="enrollment-form" method="post">
                <input type="hidden" name="formation_id" value="<?php echo $_GET["formation_id"]; ?>"/>

                <label for="email">Courriel</label>
                <input class="w-input" id="email" maxlength="256" name="email" required="required" type="email" />
                <div class="extra-fields">
                    <label for="firstname">Prénom</label>
                    <input autofocus="autofocus" class="w-input" id="firstname" maxlength="256" name="firstname" required="required" type="text" />
                    <label for="lastname">Nom</label>
                    <input class="w-input" id="lastname" maxlength="256" name="lastname" required="required" type="text" />
                </div>
                <br />

                <div class="confirm-buttons">
                    <div class="g-recaptcha" data-sitekey="6LfJ6hoUAAAAAGwAYW4y5vZ8QeZ8_YtGfKOt6bBn" data-callback="enableFormSubmission"></div>
                    <input class="book-course w-button" type="submit" name="confirm" id="confirm" value="Confirmer l&#39;inscription" style="display: none;"/>
                </div>
            </form>
        </div>
        <p>&nbsp;</p>
        <p>&nbsp;</p>
    </div>
    <script>

        var keypressTimeout = null;

        $(document).ready(function() {
            $("#email").on("change keypress keyup", function() { console.log("changed"); clearTimeout(keypressTimeout); keypressTimeout = setTimeout(checkForKnownEMails, 1000); });
        });

        function checkForKnownEMails() {
            var regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (regex.test($("#email").val())) {
                $.post("", {"action": "check-email", "email": $("#email").val()}, function(data, status) {
                    console.log(data);
                    if (data.shouldShowExtraFields === true) {
                        $(".extra-fields").css("display", "block");
                    } else {
                        $("#firstname, #lastname").removeAttr("required");
                        $(".extra-fields").css("display", "none");
                    }
                    $(".confirm-buttons").css("display", "block");
                    $("#enrollment").attr("action", "");
                }, "json");
            } else {
                $(".confirm-buttons").css("display", "none");
            }
        }

        function enableFormSubmission() {
            $("#confirm").css("display", "block");

        }

    </script>
<?php get_footer(); ?>