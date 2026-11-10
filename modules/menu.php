<?php
if (!defined('APP_ENTRY')) {
    http_response_code(404);
    exit;
}

function menu(): string
{
    $page = isset($_GET['page']) && is_string($_GET['page']) ? $_GET['page'] : 'view';
    $pages = ['view', 'add', 'edit', 'delete'];
    if (!in_array($page, $pages, true)) {
        $page = 'view';
    }

    $items = [
        'view' => 'Просмотр',
        'add' => 'Добавление записи',
        'edit' => 'Редактирование записи',
        'delete' => 'Удаление записи',
    ];

    $html = '<nav class="menu" aria-label="Основное меню"><div class="menu__main">';
    foreach ($items as $key => $label) {
        $active = $page === $key ? ' is-active' : '';
        $current = $page === $key ? ' aria-current="page"' : '';
        $html .= '<a class="menu__link' . $active . '" href="index.php?page=' . h($key) . '"' . $current . '>' . h($label) . '</a>';
    }
    $html .= '</div>';

    if ($page === 'view') {
        $sort = isset($_GET['sort']) && is_string($_GET['sort']) ? $_GET['sort'] : 'created';
        $sorts = [
            'created' => 'По порядку добавления',
            'surname' => 'По фамилии',
            'birth_date' => 'По дате рождения',
        ];
        if (!array_key_exists($sort, $sorts)) {
            $sort = 'created';
        }

        $html .= '<div class="menu__sub" aria-label="Сортировка записей">';
        foreach ($sorts as $key => $label) {
            $active = $sort === $key ? ' is-active' : '';
            $current = $sort === $key ? ' aria-current="true"' : '';
            $html .= '<a class="menu__sub-link' . $active . '" href="index.php?page=view&amp;sort=' . h($key) . '"' . $current . '>' . h($label) . '</a>';
        }
        $html .= '</div>';
    }

    $html .= '</nav>';
    return $html;
}
