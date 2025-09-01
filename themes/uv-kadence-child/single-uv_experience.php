<?php
/**
 * Template for single UV Experience posts.
 */

get_header();

do_action( 'kadence_before_main_content' );
?>

<div class="container">
    <div id="primary" class="content-area">
        <main id="main-content" role="main" class="site-main">
            <?php
            do_action( 'kadence_before_content' );

            while ( have_posts() ) :
                the_post();

                get_template_part( 'template-parts/content', 'uv_experience' );

                if ( function_exists( 'kadence_display_comments' ) && kadence_display_comments() ) :
                    comments_template();
                endif;
            endwhile;

            do_action( 'kadence_after_content' );
            ?>
        </main>
    </div>
</div>

<?php
do_action( 'kadence_after_main_content' );

get_footer();
