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
                $lang = function_exists( 'pll_current_language' ) ? pll_current_language( 'slug' ) : substr( get_locale(), 0, 2 );
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
                        if ( ! is_wp_error( $t ) && $t ) {
                            if ( function_exists( 'pll_get_term' ) && $lang ) {
                                $tid = pll_get_term( $t->term_id, $lang );
                                if ( $tid ) {
                                    $t = get_term( $tid, 'uv_position' );
                                }
                            }
                            if ( $t && ! is_wp_error( $t ) ) {
                                $role = $t->name;
                            }
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
                    <a href="<?php echo esc_url( $url ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'View profile for %s', 'uv-kadence-child' ), $user->display_name ) ); ?>">
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
                            <h3><?php echo esc_html( $user->display_name ); ?></h3>
                            <?php if ( $role ) : ?>
                                <div class="uv-role"><?php echo esc_html( $role ); ?></div>
                            <?php endif; ?>
                        </div>
                    </a>
                    <?php
                    $phone      = get_user_meta( $user_id, 'uv_phone', true );
                    $email      = $user->user_email;
                    $show_phone = get_user_meta( $user_id, 'uv_show_phone', true ) === '1';

                    if ( ( $phone && $show_phone ) || $email ) :
                        $email_label = __( 'Email:', 'uv-kadence-child' );
                        $phone_label = __( 'Mobile:', 'uv-kadence-child' );
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
            <h2><?php esc_html_e( 'Partners', 'uv-core' ); ?></h2>
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
            <div class="uv-external-link">
                <a class="uv-related-button" href="<?php echo esc_url( $external_url ); ?>" target="_blank" rel="noopener">
                    <?php esc_html_e( 'Visit website', 'uv-core' ); ?>
                </a>
            </div>
            <?php endif; ?>
        </div>

        <?php
        $related = absint( get_post_meta( get_the_ID(), 'uv_related_post', true ) );
        if ( $related ) :
        ?>
        <div class="uv-related-post">
            <a class="uv-related-button" href="<?php echo esc_url( get_permalink( $related ) ); ?>"><?php esc_html_e( 'Read blog post', 'uv-kadence-child' ); ?></a>
        </div>
        <?php endif; ?>
    </div>
</article>
