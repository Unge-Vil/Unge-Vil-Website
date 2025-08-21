<?php
/**
 * Template for single UV Experience posts.
 */

get_header();

if ( have_posts() ) :
    ?>
    <main id="primary" class="site-main">
    <?php
    while ( have_posts() ) :
        the_post();
        ?>
        <article <?php post_class(); ?>>
            <h1><?php the_title(); ?></h1>
            <div>
                <?php the_content(); ?>
            </div>
            <?php
            $related = get_post_meta( get_the_ID(), 'uv_related_post', true );
            if ( $related ) :
                ?>
                <div class="uv-related-post">
                    <h2><?php esc_html_e( 'Related Post', 'uv-kadence-child' ); ?></h2>
                    <a href="<?php echo esc_url( get_permalink( $related ) ); ?>">
                        <?php echo esc_html( get_the_title( $related ) ); ?>
                    </a>
                </div>
                <?php
            endif;
            ?>
        </article>
        <?php
    endwhile;
    ?>
    </main>
    <?php
endif;

get_footer();
