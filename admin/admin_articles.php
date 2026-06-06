<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

use nexpell\LanguageService;
use nexpell\NavigationUpdater;// SEO Anpassung
use nexpell\AccessControl;
global $languageService;

$languageService->readPluginModule('articles');

// Den Admin-Zugriff für das Modul überprüfen
AccessControl::checkAdminAccess('articles');

// Pfade
$uploadDir   = __DIR__ . '/../images/';          // für allgemeine Uploads
$plugin_path = __DIR__ . '/../images/article/';  // für Bannerbild Upload
$filepath    = $plugin_path;                     // historisch

// Parameter aus URL lesen
$action = $_GET['action'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$sortBy = $_GET['sort_by'] ?? 'created_at';
$sortDir = ($_GET['sort_dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

// Max Artikel pro Seite
$perPage = 10;

// Whitelist für Sortierung
$allowedSorts = ['title', 'created_at'];
if (!in_array($sortBy, $allowedSorts)) {
    $sortBy = 'created_at';
}

// DELETE
if (isset($_GET['delete'])) {
    $id = (int)($_GET['id'] ?? 0);

    $CAPCLASS = new \nexpell\Captcha;
    if ($id > 0 && $CAPCLASS->checkCaptcha(0, $_GET['captcha_hash'] ?? '')) {

        // Artikel-Daten laden
        $bannerImage = '';
        $articleTitle = '';
        $stmt = $_database->prepare("SELECT banner_image, title FROM plugins_articles WHERE id = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->bind_result($bannerImage, $articleTitle);
            $found = (bool)$stmt->fetch();
            $stmt->close();
        } else {
            $found = false;
        }

        if (!$found) {
            nx_redirect('admincenter.php?site=admin_articles', 'danger', 'alert_not_found', false);
        }

        // DB löschen
        $stmtDel = $_database->prepare("DELETE FROM plugins_articles WHERE id = ?");
        if ($stmtDel) {
            $stmtDel->bind_param('i', $id);
            $stmtDel->execute();
            $ok = ($stmtDel->affected_rows > 0);
            $stmtDel->close();
        } else {
            $ok = false;
        }

        if (!$ok) {
            nx_redirect('admincenter.php?site=admin_articles', 'danger', 'alert_not_found', false);
        }

        nx_audit_delete('admin_articles', (string)$id, ($articleTitle !== '' ? $articleTitle : (string)$id), 'admincenter.php?site=admin_articles');

        // Bild löschen (falls vorhanden)
        if (!empty($bannerImage)) {
            $possiblePaths = [
                __DIR__ . '/../../../../images/article/' . $bannerImage,
                __DIR__ . '/../images/article/' . $bannerImage,
                __DIR__ . '/../../../../includes/plugins/articles/images/article/' . $bannerImage,
            ];
            foreach ($possiblePaths as $imagePath) {
                if (is_file($imagePath)) {
                    @unlink($imagePath);
                    break;
                }
            }
        }

        nx_redirect('admincenter.php?site=admin_articles', 'success', 'alert_deleted', false);
    }

    nx_redirect('admincenter.php?site=admin_articles', 'danger', 'alert_transaction_invalid', false);
}

// Artikel hinzufügen / bearbeiten
if (($action ?? '') === "add" || ($action ?? '') === "edit") {
    $id = intval($_GET['id'] ?? 0);
    $isEdit = $id > 0;

    // Default-Daten
    $data = [
        'category_id'   => 0,
        'title'         => '',
        'content'       => '',
        'slug'          => '',
        'banner_image'  => '',
        'sort_order'    => 0,
        'is_active'     => 0,
        'allow_comments'=> 0,
    ];

    // Beim Edit vorhandene Daten laden
    if ($isEdit) {
        $stmt = $_database->prepare("SELECT category_id, title, content, slug, banner_image, sort_order, is_active, allow_comments FROM plugins_articles WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->bind_result(
            $data['category_id'], $data['title'], $data['content'], $data['slug'],
            $data['banner_image'], $data['sort_order'], $data['is_active'], $data['allow_comments']
        );
        if (!$stmt->fetch()) {
            nx_redirect('admincenter.php?site=admin_articles', 'danger', 'alert_not_found', false);
        }
        $stmt->close();
    }

    $error = '';

   if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cat            = (int)($_POST['category_id'] ?? 0);
    $title          = trim($_POST['title'] ?? '');
    $content        = $_POST['message'] ?? '';
    $slug           = trim($_POST['slug'] ?? '');
    $sort_order     = (int)($_POST['sort_order'] ?? 0);
    $is_active      = isset($_POST['is_active']) ? 1 : 0;
    $allow_comments = isset($_POST['allow_comments']) ? 1 : 0;
    $filename       = $data['banner_image'];

    // Bannerbild-Upload prüfen
    if (!empty($_FILES['banner_image']['name'])) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $imageType = mime_content_type($_FILES['banner_image']['tmp_name']);

        if (in_array($imageType, $allowedTypes, true)) {
            $ext = strtolower(pathinfo($_FILES['banner_image']['name'], PATHINFO_EXTENSION));
            $filename = $isEdit ? $id . '.' . $ext : uniqid() . '.' . $ext;
            $targetPath = $plugin_path . $filename;

            if (move_uploaded_file($_FILES['banner_image']['tmp_name'], $targetPath)) {
                if ($isEdit && $data['banner_image'] && $data['banner_image'] !== $filename && file_exists($plugin_path . $data['banner_image'])) {
                    @unlink($plugin_path . $data['banner_image']);
                }
            } else {
                nx_alert('danger', 'alert_upload_failed', false);
                return;
            }
        } else {
            nx_alert('danger', 'alert_upload_error', false);
            return;
        }
    } elseif (!$isEdit) {
        nx_alert('warning', 'alert_missing_required', false);
        return;
    }

    if ($isEdit) {
        $stmt = $_database->prepare("
            UPDATE plugins_articles SET
                category_id = ?,
                title = ?,
                content = ?,
                slug = ?,
                banner_image = ?,
                sort_order = ?,
                is_active = ?,
                allow_comments = ?
            WHERE id = ?
        ");
        if (!$stmt) {
            nx_redirect('admincenter.php?site=admin_articles', 'danger', 'alert_save_failed', false);
            return;
        }
        $stmt->bind_param(
            'issssiiii',
            $cat,
            $title,
            $content,
            $slug,
            $filename,
            $sort_order,
            $is_active,
            $allow_comments,
            $id
        );
        $stmt->execute();
        $stmt->close();

        nx_audit_update('admin_articles', (string)$id, true, $title, 'admincenter.php?site=admin_articles');
    } else {
        if (!isset($_SESSION['userID'])) {
            nx_redirect('admincenter.php?site=admin_articles', 'danger', 'alert_access_denied', false);
        }

        $userID = (int)$_SESSION['userID'];

        $stmt = $_database->prepare("
            INSERT INTO plugins_articles
            (category_id, title, content, slug, banner_image, sort_order, updated_at, userID, is_active, allow_comments)
            VALUES
            (?, ?, ?, ?, ?, ?, UNIX_TIMESTAMP(), ?, ?, ?)
        ");
        if (!$stmt) {
            nx_redirect('admincenter.php?site=admin_articles', 'danger', 'alert_save_failed', false);
            return;
        }
        $stmt->bind_param(
            'issssiiii',
            $cat,
            $title,
            $content,
            $slug,
            $filename,
            $sort_order,
            $userID,
            $is_active,
            $allow_comments
        );
        $stmt->execute();
        $stmt->close();

        $newId = (int)($_database->insert_id ?? 0);
        nx_audit_create('admin_articles', (string)$newId, $title, 'admincenter.php?site=admin_articles');
    }

    // Datei-Name des aktuellen Admin-Moduls ermitteln
    $admin_file = basename(__FILE__, '.php');
    echo NavigationUpdater::updateFromAdminFile($admin_file);

    nx_redirect('admincenter.php?site=admin_articles', 'success', 'alert_saved', false);
}
?>
<style>
 .articles-admin {
    --articles-admin-preview-bg: rgba(248, 249, 250, 0.92);
    --articles-admin-preview-border: var(--bs-border-color);
  }

  [data-bs-theme="dark"] .articles-admin {
    --articles-admin-preview-bg: rgba(23, 29, 39, 0.96);
    --articles-admin-preview-border: var(--bs-border-color);
  }

 .banner-preview {
    aspect-ratio: 16 / 9;
    width: 100%;
    object-fit: cover;
    border-radius: .75rem;
    border: 1px solid var(--articles-admin-preview-border);
    background: var(--articles-admin-preview-bg);
  }
</style>
<div class="articles-admin">
    <form method="post" class="needs-validation" enctype="multipart/form-data" novalidate>
      <div class="row g-3">

        <!-- LEFT COLUMN -->
        <div class="col-12 col-lg-8">

          <!-- Content -->
          <div class="card mb-3">
            <div class="card-body p-3 p-lg-4">
              <div class="row g-3">

                <div class="col-12 col-md-6">
                  <label for="category_id" class="form-label"><?= $languageService->get('category') ?>:</label>
                  <select class="form-select" name="category_id" id="category_id" required>
                    <option value="" <?= empty($data['category_id']) ? 'selected' : '' ?> disabled hidden>
                      <?= $languageService->get('error_choose_catg') ?>
                    </option>
                    <?php
                      $stmtCat = $_database->prepare("SELECT id, name FROM plugins_articles_categories ORDER BY name");
                      $stmtCat->execute();
                      $resCat = $stmtCat->get_result();
                      while ($cat = $resCat->fetch_assoc()) {
                        $selected = ($cat['id'] == $data['category_id']) ? 'selected' : '';
                        echo '<option value="' . (int)$cat['id'] . '" ' . $selected . '>' . htmlspecialchars($cat['name']) . '</option>';
                      }
                      $stmtCat->close();
                    ?>
                  </select>
                  <div class="invalid-feedback"><?= $languageService->get('error_choose_catg') ?></div>
                </div>

                <div class="col-12 col-md-6">
                  <label for="title" class="form-label"><?= $languageService->get('label_title') ?>:</label>
                  <input class="form-control" type="text" name="title" id="title"
                         value="<?= htmlspecialchars($data['title'])?>" required>
                  <div class="invalid-feedback"><?= $languageService->get('error_choose_title') ?></div>
                </div>

                <div class="col-12">
                    <label for="content_editor" class="form-label">
                        <?= $languageService->get('label_content') ?>:
                    </label>
                    <!-- Vorher war hier CKEditor - für künftige Änderungen vermerken -->
                    <textarea
                        class="form-control lang-field"
                        data-editor="nx_editor"
                        name="message"
                        id="content_editor"
                        rows="10"
                        required
                    ><?= htmlspecialchars($data['content'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>

                    <div class="invalid-feedback">
                        <?= $languageService->get('error_content') ?>
                    </div>
                </div>

              </div>
            </div>
          </div>

          <!-- Meta + Status -->
          <div class="row g-3">

            <!-- Meta -->
            <div class="col-12 col-md-6">
                <div class="card h-100">
                <div class="card-body p-3 p-lg-4">
                  <div class="fw-semibold mb-2"><?= $languageService->get('label_meta') ?></div>

                <label for="slug" class="form-label"><?= $languageService->get('label_slug') ?>:</label>
                <div class="input-group">
                <input
                    class="form-control"
                    type="text"
                    name="slug"
                    id="slug"
                    value="<?= htmlspecialchars($data['slug']) ?>"
                    pattern="^[a-z0-9]+(?:-[a-z0-9]+)*$"
                >
                <button class="btn btn-outline-secondary" type="button" id="regenSlug">
                    <?= $languageService->get('btn_generate_slug') ?>
                </button>
                </div>

                <div class="form-text text-muted mb-4">
                    <?= $languageService->get('info_generate_slug') ?>
                </div>

                  <label for="sort_order" class="form-label"><?= $languageService->get('sort') ?>:</label>
                  <input class="form-control" type="number" name="sort_order" id="sort_order"
                         value="<?= htmlspecialchars($data['sort_order']) ?>" min="0" step="1">
                </div>
              </div>
            </div>

            <!-- Settings -->
            <div class="col-12 col-md-6">
                <div class="card h-100">
                    <div class="card-body p-3 p-lg-4 d-flex flex-column">
                        <div class="fw-semibold mb-3"><?= $languageService->get('settings') ?></div>

                        <!-- Switches -->
                        <div class="d-flex flex-column gap-2">
                            <div class="form-check form-switch w-100">
                            <input class="form-check-input" type="checkbox" name="is_active" id="is_active"
                                    <?= !empty($data['is_active']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="is_active"><?= $languageService->get('active') ?></label>
                            </div>

                            <div class="form-check form-switch w-100">
                            <input class="form-check-input" type="checkbox" name="allow_comments" id="allow_comments"
                                    <?= !empty($data['allow_comments']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="allow_comments"><?= $languageService->get('toggle_allow_comments') ?></label>
                            </div>
                        </div>

                        <div class="mt-auto">
                            <hr class="my-3">
                            <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <?= $isEdit ? $languageService->get('save') : $languageService->get('add') ?>
                            </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
          </div>
        </div>

        <!-- Rechte Box: Banner -->
        <div class="col-12 col-lg-4">
          <div class="sticky-col">
            <div class="card">
              <div class="card-body p-3 p-lg-4">
                <div class="fw-semibold mb-2"><?= $languageService->get('label_banner') ?></div>

                <?php if ($isEdit && $data['banner_image'] && file_exists($plugin_path . $data['banner_image'])): ?>
                  <div class="mb-3">
                    <img class="banner-preview"
                        src="/includes/plugins/articles/images/article/<?= htmlspecialchars($data['banner_image']) ?>"
                         alt="<?= htmlspecialchars($languageService->get('current_banner'), ENT_QUOTES, 'UTF-8') ?>">
                  </div>
                <?php endif; ?>

                <label for="banner_image" class="form-label">
                    <?= $isEdit ? $languageService->get('label_banner_new') : $languageService->get('label_banner_upload') ?>
                </label>
                <input class="form-control" type="file" name="banner_image" id="banner_image"
                       accept="image/*" <?= $isEdit ? '' : 'required' ?>>
              </div>
            </div>
          </div>
        </div>
      </div>
    </form>
</div>

<script>
  // Bootstrap validation (ohne CKEditor)
  (() => {
    const form = document.querySelector('.needs-validation');
    if (!form) return;

    form.addEventListener('submit', (event) => {
      // Bootstrap 5 Validation
      if (!form.checkValidity()) {
        event.preventDefault();
        event.stopPropagation();
      }

      form.classList.add('was-validated');
    }, false);
  })();

  const i18n = {
    missingTitleForSlug: <?= json_encode(
      $languageService->get('error_title_for_slug'),
      JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    ) ?>
  };

  // Slugify helper (Titel -> slug)
  function slugify(str) {
    return (str ?? '')
      .toString()
      .trim()
      .toLowerCase()
      .replace(/ä/g, 'ae').replace(/ö/g, 'oe').replace(/ü/g, 'ue').replace(/ß/g, 'ss')
      .replace(/[^a-z0-9\s-]/g, '')
      .replace(/\s+/g, '-')
      .replace(/-+/g, '-')
      .replace(/^-+|-+$/g, '');
  }

  const titleEl  = document.getElementById('title');
  const slugEl   = document.getElementById('slug');
  const regenBtn = document.getElementById('regenSlug');

  let slugManuallyEdited = false;

  // Guard: wenn Button gedrückt wird, aber kein Titel vorhanden ist
  function showMissingTitleFeedback() {
    if (!titleEl) return;

    // Zeigt eine native Validierungs-Meldung (Bootstrap greift mit was-validated auf)
    titleEl.setCustomValidity(i18n.missingTitleForSlug);
    titleEl.reportValidity();

    // Nach kurzer Zeit wieder "freigeben", damit normale Validierung greift
    window.setTimeout(() => {
      titleEl.setCustomValidity('');
    }, 2000);

    // Optional: Fokus zurück auf Titel
    titleEl.focus();
  }

  if (slugEl) {
    slugEl.addEventListener('input', () => { slugManuallyEdited = true; });
  }

  if (titleEl && slugEl) {
    titleEl.addEventListener('input', () => {
      // Auto-Generate nur, wenn Nutzer den Slug noch nicht manuell angefasst hat
      // und Slug-Feld aktuell leer ist
      if (!slugManuallyEdited && (!slugEl.value || slugEl.value.trim() === '')) {
        slugEl.value = slugify(titleEl.value);
      }
    });
  }

  if (regenBtn && titleEl && slugEl) {
    regenBtn.addEventListener('click', () => {
      const title = titleEl.value.trim();

      // Kein Titel => keine Slug-Generierung + Feedback
      if (!title.length) {
        showMissingTitleFeedback();
        return;
      }

      slugEl.value = slugify(title);
      slugManuallyEdited = false;
      slugEl.focus();
    });
  }
</script>
<?php

} elseif (($action ?? '') === 'addcategory' || ($action ?? '') === 'editcategory') {
    $isEdit = $action === 'editcategory';
    $errorCat = '';
    $cat_name = '';
    $cat_description = '';
    $editId = 0;

    if ($isEdit) {
        $editId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $stmt = $_database->prepare("SELECT name, description FROM plugins_articles_categories WHERE id = ?");
        $stmt->bind_param("i", $editId);
        $stmt->execute();
        $result = $stmt->get_result();
        $catData = $result->fetch_assoc();
        $stmt->close();

        if ($catData) {
            $cat_name = $catData['name'];
            $cat_description = $catData['description'];
        } else {
            nx_alert('danger', 'alert_not_found', false);
        }
    }

    // Speichern
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cat_name'])) {
        $cat_name = trim($_POST['cat_name']);
        $cat_description = trim($_POST['cat_description'] ?? '');
        if ($cat_name === '') {
            nx_alert('danger', 'alert_missing_required', false);
        } else {
            if ($isEdit && $editId > 0) {
                $stmt = $_database->prepare("UPDATE plugins_articles_categories SET name = ?, description = ? WHERE id = ?");
                $stmt->bind_param("ssi", $cat_name, $cat_description, $editId);
                $stmt->execute();
                $stmt->close();

                nx_audit_update('admin_articles', (string)$editId, true, $cat_name, 'admincenter.php?site=admin_articles&action=categories');
            } else {
                $stmt = $_database->prepare("INSERT INTO plugins_articles_categories (name, description) VALUES (?, ?)");
                $stmt->bind_param("ss", $cat_name, $cat_description);
                $stmt->execute();
                $stmt->close();

                $newId = (int)($_database->insert_id ?? 0);
                nx_audit_create('admin_articles', (string)$newId, $cat_name, 'admincenter.php?site=admin_articles&action=categories');
            }
            nx_redirect('admincenter.php?site=admin_articles&action=categories', 'success', 'alert_saved', false);
        }
    }
    ?>
    <div class="card shadow-sm mt-4">
        <div class="card-header">
            <div class="card-title">
                <i class="bi bi-tags"></i> <?= $languageService->get('category') ?>
                <small class="text-muted"><?= $isEdit ? $languageService->get('edit') : $languageService->get('add') ?></small>
            </div>
        </div>
        <div class="card-body">
            <form method="post">
                <div class="mb-3">
                    <label for="cat_name" class="form-label"><?= $languageService->get('category_name') ?>:</label>
                    <input type="text"
                            class="form-control"
                            id="cat_name"
                            name="cat_name"
                            value="<?= htmlspecialchars($cat_name) ?>"
                            required>
                </div>
                <div class="mb-3">
                    <label for="cat_description" class="form-label"><?= $languageService->get('description') ?>:</label>
                    <textarea class="form-control"
                                id="cat_description"
                                name="cat_description"
                                rows="3"><?= htmlspecialchars($cat_description) ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary">
                    <?= $isEdit ? $languageService->get('save') : $languageService->get('add') ?>
                </button>
            </form>
        </div>
    </div>
    <?php
}
 elseif (($action ?? '') === 'categories') {
    $errorCat = '';

    // Kategorie löschen
    if (isset($_GET['delcat'])) {
        $delcat = (int)$_GET['delcat'];

        $CAPCLASS = new \nexpell\Captcha;
        if (!$CAPCLASS->checkCaptcha(0, $_GET['captcha_hash'] ?? '')) {
            nx_redirect('admincenter.php?site=admin_articles&action=categories', 'danger', 'alert_transaction_invalid', false);
        }

        $catName = '';
        $stmt_name = $_database->prepare("SELECT name FROM plugins_articles_categories WHERE id = ? LIMIT 1");
        $stmt_name->bind_param("i", $delcat);
        $stmt_name->execute();
        $res = $stmt_name->get_result();
        if ($res && ($r = $res->fetch_assoc())) {
            $catName = trim((string)$r['name']);
        }
        $stmt_name->close();

        $stmt = $_database->prepare("DELETE FROM plugins_articles_categories WHERE id = ?");
        $stmt->bind_param("i", $delcat);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            nx_audit_delete('admin_articles', (string)$delcat, ($catName !== '' ? $catName : (string)$delcat), 'admincenter.php?site=admin_articles&action=categories');
            nx_redirect('admincenter.php?site=admin_articles&action=categories', 'success', 'alert_deleted', false);
        }
        $stmt->close();

        nx_redirect('admincenter.php?site=admin_articles&action=categories', 'danger', 'alert_not_found', false);
    }

    // Kategorien laden inkl. Beschreibung
    $result = $_database->query("SELECT id, name, description FROM plugins_articles_categories ORDER BY name");

    // Transaction Hash für Delete-Buttons
    $CAPCLASS = new \nexpell\Captcha;
    $CAPCLASS->createTransaction();
    $hash = $CAPCLASS->getHash();
    ?>

    <div class="card shadow-sm mt-4">
        <div class="card-header">
            <div class="card-title">
                <i class="bi bi-tags"></i> <span><?= $languageService->get('category') ?></span>
                <small class="text-muted"><?= $languageService->get('label_manage_catg') ?></small>
            </div>
        </div>
        <div class="card-body">
            <a href="admincenter.php?site=admin_articles&action=addcategory"
                class="btn btn-secondary mb-3">
                <?= $languageService->get('add') ?>
            </a>
                <table class="table mt-2">
                    <thead>
                    <tr>
                        <th><?= $languageService->get('id') ?></th>
                        <th><?= $languageService->get('name') ?></th>
                        <th><?= $languageService->get('description') ?></th>
                        <th><?= $languageService->get('actions') ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php while ($cat = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= (int)$cat['id'] ?></td>
                            <td><?= htmlspecialchars($cat['name']) ?></td>
                            <td><?= htmlspecialchars($cat['description']) ?></td>
                            <td>
                                <a href="admincenter.php?site=admin_articles&action=editcategory&id=<?= (int)$cat['id'] ?>"
                                   class="btn btn-warning d-inline-flex align-items-center gap-1 w-auto"><i class="bi bi-pencil-square"></i> <?= $languageService->get('edit') ?></a>
                                <?php
                                    $catIdInt = (int)$cat['id'];
                                    $deleteUrl = 'admincenter.php?site=admin_articles&action=categories&delcat=' . $catIdInt . '&captcha_hash=' . rawurlencode($hash);
                                ?>
                                <button type="button"
                                        class="btn btn-danger d-inline-flex align-items-center gap-1 w-auto"
                                        data-bs-toggle="modal"
                                        data-bs-target="#confirmDeleteModal"
                                        data-delete-url="<?= htmlspecialchars($deleteUrl, ENT_QUOTES, 'UTF-8') ?>">
                                    <i class="bi bi-trash3"></i> <?= $languageService->get('delete') ?>
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php
}
 else {

    // Artikelliste anzeigen
    $result = $_database->query("SELECT a.id, a.title, a.sort_order, a.is_active, c.name as category_name FROM plugins_articles a LEFT JOIN plugins_articles_categories c ON a.category_id = c.id ORDER BY a.sort_order ASC, a.title ASC");

    // Transaction Hash für Delete-Buttons
    $CAPCLASS = new \nexpell\Captcha;
    $CAPCLASS->createTransaction();
    $hash = $CAPCLASS->getHash();
    ?>

    <div class="card shadow-sm mt-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div class="card-title">
                <i class="bi bi-journal-text"></i> <span><?= $languageService->get('label_manage_articles') ?></span>
            </div>
        </div>
            <div class="card-body">
                <a href="admincenter.php?site=admin_articles&action=add" class="btn btn-secondary me-2 mb-3"><?= $languageService->get('new') ?></a>
                <a href="admincenter.php?site=admin_articles&action=categories" class="btn btn-secondary mb-3"><?= $languageService->get('categories') ?></a>

                <div class="table-responsive">
                    <table class="table">
                        <thead>
                        <tr>
                            <th><?= $languageService->get('id') ?></th>
                            <th><?= $languageService->get('title') ?></th>
                            <th><?= $languageService->get('category') ?></th>
                            <th><?= $languageService->get('sort') ?></th>
                            <th><?= $languageService->get('active') ?></th>
                            <th><?= $languageService->get('actions') ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= (int)$row['id'] ?></td>
                                <td><?= htmlspecialchars($row['title']) ?></td>
                                <td><?= htmlspecialchars($row['category_name'] ?? '-') ?></td>
                                <td><?= (int)$row['sort_order'] ?></td>
                                <td><?= $row['is_active'] ? '<span class="badge bg-success">' . $languageService->get('yes') . '</span>' : '<span class="badge bg-secondary">' . $languageService->get('no') . '</span>' ?></td>
                                <td>
                                    <a href="admincenter.php?site=admin_articles&action=edit&id=<?= (int)$row['id'] ?>" class="btn btn-warning d-inline-flex align-items-center gap-1 w-auto"><i class="bi bi-pencil-square"></i> <?= $languageService->get('edit') ?></a>
                                    <?php
                                        $articleIdInt = (int)$row['id'];
                                        $deleteUrl = 'admincenter.php?site=admin_articles&delete=true&id=' . $articleIdInt . '&captcha_hash=' . rawurlencode($hash);
                                    ?>
                                    <button type="button"
                                            class="btn btn-danger d-inline-flex align-items-center gap-1 w-auto"
                                            data-bs-toggle="modal"
                                            data-bs-target="#confirmDeleteModal"
                                            data-delete-url="<?= htmlspecialchars($deleteUrl, ENT_QUOTES, 'UTF-8') ?>">
                                        <i class="bi bi-trash3"></i> <?= $languageService->get('delete') ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
        </div>
    </div>
<?php
}
?>
