


<?php
$valor = get_field('novedades'); 
if ($valor) {
    echo '<p>' . esc_html($valor) . '</p>';
} else {
    echo '<p>No hay contenido en el campo ACF.</p>';
}
?>


<h1><?php the_title(); ?></h1>
<div>
    <?php the_content(); ?>
</div>



