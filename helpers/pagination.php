<?php

function getPaginationLinks($page, $total_pages, $page_url) {
    $pagination = "";
    
    // Previous button
    if ($page > 1) {
        $pagination .= "<li class='page-item'><a class='page-link' href='{$page_url}?page=" . ($page - 1) . "'>Previous</a></li>";
    } else {
        $pagination .= "<li class='page-item disabled'><a class='page-link'>Previous</a></li>";
    }
    
    // Page numbers
    for ($i = max(1, $page - 2); $i <= min($page + 2, $total_pages); $i++) {
        if ($i == $page) {
            $pagination .= "<li class='page-item active'><a class='page-link' href='#'>{$i}</a></li>";
        } else {
            $pagination .= "<li class='page-item'><a class='page-link' href='{$page_url}?page={$i}'>{$i}</a></li>";
        }
    }
    
    // Next button
    if ($page < $total_pages) {
        $pagination .= "<li class='page-item'><a class='page-link' href='{$page_url}?page=" . ($page + 1) . "'>Next</a></li>";
    } else {
        $pagination .= "<li class='page-item disabled'><a class='page-link'>Next</a></li>";
    }
    
    return $pagination;
}