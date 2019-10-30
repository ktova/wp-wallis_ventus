<?php /* Template Name: InscriptionMembre */ ?>
<?php

require_once __DIR__ . '/vendor/autoload.php';

function wallis_templating($template, $content)
{
    $engine = new \Handlebars\Handlebars;
    $rendered = $engine->render($template, $content);

    return $rendered;
}

function wallis_recaptcha_script() {
    echo "<script src='https://www.google.com/recaptcha/api.js'></script>\n";
}
add_action('wp_head', 'wallis_recaptcha_script');

if (isset($_POST["confirm"])) {
    $prenom = sanitize_text_field($_POST["firstname"]);
    $nom = sanitize_text_field($_POST["lastname"]);
    $courriel = sanitize_text_field($_POST["email"]);
    $telephone = sanitize_text_field($_POST["phone"]);
    $adresse = sanitize_textarea_field($_POST["address"]);
    $codepostal = sanitize_text_field($_POST["cpostal"]);
    $famille = sanitize_text_field($_POST["famille"]);

    $membre = wp_insert_post(array("post_type" => "membre", "post_status" => "pending", "post_title" => $prenom . " " . $nom));
    update_field("prenom", $prenom, $membre);
    update_field("nom", $nom, $membre);
    update_field("courriels", array(array("courriel" => $courriel)), $membre);
    update_field("telephones", array(array("telephone" => $telephone)), $membre);
    update_field("adresse", $adresse, $membre);
    update_field("code_postal", $codepostal, $membre);
    update_field("famille", $famille, $membre);
	
    // Envoi du courriel au membre
    $content_member = get_field("inscription_nouveau_membre", "options");
    wp_mail($courriel, "Adhésion comme membre de Bernex.net", $content_member, array("From: Bernex.net <info@bernex.net>"));
	
	// Assigner valeur au code postal pour envoyer le label
	$fullcodepost = get_field_object("code_postal", $membre);
	$valoue = $fullcodepost['value'];
	$cplabel = $fullcodepost['choices'][ $valoue ];
	

    // Envoi du courriel au secrétariat
    $content_admin_template = get_field("admin_inscription_nouveau_membre", "options");
    $content_admin = wallis_templating($content_admin_template, array(
	    "membre_id" => $membre,
	    "prenom" => $prenom,
        "nom" => $nom,
        "courriels_0_courriel" => $courriel,
        "telephones_0_telephone" => $telephone,
        "adresse" => $adresse,
		"cpostal" => $cplabel,
        "famille" => $famille,
        "edit-post-link" => get_edit_post_link($membre),
    ));
    wp_mail("teva@2gik.ch", "Nouveau membre", $content_admin, array("From: Bernex.net <info@bernex.net>"));
    wp_mail("xavier@2gik.ch", "Nouveau membre", $content_admin, array("From: Bernex.net <info@bernex.net>"));
    wp_mail("inscriptions@bernex.net", "Nouveau membre", $content_admin, array("From: Bernex.net <info@bernex.net>"));

    wp_redirect("https://bernex.net/merci-pour-votre-adhesion/");
    exit;
}

get_header();
?>

    <div class="full in-page-block-wrapper">
        <h1 class="intit"><?php the_title(); ?></h1>
        <?php 
  echo "<div id='conditainer'><h2>Conditions</h2>
L’association est sise à Bernex, mais elle est ouverte aux habitants des autres communes. La cotisation annuelle est de 80 CHF pour une personne et de 120 CHF pour une famille vivant sous le même toit. Un bulletin de versement vous est adressé par voie postale lors de l’inscription.<br><br>
<div class='alertinfo'> <img src=/wp-content/uploads/infoicon.svg> Vous n'avez pas besoin de vous réinscrire si vous avez déja été membre Bernex.net par le passé, en cas de doute contactez-nous à info@bernex.net.<br> <img src=/wp-content/uploads/infoicon.svg> Merci de remplir complètement le formulaire avant de nous l'envoyer.</div>
</div>"
?>
        <div>
            <form action="/new-inscription-membre/?submit" id="enrollment" name="enrollment-form" method="post">
                <input type="hidden" name="formation" value="<?php echo @$_GET["formation"]; ?>"/>
                <label for="firstname">Prénom</label>
                <input autofocus="autofocus" class="w-input" id="firstname" maxlength="256" name="firstname" required="required" type="text" required/>
                <label for="lastname">Nom</label>
                <input class="w-input" id="lastname" maxlength="256" name="lastname" required="required" type="text" required/>
                <label for="email">Courriel</label>
                <input class="w-input" id="email" maxlength="256" name="email" required="required" type="email" placeholder="adresse.courriel@service.ch" required/>
                <label for="phone">Numéro de téléphone</label>
                <input class="w-input" id="phone" maxlength="256" name="phone" required="required" type="tel" placeholder="+4122_______" required/>
                <label for="address">Adresse complète</label>
                <textarea class="w-input" id="address" maxlength="256" name="address" required="required" placeholder="" required></textarea>
                <label for="cpostal">Code Postal</label>
                <input class="w-input" id="cpostal" maxlength="256" name="cpostal" required="required" placeholder="Ex: 1233"required/><br>
                <label for="famille">Cotisation :</label><br>
                <input type="radio" name="famille" value="Individuelle">Individuelle (80.- CHF/an)<br>
                <input type="radio" name="famille" value="Famille">Famille (120.- CHF/an)<br>
                <br />
                 <div class="g-recaptcha" data-sitekey="6Leq1bcUAAAAAJQxpA4vOD0m91FB91ET_-5uOziX" data-callback="enableFormSubmission"></div>
                    <p>&nbsp;</p>
                <input class="book-course w-button" type="submit" name="confirm" id="confirm" value="Confirmer l&#39;inscription" style="display: none;"/>
            </form>
        </div>
        <p></p>
        <p></p>
    </div>

    <script type="text/javascript">
	function enableFormSubmission() {
            $("#confirm").css("display", "block");
        }
		
    var button = document.getElementById('confirm')
    button.addEventListener('click',hide,false);

    function hide() {
        this.style.display = 'none'
    }   
    </script>

<?php get_footer(); ?>