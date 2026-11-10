<?php
if (!defined('APP_ENTRY')) {
    http_response_code(404);
    exit;
}

function edit_module(PDO $pdo): string
{
    $contacts = $pdo->query('SELECT id, surname, name, lastname, gender, birth_date, phone, address, email, comment FROM contacts ORDER BY surname ASC, name ASC, id ASC')->fetchAll();
    $html = '<section class="panel edit-panel"><h1>Редактирование записи</h1>';

    if ($contacts === []) {
        return $html . '<div class="message">Нет записей для редактирования. Сначала добавьте контакт.</div></section>';
    }

    $ids = array_map(static fn (array $contact): int => (int) $contact['id'], $contacts);
    $requestedId = isset($_POST['id']) ? (int) $_POST['id'] : (isset($_GET['id']) ? (int) $_GET['id'] : 0);
    $selected = null;
    foreach ($contacts as $contact) {
        if ((int) $contact['id'] === $requestedId) {
            $selected = $contact;
            break;
        }
    }
    if ($selected === null) {
        $selected = $contacts[0];
    }
    $selectedId = (int) $selected['id'];
    $values = contact_values_from_row($selected);
    $errors = [];
    $saved = isset($_GET['saved']) && $_GET['saved'] === '1';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!csrf_is_valid()) {
            $errors[] = 'Сессия формы истекла. Обновите страницу и повторите отправку.';
        } else {
            $validation = validate_contact_input($_POST);
            $values = $validation['values'];
            $errors = $validation['errors'];

            if ($errors === []) {
                $sql = 'UPDATE contacts SET surname = :surname, name = :name, lastname = :lastname, gender = :gender, birth_date = :birth_date, phone = :phone, address = :address, email = :email, comment = :comment WHERE id = :id';
                try {
                    $data = $validation['data'];
                    $data['id'] = $selectedId;
                    $statement = $pdo->prepare($sql);
                    $statement->execute($data);
                    header('Location: index.php?page=edit&id=' . $selectedId . '&saved=1');
                    exit;
                } catch (PDOException $exception) {
                    error_log($exception->getMessage());
                    $errors[] = 'Ошибка: изменения не сохранены.';
                }
            }
        }
    }

    $html .= '<div class="edit-layout"><div class="edit-list"><h2>Выберите контакт</h2><ul>';
    foreach ($contacts as $contact) {
        $id = (int) $contact['id'];
        $active = $id === $selectedId ? ' is-current' : '';
        $html .= '<li><a class="edit-list__link' . $active . '" href="index.php?page=edit&amp;id=' . $id . '">' . h($contact['surname'] . ' ' . $contact['name']) . '</a></li>';
    }
    $html .= '</ul></div><div class="edit-form"><h2>Данные контакта</h2>';

    if ($saved) {
        $html .= '<div class="message success">Запись сохранена</div>';
    }
    if ($errors !== []) {
        $html .= '<div class="message error"><strong>Ошибка: изменения не сохранены</strong><ul>';
        foreach ($errors as $error) {
            $html .= '<li>' . h($error) . '</li>';
        }
        $html .= '</ul></div>';
    }

    $html .= '<form method="post" action="index.php?page=edit&amp;id=' . $selectedId . '" class="contact-form">'
        . '<input type="hidden" name="id" value="' . $selectedId . '">'
        . '<input type="hidden" name="csrf_token" value="' . h(csrf_token()) . '">'
        . '<div class="form-row"><label for="surname">Фамилия <span class="required">*</span></label><input id="surname" type="text" name="surname" value="' . h($values['surname']) . '" required maxlength="100"></div>'
        . '<div class="form-row"><label for="name">Имя <span class="required">*</span></label><input id="name" type="text" name="name" value="' . h($values['name']) . '" required maxlength="100"></div>'
        . '<div class="form-row"><label for="lastname">Отчество</label><input id="lastname" type="text" name="lastname" value="' . h($values['lastname']) . '" maxlength="100"></div>'
        . '<div class="form-row"><label for="gender">Пол</label><select id="gender" name="gender"><option value="">Не указан</option><option value="мужской"' . ($values['gender'] === 'мужской' ? ' selected' : '') . '>мужской</option><option value="женский"' . ($values['gender'] === 'женский' ? ' selected' : '') . '>женский</option></select></div>'
        . '<div class="form-row"><label for="date">Дата рождения</label><input id="date" type="date" name="date" value="' . h($values['date']) . '"></div>'
        . '<div class="form-row"><label for="phone">Телефон</label><input id="phone" type="tel" name="phone" value="' . h($values['phone']) . '" maxlength="40"></div>'
        . '<div class="form-row"><label for="location">Адрес</label><input id="location" type="text" name="location" value="' . h($values['location']) . '" maxlength="255"></div>'
        . '<div class="form-row"><label for="email">E-mail</label><input id="email" type="email" name="email" value="' . h($values['email']) . '" maxlength="190"></div>'
        . '<div class="form-row"><label for="comment">Комментарий</label><textarea id="comment" name="comment" rows="4">' . h($values['comment']) . '</textarea></div>'
        . '<div class="form-actions"><button class="form-btn" type="submit">Сохранить изменения</button></div>'
        . '</form></div></div></section>';

    return $html;
}
