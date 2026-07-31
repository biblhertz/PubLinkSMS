<?php
namespace Biblhertz\Manifest_Server\om;

use Biblhertz\Manifest_Server\utilities\PDODatabase;

/**
 * ManifestServerObject
 *
 * Abstract base class for all domain objects in the SimpleManifestServer
 * object model. Provides id, name, the shared database connection, and the
 * single-row fetch helper used by every concrete subclass.
 *
 * Ported from Biblhertz\Resolver\om\ResolverObject — only the namespace
 * and class name have changed.
 *
 * @package Biblhertz\Manifest_Server\om
 */
abstract class ManifestServerObject
{
    protected int         $id;
    protected string      $name;
    protected PDODatabase $objDB;
    protected string      $tableName;

    public function getID(): int           { return $this->id; }
    public function setID(int $id): void   { $this->id = $id; }
    public function getName(): string      { return $this->name ?? ''; }
    public function setName(string $n): void { $this->name = $n; }
    public function getObjDB(): PDODatabase  { return $this->objDB; }
    public function setObjDB(PDODatabase $o): void { $this->objDB = $o; }

    public function fetchItem(): ?array
    {
        if (!is_numeric($this->id)) return null;
        $item = $this->objDB->preparedStatement(
            'SELECT * FROM ' . $this->tableName . ' WHERE id = ?',
            [$this->id]
        );
        if ($item->rowCount() !== 1) return null;
        return $item->fetch();
    }
}
