<?php
/**
 * Template for displaying individual team members.
 * Triggered on author archive URLs when the `team` query var is present.
 */

get_header();

$user = get_queried_object();
if ($user instanceof WP_User) :
    $uid  = $user->ID;
    $lang = function_exists('pll_current_language') ? pll_current_language('slug') : substr(get_locale(), 0, 2);
    ?>
    <article class="uv-team-member">
        <header class="uv-member-header">
            <h1><?php echo esc_html($user->display_name); ?></h1>
            <?php if (function_exists('uv_people_get_avatar')) : ?>
                <div class="uv-avatar"><?php echo uv_people_get_avatar($uid); ?></div>
            <?php endif; ?>
        </header>
        <?php
        $bio = get_the_author_meta( 'description', $uid );
        if ( $bio ) {
            echo '<div class="uv-bio">' . wp_kses_post( wpautop( $bio ) ) . '</div>';
        }

        $quote_nb = get_user_meta($uid, 'uv_quote_nb', true);
        $quote_en = get_user_meta($uid, 'uv_quote_en', true);
        $quote    = ($lang === 'en') ? ($quote_en ?: $quote_nb) : ($quote_nb ?: $quote_en);
        if ($quote) {
            echo '<blockquote class="uv-quote">“' . esc_html($quote) . '”</blockquote>';
        }

        $phone      = get_user_meta($uid, 'uv_phone', true);
        $show_phone = get_user_meta($uid, 'uv_show_phone', true) === '1';
        $email      = get_the_author_meta('user_email', $uid);
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

        if (function_exists('uv_core_get_experiences_for_user')) {
            $experiences = uv_core_get_experiences_for_user($uid);
            if ($experiences) {
                echo '<h2>' . esc_html__('Experiences', 'uv-kadence-child') . '</h2>';
                echo '<ul class="uv-experiences">';
                foreach ($experiences as $exp) {
                    echo '<li><a href="' . esc_url(get_permalink($exp)) . '">' . esc_html(get_the_title($exp)) . '</a></li>';
                }
                echo '</ul>';
            }
        }
        ?>
    </article>
<?php endif; ?>

<?php get_footer();
