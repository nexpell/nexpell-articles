<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

use nexpell\LanguageService;
use nexpell\SeoUrlHandler;

global $languageService;

$lang = $languageService->detectLanguage();
$languageService->readPluginModule('articles');

if (!defined('ARTICLES_WIDGET_NEWS_CSS_LOADED')) {
    define('ARTICLES_WIDGET_NEWS_CSS_LOADED', true);
    $articlesNewsCssPath = __DIR__ . '/css/widget_articles_news.css';
    $articlesNewsCssVersion = file_exists($articlesNewsCssPath) ? filemtime($articlesNewsCssPath) : time();
    echo '<link rel="stylesheet" href="/includes/plugins/articles/css/widget_articles_news.css?v='
        . (int)$articlesNewsCssVersion . '">' . PHP_EOL;
}

// Neueste Artikel (max. 3)
$latest = safe_query("
    SELECT * FROM plugins_articles
    WHERE is_active = 1
    ORDER BY updated_at DESC
    LIMIT 3
");

// Hilfsfunktion: Text kuerzen
if (!function_exists('shortenText')) {
    function shortenText($text, $length = 200) {
        $text = trim((string)$text);
        if (mb_strlen($text, 'UTF-8') <= $length) {
            return $text;
        }
        return mb_substr($text, 0, $length, 'UTF-8') . '...';
    }
}
?>

<section class="articles-widget-news my-4" aria-labelledby="articles-widget-news-title">
  <div class="articles-widget-news__head">
    <div>
      <p class="articles-widget-news__kicker mb-1">News</p>
      <h4 id="articles-widget-news-title" class="articles-widget-news__title mb-0">
        <i class="bi bi-newspaper" aria-hidden="true"></i>
        Aktuelle Artikel
      </h4>
    </div>
    <span class="articles-widget-news__count">Top 3</span>
  </div>

  <div class="articles-widget-news__grid">
    <?php while ($article = mysqli_fetch_array($latest)):
        $title = htmlspecialchars($article['title'] ?? 'Unbekannter Artikel', ENT_QUOTES, 'UTF-8');
        $contentRaw = html_entity_decode((string)($article['content'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $content = htmlspecialchars(shortenText(strip_tags($contentRaw), 190), ENT_QUOTES, 'UTF-8');
        $timestamp = (int)($article['updated_at'] ?? 0);
        $isNew = $timestamp > 0 && (time() - $timestamp) < (7 * 24 * 60 * 60);
        $articleUrl = SeoUrlHandler::convertToSeoUrl(
            'index.php?site=articles&action=watch&id=' . (int)$article['id']
        );
    ?>
      <a href="<?= htmlspecialchars($articleUrl, ENT_QUOTES, 'UTF-8') ?>"
         class="articles-widget-news__card"
         title="<?= $title ?>">
        <div class="articles-widget-news__card-head">
          <h5 class="articles-widget-news__card-title"><?= $title ?></h5>
          <?php if ($isNew): ?>
            <span class="articles-widget-news__badge"><?= htmlspecialchars($languageService->get('new'), ENT_QUOTES, 'UTF-8') ?></span>
          <?php endif; ?>
        </div>

        <div class="articles-widget-news__date">
          <i class="bi bi-clock" aria-hidden="true"></i>
          <span>
            <?= ($timestamp > 0) ? date('d.m.Y H:i', $timestamp) : 'Kein gueltiges Datum' ?>
          </span>
        </div>

        <p class="articles-widget-news__excerpt"><?= $content ?></p>

        <span class="articles-widget-news__read">
          <?= htmlspecialchars($languageService->get('read_more'), ENT_QUOTES, 'UTF-8') ?>
          <i class="bi bi-arrow-right-short" aria-hidden="true"></i>
        </span>
      </a>
    <?php endwhile; ?>
  </div>
</section>
