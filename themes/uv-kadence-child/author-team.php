<?php
/**
 * Template for displaying individual team members.
 * Triggered on author archive URLs when the `team` query var is present.
 */

get_header();
?>
<main id="main-content" class="site-main">
<?php
$user = get_queried_object();
if ($user instanceof WP_User) :
    $uid  = $user->ID;
    ?>
    <div class="uv-team-container">
        <article class="uv-team-member">
            <header class="uv-member-header">
            <div class="uv-header-block">
                <?php if (function_exists('uv_people_get_avatar')) : ?>
                    <div class="uv-avatar"><?php echo uv_people_get_avatar($uid); ?></div>
                <?php endif; ?>
                <h1 class="notranslate"><?php echo esc_html($user->display_name); ?></h1>
            </div>
            <?php
            $position = '';
            $position_term = get_user_meta($uid, 'uv_position_term', true);
            if ($position_term) {
                $t = get_term($position_term, 'uv_position');
                if (!is_wp_error($t) && $t) {
                    if ($t && !is_wp_error($t)) {
                        $position = $t->name;
                    }
                }
            }
            if (!$position) {
                $position = get_user_meta($uid, 'uv_position_nb', true);
            }
            if ($position) {
                echo '<div class="uv-position notranslate">' . esc_html($position) . '</div>';
            }

            $phone      = get_user_meta($uid, 'uv_phone', true);
            $show_phone = get_user_meta($uid, 'uv_show_phone', true) === '1';
            $email      = get_the_author_meta('user_email', $uid);
            $birthdate  = get_user_meta($uid, 'uv_birthdate', true);
            if (($phone && $show_phone) || $email) {
                echo '<div class="uv-contact">';
                if ($phone && $show_phone) {
                    echo '<div><a href="tel:' . esc_attr($phone) . '">' . esc_html($phone) . '</a></div>';
                }
                if ($email) {
                    echo '<div><a href="mailto:' . esc_attr($email) . '">' . esc_html($email) . '</a></div>';
                }
                echo '</div>';
            }

            if ($birthdate) {
                $bd = DateTime::createFromFormat('Y-m-d', $birthdate);
                if ($bd) {
                    $age = (new DateTime())->diff($bd)->y;
                    echo '<div class="uv-age">' . sprintf(esc_html__('Alder: %d', 'uv-kadence-child'), $age) . '</div>';
                }
            }

            $quote_nb = get_user_meta($uid, 'uv_quote_nb', true);
            if ($quote_nb) {
                echo '<blockquote class="uv-quote">“' . esc_html($quote_nb) . '”</blockquote>';
            }
            ?>
        </header>
        <?php
        $locations = get_user_meta($uid, 'uv_location_terms', true);
        $birthdate = get_user_meta($uid, 'uv_birthdate', true);
        $age_pill = '';
        if ($birthdate) {
            $bd = DateTime::createFromFormat('Y-m-d', $birthdate);
            if ($bd) {
                $age  = (new DateTime())->diff($bd)->y;
                $text = ($age >= 30)
                    ? esc_html__('Voksen leder', 'uv-kadence-child')
                    : esc_html__('Ung leder', 'uv-kadence-child');
                $age_pill = '<span class="uv-age-pill">' . $text . '</span>';
            }
        }
        if ($age_pill || (is_array($locations) && $locations)) {
            echo '<div class="uv-locations">';
            if ($age_pill) {
                echo $age_pill;
            }
            if (is_array($locations) && $locations) {
                foreach ($locations as $loc_id) {
                    $loc_term = get_term($loc_id, 'uv_location');
                    if (!is_wp_error($loc_term) && $loc_term) {
                        if ($loc_term && !is_wp_error($loc_term)) {
                            echo '<span class="uv-location-pill notranslate">' . esc_html($loc_term->name) . '</span>';
                        }
                    }
                }
            }
            echo '</div>';
        }
        $bio_nb = get_user_meta( $uid, 'uv_bio_nb', true );
        if ( $bio_nb ) {
            echo '<div class="uv-bio">' . wp_kses_post( wpautop( $bio_nb ) ) . '</div>';
        }

        if (function_exists('uv_core_get_experiences_for_user')) {
            $experiences = uv_core_get_experiences_for_user($uid);
            if ($experiences) {
                echo '<h2>' . esc_html__('Erfaringer i Unge Vil', 'uv-kadence-child') . '</h2>';
                echo '<ul class="uv-experiences">';
                foreach ($experiences as $exp) {
                    echo '<li><a href="' . esc_url(get_permalink($exp)) . '">' . esc_html(get_the_title($exp)) . '</a></li>';
                }
                echo '</ul>';
            } elseif (current_user_can('edit_users')) {
                echo '<p class="uv-experiences-placeholder">' . esc_html__('Ingen registrerte erfaringer.', 'uv-kadence-child') . '</p>';
            }
        }

        $articles_query = new WP_Query(
            array(
                'author'        => $uid,
                'post_type'     => 'post',
                'posts_per_page' => -1,
                'post_status'   => 'publish',
            )
        );
        if ($articles_query->have_posts()) {
            echo '<h2>' . esc_html__('Artikler', 'uv-kadence-child') . '</h2>';
            echo '<ul class="uv-articles">';
            while ($articles_query->have_posts()) {
                $articles_query->the_post();
                echo '<li><a href="' . esc_url(get_permalink()) . '">' . esc_html(get_the_title()) . '</a></li>';
            }
            echo '</ul>';
        }
        wp_reset_postdata();
        ?>
        </article>
    </div>
<?php endif; ?>
</main>
<?php get_footer();
