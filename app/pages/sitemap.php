<?php

namespace Pages;


class sitemap {
    function getIndex() {
        header('Content-Type: application/xml');
        $data = '<test></test>';
        $data = file_get_contents('sitemap.xml');
        return $data;
    }

    function getTest() {
        return 'Hello Again';
    }
}