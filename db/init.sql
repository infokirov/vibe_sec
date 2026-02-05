CREATE TABLE departments (
    id SERIAL PRIMARY KEY,
    name TEXT NOT NULL
);

CREATE TABLE positions (
    id SERIAL PRIMARY KEY,
    name TEXT NOT NULL
);

CREATE TABLE resources (
    id SERIAL PRIMARY KEY,
    name TEXT NOT NULL
);

CREATE TABLE internet_resources (
    id SERIAL PRIMARY KEY,
    name TEXT NOT NULL
);

CREATE TABLE software_requirements (
    id SERIAL PRIMARY KEY,
    name TEXT NOT NULL
);

CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    full_name TEXT NOT NULL,
    department_id INTEGER REFERENCES departments(id),
    position_id INTEGER REFERENCES positions(id),
    is_terminated BOOLEAN NOT NULL DEFAULT FALSE,
    manager_id INTEGER REFERENCES users(id),
    is_manager BOOLEAN NOT NULL DEFAULT FALSE,
    is_director BOOLEAN NOT NULL DEFAULT FALSE,
    is_executor BOOLEAN NOT NULL DEFAULT TRUE
);

CREATE TABLE access_cards (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    abs1_access BOOLEAN NOT NULL DEFAULT FALSE,
    abs2_access BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE TABLE access_card_resources (
    card_id INTEGER REFERENCES access_cards(id) ON DELETE CASCADE,
    resource_id INTEGER REFERENCES resources(id) ON DELETE CASCADE,
    PRIMARY KEY (card_id, resource_id)
);

CREATE TABLE access_card_internet_resources (
    card_id INTEGER REFERENCES access_cards(id) ON DELETE CASCADE,
    resource_id INTEGER REFERENCES internet_resources(id) ON DELETE CASCADE,
    PRIMARY KEY (card_id, resource_id)
);

CREATE TABLE access_card_software (
    card_id INTEGER REFERENCES access_cards(id) ON DELETE CASCADE,
    resource_id INTEGER REFERENCES software_requirements(id) ON DELETE CASCADE,
    PRIMARY KEY (card_id, resource_id)
);

CREATE TABLE access_card_history (
    id SERIAL PRIMARY KEY,
    card_id INTEGER NOT NULL,
    action TEXT NOT NULL,
    details JSONB NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT NOW()
);

INSERT INTO departments (name) VALUES
('Информационные технологии'),
('Финансовый контроль'),
('Служба безопасности'),
('Операционный блок');

INSERT INTO positions (name) VALUES
('Инженер по ИБ'),
('Финансовый аналитик'),
('Администратор системы'),
('Специалист поддержки');

INSERT INTO resources (name) VALUES
('Хранилище документов'),
('Система учета клиентов'),
('Портал заявок'),
('Сервис командировок');

INSERT INTO internet_resources (name) VALUES
('Банковский портал'),
('Поставщики оборудования'),
('Госзакупки'),
('Почтовый шлюз');

INSERT INTO software_requirements (name) VALUES
('Office пакет'),
('VPN клиент'),
('Антивирус'),
('Финансовая аналитика');

INSERT INTO users (full_name, department_id, position_id, is_terminated, manager_id, is_manager, is_director, is_executor) VALUES
('Иванов Иван Иванович', 1, 3, FALSE, NULL, TRUE, FALSE, TRUE),
('Петрова Анна Сергеевна', 2, 2, FALSE, 1, FALSE, FALSE, TRUE),
('Сидоров Алексей Николаевич', 4, 4, FALSE, 1, FALSE, FALSE, TRUE),
('Смирнова Ольга Викторовна', 3, 1, TRUE, 1, FALSE, TRUE, FALSE);

INSERT INTO access_cards (user_id, abs1_access, abs2_access) VALUES
(1, TRUE, TRUE),
(2, TRUE, FALSE),
(3, FALSE, TRUE);

INSERT INTO access_card_resources (card_id, resource_id) VALUES
(1, 1),
(1, 2),
(2, 3),
(3, 1);

INSERT INTO access_card_internet_resources (card_id, resource_id) VALUES
(1, 1),
(2, 2),
(3, 3);

INSERT INTO access_card_software (card_id, resource_id) VALUES
(1, 1),
(1, 2),
(2, 4),
(3, 1);

INSERT INTO access_card_history (card_id, action, details) VALUES
(1, 'created', '{"message": "Карточка создана"}'),
(2, 'created', '{"message": "Карточка создана"}'),
(3, 'created', '{"message": "Карточка создана"}');
