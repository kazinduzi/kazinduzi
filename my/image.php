<?php

defined('KAZINDUZI_PATH') || exit('No direct script access allowed');

$config = array(
    'library' => 'gd', #'imagick'
    'upload_dir' => '/html/images',
    'upload_path' => BASE_PATH . '/html/images/',
    'thumbnail_dir' => '/html/thumbs',
    'thumbnail_path' => BASE_PATH . '/html/thumbs',
    'quality' => 85,
);