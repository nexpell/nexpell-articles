<?php
safe_query("CREATE TABLE IF NOT EXISTS plugins_articles_categories (
  id INT(11) NOT NULL AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL DEFAULT '',
  description TEXT NOT NULL,
  sort_order INT(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (id)
) AUTO_INCREMENT=1
  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

safe_query("CREATE TABLE IF NOT EXISTS plugins_articles (
  id INT(11) NOT NULL AUTO_INCREMENT,
  category_id int(11) NOT NULL DEFAULT 0,
  title varchar(255) NOT NULL DEFAULT '',
  content text NOT NULL,
  slug varchar(255) NOT NULL DEFAULT '',
  banner_image varchar(255) NOT NULL DEFAULT '',
  sort_order int(11) NOT NULL DEFAULT 0,
  updated_at int(14) NOT NULL DEFAULT 0,
  userID int(11) NOT NULL DEFAULT 0,
  is_active tinyint(1) NOT NULL DEFAULT 0,
  rating int(11) NOT NULL DEFAULT 0,
  points int(11) NOT NULL DEFAULT 0,
  votes int(11) NOT NULL DEFAULT 0,
  views int(11) NOT NULL DEFAULT 0,
  allow_comments TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (id)
) AUTO_INCREMENT=1
  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

safe_query("CREATE TABLE IF NOT EXISTS plugins_articles_settings (
  articlessetID int(11) NOT NULL AUTO_INCREMENT,
  articles int(11) NOT NULL,
  articleschars int(11) NOT NULL,
  PRIMARY KEY (articlessetID)
) AUTO_INCREMENT=1
  DEFAULT CHARSET=utf8 DEFAULT COLLATE utf8_unicode_ci");

safe_query("INSERT IGNORE INTO plugins_articles_settings (articlessetID, articles, articleschars) VALUES
(1, 4, '100')");

## SYSTEM #####################################################################################################################################

safe_query("
    INSERT IGNORE INTO settings_plugins
        (pluginID, modulname, admin_file, activate, author, website, index_link, hiddenfiles, version, path, status_display, plugin_display, widget_display, delete_display, sidebar)
    VALUES
        ('', 'articles', 'admin_articles', 1, 'T-Seven', 'https://www.nexpell.de', 'articles', '', '0.3', 'includes/plugins/articles/', 1, 1, 1, 1, 'deactivated');
");

safe_query("
    INSERT IGNORE INTO settings_plugins_lang 
        (content_key, language, content, updated_at)
    VALUES
        ('plugin_name_articles', 'de', 'Articles', NOW()),
        ('plugin_name_articles', 'en', 'Articles', NOW()),
        ('plugin_name_articles', 'it', 'Articles', NOW()),

        ('plugin_info_articles', 'de', 'Mit diesem Plugin könnt ihr eure Articles anzeigen lassen.', NOW()),
        ('plugin_info_articles', 'en', 'With this plugin you can display your articles.', NOW()),
        ('plugin_info_articles', 'it', 'Con questo plugin è possibile mostrare gli Articoli sul sito web.', NOW())
");

safe_query("INSERT IGNORE INTO `settings_widgets` (`widget_key`, `title`, `modulname`, `plugin`, `description`, `allowed_zones`, `active`, `version`, `created_at`) VALUES
('widget_articles_news', 'Artikel Widget News', 'articles', 'articles', NULL, 'maintop,mainbottom', 1, '1.0.0', NOW()),
('widget_articles_content', 'Artikel Widget Content', 'articles', 'articles', NULL, 'maintop,mainbottom', 1, '1.0.0', NOW()),
('widget_articles_sidebar', 'Artikel Widget Sidebar', 'articles', 'articles', NULL, 'left,right', 1, '1.0.0', NOW())");

## NAVIGATION #####################################################################################################################################

safe_query("
    INSERT IGNORE INTO navigation_dashboard_links
        (catID, modulname, url, sort)
    VALUES
        (8, 'articles', 'admincenter.php?site=admin_articles', 1)
");
$linkID = mysqli_insert_id($_database);

safe_query("
    INSERT IGNORE INTO navigation_dashboard_lang
        (content_key, language, content, updated_at)
    VALUES
        ('nav_link_{$linkID}', 'de', 'Artikel', NOW()),
        ('nav_link_{$linkID}', 'en', 'Articles', NOW()),
        ('nav_link_{$linkID}', 'it', 'Articoli', NOW())
");

safe_query("
    INSERT IGNORE INTO navigation_website_sub
        (mnavID, modulname, url, sort, indropdown, last_modified)
    VALUES
        (3, 'articles', 'index.php?site=articles', 1, 1, NOW())
");

$snavID = mysqli_insert_id($_database);

safe_query("
    INSERT IGNORE INTO navigation_website_lang
        (content_key, language, content, updated_at)
    VALUES
        ('nav_sub_{$snavID}', 'de', 'Artikel', NOW()),
        ('nav_sub_{$snavID}', 'en', 'Articles', NOW()),
        ('nav_sub_{$snavID}', 'it', 'Articoli', NOW())
");

#######################################################################################################################################
safe_query("
  INSERT IGNORE INTO user_role_admin_navi_rights (id, roleID, type, modulname)
  VALUES ('', 1, 'link', 'articles')
");
 ?>

