<?php
if (!defined('APP_ENTRY')) {
    http_response_code(404);
    exit;
}

function delete_module(PDO $pdo): string
{
    $notice = '';
    $noticeType = 'success';

    if (isset($_GET['id'])) {
        $id = filter_var($_GET['id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($id !== false) {
            $find = $pdo->prepare('SELECT surname FROM contacts WHERE id = :id');
            $find->execute(['id' => $id]);
            $contact = $find->fetch();

            if ($contact) {
                $delete = $pdo->prepare('DELETE FROM contacts WHERE id = :id');
                $delete->execute(['id' => $id]);
                header('Location: index.php?page=delete&deleted=' . rawurlencode((string) $contact['surname']));
                exit;
            }

            $notice = 'Запись не найдена или уже была удалена.';
            $noticeType = 'error';
        }
    }

    if (isset($_GET['deleted']) && is_string($_GET['deleted']) && $_GET['deleted'] !== '') {
        $notice = 'Запись с фамилией ' . $_GET['deleted'] . ' удалена';
    }

    $contacts = $pdo->query('SELECT id, surname, name, lastname FROM contacts ORDER BY surname ASC, name ASC, id ASC')->fetchAll();
    $html = '<section class="panel"><h1>Удаление записи</h1>';

    if ($notice !== '') {
        $html .= '<div class="message ' . ($noticeType === 'success' ? 'success' : 'error') . '">' . h($notice) . '</div>';
    }

    if ($contacts === []) {
        $html .= '<div class="message">В записной книжке нет контактов для удаления.</div>';
    } else {
        $html .= '<p class="section-description">Выберите контакт, который необходимо удалить.</p><ul class="delete-list">';
        foreach ($contacts as $contact) {
            $label = trim((string) $contact['surname'] . ' ' . first_letter((string) $contact['name']) . ' ' . first_letter((string) ($contact['lastname'] ?? '')));
            $html .= '<li><a class="delete-list__link" href="index.php?page=delete&amp;id=' . (int) $contact['id'] . '" onclick="return confirm(\'Удалить выбранную запись?\')">' . h($label) . '</a></li>';
        }
        $html .= '</ul>';
    }

    $html .= '</section>';
    return $html;
}
