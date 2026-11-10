<?php
declare(strict_types=1);

session_start();
define('APP_ENTRY', true);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/modules/menu.php';
require_once __DIR__ . '/modules/viewer.php';
require_once __DIR__ . '/modules/add.php';
require_once __DIR__ . '/modules/edit.php';
require_once __DIR__ . '/modules/delete.php';

$allowedPages = ['view', 'add', 'edit', 'delete'];
$page = isset($_GET['page']) && is_string($_GET['page']) ? $_GET['page'] : 'view';
if (!in_array($page, $allowedPages, true)) {
    $page = 'view';
}

$content = '';
try {
    $pdo = get_db_connection();

    switch ($page) {
        case 'add':
            $content = add_module($pdo);
            break;
        case 'edit':
            $content = edit_module($pdo);
            break;
        case 'delete':
            $content = delete_module($pdo);
            break;
        case 'view':
        default:
            $sort = isset($_GET['sort']) && is_string($_GET['sort']) ? $_GET['sort'] : 'created';
            $pageNumber = isset($_GET['page_num']) ? max(1, (int) $_GET['page_num']) : 1;
            $content = viewer_module($pdo, $sort, $pageNumber);
            break;
    }
} catch (Throwable $exception) {
    error_log($exception->getMessage());
    $content = '<section class="message error"><strong>Не удалось подключиться к базе данных.</strong><p>Проверьте настройки подключения в файле <code>config.php</code> и убедитесь, что структура базы данных создана с помощью файла <code>database.sql</code>.</p></section>';
}

?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Записная книжка для хранения контактных данных">
    <title>Телефонная книга</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <header class="site-header">
        <div class="site-header__inner">
            <div class="site-header__title">Телефонная книга</div>
            <div class="site-header__subtitle">Хранение и управление контактами</div>
        </div>
    </header>

    <?= menu() ?>

    <main class="container">
        <?= $content ?>
    </main>

    <footer class="site-footer">
        <div class="container">Записная книжка</div>
    </footer>
</body>
</html>
