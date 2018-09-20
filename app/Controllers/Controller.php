<?php

namespace App\Controllers;

use Slim\Container;

class Controller {
    protected $container;
    protected $logger;
    protected $db;
    protected $helper;


    protected $_tb_article = 'ns_article_pages';
    protected $_tb_category = 'ns_article_categories';
    protected $_tb_user = 'ns_users';
    protected $_tb_comment = 'ns_article_comments';

    public function __construct(Container $container) {
        $this->container = $container;
        
        $this->logger = $container->get('logger');
        $this->db = $container->get('DB');
        $this->helper = $container->get('helper');
    }
}

/**
 * Filename : Controller.php
 * Location : /Controller.php
 */
