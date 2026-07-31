<?php
namespace Biblhertz\Manifest_Server\om;

use Biblhertz\Manifest_Server\utilities\PDODatabase;

/**
 * ApiKey
 *
 * Domain object representing an API key stored in the `api_key` table.
 * Keys are stored as bcrypt hashes; the raw value is only available
 * immediately after {@see create()} and is never persisted in plain text.
 *
 * Each key can be linked to one or more {@see ApiEndpoint} records through
 * the `api_key_api_endpoint` join table.
 *
 * Ported from Biblhertz\Resolver\om\ApiKey — only the namespace has changed.
 *
 * @package Biblhertz\Manifest_Server\om
 */
class ApiKey extends ManifestServerObject
{
    /** @var string First 8 hex characters of the raw key, used for display identification. */
    private string $keyPrefix;

    /** @var string bcrypt hash of the raw key. */
    private string $keyHash;

    /** @var string ISO datetime of when the key was created. */
    private string $createdAt;

    /** @var string|null ISO datetime of when the key was last used, or null. */
    private ?string $lastUsed;

    /** @var bool Whether the key is currently active. */
    private bool $active;


    public function __construct(PDODatabase $objDB, int $id)
    {
        $this->tableName = 'api_key';
        $this->objDB     = $objDB;
        $this->id        = $id;

        $row = $this->fetchItem();
        if ($row) {
            $this->name      = $row['name']       ?? '';
            $this->keyPrefix = $row['key_prefix'] ?? '';
            $this->keyHash   = $row['key_hash']   ?? '';
            $this->createdAt = $row['created_at'] ?? '';
            $this->lastUsed  = $row['last_used']  ?: null;
            $this->active    = (bool) ($row['active'] ?? 0);
        }
    }

    public function getKeyPrefix(): string { return $this->keyPrefix ?? ''; }
    public function getCreatedAt(): string { return $this->createdAt ?? ''; }
    public function getLastUsed(): ?string { return $this->lastUsed  ?? null; }
    public function isActive(): bool       { return $this->active    ?? false; }

    /**
     * Return names of all endpoints associated with this key.
     *
     * @return string[]
     */
    public function getEndpointNames(): array
    {
        $stmt  = $this->objDB->preparedSelect(
            'SELECT ae.name FROM api_endpoint ae
               JOIN api_key_api_endpoint akae ON ae.id = akae.api_endpoint_id
              WHERE akae.api_key_id = ?
              ORDER BY ae.name',
            [$this->id]
        );
        $names = [];
        while ($row = $stmt->fetch()) {
            $names[] = $row['name'];
        }
        return $names;
    }

    /**
     * Generate a new random key, persist the hash, link the given endpoints,
     * and return the raw key value (only available at creation time).
     *
     * @param  PDODatabase $objDB
     * @param  string      $name        Human-readable label for this key.
     * @param  int[]       $endpointIds IDs of endpoints to associate.
     * @return array{id:int, key:string, prefix:string}
     */
    public static function create(PDODatabase $objDB, string $name, array $endpointIds): array
    {
        $rawKey = bin2hex(random_bytes(32));
        $prefix = substr($rawKey, 0, 8);
        $hash   = password_hash($rawKey, PASSWORD_BCRYPT);

        $keyId = $objDB->insert('api_key', [
            'name'       => $name,
            'key_hash'   => $hash,
            'key_prefix' => $prefix,
            'created_at' => date('Y-m-d H:i:s'),
            'active'     => 1,
        ]);

        foreach ($endpointIds as $endpointId) {
            $objDB->insert('api_key_api_endpoint', [
                'api_key_id'      => $keyId,
                'api_endpoint_id' => (int) $endpointId,
            ]);
        }

        return ['id' => $keyId, 'key' => $rawKey, 'prefix' => $prefix];
    }

    /**
     * Hard-delete this key and its endpoint links.
     */
    public function delete(): void
    {
        $this->objDB->preparedStatement('DELETE FROM api_key WHERE id = ?', [$this->id]);
    }

    /**
     * Return all key records, newest first.
     *
     * @return ApiKey[]
     */
    public static function getAll(PDODatabase $objDB): array
    {
        $stmt = $objDB->preparedSelect(
            'SELECT id FROM api_key ORDER BY created_at DESC', []
        );
        $keys = [];
        while ($row = $stmt->fetch()) {
            $keys[] = new self($objDB, (int) $row['id']);
        }
        return $keys;
    }

    /**
     * Validate a raw key string against all active keys for a given endpoint.
     * Records `last_used` on a successful match.
     *
     * @param  PDODatabase $objDB
     * @param  string      $rawKey       Value from the X-API-Key header.
     * @param  string      $endpointName Endpoint being accessed.
     * @return bool
     */
    public static function validate(PDODatabase $objDB, string $rawKey, string $endpointName): bool
    {
        $stmt = $objDB->preparedSelect(
            'SELECT ak.id, ak.key_hash FROM api_key ak
               JOIN api_key_api_endpoint akae ON ak.id  = akae.api_key_id
               JOIN api_endpoint ae           ON ae.id  = akae.api_endpoint_id
              WHERE ak.active = 1 AND ae.name = ?',
            [$endpointName]
        );

        while ($row = $stmt->fetch()) {
            if (password_verify($rawKey, $row['key_hash'])) {
                $objDB->update('api_key', ['last_used' => date('Y-m-d H:i:s')], 'id = ' . (int) $row['id']);
                return true;
            }
        }
        return false;
    }
}
