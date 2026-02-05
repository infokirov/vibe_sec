<?php

declare(strict_types=1);

class UsersController
{
    public function index(): void
    {
        $status = $_GET['status'] ?? 'all';
        $db = Database::getConnection();

        $sql = 'SELECT u.id, u.full_name, u.department_id, d.name AS department_name, u.position_id, p.name AS position_name, u.is_terminated, u.manager_id, m.full_name AS manager_name, u.is_manager, u.is_director, u.is_executor
                FROM users u
                LEFT JOIN departments d ON d.id = u.department_id
                LEFT JOIN positions p ON p.id = u.position_id
                LEFT JOIN users m ON m.id = u.manager_id';
        $params = [];
        if ($status === 'active') {
            $sql .= ' WHERE u.is_terminated = false';
        } elseif ($status === 'terminated') {
            $sql .= ' WHERE u.is_terminated = true';
        }
        $sql .= ' ORDER BY u.full_name';
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $users = $stmt->fetchAll();

        Response::json(['users' => $users]);
    }

    public function store(): void
    {
        $data = Response::input();
        $db = Database::getConnection();

        $isTerminated = $this->toBool($data['is_terminated'] ?? false);
        $isManager = $this->toBool($data['is_manager'] ?? false);
        $isDirector = $this->toBool($data['is_director'] ?? false);
        $isExecutor = $this->toBool($data['is_executor'] ?? true);

        $stmt = $db->prepare('INSERT INTO users (full_name, department_id, position_id, is_terminated, manager_id, is_manager, is_director, is_executor)
                              VALUES (:full_name, :department_id, :position_id, :is_terminated, :manager_id, :is_manager, :is_director, :is_executor)
                              RETURNING id');
        $stmt->execute([
            ':full_name' => $data['full_name'] ?? '',
            ':department_id' => $data['department_id'] ?? null,
            ':position_id' => $data['position_id'] ?? null,
            ':is_terminated' => $isTerminated,
            ':manager_id' => $data['manager_id'] ?? null,
            ':is_manager' => $isManager,
            ':is_director' => $isDirector,
            ':is_executor' => $isExecutor,
        ]);
        $id = $stmt->fetchColumn();

        Response::json(['id' => $id], 201);
    }

    public function update(string $id): void
    {
        $data = Response::input();
        $db = Database::getConnection();

        $isTerminated = $this->toBool($data['is_terminated'] ?? false);
        $isManager = $this->toBool($data['is_manager'] ?? false);
        $isDirector = $this->toBool($data['is_director'] ?? false);
        $isExecutor = $this->toBool($data['is_executor'] ?? true);

        $stmt = $db->prepare('UPDATE users
                              SET full_name = :full_name,
                                  department_id = :department_id,
                                  position_id = :position_id,
                                  is_terminated = :is_terminated,
                                  manager_id = :manager_id,
                                  is_manager = :is_manager,
                                  is_director = :is_director,
                                  is_executor = :is_executor
                              WHERE id = :id');
        $stmt->execute([
            ':full_name' => $data['full_name'] ?? '',
            ':department_id' => $data['department_id'] ?? null,
            ':position_id' => $data['position_id'] ?? null,
            ':is_terminated' => $isTerminated,
            ':manager_id' => $data['manager_id'] ?? null,
            ':is_manager' => $isManager,
            ':is_director' => $isDirector,
            ':is_executor' => $isExecutor,
            ':id' => $id,
        ]);

        Response::json(['status' => 'updated']);
    }

    public function destroy(string $id): void
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('DELETE FROM users WHERE id = :id');
        $stmt->execute([':id' => $id]);

        Response::json(['status' => 'deleted']);
    }

    public function copy(string $id): void
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT * FROM users WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $user = $stmt->fetch();
        if (!$user) {
            Response::json(['error' => 'User not found'], 404);
            return;
        }

        $newName = $user['full_name'] . ' (копия)';
        $insert = $db->prepare('INSERT INTO users (full_name, department_id, position_id, is_terminated, manager_id, is_manager, is_director, is_executor)
                                VALUES (:full_name, :department_id, :position_id, :is_terminated, :manager_id, :is_manager, :is_director, :is_executor)
                                RETURNING id');
        $insert->execute([
            ':full_name' => $newName,
            ':department_id' => $user['department_id'],
            ':position_id' => $user['position_id'],
            ':is_terminated' => $user['is_terminated'],
            ':manager_id' => $user['manager_id'],
            ':is_manager' => $user['is_manager'],
            ':is_director' => $user['is_director'],
            ':is_executor' => $user['is_executor'],
        ]);
        $newId = $insert->fetchColumn();

        Response::json(['id' => $newId], 201);
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
}
