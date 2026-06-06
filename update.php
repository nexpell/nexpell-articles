<?php
global $str, $modulname, $version, $_database;

$modulname = 'articles';
$version = '0.3';
$str = 'Articles';

echo "<div class='card'><div class='card-header'>{$str} Database Update</div><div class='card-body'>";

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

safe_query("INSERT IGNORE INTO settings_plugins
  (pluginID, modulname, admin_file, activate, author, website, index_link, hiddenfiles, version, path, status_display, plugin_display, widget_display, delete_display, sidebar)
VALUES
  ('', 'articles', 'admin_articles', 1, 'T-Seven', 'https://www.nexpell.de', 'articles', '', '0.3', 'includes/plugins/articles/', 1, 1, 1, 1, 'deactivated')");

safe_query("INSERT INTO settings_plugins_lang (content_key, language, content, modulname, updated_at) VALUES
  ('plugin_name_articles', 'de', 'Articles', 'articles', NOW()),
  ('plugin_name_articles', 'en', 'Articles', 'articles', NOW()),
  ('plugin_name_articles', 'it', 'Articles', 'articles', NOW()),
  ('plugin_info_articles', 'de', 'Mit diesem Plugin koennt ihr eure Articles anzeigen lassen.', 'articles', NOW()),
  ('plugin_info_articles', 'en', 'With this plugin you can display your articles.', 'articles', NOW()),
  ('plugin_info_articles', 'it', 'Con questo plugin e possibile mostrare gli Articoli sul sito web.', 'articles', NOW())
ON DUPLICATE KEY UPDATE
  content = VALUES(content),
  modulname = VALUES(modulname),
  updated_at = VALUES(updated_at)");

safe_query("INSERT IGNORE INTO settings_widgets
  (widget_key, title, modulname, plugin, description, allowed_zones, active, version, created_at)
VALUES
  ('widget_articles_news', 'Artikel Widget News', 'articles', 'articles', NULL, 'maintop,mainbottom', 1, '1.0.0', NOW()),
  ('widget_articles_content', 'Artikel Widget Content', 'articles', 'articles', NULL, 'maintop,mainbottom', 1, '1.0.0', NOW()),
  ('widget_articles_sidebar', 'Artikel Widget Sidebar', 'articles', 'articles', NULL, 'left,right', 1, '1.0.0', NOW())");

$dashboardLinkId = 0;
$dashboardLinkRes = safe_query("SELECT linkID FROM navigation_dashboard_links
  WHERE modulname = 'articles' AND url = 'admincenter.php?site=admin_articles'
  ORDER BY linkID ASC LIMIT 1");
if ($dashboardLinkRes && ($dashboardLinkRow = mysqli_fetch_assoc($dashboardLinkRes))) {
    $dashboardLinkId = (int) ($dashboardLinkRow['linkID'] ?? 0);
} else {
    safe_query("INSERT INTO navigation_dashboard_links
      (catID, modulname, url, sort)
    VALUES
      (8, 'articles', 'admincenter.php?site=admin_articles', 1)");
    $dashboardLinkId = (int) mysqli_insert_id($_database);
}
if ($dashboardLinkId > 0) {
    safe_query("INSERT INTO navigation_dashboard_lang (content_key, language, content, modulname, updated_at) VALUES
      ('nav_link_{$dashboardLinkId}', 'de', 'Artikel', 'articles', NOW()),
      ('nav_link_{$dashboardLinkId}', 'en', 'Articles', 'articles', NOW()),
      ('nav_link_{$dashboardLinkId}', 'it', 'Articoli', 'articles', NOW())
    ON DUPLICATE KEY UPDATE
      content = VALUES(content),
      modulname = VALUES(modulname),
      updated_at = VALUES(updated_at)");
}

$websiteSubId = 0;
$websiteSubRes = safe_query("SELECT snavID FROM navigation_website_sub
  WHERE modulname = 'articles' AND url = 'index.php?site=articles'
  ORDER BY snavID ASC LIMIT 1");
if ($websiteSubRes && ($websiteSubRow = mysqli_fetch_assoc($websiteSubRes))) {
    $websiteSubId = (int) ($websiteSubRow['snavID'] ?? 0);
} else {
    safe_query("INSERT INTO navigation_website_sub
      (mnavID, modulname, url, sort, indropdown, last_modified)
    VALUES
      (3, 'articles', 'index.php?site=articles', 1, 1, NOW())");
    $websiteSubId = (int) mysqli_insert_id($_database);
}
if ($websiteSubId > 0) {
    safe_query("INSERT INTO navigation_website_lang (content_key, language, content, modulname, updated_at) VALUES
      ('nav_sub_{$websiteSubId}', 'de', 'Artikel', 'articles', NOW()),
      ('nav_sub_{$websiteSubId}', 'en', 'Articles', 'articles', NOW()),
      ('nav_sub_{$websiteSubId}', 'it', 'Articoli', 'articles', NOW())
    ON DUPLICATE KEY UPDATE
      content = VALUES(content),
      modulname = VALUES(modulname),
      updated_at = VALUES(updated_at)");
}

safe_query("INSERT IGNORE INTO user_role_admin_navi_rights (id, roleID, type, modulname)
VALUES ('', 1, 'link', 'articles')");

echo "</div></div>";
