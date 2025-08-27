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
                    $url     = add_query_arg( 'team', '1', get_author_posts_url( $user_id ) );
                ?>
                <article class="uv-person" role="listitem">
                    <a href="<?php echo esc_url( $url ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'View profile for %s', 'uv-kadence-child' ), $user->display_name ) ); ?>">
                        <div class="uv-avatar"><?php echo get_avatar( $user_id, 96 ); ?></div>
                        <div class="uv-info">
                            <h3><?php echo esc_html( $user->display_name ); ?></h3>
                            <?php if ( $role ) : ?>
                                <div class="uv-role"><?php echo esc_html( $role ); ?></div>
                            <?php endif; ?>
                        </div>
                    </a>
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
