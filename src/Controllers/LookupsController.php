<?php

declare(strict_types=1);

class LookupsController
{
    public function index(): void
    {
        $db = Database::getConnection();
        $departments = $db->query('SELECT id, name FROM departments ORDER BY name')->fetchAll();
        $positions = $db->query('SELECT id, name FROM positions ORDER BY name')->fetchAll();
        $resources = $db->query('SELECT id, name FROM resources ORDER BY name')->fetchAll();
        $internetResources = $db->query('SELECT id, name FROM internet_resources ORDER BY name')->fetchAll();
        $software = $db->query('SELECT id, name FROM software_requirements ORDER BY name')->fetchAll();

        Response::json([
            'departments' => $departments,
            'positions' => $positions,
            'resources' => $resources,
            'internetResources' => $internetResources,
            'software' => $software,
        ]);
    }
}
