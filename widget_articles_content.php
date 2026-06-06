<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

use nexpell\LanguageService;
use nexpell\SeoUrlHandler;

global $languageService;

$lang = $languageService->detectLanguage();
$languageService->readPluginModule('articles');

$tpl = new Template();
$config = mysqli_fetch_array(safe_query("SELECT selected_style FROM settings_headstyle_config WHERE id=1"));
$class = htmlspecialchars($config['selected_style'] ?? '', ENT_QUOTES, 'UTF-8');

// Header-Daten
$data_array = [
    'class' => $class,
    'title' => $languageService->get('title'),
    'subtitle' => 'Articles'
];

if (!defined('ARTICLES_WIDGET_CONTENT_CSS_LOADED')) {
    define('ARTICLES_WIDGET_CONTENT_CSS_LOADED', true);
    $articlesContentCssPath = __DIR__ . '/css/widget_articles_content.css';
    $articlesContentCssVersion = file_exists($articlesContentCssPath) ? filemtime($articlesContentCssPath) : time();
    echo '<link rel="stylesheet" href="/includes/plugins/articles/css/widget_articles_content.css?v='
        . (int)$articlesContentCssVersion . '">' . PHP_EOL;
}

#echo $tpl->loadTemplate("articles", "head", $data_array, 'plugin');

if (!function_exists('articles_widget_content_excerpt')) {
    function articles_widget_content_excerpt($text, $length = 160) {
        $text = html_entity_decode((string)$text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = trim(preg_replace('/\s+/u', ' ', strip_tags($text)) ?? '');
        if (mb_strlen($text, 'UTF-8') <= $length) {
            return $text;
        }
        return mb_substr($text, 0, $length, 'UTF-8') . '...';
    }
}

$settingsQry = safe_query("SELECT * FROM plugins_articles_settings");
$settingsRow = mysqli_fetch_array($settingsQry);
$maxArticlesChars = (int)($settingsRow['articleschars'] ?? 160);
if ($maxArticlesChars <= 0) {
    $maxArticlesChars = 160;
}

$qry = safe_query("SELECT * FROM plugins_articles WHERE id!=0 ORDER BY id DESC LIMIT 0,3");
$anz = mysqli_num_rows($qry);

if ($anz) {
    ?>

    <section class="articles-widget-content my-4" aria-labelledby="articles-widget-content-title">
      <div class="articles-widget-content__head">
        <div>
          <p class="articles-widget-content__kicker mb-1">Artikel</p>
          <h4 id="articles-widget-content-title" class="articles-widget-content__title mb-0">
            <i class="bi bi-journal-text" aria-hidden="true"></i>
            <?php echo htmlspecialchars($languageService->get('title'), ENT_QUOTES, 'UTF-8'); ?>
          </h4>
        </div>
        <span class="articles-widget-content__count">Top 3</span>
      </div>

      <div class="articles-widget-content__grid">
        <?php
        while ($ds = mysqli_fetch_array($qry)) {
            $dateString = $ds['date'] ?? '';
            $timestamp = strtotime($dateString) ?: (int)($ds['updated_at'] ?? time());
            if ($timestamp <= 0) {
                $timestamp = time();
            }

            $tag = date("d", $timestamp);
            $monat = date("n", $timestamp);
            $year = date("Y", $timestamp);

            $monate = [
                1 => $languageService->get('jan'), 2 => $languageService->get('feb'),
                3 => $languageService->get('mar'), 4 => $languageService->get('apr'),
                5 => $languageService->get('may'), 6 => $languageService->get('jun'),
                7 => $languageService->get('jul'), 8 => $languageService->get('aug'),
                9 => $languageService->get('sep'), 10 => $languageService->get('oct'),
                11 => $languageService->get('nov'), 12 => $languageService->get('dec')
            ];
            $monatname = $monate[(int)$monat] ?? '';

            $translate = new multiLanguage($lang);
            $translate->detectLanguages($ds['title'] ?? '');
            $articleTitle = $translate->getTextByLanguage($ds['title'] ?? '');
            $articleTitle = htmlspecialchars($articleTitle, ENT_QUOTES, 'UTF-8');

            $excerpt = htmlspecialchars(
                articles_widget_content_excerpt($ds['content'] ?? '', min($maxArticlesChars, 220)),
                ENT_QUOTES,
                'UTF-8'
            );

            $bannerImage = $ds['banner_image'] ?? '';
            $image = $bannerImage
                ? "/includes/plugins/articles/images/article/" . $bannerImage
                : "/includes/plugins/articles/images/no-image.jpg";

            $catID = (int)($ds['category_id'] ?? 0);
            $catQuery = safe_query("SELECT name FROM plugins_articles_categories WHERE id = $catID");
            $cat = mysqli_fetch_assoc($catQuery);
            $catName = htmlspecialchars($cat['name'] ?? 'Allgemein', ENT_QUOTES, 'UTF-8');

            $userID = (int)($ds['userID'] ?? 0);
            $username = htmlspecialchars(getusername($userID), ENT_QUOTES, 'UTF-8');
            $avatar = htmlspecialchars(getavatar($userID), ENT_QUOTES, 'UTF-8');

            $articleUrl = SeoUrlHandler::convertToSeoUrl(
                "index.php?site=articles&action=watch&id=" . (int)($ds['id'] ?? 0)
            );
            ?>

            <a href="<?php echo htmlspecialchars($articleUrl, ENT_QUOTES, 'UTF-8'); ?>"
               class="articles-widget-content__card"
               title="<?php echo $articleTitle; ?>">
              <figure class="articles-widget-content__media">
                <img src="<?php echo htmlspecialchars($image, ENT_QUOTES, 'UTF-8'); ?>" alt="">
                <span class="articles-widget-content__category"><?php echo $catName; ?></span>
              </figure>

              <div class="articles-widget-content__body">
                <div class="articles-widget-content__date">
                  <strong><?php echo $tag; ?></strong>
                  <span><?php echo htmlspecialchars($monatname, ENT_QUOTES, 'UTF-8'); ?></span>
                  <span><?php echo $year; ?></span>
                </div>

                <h5 class="articles-widget-content__card-title"><?php echo $articleTitle; ?></h5>

                <p class="articles-widget-content__excerpt"><?php echo $excerpt; ?></p>

                <div class="articles-widget-content__meta">
                  <span class="articles-widget-content__author">
                    <img src="<?php echo $avatar; ?>" alt="">
                    <?php echo $username; ?>
                  </span>
                  <span class="articles-widget-content__read">
                    <?php echo htmlspecialchars($languageService->get('read_more'), ENT_QUOTES, 'UTF-8'); ?>
                    <i class="bi bi-arrow-right-short" aria-hidden="true"></i>
                  </span>
                </div>
              </div>
            </a>

            <?php
        }
        ?>
      </div>
    </section>

    <?php
} else {
    echo $languageService->get('no_articles');
}
?>
