<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title><?php bloginfo("title"); ?></title>
  <meta content="<?php bloginfo("title"); ?>" property="og:title">
  <meta name="google-site-verification" content="Vk5TG5M1ckCY57W_j2ctT-LFu9qDv8nsQdXSeueIQBg" />
  <meta content="width=device-width, initial-scale=1" name="viewport">
  <link rel="stylesheet" href="<?php echo get_stylesheet_uri(); ?>">
  <link href="<?php echo get_stylesheet_directory_uri(); ?>/css/bernexnet.css" rel="stylesheet" type="text/css">
  <link href="<?php echo get_stylesheet_directory_uri(); ?>/css/custom.css" rel="stylesheet" type="text/css">
  <script src="/js/bernexnet.js" type="text/javascript"></script>
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.0/jquery.min.js" type="text/javascript"></script>
  <style>
    hr {
      border: none;
    }
    html {
        height: auto !important;
    }
  </style>
  <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
#créer nouveau header

  <div class="page-wrapper">
    <div class="sidebar-wrapper">
      <div class="navigation-sidebar w-nav" data-animation="default" data-collapse="medium" data-duration="200">
        <div class="sidebar-container w-clearfix">
          <a class="w-nav-brand" href="/"><img class="logo" src="<?php echo get_stylesheet_directory_uri(); ?>/images/logo-white@2x.png" width="87">
          </a>
          <div class="hamburger-button w-nav-button">
            <div class="hamburger-button w-icon-nav-menu"></div>
          </div>
          <nav class="navigation-menu-items-container w-nav-menu" role="navigation">
              <?php the_webflow_menu(); ?>
          </nav>
        </div>
      </div>
    </div>
    <div class="content-wrapper">
      