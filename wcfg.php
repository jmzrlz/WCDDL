<?php
// WarezCoders DDL Script
// v3
// Created by WarezCoders
// www.warezcoders.com
// This file coded by JmZ

# Database
define('WCDDL_DB_DSN', 'mysql:host=localhost;dbname=mydbname');
define('WCDDL_DB_USER', 'db_user');
define('WCDDL_DB_PASS', 'db_pass');
define('WCDDL_DB_PREFIX', 'wcddl_');

# Pagination HTML structure
define('WCDDL_PAGES_DEFAULT', '<a href="index.php?page=#page#">#page#</a>');
define('WCDDL_PAGES_QUERY', '<a href="index.php?page=#page#&query=#query#">#page#</a>');
define('WCDDL_PAGES_TYPE', '<a href="index.php?page=#page#&type=#type#">#page#</a>');
define('WCDDL_PAGES_QUERY_TYPE', '<a href="index.php?page=#page#&type=#type#&query=#query#">#page#</a>');

# Whitelist / Blacklist
define('WCDDL_WHITELIST', 0);
define('WCDDL_BLACKLIST', 1);

# Admin
define('WCDDL_ADMIN_PASS', 'adminpasshere');
define('WCDDL_ADMIN_EMAIL', 'admin@yoursite.com');
define('WCDDL_ADMIN_LOCATION', 'wc3admin.php');

# Core
define('WCDDL_PATH', '/path/to/wcddl/');
define('WCDDL_PATH_MODULES', '/path/to/wcddl/modules/dir');
define('WCDDL_URL', 'http://mysite.com/');
define('WCDDL_TYPES', 'app,game,music,movie,xxx,other');
define('WCDDL_VERSION', 3.12);
