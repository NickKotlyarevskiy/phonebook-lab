<?php
if (!defined('APP_ENTRY')) {
    http_response_code(404);
    exit;
}

function viewer_module(PDO $pdo, string $sort, int $pageNumber): string
{
    $orderBy = [
        'created' => 'id ASC',
        'surname' => 'surname ASC, name ASC, id ASC',
        'birth_date' => '(birth_date IS NULL) ASC, birth_date ASC, id ASC',
    ];
    if (!isset($orderBy[$sort])) {
        $sort = 'created';
    }

    $perPage = 10;
    $count = (int) $pdo->query('SELECT COUNT(*) FROM contacts')->fetchColumn();
    $totalPages = max(1, (int) ceil($count / $perPage));
    $pageNumber = min(max(1, $pageNumber), $totalPages);
    $offset = ($pageNumber - 1) * $perPage;

    $statement = $pdo->prepare('SELECT id, surname, name, lastname, gender, birth_date, phone, address, email, comment FROM contacts ORDER BY ' . $orderBy[$sort] . ' LIMIT :limit OFFSET :offset');
    $statement->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
    $statement->execute();
    $contacts = $statement->fetchAll();

    $html = '<section class="panel"><div class="section-heading"><h1>Контакты</h1><span class="record-count">Всего записей: ' . $count . '</span></div>';

    if ($contacts === []) {
        $html .= '<div class="message">Записная книжка пока пуста. Выберите пункт «Добавление записи», чтобы создать первый контакт.</div></section>';
        return $html;
    }

    $html .= '<div class="table-wrap"><table><thead><tr>'
        . '<th>Фамилия</th><th>Имя</th><th>Отчество</th><th>Пол</th><th>Дата рождения</th>'
        . '<th>Телефон</th><th>Адрес</th><th>E-mail</th><th>Комментарий</th>'
        . '</tr></thead><tbody>';

    foreach ($contacts as $contact) {
        $html .= '<tr>'
            . '<td>' . h($contact['surname']) . '</td>'
            . '<td>' . h($contact['name']) . '</td>'
            . '<td>' . h($contact['lastname'] ?: '—') . '</td>'
            . '<td>' . h($contact['gender'] ?: '—') . '</td>'
            . '<td>' . h(format_birth_date($contact['birth_date'])) . '</td>'
            . '<td>' . h($contact['phone'] ?: '—') . '</td>'
            . '<td>' . h($contact['address'] ?: '—') . '</td>'
            . '<td>' . h($contact['email'] ?: '—') . '</td>'
            . '<td>' . nl2br(h($contact['comment'] ?: '—')) . '</td>'
            . '</tr>';
    }

    $html .= '</tbody></table></div>';

    if ($totalPages > 1) {
        $html .= '<nav class="pagination" aria-label="Страницы записной книжки">';
        for ($number = 1; $number <= $totalPages; $number++) {
            $active = $number === $pageNumber ? ' is-active' : '';
            $html .= '<a class="pagination__link' . $active . '" href="index.php?page=view&amp;sort=' . h($sort) . '&amp;page_num=' . $number . '"' . ($number === $pageNumber ? ' aria-current="page"' : '') . '>' . $number . '</a>';
        }
        $html .= '</nav>';
    }

    $html .= '</section>';
    return $html;
}
