<?php if ($total_pages > 0): ?>
<ul class="pagination" data-mijora-paginator>
    <?php if ($current_page > 1): ?>
    <li>
        <a href="#" class="paginator-btn-previous" data-js-function="<?php echo $js_function; ?>"  data-page="1">&lt;&lt;</a>
    </li>
    <?php else: ?>
    <li class="disabled">
        <span>&lt;&lt;</span>
    </li>
    <?php endif; ?>
    
    <?php if ($current_page > 1): ?>
    <li>
        <a href="#" class="paginator-btn-previous" data-js-function="<?php echo $js_function; ?>"  data-page="<?php echo $current_page - 1; ?>">&lt;</a>
    </li>
    <?php else: ?>
    <li class="disabled">
        <span>&lt;</span>
    </li>
    <?php endif; ?>
    
    <li class="active">
        <span class="current_page"><?php echo $current_page; ?></span>
        <span>/</span>
        <span class="total_pages"><?php echo $total_pages; ?></span>
    </li>
    
    <?php if ($current_page < $total_pages): ?>
    <li>
        <a href="#" class="paginator-btn-next" data-js-function="<?php echo $js_function; ?>" data-page="<?php echo $current_page + 1; ?>">&gt;</a>
    </li>
    <?php else: ?>
    <li class="disabled">
        <span>&gt;</span>
    </li>
    <?php endif; ?>

    <?php if ($current_page < $total_pages): ?>
    <li>
        <a href="#" class="paginator-btn-next" data-js-function="<?php echo $js_function; ?>" data-page="<?php echo $total_pages; ?>">&gt;&gt;</a>
    </li>
    <?php else: ?>
    <li class="disabled">
        <span>&gt;&gt;</span>
    </li>
    <?php endif; ?>
</ul>
<?php endif; ?>