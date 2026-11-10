CREATE TABLE IF NOT EXISTS contacts (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    surname VARCHAR(100) NOT NULL,
    name VARCHAR(100) NOT NULL,
    lastname VARCHAR(100) NOT NULL DEFAULT '',
    gender ENUM('мужской', 'женский') NULL,
    birth_date DATE NULL,
    phone VARCHAR(40) NOT NULL DEFAULT '',
    address VARCHAR(255) NOT NULL DEFAULT '',
    email VARCHAR(190) NULL,
    comment TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_contacts_surname_name (surname, name),
    INDEX idx_contacts_birth_date (birth_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
