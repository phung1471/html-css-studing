<?php get_header(); ?>
    <div id="content" class="content site-content">
        <section id="main-content" class="site-main" role="main">
            <?php if (have_posts()) : ?>
                <?php while (have_posts()) : ?>
                    <?php the_post(); ?>
                    <div class="page-title">
                        <h1><?php the_title() ?></h1>
                    </div>
                    <?php the_content(); ?>
                <?php endwhile; ?>
            <?php endif; ?>

        </section>
        <section id="sidebar">
            <?php get_sidebar(); ?>
        </section>
    </div>

<?php get_footer(); ?>