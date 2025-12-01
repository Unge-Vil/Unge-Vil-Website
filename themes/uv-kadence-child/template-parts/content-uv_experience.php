<?php
/**
 * Content template for UV Experience single posts.
 */
?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
    <div class="entry-content-wrap">
        <header class="entry-header">
            <?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
        </header>

        <div class="entry-content">
            <?php the_content(); ?>
            <?php
            $users = get_post_meta( get_the_ID(), 'uv_experience_users', false );
            if ( $users && is_array( $users ) ) :
                wp_enqueue_style( 'uv-team-grid-style', plugins_url( 'uv-people/blocks/team-grid/style.css' ), [], defined( 'UV_PEOPLE_VERSION' ) ? UV_PEOPLE_VERSION : null );
            ?>
            <div class="uv-experience-users uv-team-grid" role="list">
                <?php
                $lang = substr( get_locale(), 0, 2 );
                foreach ( $users as $user_id ) :
                    $user_id = absint( $user_id );
                    $user    = get_user_by( 'id', $user_id );

                    if ( ! $user ) {
                        continue;
                    }

                    $role    = '';
                    $role_term = get_user_meta( $user_id, 'uv_position_term', true );
                    if ( $role_term ) {
                        $t = get_term( $role_term, 'uv_position' );
                        if ( $t && ! is_wp_error( $t ) ) {
                            $role = $t->name;
                        }
                    }
                    if ( ! $role ) {
                        $role_nb = get_user_meta( $user_id, 'uv_position_nb', true );
                        $role_en = get_user_meta( $user_id, 'uv_position_en', true );
                        $role    = ( 'en' === $lang ) ? ( $role_en ?: $role_nb ) : ( $role_nb ?: $role_en );
                    }
                    $url     = add_query_arg(
                        [
                            'team'        => 1,
                            'author_name' => get_the_author_meta( 'user_nicename', $user_id ),
                        ],
                        home_url( '/' )
                    );
                ?>
                <article class="uv-person" role="listitem">
                    <a href="<?php echo esc_url( $url ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Se profil for %s', 'uv-kadence-child' ), $user->display_name ) ); ?>">
                        <div class="uv-avatar">
                            <?php
                            if ( function_exists( 'uv_people_get_avatar' ) ) {
                                echo uv_people_get_avatar( $user_id );
                            } else {
                                echo get_avatar( $user_id, 96 );
                            }
                            ?>
                        </div>
                        <div class="uv-info">
                            <h3 class="notranslate"><?php echo esc_html( $user->display_name ); ?></h3>
                            <?php if ( $role ) : ?>
                                <div class="uv-role notranslate"><?php echo esc_html( $role ); ?></div>
                            <?php endif; ?>
                        </div>
                    </a>
                    <?php
                    $phone      = get_user_meta( $user_id, 'uv_phone', true );
                    $email      = $user->user_email;
                    $show_phone = get_user_meta( $user_id, 'uv_show_phone', true ) === '1';

                    if ( ( $phone && $show_phone ) || $email ) :
                        $email_label = __( 'E-post:', 'uv-kadence-child' );
                        $phone_label = __( 'Mobil:', 'uv-kadence-child' );
                    ?>
                    <div class="uv-contact">
                        <?php if ( $email ) : ?>
                            <div class="uv-email"><span class="label"><?php echo esc_html( $email_label ); ?></span><a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a></div>
                        <?php endif; ?>
                        <?php if ( $phone && $show_phone ) : ?>
                            <div class="uv-mobile"><span class="label"><?php echo esc_html( $phone_label ); ?></span><a href="tel:<?php echo esc_attr( $phone ); ?>"><?php echo esc_html( $phone ); ?></a></div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    <?php
                    $quote_nb = get_user_meta( $user_id, 'uv_quote_nb', true );
                    $quote_en = get_user_meta( $user_id, 'uv_quote_en', true );
                    $quote    = ( 'en' === $lang ) ? ( $quote_en ?: $quote_nb ) : ( $quote_nb ?: $quote_en );
                    if ( $quote ) :
                    ?>
                    <div class="uv-quote"><span class="uv-quote-icon">&ldquo;</span><?php echo esc_html( $quote ); ?></div>
                    <?php endif; ?>
                </article>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <?php
            $partners = get_post_meta( get_the_ID(), 'uv_experience_partners', false );
            if ( $partners ) :
                $partner_query = new WP_Query(
                    [
                        'post_type'      => 'uv_partner',
                        'post__in'       => array_map( 'absint', $partners ),
                        'posts_per_page' => -1,
                        'orderby'        => 'post__in',
                    ]
                );
                if ( $partner_query->have_posts() ) :
            ?>
            <h2><?php esc_html_e( 'Partnere', 'uv-core' ); ?></h2>
            <ul class="uv-card-list uv-card-grid">
                <?php
                while ( $partner_query->have_posts() ) :
                    $partner_query->the_post();
                    $link    = get_post_meta( get_the_ID(), 'uv_partner_url', true );
                    $display = get_post_meta( get_the_ID(), 'uv_partner_display', true );
                    if ( ! $display ) {
                        $display = has_post_thumbnail() ? 'circle_title' : 'title_only';
                    }
                    if ( ! has_post_thumbnail() ) {
                        $display = 'title_only';
                    }
                    $classes = 'uv-card uv-partner uv-partner--' . esc_attr( $display );
                    echo '<li class="' . $classes . '">';
                    echo $link
                        ? '<a href="' . esc_url( $link ) . '" target="_blank" rel="noopener nofollow">'
                        : '<a href="' . esc_url( get_permalink() ) . '" rel="noopener">';
                    $fallback     = '<span class="uv-partner-icon"></span>';
                    $render_thumb = function( $attrs = [] ) use ( $fallback ) {
                        if ( has_post_thumbnail() ) {
                            $attrs = wp_parse_args( $attrs, [ 'alt' => esc_attr( get_the_title() ) ] );
                            the_post_thumbnail( 'uv_card', $attrs );
                        } else {
                            echo $fallback;
                        }
                    };
                    switch ( $display ) {
                        case 'logo_only':
                            $render_thumb();
                            break;
                        case 'circle_title':
                            $render_thumb( [ 'class' => 'is-circle' ] );
                            echo '<div class="uv-card-body"><strong>' . esc_html( get_the_title() ) . '</strong>';
                            $excerpt = get_the_excerpt();
                            if ( $excerpt ) {
                                echo '<div>' . esc_html( $excerpt ) . '</div>';
                            }
                            echo '</div>';
                            break;
                        case 'title_only':
                            echo '<div class="uv-card-body"><strong>' . esc_html( get_the_title() ) . '</strong>';
                            $excerpt = get_the_excerpt();
                            if ( $excerpt ) {
                                echo '<div>' . esc_html( $excerpt ) . '</div>';
                            }
                            echo '</div>';
                            break;
                        case 'logo_title':
                        default:
                            $render_thumb();
                            echo '<div class="uv-card-body"><strong>' . esc_html( get_the_title() ) . '</strong>';
                            $excerpt = get_the_excerpt();
                            if ( $excerpt ) {
                                echo '<div>' . esc_html( $excerpt ) . '</div>';
                            }
                            echo '</div>';
                            break;
                    }
                    echo '</a></li>';
                endwhile;
                wp_reset_postdata();
                ?>
            </ul>
            <?php
                endif;
            endif;
            ?>
            <?php
            $external_url = get_post_meta( get_the_ID(), 'uv_external_url', true );
            if ( $external_url ) :
            ?>
            <div class="uv-related-link uv-related-link--external">
                <a class="uv-related-button" href="<?php echo esc_url( $external_url ); ?>" target="_blank" rel="noopener">
                    <span class="uv-related-button__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" role="img" focusable="false" xmlns="http://www.w3.org/2000/svg">
                            <path d="M9.75 3h10.5M21 3v10.5" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" />
                            <path d="M21 3 12.75 11.25" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" />
                            <path d="M14.25 7.5H5.25A2.25 2.25 0 0 0 3 9.75v9A2.25 2.25 0 0 0 5.25 21h9a2.25 2.25 0 0 0 2.25-2.25v-9" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" />
                        </svg>
                    </span>
                    <span class="uv-related-button__text"><?php esc_html_e( 'Besøk nettsiden', 'uv-kadence-child' ); ?></span>
                </a>
            </div>
            <?php endif; ?>
        </div>

        <?php
        $related = absint( get_post_meta( get_the_ID(), 'uv_related_post', true ) );
        if ( $related ) :
        ?>
        <div class="uv-related-link uv-related-link--blog">
            <a class="uv-related-button" href="<?php echo esc_url( get_permalink( $related ) ); ?>">
                <span class="uv-related-button__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" role="img" focusable="false" xmlns="http://www.w3.org/2000/svg">
                        <path d="M4.5 5.25A2.25 2.25 0 0 1 6.75 3h10.5A2.25 2.25 0 0 1 19.5 5.25v13.5A2.25 2.25 0 0 1 17.25 21h-12A2.25 2.25 0 0 1 3 18.75V6" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" />
                        <path d="M6.75 8.25h10.5M6.75 12h3m-3 3.75h3m3-3.75h3" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" />
                    </svg>
                </span>
                <span class="uv-related-button__text"><?php esc_html_e( 'Les blogginnlegget', 'uv-kadence-child' ); ?></span>
            </a>
        </div>
        <?php endif; ?>
    </div>
</article>
