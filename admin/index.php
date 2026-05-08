<?php
require_once __DIR__ . '/config.php';

session_name(SESSION_NAME);
session_start();

$error = '';

// ── Logout ───────────────────────────────────────────────────
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

// ── Login POST ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    $pw = $_POST['password'] ?? '';
    if (password_verify($pw, ADMIN_PASSWORD_HASH)) {
        $_SESSION['logged_in']   = true;
        $_SESSION['csrf_token']  = bin2hex(random_bytes(32));
        header('Location: index.php');
        exit;
    }
    $error = '密碼錯誤，請重試。';
}

// ── Load news data ────────────────────────────────────────────
$posts = [];
if ($_SESSION['logged_in'] ?? false) {
    if (file_exists(NEWS_FILE)) {
        $raw = file_get_contents(NEWS_FILE);
        $data = json_decode($raw, true);
        $posts = $data['posts'] ?? [];
        usort($posts, fn($a, $b) => strcmp($b['date'], $a['date']));
    }
    // Ensure CSRF token exists
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

$loggedIn = $_SESSION['logged_in'] ?? false;
$csrf     = $_SESSION['csrf_token'] ?? '';
$msgOk    = isset($_GET['ok']);
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>玉里協天宮｜活動消息後台</title>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --bg:      #0f0f0f;
      --surface: #1c1c1c;
      --border:  #333;
      --gold:    #c8960c;
      --gold-lt: #f0c040;
      --text:    #e8e0d0;
      --muted:   #888;
      --danger:  #8b0000;
      --radius:  6px;
    }

    body {
      background: var(--bg);
      color: var(--text);
      font-family: 'Noto Sans TC', system-ui, sans-serif;
      font-size: 15px;
      line-height: 1.7;
      min-height: 100vh;
    }

    /* ── Layout ── */
    .wrap {
      max-width: 700px;
      margin: 0 auto;
      padding: 2.5rem 1.5rem 4rem;
    }

    /* ── Header ── */
    .site-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      border-bottom: 1px solid var(--border);
      padding-bottom: 1.25rem;
      margin-bottom: 2rem;
    }
    .site-title {
      font-size: 1rem;
      letter-spacing: 0.15em;
      color: var(--gold-lt);
    }
    .site-title small {
      display: block;
      font-size: 0.7rem;
      color: var(--muted);
      letter-spacing: 0.1em;
      margin-top: 2px;
    }
    .btn-logout {
      font-size: 0.8rem;
      color: var(--muted);
      text-decoration: none;
      border: 1px solid var(--border);
      padding: 0.3rem 0.85rem;
      border-radius: var(--radius);
      transition: color 0.2s, border-color 0.2s;
    }
    .btn-logout:hover { color: var(--text); border-color: var(--muted); }

    /* ── Flash messages ── */
    .flash {
      padding: 0.75rem 1rem;
      border-radius: var(--radius);
      margin-bottom: 1.5rem;
      font-size: 0.875rem;
    }
    .flash--ok    { background: #1a2a1a; border: 1px solid #3a6a3a; color: #8ec98e; }
    .flash--error { background: #2a1a1a; border: 1px solid #6a2a2a; color: #d08080; }

    /* ── Section title ── */
    h2 {
      font-size: 0.75rem;
      letter-spacing: 0.2em;
      text-transform: uppercase;
      color: var(--gold);
      margin-bottom: 1rem;
    }

    /* ── Add form ── */
    .card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 1.5rem;
      margin-bottom: 2rem;
    }

    .field { margin-bottom: 1rem; }
    label {
      display: block;
      font-size: 0.8rem;
      color: var(--muted);
      margin-bottom: 0.35rem;
      letter-spacing: 0.05em;
    }
    input[type="text"],
    input[type="date"],
    input[type="url"],
    input[type="password"],
    textarea {
      width: 100%;
      background: #111;
      border: 1px solid var(--border);
      border-radius: var(--radius);
      color: var(--text);
      padding: 0.55rem 0.85rem;
      font-size: 0.95rem;
      outline: none;
      transition: border-color 0.2s;
      font-family: inherit;
    }
    textarea {
      resize: vertical;
      min-height: 120px;
      line-height: 1.7;
    }
    input:focus, textarea:focus { border-color: var(--gold); }
    .field-hint {
      font-size: 0.72rem;
      color: var(--muted);
      margin-top: 0.3rem;
    }

    .btn-primary {
      background: var(--gold);
      color: #1a0a00;
      border: none;
      border-radius: var(--radius);
      padding: 0.6rem 1.5rem;
      font-size: 0.9rem;
      font-weight: 700;
      cursor: pointer;
      letter-spacing: 0.08em;
      transition: background 0.2s;
    }
    .btn-primary:hover { background: var(--gold-lt); }

    /* ── News list ── */
    .news-list { list-style: none; }
    .news-list li {
      display: grid;
      grid-template-columns: 110px 1fr auto;
      gap: 0.75rem;
      align-items: center;
      padding: 0.85rem 0;
      border-bottom: 1px solid var(--border);
      font-size: 0.9rem;
    }
    .news-list li:first-child { border-top: 1px solid var(--border); }
    .news-date { color: var(--gold); font-variant-numeric: tabular-nums; }
    .news-title-text {
      color: var(--text);
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .news-title-text a { color: var(--text); text-decoration: underline dotted; }

    .btn-delete {
      background: none;
      border: 1px solid #4a1a1a;
      color: #d08080;
      border-radius: var(--radius);
      padding: 0.25rem 0.6rem;
      font-size: 0.75rem;
      cursor: pointer;
      white-space: nowrap;
      transition: background 0.2s;
    }
    .btn-delete:hover { background: #2a1a1a; }

    .empty-state {
      color: var(--muted);
      font-size: 0.875rem;
      padding: 1.5rem 0;
      text-align: center;
    }

    /* ── Login page ── */
    .login-wrap {
      max-width: 380px;
      margin: 8vh auto 0;
      padding: 0 1.5rem;
      text-align: center;
    }
    .login-logo {
      font-size: 1.5rem;
      color: var(--gold-lt);
      letter-spacing: 0.2em;
      margin-bottom: 0.25rem;
    }
    .login-sub {
      font-size: 0.75rem;
      color: var(--muted);
      letter-spacing: 0.15em;
      margin-bottom: 2.5rem;
    }
    .login-wrap .card { text-align: left; }
    .link-back {
      display: inline-block;
      margin-top: 1.5rem;
      font-size: 0.8rem;
      color: var(--muted);
      text-decoration: none;
    }
    .link-back:hover { color: var(--text); }
  </style>
</head>
<body>

<?php if (!$loggedIn): ?>
<!-- ============================================================
     Login Page
     ============================================================ -->
<div class="login-wrap">
  <p class="login-logo">玉里協天宮</p>
  <p class="login-sub">活動消息後台管理</p>

  <?php if ($error): ?>
    <div class="flash flash--error"><?= e($error) ?></div>
  <?php endif; ?>

  <div class="card">
    <form method="post" action="index.php">
      <div class="field">
        <label for="pw">管理員密碼</label>
        <input type="password" id="pw" name="password" autofocus autocomplete="current-password" required>
      </div>
      <button type="submit" class="btn-primary" style="width:100%">登入</button>
    </form>
  </div>

  <a href="../index.html" class="link-back">← 返回網站首頁</a>
</div>

<?php else: ?>
<!-- ============================================================
     Admin Dashboard
     ============================================================ -->
<div class="wrap">
  <header class="site-header">
    <div class="site-title">
      玉里協天宮
      <small>活動消息後台</small>
    </div>
    <a href="index.php?logout=1" class="btn-logout">登出</a>
  </header>

  <?php if ($msgOk): ?>
    <div class="flash flash--ok">✓ 操作成功，消息已更新。</div>
  <?php endif; ?>

  <!-- ── Add Form ── -->
  <h2>新增活動消息</h2>
  <div class="card">
    <form method="post" action="save.php">
      <input type="hidden" name="action" value="add">
      <input type="hidden" name="csrf"   value="<?= e($csrf) ?>">
      <div class="field">
        <label for="title">消息標題 <span style="color:#d08080">*</span></label>
        <input type="text" id="title" name="title" placeholder="例：農曆六月廿四 關聖帝君聖誕千秋慶典" required>
      </div>
      <div class="field">
        <label for="date">日期 <span style="color:#d08080">*</span></label>
        <input type="date" id="date" name="date" value="<?= date('Y-m-d') ?>" required>
      </div>
      <div class="field">
        <label for="content">詳細內文（選填）</label>
        <textarea id="content" name="content" placeholder="在此輸入消息的詳細說明&#10;&#10;空一行會產生新段落"></textarea>
        <p class="field-hint">空一行（按兩次 Enter）= 新段落　／　單次換行會在同段落內換行</p>
      </div>
      <div class="field">
        <label for="link">外部連結（選填）</label>
        <input type="url" id="link" name="link" placeholder="https://...（若有外部網頁可填入）">
        <p class="field-hint">填入後，詳細頁底部會顯示「查看更多詳情」按鈕</p>
      </div>
      <button type="submit" class="btn-primary">新增消息</button>
    </form>
  </div>

  <!-- ── Current Posts ── -->
  <h2>目前消息（共 <?= count($posts) ?> 則）</h2>
  <?php if (empty($posts)): ?>
    <p class="empty-state">尚無任何消息，請使用上方表單新增。</p>
  <?php else: ?>
    <ul class="news-list">
      <?php foreach ($posts as $post): ?>
      <li>
        <span class="news-date"><?= e($post['date']) ?></span>
        <span class="news-title-text">
          <?php if (!empty($post['link'])): ?>
            <a href="<?= e($post['link']) ?>" target="_blank" rel="noopener"><?= e($post['title']) ?></a>
          <?php else: ?>
            <?= e($post['title']) ?>
          <?php endif; ?>
        </span>
        <form method="post" action="save.php"
              onsubmit="return confirm('確定要刪除此則消息嗎？')">
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="csrf"   value="<?= e($csrf) ?>">
          <input type="hidden" name="id"     value="<?= (int)$post['id'] ?>">
          <button type="submit" class="btn-delete">刪除</button>
        </form>
      </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</div>
<?php endif; ?>

</body>
</html>
