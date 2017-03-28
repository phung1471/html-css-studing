<?php get_header(); ?>
    <div class="content">

        <section id="main-content">
            <?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
                <?php get_template_part( 'template-parts/post/content', get_post_format() ); ?>
                <?php get_template_part( 'author-bio' ); ?>
                <?php comments_template(); ?>
            <?php endwhile; ?>
            <?php else : ?>
                <?php get_template_part( 'template-parts/post/content', 'none' ); ?>
            <?php endif; ?>
        </section>
        <section id="sidebar">
            <?php get_sidebar(); ?>
        </section>

    </div>
<?php if ( is_singular() ) wp_enqueue_script( "comment-reply" ); ?>
<?php
$args = array(
    'walker'            => null,
    'max_depth'         => '',
    'style'             => 'ul',
    'callback'          => null,
    'end-callback'      => null,
    'type'              => 'all',
    'reply_text'        => 'Reply',
    'page'              => '',
    'per_page'          => '',
    'avatar_size'       => 32,
    'reverse_top_level' => null,
    'reverse_children'  => '',
    'format'            => 'html5', // or 'xhtml' if no 'HTML5' theme support
    'short_ping'        => false,   // @since 3.6
    'echo'              => true     // boolean, default is true
);
wp_list_comments( $args );
paginate_comments_links()
?>
<?php comment_form(); ?>

<?php get_footer(); ?>