<?php
/**
 * Template for UV Location taxonomy archive.
 *
 * Displays the term image and description followed by
 * location-specific team members, news posts and activities.
 */

get_header();

$term = get_queried_object();
if ( $term && ! is_wp_error( $term ) ) {
    $slug   = $term->slug;
    $img_id = get_term_meta( $term->term_id, 'uv_location_image', true );
    ?>
    <main id="primary" class="site-main">
        <article class="uv-location">
            <div class="uv-card">
                <?php if ( $img_id ) : ?>
                    <?php echo wp_get_attachment_image( $img_id, 'uv_card', false, [ 'alt' => esc_attr( $term->name ) ] ); ?>
                <?php endif; ?>
                <div class="uv-card-body">
                    <h1><?php echo esc_html( $term->name ); ?></h1>
                    <?php if ( term_description() ) : ?>
                        <div><?php echo wp_kses_post( term_description() ); ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <h2><?php esc_html_e( 'Team', 'uv-kadence-child' ); ?></h2>
            <?php echo do_shortcode( '[uv_team location="' . esc_attr( $slug ) . '"]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            <h2><?php esc_html_e( 'News', 'uv-kadence-child' ); ?></h2>
            <?php echo do_shortcode( '[uv_news location="' . esc_attr( $slug ) . '"]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            <h2><?php esc_html_e( 'Activities', 'uv-kadence-child' ); ?></h2>
            <?php echo do_shortcode( '[uv_activities location="' . esc_attr( $slug ) . '"]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        </article>
    </main>
    <?php
}

get_footer();
