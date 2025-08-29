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

                    $role_nb = get_user_meta( $user_id, 'uv_position_nb', true );
                    $role_en = get_user_meta( $user_id, 'uv_position_en', true );
                    $role    = ( 'en' === $lang ) ? ( $role_en ?: $role_nb ) : ( $role_nb ?: $role_en );
                    $url     = add_query_arg(
                        [
                            'team'   => 1,
                            'author' => get_the_author_meta( 'user_nicename', $user_id ),
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
                        $email_label = ( 'en' === $lang ) ? __( 'Email:', 'uv-people' ) : __( 'E-post:', 'uv-people' );
                        $phone_label = ( 'en' === $lang ) ? __( 'Mobile:', 'uv-people' ) : __( 'Mobil:', 'uv-people' );
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
