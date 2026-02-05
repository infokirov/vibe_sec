<?php

declare(strict_types=1);

class CardsController
{
    public function index(): void
    {
        $db = Database::getConnection();
        $userId = $_GET['user_id'] ?? null;

        $sql = 'SELECT c.id, c.user_id, u.full_name, c.abs1_access, c.abs2_access, c.created_at, c.updated_at
                FROM access_cards c
                JOIN users u ON u.id = c.user_id';
        $params = [];
        if ($userId) {
            $sql .= ' WHERE c.user_id = :user_id';
            $params[':user_id'] = $userId;
        }
        $sql .= ' ORDER BY c.updated_at DESC';

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $cards = $stmt->fetchAll();

        Response::json(['cards' => $cards]);
    }

    public function show(string $id): void
    {
        $db = Database::getConnection();
        $card = $this->fetchCard($db, $id);
        if (!$card) {
            Response::json(['error' => 'Card not found'], 404);
            return;
        }

        Response::json(['card' => $card]);
    }

    public function store(): void
    {
        $data = Response::input();
        $db = Database::getConnection();

        $userId = $this->toNullableInt($data['user_id'] ?? null);
        if ($userId === null) {
            Response::json(['message' => 'Выберите пользователя для карточки'], 422);
            return;
        }

        $abs1Access = $this->toBool($data['abs1_access'] ?? false);
        $abs2Access = $this->toBool($data['abs2_access'] ?? false);

        $stmt = $db->prepare('INSERT INTO access_cards (user_id, abs1_access, abs2_access)
                              VALUES (:user_id, :abs1_access, :abs2_access)
                              RETURNING id');
        $stmt->execute([
            ':user_id' => $userId,
            ':abs1_access' => $abs1Access,
            ':abs2_access' => $abs2Access,
        ]);
        $cardId = $stmt->fetchColumn();

        $this->syncRelations($db, $cardId, $data);
        $this->logHistory($db, $cardId, 'created', ['message' => 'Карточка создана']);

        Response::json(['id' => $cardId], 201);
    }

    public function update(string $id): void
    {
        $data = Response::input();
        $db = Database::getConnection();
        $current = $this->fetchCard($db, $id);

        if (!$current) {
            Response::json(['error' => 'Card not found'], 404);
            return;
        }

        $stmt = $db->prepare('UPDATE access_cards
                              SET abs1_access = :abs1_access,
                                  abs2_access = :abs2_access,
                                  updated_at = NOW()
                              WHERE id = :id');
        $abs1Access = $this->toBool($data['abs1_access'] ?? false);
        $abs2Access = $this->toBool($data['abs2_access'] ?? false);
        $stmt->execute([
            ':abs1_access' => $abs1Access,
            ':abs2_access' => $abs2Access,
            ':id' => $id,
        ]);

        $changes = $this->diffChanges($current, [
            'abs1_access' => $abs1Access,
            'abs2_access' => $abs2Access,
        ]);
        $this->syncRelations($db, $id, $data, $changes);

        if (!empty($changes)) {
            $this->logHistory($db, $id, 'updated', $changes);
        }

        Response::json(['status' => 'updated']);
    }

    public function destroy(string $id): void
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('DELETE FROM access_cards WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $this->logHistory($db, $id, 'deleted', ['message' => 'Карточка удалена']);

        Response::json(['status' => 'deleted']);
    }

    public function history(string $id): void
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT id, action, details, created_at FROM access_card_history WHERE card_id = :id ORDER BY created_at DESC');
        $stmt->execute([':id' => $id]);
        $history = $stmt->fetchAll();

        Response::json(['history' => $history]);
    }

    public function print(string $id): void
    {
        $db = Database::getConnection();
        $card = $this->fetchCard($db, $id);
        if (!$card) {
            Response::html('<h1>Карточка не найдена</h1>', 404);
            return;
        }

        ob_start();
        ?>
        <!DOCTYPE html>
        <html lang="ru">
        <head>
            <meta charset="UTF-8">
            <title>Карточка доступа</title>
            <style>
                body { font-family: Arial, sans-serif; padding: 24px; }
                h1 { margin-bottom: 8px; }
                .section { margin-bottom: 16px; }
                ul { margin: 4px 0 0 16px; }
            </style>
        </head>
        <body>
            <h1>Карточка доступа</h1>
            <div class="section">
                <strong>Сотрудник:</strong> <?= htmlspecialchars($card['full_name']) ?>
            </div>
            <div class="section">
                <strong>Доступы:</strong>
                <div>АБС1: <?= $card['abs1_access'] ? 'Да' : 'Нет' ?></div>
                <div>АБС2: <?= $card['abs2_access'] ? 'Да' : 'Нет' ?></div>
            </div>
            <div class="section">
                <strong>Ресурсы:</strong>
                <ul>
                    <?php foreach ($card['resources'] as $resource): ?>
                        <li><?= htmlspecialchars($resource['name']) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="section">
                <strong>Интернет-ресурсы:</strong>
                <ul>
                    <?php foreach ($card['internet_resources'] as $resource): ?>
                        <li><?= htmlspecialchars($resource['name']) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="section">
                <strong>ПО:</strong>
                <ul>
                    <?php foreach ($card['software'] as $software): ?>
                        <li><?= htmlspecialchars($software['name']) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </body>
        </html>
        <?php
        $html = ob_get_clean();
        Response::html($html);
    }

    private function fetchCard(PDO $db, string $id): ?array
    {
        $stmt = $db->prepare('SELECT c.id, c.user_id, u.full_name, c.abs1_access, c.abs2_access, c.created_at, c.updated_at
                              FROM access_cards c
                              JOIN users u ON u.id = c.user_id
                              WHERE c.id = :id');
        $stmt->execute([':id' => $id]);
        $card = $stmt->fetch();
        if (!$card) {
            return null;
        }

        $card['resources'] = $this->fetchRelation($db, 'resources', 'access_card_resources', $id);
        $card['internet_resources'] = $this->fetchRelation($db, 'internet_resources', 'access_card_internet_resources', $id);
        $card['software'] = $this->fetchRelation($db, 'software_requirements', 'access_card_software', $id);

        return $card;
    }

    private function fetchRelation(PDO $db, string $table, string $pivot, string $cardId): array
    {
        $stmt = $db->prepare("SELECT r.id, r.name FROM {$table} r JOIN {$pivot} p ON p.resource_id = r.id WHERE p.card_id = :card_id ORDER BY r.name");
        $stmt->execute([':card_id' => $cardId]);
        return $stmt->fetchAll();
    }

    private function syncRelations(PDO $db, string $cardId, array $data, array &$changes = []): void
    {
        $this->syncPivot($db, $cardId, 'access_card_resources', 'resource_id', $this->toIntArray($data['resources'] ?? []), 'resources', $changes, 'ресурсы');
        $this->syncPivot($db, $cardId, 'access_card_internet_resources', 'resource_id', $this->toIntArray($data['internet_resources'] ?? []), 'internet_resources', $changes, 'интернет-ресурсы');
        $this->syncPivot($db, $cardId, 'access_card_software', 'resource_id', $this->toIntArray($data['software'] ?? []), 'software', $changes, 'ПО');
    }

    private function syncPivot(PDO $db, string $cardId, string $table, string $column, array $newIds, string $changeKey, array &$changes, string $label): void
    {
        $stmt = $db->prepare("SELECT {$column} FROM {$table} WHERE card_id = :card_id");
        $stmt->execute([':card_id' => $cardId]);
        $current = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $toAdd = array_values(array_diff($newIds, $current));
        $toRemove = array_values(array_diff($current, $newIds));

        foreach ($toAdd as $id) {
            $insert = $db->prepare("INSERT INTO {$table} (card_id, {$column}) VALUES (:card_id, :resource_id)");
            $insert->execute([':card_id' => $cardId, ':resource_id' => $id]);
        }

        foreach ($toRemove as $id) {
            $delete = $db->prepare("DELETE FROM {$table} WHERE card_id = :card_id AND {$column} = :resource_id");
            $delete->execute([':card_id' => $cardId, ':resource_id' => $id]);
        }

        if (!empty($toAdd) || !empty($toRemove)) {
            $changes[$changeKey] = [
                'label' => $label,
                'added' => $toAdd,
                'removed' => $toRemove,
            ];
        }
    }

    private function diffChanges(array $current, array $data): array
    {
        $changes = [];
        $abs1 = (bool)($data['abs1_access'] ?? false);
        $abs2 = (bool)($data['abs2_access'] ?? false);

        if ($abs1 !== (bool)$current['abs1_access']) {
            $changes['abs1_access'] = [
                'label' => 'АБС1',
                'from' => (bool)$current['abs1_access'],
                'to' => $abs1,
            ];
        }
        if ($abs2 !== (bool)$current['abs2_access']) {
            $changes['abs2_access'] = [
                'label' => 'АБС2',
                'from' => (bool)$current['abs2_access'],
                'to' => $abs2,
            ];
        }

        return $changes;
    }

    private function logHistory(PDO $db, string $cardId, string $action, array $details): void
    {
        $stmt = $db->prepare('INSERT INTO access_card_history (card_id, action, details) VALUES (:card_id, :action, :details)');
        $stmt->execute([
            ':card_id' => $cardId,
            ':action' => $action,
            ':details' => json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
    }

    private function toBool($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_string($value)) {
            $normalized = mb_strtolower(trim($value));
            if ($normalized === '' || $normalized === '0' || $normalized === 'false' || $normalized === 'нет') {
                return false;
            }
            if ($normalized === '1' || $normalized === 'true' || $normalized === 'да') {
                return true;
            }
        }
        return (bool)$value;
    }

    private function toNullableInt($value): ?int
    {
        if ($value === null) {
            return null;
        }
        if (is_string($value)) {
            $trimmed = trim($value);
            if ($trimmed === '') {
                return null;
            }
            if (is_numeric($trimmed)) {
                return (int)$trimmed;
            }
            return null;
        }
        if (is_int($value)) {
            return $value;
        }
        if (is_numeric($value)) {
            return (int)$value;
        }
        return null;
    }

    private function toIntArray($values): array
    {
        if (!is_array($values)) {
            return [];
        }
        $result = [];
        foreach ($values as $value) {
            $intValue = $this->toNullableInt($value);
            if ($intValue !== null) {
                $result[] = $intValue;
            }
        }
        return $result;
    }
}
