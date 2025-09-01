<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<header role="banner">
    <div class="site-branding">
        <a href="<?php echo esc_url( home_url('/') ); ?>">
            <?php bloginfo('name'); ?>
        </a>
    </div>
    <?php if ( has_nav_menu( 'primary' ) ) : ?>
    <nav role="navigation">
        <?php
        wp_nav_menu( [
            'theme_location' => 'primary',
            'menu_id'        => 'primary-menu',
            'container'      => false,
        ] );
        ?>
    </nav>
    <?php endif; ?>
</header>
