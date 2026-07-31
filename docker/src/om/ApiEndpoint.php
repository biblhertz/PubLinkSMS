<?php
namespace Biblhertz\Manifest_Server\om;

use Biblhertz\Manifest_Server\utilities\PDODatabase;

/**
 * ApiEndpoint
 *
 * Domain object representing a named API endpoint that can be associated
 * with one or more {@see ApiKey} instances through the `api_key_api_endpoint`
 * join table.
 *
 * Ported from Biblhertz\Resolver\om\ApiEndpoint — only the namespace has changed.
 *
 * @package Biblhertz\Manifest_Server\om
 */
class ApiEndpoint extends ManifestServerObject
{
    private string $description;

    public function __construct(PDODatabase $objDB, int $id)
    {
        $this->tableName = 'api_endpoint';
        $this->objDB     = $objDB;
        $this->id        = $id;

        $row = $this->fetchItem();
        if ($row) {
            $this->name        = $row['name']        ?? '';
            $this->description = $row['description'] ?? '';
        }
    }

    public function getDescription(): string { return $this->description ?? ''; }

    /**
     * Return all endpoint records ordered by name.
     *
     * @return ApiEndpoint[]
     */
    public static function getAll(PDODatabase $objDB): array
    {
        $stmt      = $objDB->preparedSelect('SELECT id FROM api_endpoint ORDER BY name', []);
        $endpoints = [];
        while ($row = $stmt->fetch()) {
            $endpoints[] = new self($objDB, (int) $row['id']);
        }
        return $endpoints;
    }
}
