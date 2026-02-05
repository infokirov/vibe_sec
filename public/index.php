<?php

declare(strict_types=1);

require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/Router.php';
require_once __DIR__ . '/src/Controllers/Response.php';
require_once __DIR__ . '/src/Controllers/LookupsController.php';
require_once __DIR__ . '/src/Controllers/UsersController.php';
require_once __DIR__ . '/src/Controllers/CardsController.php';

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

if (str_starts_with($uri, '/api/')) {
    $router = new Router();
    $lookups = new LookupsController();
    $users = new UsersController();
    $cards = new CardsController();

    $router->add('GET', '/api/lookups', [$lookups, 'index']);

    $router->add('GET', '/api/users', [$users, 'index']);
    $router->add('POST', '/api/users', [$users, 'store']);
    $router->add('PUT', '/api/users/{id}', [$users, 'update']);
    $router->add('DELETE', '/api/users/{id}', [$users, 'destroy']);
    $router->add('POST', '/api/users/{id}/copy', [$users, 'copy']);

    $router->add('GET', '/api/cards', [$cards, 'index']);
    $router->add('GET', '/api/cards/{id}', [$cards, 'show']);
    $router->add('POST', '/api/cards', [$cards, 'store']);
    $router->add('PUT', '/api/cards/{id}', [$cards, 'update']);
    $router->add('DELETE', '/api/cards/{id}', [$cards, 'destroy']);
    $router->add('GET', '/api/cards/{id}/history', [$cards, 'history']);
    $router->add('GET', '/api/cards/{id}/print', [$cards, 'print']);

    $router->dispatch($method, $uri);
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Карточки доступа</title>
    <link rel="stylesheet" href="/assets/styles.css?v=<?= filemtime(__DIR__ . '/assets/styles.css') ?>">
</head>
<body>
    <header class="app-header">
        <div>
            <h1>Управление карточками доступа</h1>
            <p>Справочники пользователей, доступов и программного обеспечения.</p>
        </div>
        <div class="status-filter">
            <label for="statusFilter">Фильтр по статусу:</label>
            <select id="statusFilter">
                <option value="all">Все</option>
                <option value="active">Активные</option>
                <option value="terminated">Выключен (уволен)</option>
            </select>
        </div>
    </header>

    <main class="grid">
        <section class="panel">
            <div class="panel-header">
                <h2>Пользователи</h2>
                <button class="primary" id="addUserBtn">+ Добавить пользователя</button>
            </div>
            <div id="usersList" class="list"></div>
        </section>

        <section class="panel">
            <div class="panel-header">
                <h2>Карточки доступа</h2>
                <button class="primary" id="addCardBtn">+ Добавить карточку</button>
            </div>
            <div id="cardsList" class="list"></div>
        </section>

        <section class="panel">
            <div class="panel-header">
                <h2>История изменений</h2>
            </div>
            <div id="historyList" class="history"></div>
        </section>
    </main>

    <section class="panel full-width">
        <div class="panel-header">
            <h2>Справочники</h2>
        </div>
        <div class="lookup-grid">
            <div>
                <h3>Отделы</h3>
                <ul id="departmentsList"></ul>
            </div>
            <div>
                <h3>Должности</h3>
                <ul id="positionsList"></ul>
            </div>
            <div>
                <h3>Ресурсы</h3>
                <ul id="resourcesList"></ul>
            </div>
            <div>
                <h3>Интернет-ресурсы</h3>
                <ul id="internetResourcesList"></ul>
            </div>
            <div>
                <h3>Требования к ПО</h3>
                <ul id="softwareList"></ul>
            </div>
        </div>
    </section>

    <div class="modal" id="userModal">
        <div class="modal-content">
            <h3 id="userModalTitle">Добавить пользователя</h3>
            <form id="userForm">
                <input type="hidden" id="userId">
                <label>ФИО
                    <input type="text" id="fullName" required>
                </label>
                <label>Отдел
                    <select id="departmentSelect"></select>
                </label>
                <label>Должность
                    <select id="positionSelect"></select>
                </label>
                <label>Руководитель
                    <select id="managerSelect"></select>
                </label>
                <div class="checkbox-row">
                    <label><input type="checkbox" id="terminated"> Признак уволен</label>
                    <label><input type="checkbox" id="isManager"> Руководитель</label>
                    <label><input type="checkbox" id="isDirector"> Директор</label>
                    <label><input type="checkbox" id="isExecutor"> Исполнитель</label>
                </div>
                <div class="modal-actions">
                    <button type="button" class="ghost" data-close>Отмена</button>
                    <button type="submit" class="primary">Сохранить</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal" id="cardModal">
        <div class="modal-content large">
            <h3 id="cardModalTitle">Добавить карточку</h3>
            <form id="cardForm">
                <input type="hidden" id="cardId">
                <label>Пользователь
                    <select id="cardUserSelect" required></select>
                </label>
                <div class="multi-column">
                    <label>Ресурсы для доступа
                        <select id="resourcesSelect" multiple></select>
                    </label>
                    <label>Ресурсы в интернете
                        <select id="internetResourcesSelect" multiple></select>
                    </label>
                    <label>Набор ПО
                        <select id="softwareSelect" multiple></select>
                    </label>
                </div>
                <div class="checkbox-row">
                    <label><input type="checkbox" id="abs1Access"> Доступ в АБС1</label>
                    <label><input type="checkbox" id="abs2Access"> Доступ в АБС2</label>
                </div>
                <div class="modal-actions">
                    <button type="button" class="ghost" data-close>Отмена</button>
                    <button type="submit" class="primary">Сохранить</button>
                </div>
            </form>
        </div>
    </div>

    <script src="/assets/app.js?v=<?= filemtime(__DIR__ . '/assets/app.js') ?>"></script>
</body>
</html>
