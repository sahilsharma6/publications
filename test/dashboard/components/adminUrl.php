<?php


$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'
    || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";

$root_url = 'http://localhost/book/test/dashboard/';
// $root_url = $protocol . $_SERVER['HTTP_HOST'] . '/test/dashboard/';

?>