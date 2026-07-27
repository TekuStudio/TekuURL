<?php
require_once __DIR__ . '/lang.php';
require_once __DIR__ . '/config.php';
requireLogin();
$user = getCurrentUser($tekupdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['original_url'])) {
    if (!csrfVerify()) {
        header("Location: links.php");
        exit;
    }
    if (!checkRateLimit(getClientIp(), 'create_link', $tekupdo)) {
        setFlash('error', $lang === 'es' ? 'Demasiadas peticiones. Espera un momento.' : 'Too many requests. Please wait.');
        header("Location: links.php");
        exit;
    }

    $originalUrl = trim($_POST['original_url']);
    $customCode = trim($_POST['custom_code'] ?? '');
    $linkTitle = trim($_POST['title'] ?? '');
    $expiresAt = !empty($_POST['expires_at']) ? $_POST['expires_at'] : null;
    $linkPassword = $_POST['link_password'] ?? '';
    $tagsInput = trim($_POST['tags'] ?? '');

    if (!filter_var($originalUrl, FILTER_VALIDATE_URL)) {
        setFlash('error', $lang === 'es' ? 'URL invalida.' : 'Invalid URL.');
    } elseif (!checkUserQuota($user['id'], $user['plan_status'], $tekupdo)) {
        setFlash('error', $lang === 'es' ? 'Limite de enlaces alcanzado.' : 'Link limit reached for your plan.');
    } else {
        $shortCode = $customCode ?: generateShortCode();
        $checkStmt = $tekupdo->prepare("SELECT id FROM shortened_urls WHERE short_code = ?");
        $checkStmt->execute([$shortCode]);
        if ($checkStmt->fetch()) {
            setFlash('error', $lang === 'es' ? 'El codigo ya existe.' : 'Short code already exists.');
        } else {
            $qrFolder = __DIR__ . '/uploads/qrcodes/';
            if (!is_dir($qrFolder)) mkdir($qrFolder, 0755, true);
            $qrFileName = $shortCode . '.png';
            $qrFullPath = $qrFolder . $qrFileName;

            $qrUrl = BASE_URL . '/' . $shortCode;
            $apiUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($qrUrl) . '&format=png&bgcolor=ffffff&color=000000';
            @file_put_contents($qrFullPath, @file_get_contents($apiUrl));

            $passwordHash = !empty($linkPassword) ? password_hash($linkPassword, PASSWORD_BCRYPT) : null;

            $stmt = $tekupdo->prepare("INSERT INTO shortened_urls (user_id, original_url, short_code, qr_code_path, title, expires_at, link_password_hash) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$user['id'], $originalUrl, $shortCode, $qrFullPath, $linkTitle ?: null, $expiresAt, $passwordHash]);
            $linkId = $tekupdo->lastInsertId();

                if ($tagsInput) {
                $tagNames = array_unique(array_filter(array_map('trim', explode(',', $tagsInput))));
                foreach ($tagNames as $tagName) {
                    if (strlen($tagName) > 50) continue;
                    $tagStmt = $tekupdo->prepare("INSERT IGNORE INTO tags (user_id, name) VALUES (?, ?)");
                    $tagStmt->execute([$user['id'], strtolower($tagName)]);
                    $findTag = $tekupdo->prepare("SELECT id FROM tags WHERE user_id = ? AND name = ?");
                    $findTag->execute([$user['id'], strtolower($tagName)]);
                    $tagId = $findTag->fetchColumn();
                    if ($tagId) {
                        $linkTagStmt = $tekupdo->prepare("INSERT IGNORE INTO link_tags (link_id, tag_id) VALUES (?, ?)");
                        $linkTagStmt->execute([$linkId, $tagId]);
                    }
                }
            }

            resetRateLimit(getClientIp(), 'create_link', $tekupdo);
            setFlash('success', $lang === 'es' ? 'Enlace creado.' : 'Link created.');
            header("Location: links.php");
            exit;
        }
    }
    header("Location: links.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle'])) {
    if (!csrfVerify()) {
        header("Location: links.php");
        exit;
    }
    $toggleId = (int)$_POST['toggle'];
    $stmt = $tekupdo->prepare("UPDATE shortened_urls SET is_active = NOT is_active WHERE id = ? AND user_id = ?");
    $stmt->execute([$toggleId, $user['id']]);
    setFlash('success', $lang === 'es' ? 'Estado actualizado.' : 'Status updated.');
    header("Location: links.php");
    exit;
}

$search = $_GET['search'] ?? '';
$tagFilter = $_GET['tag'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;

$where = "WHERE su.user_id = ?";
$params = [$user['id']];

if ($search) {
    $where .= " AND (su.original_url LIKE ? OR su.short_code LIKE ? OR su.title LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

if ($tagFilter) {
    $where .= " AND su.id IN (SELECT lt.link_id FROM link_tags lt JOIN tags t ON lt.tag_id = t.id WHERE t.user_id = ? AND t.name = ?)";
    $params[] = $user['id'];
    $params[] = strtolower($tagFilter);
}

$countStmt = $tekupdo->prepare("SELECT COUNT(*) FROM shortened_urls su {$where}");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();
$totalPages = max(1, ceil($total / $perPage));

$stmt = $tekupdo->prepare("SELECT su.*, (SELECT COUNT(*) FROM link_metrics lm WHERE lm.url_id = su.id) as click_count FROM shortened_urls su {$where} ORDER BY su.created_at DESC LIMIT {$perPage} OFFSET {$offset}");
$stmt->execute($params);
$links = $stmt->fetchAll();

$allTags = $tekupdo->prepare("SELECT t.name, COUNT(lt.link_id) as link_count FROM tags t LEFT JOIN link_tags lt ON t.id = lt.tag_id WHERE t.user_id = ? GROUP BY t.id, t.name ORDER BY t.name");
$allTags->execute([$user['id']]);
$userTags = $allTags->fetchAll();

include __DIR__ . '/views/layout_header.php';
?>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem;">
    <div>
        <h1 style="font-size:1.75rem;font-weight:700;text-transform:uppercase;margin-bottom:0.25rem;"><?= e($t['links_title']) ?></h1>
        <p style="font-size:0.75rem;color:#999;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;"><?= formatNumber($total) ?> <?= e($lang === 'es' ? 'enlaces' : 'links') ?> &middot; <?= e($lang === 'es' ? 'Limite' : 'Limit') ?>: <?= formatNumber(getLinkLimit($user['plan_status'])) ?></p>
    </div>
    <button onclick="document.getElementById('createModal').classList.add('active');" class="nb-btn nb-btn-filled nb-btn-sm">
        <i class="fas fa-plus"></i> <?= e($t['links_create']) ?>
    </button>
</div>

<?php if (!empty($userTags)): ?>
<div style="display:flex;flex-wrap:wrap;gap:0.35rem;margin-bottom:1rem;">
    <a href="links.php" class="nb-tag <?= !$tagFilter ? 'nb-tag-filled' : '' ?>" style="font-size:0.55rem;"><?= e($lang === 'es' ? 'Todos' : 'All') ?></a>
    <?php foreach ($userTags as $ut): ?>
    <a href="links.php?tag=<?= urlencode($ut['name']) ?>" class="nb-tag <?= $tagFilter === $ut['name'] ? 'nb-tag-filled' : '' ?>" style="font-size:0.55rem;"><?= e($ut['name']) ?> (<?= $ut['link_count'] ?>)</a>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="nb-card mb-2" style="padding:1rem;">
    <form method="GET" style="display:flex;gap:0.5rem;">
        <input type="text" name="search" value="<?= e($search) ?>" class="nb-input" placeholder="<?= e($lang === 'es' ? 'Buscar por URL o codigo...' : 'Search by URL or code...') ?>" style="box-shadow:none;border-width:2px;flex:1;">
        <button type="submit" class="nb-btn nb-btn-sm"><i class="fas fa-search"></i></button>
        <?php if ($search): ?>
        <a href="links.php" class="nb-btn nb-btn-ghost nb-btn-sm"><i class="fas fa-times"></i></a>
        <?php endif; ?>
    </form>
</div>

<?php if (empty($links)): ?>
<div class="nb-card">
    <div class="nb-empty">
        <div class="nb-empty-icon"><i class="fas fa-link"></i></div>
        <div class="nb-empty-text"><?= $search ? e($lang === 'es' ? 'Sin resultados' : 'No results') : e($t['links_empty']) ?></div>
        <div class="nb-empty-sub"><?= $search ? e($lang === 'es' ? 'Intenta con otro termino de busqueda.' : 'Try a different search term.') : e($lang === 'es' ? 'Crea tu primer enlace para empezar a rastrear.' : 'Create your first link to start tracking.') ?></div>
        <?php if (!$search): ?>
        <button onclick="document.getElementById('createModal').classList.add('active');" class="nb-btn nb-btn-filled nb-btn-sm"><i class="fas fa-plus"></i> <?= e($t['links_create']) ?></button>
        <?php endif; ?>
    </div>
</div>
<?php else: ?>
<div class="nb-card" style="padding:0;overflow:hidden;">
    <div class="nb-table-wrap" style="border:none;">
        <table class="nb-table">
            <thead>
                <tr>
                    <th><?= e($lang === 'es' ? 'Codigo' : 'Code') ?></th>
                    <th><?= e($lang === 'es' ? 'URL Destino' : 'Destination') ?></th>
                    <th style="text-align:center;"><?= e($t['links_clicks']) ?></th>
                    <th style="text-align:center;"><?= e($t['links_status']) ?></th>
                    <th><?= e($t['links_created']) ?></th>
                    <th style="text-align:center;"><?= e($t['links_qr']) ?></th>
                    <th style="text-align:center;"><?= e($t['links_actions']) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($links as $l): ?>
                <tr>
                    <td>
                        <span class="text-mono" style="font-size:0.8rem;"><?= e($l['short_code']) ?></span>
                        <?php if ($l['title']): ?>
                        <div style="font-size:0.6rem;color:#999;max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?= e($l['title']) ?>"><?= e($l['title']) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($l['link_password_hash'])): ?>
                        <span class="nb-badge nb-badge-filled" style="font-size:0.4rem;margin-left:0.25rem;"><i class="fas fa-lock"></i></span>
                        <?php endif; ?>
                        <?php if ($l['expires_at'] && isLinkExpired($l['expires_at'])): ?>
                        <span class="nb-badge nb-badge-danger" style="font-size:0.4rem;margin-left:0.25rem;"><?= e($t['links_expired']) ?></span>
                        <?php elseif ($l['expires_at']): ?>
                        <div style="font-size:0.5rem;color:#CCC;" title="<?= e($l['expires_at']) ?>"><?= e($lang === 'es' ? 'Exp' : 'Exp') ?>: <?= date('d/m/Y', strtotime($l['expires_at'])) ?></div>
                        <?php endif; ?>
                    </td>
                    <td style="max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#666;font-size:0.75rem;" title="<?= e($l['original_url']) ?>"><?= e($l['original_url']) ?></td>
                    <td style="text-align:center;"><a href="link_analytics.php?id=<?= $l['id'] ?>" style="font-weight:700;text-decoration:underline;"><?= formatNumber($l['click_count']) ?></a></td>
                    <td style="text-align:center;">
                        <form method="POST" style="display:inline;">
                            <?= csrfField() ?>
                            <input type="hidden" name="toggle" value="<?= $l['id'] ?>">
                            <button type="submit" style="background:none;border:none;padding:0;cursor:pointer;">
                                <span class="nb-badge <?= $l['is_active'] ? 'nb-badge-success' : 'nb-badge-muted' ?>"><?= $l['is_active'] ? e($t['links_active']) : e($t['links_inactive']) ?></span>
                            </button>
                        </form>
                    </td>
                    <td style="font-size:0.7rem;color:#999;"><?= timeAgo($l['created_at']) ?></td>
                    <td style="text-align:center;">
                        <?php if ($l['qr_code_path'] && file_exists($l['qr_code_path'])): ?>
                        <a href="<?= e($l['qr_code_path']) ?>" target="_blank" class="nb-btn nb-btn-xs nb-btn-ghost" title="QR"><i class="fas fa-qrcode"></i></a>
                        <?php endif; ?>
                    </td>
                    <td style="text-align:center;">
                        <div style="display:flex;gap:0.35rem;justify-content:center;">
                            <a href="link_analytics.php?id=<?= $l['id'] ?>" class="nb-btn nb-btn-xs" title="Analytics"><i class="fas fa-chart-line"></i></a>
                            <button onclick="copyLink('<?= e($l['short_code']) ?>')" class="nb-btn nb-btn-xs" title="Copy"><i class="fas fa-copy"></i></button>
                            <form method="POST" action="delete_link.php" style="display:inline;" onsubmit="return confirm('<?= e($t['links_delete_confirm']) ?>')">
                                <?= csrfField() ?>
                                <input type="hidden" name="link_id" value="<?= $l['id'] ?>">
                                <button type="submit" class="nb-btn nb-btn-xs nb-btn-danger" title="Delete"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPages > 1): ?>
    <div style="border-top:3px solid #000;padding:1rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:0.5rem;">
        <span style="font-size:0.7rem;font-weight:700;text-transform:uppercase;color:#999;"><?= e($lang === 'es' ? 'Pagina' : 'Page') ?> <?= $page ?> <?= e($lang === 'es' ? 'de' : 'of') ?> <?= $totalPages ?></span>
        <div style="display:flex;gap:0.35rem;">
            <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>" class="nb-btn nb-btn-xs"><i class="fas fa-chevron-left"></i></a>
            <?php endif; ?>
            <?php
            $start = max(1, $page - 2);
            $end = min($totalPages, $page + 2);
            for ($i = $start; $i <= $end; $i++): ?>
            <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>" class="nb-btn nb-btn-xs <?= $i === $page ? 'nb-btn-filled' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
            <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>" class="nb-btn nb-btn-xs"><i class="fas fa-chevron-right"></i></a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<div id="createModal" class="nb-modal-overlay" onclick="if(event.target===this)this.classList.remove('active');">
    <div class="nb-modal" onclick="event.stopPropagation()">
        <div class="nb-modal-header">
            <span><i class="fas fa-plus" style="margin-right:0.5rem;"></i> <?= e($t['links_create_title']) ?></span>
            <button onclick="this.closest('.nb-modal-overlay').classList.remove('active');" class="nb-modal-close">&times;</button>
        </div>
        <div class="nb-modal-body">
            <form method="POST" style="display:flex;flex-direction:column;gap:1.25rem;">
                <?= csrfField() ?>
                <div>
                    <label style="display:block;font-size:0.7rem;font-weight:700;text-transform:uppercase;margin-bottom:0.5rem;letter-spacing:0.05em;"><?= e($t['links_original_url']) ?> *</label>
                    <input type="url" name="original_url" required class="nb-input" placeholder="https://example.com/my-very-long-url">
                </div>
                <div>
                    <label style="display:block;font-size:0.7rem;font-weight:700;text-transform:uppercase;margin-bottom:0.5rem;letter-spacing:0.05em;"><?= e($t['links_title_label']) ?></label>
                    <input type="text" name="title" class="nb-input" placeholder="<?= e($lang === 'es' ? 'Mi enlace de campana' : 'My campaign link') ?>" maxlength="255">
                </div>
                <div>
                    <label style="display:block;font-size:0.7rem;font-weight:700;text-transform:uppercase;margin-bottom:0.5rem;letter-spacing:0.05em;"><?= e($t['links_custom_code']) ?></label>
                    <input type="text" name="custom_code" class="nb-input" placeholder="<?= e($lang === 'es' ? 'mi-codigo-personalizado' : 'my-custom-code') ?>" maxlength="12" pattern="[a-zA-Z0-9_-]+">
                    <p style="font-size:0.6rem;color:#999;margin-top:0.35rem;font-weight:600;text-transform:uppercase;"><?= e($lang === 'es' ? 'Dejar vacio para generar automaticamente' : 'Leave empty to auto-generate') ?></p>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                    <div>
                        <label style="display:block;font-size:0.7rem;font-weight:700;text-transform:uppercase;margin-bottom:0.5rem;letter-spacing:0.05em;"><?= e($t['links_expires']) ?></label>
                        <input type="datetime-local" name="expires_at" class="nb-input" min="<?= date('Y-m-d\TH:i') ?>">
                        <p style="font-size:0.6rem;color:#999;margin-top:0.35rem;font-weight:600;text-transform:uppercase;"><?= e($t['links_no_expiry']) ?></p>
                    </div>
                    <div>
                        <label style="display:block;font-size:0.7rem;font-weight:700;text-transform:uppercase;margin-bottom:0.5rem;letter-spacing:0.05em;"><?= e($t['links_password']) ?></label>
                        <input type="text" name="link_password" class="nb-input" placeholder="<?= e($lang === 'es' ? 'Sin proteccion' : 'No password') ?>" maxlength="100">
                    </div>
                </div>
                <div>
                    <label style="display:block;font-size:0.7rem;font-weight:700;text-transform:uppercase;margin-bottom:0.5rem;letter-spacing:0.05em;"><?= e($t['links_tags']) ?></label>
                    <input type="text" name="tags" class="nb-input" placeholder="<?= e($lang === 'es' ? 'campana, marketing, ene2024' : 'campaign, marketing, jan2024') ?>" maxlength="200">
                    <p style="font-size:0.6rem;color:#999;margin-top:0.35rem;font-weight:600;text-transform:uppercase;"><?= e($lang === 'es' ? 'Separar con comas' : 'Separate with commas') ?></p>
                </div>
                <button type="submit" class="nb-btn nb-btn-filled" style="width:100%;justify-content:center;"><i class="fas fa-cut"></i> <?= e($t['links_submit']) ?></button>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/views/layout_footer.php'; ?>
