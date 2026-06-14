<?php

if (!function_exists('safe_query')) {
    die('Access denied');
}

global $plugin;

PluginInstallerHelper::install([

    'modulname'  => 'articles',
    'name'       => 'Articles',
    'version'    => (string)($plugin['version'] ?? '0.0.0'),
    'author'     => 'T-Seven',
    'website'    => 'https://www.nexpell.de',
    'path'       => 'includes/plugins/articles/',

    'admin_file' => 'admin_articles',
    'index_link' => 'articles',
    'sidebar'    => 'deactivated',

    'languages' => [
        'plugin_info_articles' => [
            'de' => 'Mit diesem Plugin könnt ihr eure Artikel anzeigen lassen.',
            'en' => 'With this plugin you can display your articles.',
            'it' => 'Con questo plugin è possibile mostrare gli articoli sul sito web.'
        ]
    ],

    'permissions' => [
        'articles'
    ],

    'widgets' => [
        [
            'widget_key'    => 'widget_articles_news',
            'title'         => 'Artikel Widget News',
            'description'   => null,
            'allowed_zones' => 'maintop,mainbottom'
        ],
        [
            'widget_key'    => 'widget_articles_content',
            'title'         => 'Artikel Widget Content',
            'description'   => null,
            'allowed_zones' => 'maintop,mainbottom'
        ],
        [
            'widget_key'    => 'widget_articles_sidebar',
            'title'         => 'Artikel Widget Sidebar',
            'description'   => null,
            'allowed_zones' => 'left,right'
        ]
    ],

    'admin_navigation' => [
        [
            'url'   => 'admincenter.php?site=admin_articles',
            'catID' => 8,
            'sort'  => 1,
            'labels' => [
                'de' => 'Artikel',
                'en' => 'Articles',
                'it' => 'Articoli'
            ]
        ]
    ],

    'website_navigation' => [
        [
            'url'        => 'index.php?site=articles',
            'mnavID'     => 3,
            'sort'       => 1,
            'indropdown' => 1,
            'labels' => [
                'de' => 'Artikel',
                'en' => 'Articles',
                'it' => 'Articoli'
            ]
        ]
    ]

]);

safe_query("CREATE TABLE IF NOT EXISTS plugins_articles_categories (
  id INT(11) NOT NULL AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL DEFAULT '',
  description TEXT NOT NULL,
  sort_order INT(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (id)
) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

safe_query("CREATE TABLE IF NOT EXISTS plugins_articles (
  id INT(11) NOT NULL AUTO_INCREMENT,
  category_id INT(11) NOT NULL DEFAULT 0,
  title VARCHAR(255) NOT NULL DEFAULT '',
  content TEXT NOT NULL,
  slug VARCHAR(255) NOT NULL DEFAULT '',
  banner_image VARCHAR(255) NOT NULL DEFAULT '',
  sort_order INT(11) NOT NULL DEFAULT 0,
  updated_at INT(14) NOT NULL DEFAULT 0,
  userID INT(11) NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 0,
  rating INT(11) NOT NULL DEFAULT 0,
  points INT(11) NOT NULL DEFAULT 0,
  votes INT(11) NOT NULL DEFAULT 0,
  views INT(11) NOT NULL DEFAULT 0,
  allow_comments TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (id)
) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

safe_query("CREATE TABLE IF NOT EXISTS plugins_articles_comments (
  commentID INT(11) NOT NULL AUTO_INCREMENT,
  parentID INT(11) NOT NULL DEFAULT 0,
  type CHAR(2) NOT NULL DEFAULT '',
  userID INT(11) NOT NULL DEFAULT 0,
  nickname VARCHAR(255) NOT NULL DEFAULT '',
  date INT(14) NOT NULL DEFAULT 0,
  comments TEXT NOT NULL,
  homepage VARCHAR(255) NOT NULL DEFAULT '',
  email VARCHAR(255) NOT NULL DEFAULT '',
  ip VARCHAR(255) NOT NULL DEFAULT '',
  PRIMARY KEY (commentID),
  KEY parentID (parentID),
  KEY type (type),
  KEY date (date)
) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

safe_query("CREATE TABLE IF NOT EXISTS plugins_articles_settings (
  articlessetID INT(11) NOT NULL AUTO_INCREMENT,
  articles INT(11) NOT NULL,
  articleschars INT(11) NOT NULL,
  PRIMARY KEY (articlessetID)
) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

safe_query("INSERT IGNORE INTO plugins_articles_settings (articlessetID, articles, articleschars) VALUES
(1, 4, 100)");
