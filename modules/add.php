<?php
if (!defined('APP_ENTRY')) {
    http_response_code(404);
    exit;
}

function add_module(PDO $pdo): string
{
    $values = empty_contact_values();
    $errors = [];
    $notice = $_SESSION['add_notice'] ?? null;
    unset($_SESSION['add_notice']);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!csrf_is_valid()) {
            $errors[] = 'Сессия формы истекла. Обновите страницу и повторите отправку.';
        } else {
            $validation = validate_contact_input($_POST);
            $values = $validation['values'];
            $errors = $validation['errors'];

            if ($errors === []) {
                $sql = 'INSERT INTO contacts (surname, name, lastname, gender, birth_date, phone, address, email, comment) VALUES (:surname, :name, :lastname, :gender, :birth_date, :phone, :address, :email, :comment)';
                try {
                    $statement = $pdo->prepare($sql);
                    $statement->execute($validation['data']);
                    $_SESSION['add_notice'] = ['type' => 'success', 'text' => 'Запись добавлена'];
                    header('Location: index.php?page=add');
                    exit;
                } catch (PDOException $exception) {
                    error_log($exception->getMessage());
                    $errors[] = 'Ошибка: запись не добавлена.';
                }
            }
        }
    }

    $html = '<section class="panel form-panel"><h1>Добавление записи</h1>';
    if (is_array($notice) && isset($notice['text'])) {
        $html .= '<div class="message success">' . h($notice['text']) . '</div>';
    }
    if ($errors !== []) {
        $html .= '<div class="message error"><strong>Ошибка: запись не добавлена</strong><ul>';
        foreach ($errors as $error) {
            $html .= '<li>' . h($error) . '</li>';
        }
        $html .= '</ul></div>';
    }

    $html .= '<form method="post" action="index.php?page=add" class="contact-form">'
        . '<input type="hidden" name="csrf_token" value="' . h(csrf_token()) . '">'
        . '<div class="form-row"><label for="surname">Фамилия <span class="required">*</span></label><input id="surname" type="text" name="surname" placeholder="Фамилия" value="' . h($values['surname']) . '" required maxlength="100"></div>'
        . '<div class="form-row"><label for="name">Имя <span class="required">*</span></label><input id="name" type="text" name="name" placeholder="Имя" value="' . h($values['name']) . '" required maxlength="100"></div>'
        . '<div class="form-row"><label for="lastname">Отчество</label><input id="lastname" type="text" name="lastname" placeholder="Отчество" value="' . h($values['lastname']) . '" maxlength="100"></div>'
        . '<div class="form-row"><label for="gender">Пол</label><select id="gender" name="gender"><option value="">Не указан</option><option value="мужской"' . ($values['gender'] === 'мужской' ? ' selected' : '') . '>мужской</option><option value="женский"' . ($values['gender'] === 'женский' ? ' selected' : '') . '>женский</option></select></div>'
        . '<div class="form-row"><label for="date">Дата рождения</label><input id="date" type="date" name="date" value="' . h($values['date']) . '"></div>'
        . '<div class="form-row"><label for="phone">Телефон</label><input id="phone" type="tel" name="phone" placeholder="Телефон" value="' . h($values['phone']) . '" maxlength="40"></div>'
        . '<div class="form-row"><label for="location">Адрес</label><input id="location" type="text" name="location" placeholder="Адрес" value="' . h($values['location']) . '" maxlength="255"></div>'
        . '<div class="form-row"><label for="email">E-mail</label><input id="email" type="email" name="email" placeholder="E-mail" value="' . h($values['email']) . '" maxlength="190"></div>'
        . '<div class="form-row"><label for="comment">Комментарий</label><textarea id="comment" name="comment" placeholder="Краткий комментарий" rows="4">' . h($values['comment']) . '</textarea></div>'
        . '<div class="form-actions"><button class="form-btn" type="submit">Добавить запись</button></div>'
        . '</form></section>';

    return $html;
}
