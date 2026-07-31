<?php
namespace Biblhertz\Manifest_Server\utilities;

use PDO;
use PDOException;
use PDOStatement;

/**
 * PDODatabase
 *
 * Lightweight PDO abstraction layer providing parameterised query helpers,
 * dynamic INSERT/UPDATE builders, and transaction management.
 *
 * Ported from Biblhertz\Publink\utilities\PDODatabase — only the namespace
 * has been changed.
 *
 * @package Biblhertz\Manifest_Server\utilities
 */
class PDODatabase {

    private PDO $db;

    private static string $user          = "";
    private static string $password      = "";
    private static string $host          = "mysql";
    private static string $database_name = "";

    private bool $DEBUG        = false;
    private bool $INSERT_DEBUG = false;

    private int $rowCount;
    private $statement;


    public function __construct() {
        $dsn     = "mysql:host=" . PDODatabase::$host . ";dbname=" . PDODatabase::$database_name . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        $this->db = new PDO($dsn, PDODatabase::$user, PDODatabase::$password, $options);
    }

    public function getConnection(): PDO { return $this->db; }

    public function quote(string $str): string { return $this->db->quote($str); }

    public function numRows(): int { return $this->rowCount; }

    public static function setHost(string $host): void         { PDODatabase::$host          = $host; }
    public static function setUser(string $user): void         { PDODatabase::$user          = $user; }
    public static function setPassword(string $password): void { PDODatabase::$password      = $password; }
    public static function setDatabaseName(string $name): void { PDODatabase::$database_name = $name; }

    public function preparedStatement(string $sql, array $vals): PDOStatement {
        if ($this->DEBUG) error_log($sql . " :: " . implode(" ", $vals));
        $this->statement = $this->db->prepare($sql, [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY]);
        $this->statement->execute($vals);
        $this->rowCount  = $this->statement->rowCount();
        return $this->statement;
    }

    public function preparedSelect(string $sql, array $vals): PDOStatement {
        return $this->preparedStatement($sql, $vals);
    }

    public function preparedGetOne(string $sql, array $vals): mixed {
        if ($this->DEBUG) error_log($sql . " :: " . implode(" ", $vals));
        $this->statement = $this->db->prepare($sql, [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY]);
        $this->statement->execute($vals);
        $this->rowCount  = $this->statement->rowCount();
        return $this->statement->fetchColumn();
    }

    public function preparedGetRow(string $sql, array $vals): array|false {
        if ($this->DEBUG) error_log($sql . " :: " . implode(" ", $vals));
        $this->statement = $this->db->prepare($sql, [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY]);
        $this->statement->execute($vals);
        $this->rowCount  = $this->statement->rowCount();
        return $this->statement->fetch();
    }

    public function select(string $sql): PDOStatement {
        $sql            = trim($sql);
        if ($this->DEBUG) error_log($sql);
        $result         = $this->db->query($sql);
        $this->rowCount = $result->rowCount();
        return $result;
    }

    public function getOne(string $sql): mixed {
        $sql            = trim($sql);
        if ($this->DEBUG) error_log($sql);
        $q              = $this->db->query($sql);
        $this->rowCount = $q->rowCount();
        return $q->fetchColumn();
    }

    private function quoteIdentifier(string $name): string {
        return '`' . str_replace('`', '``', $name) . '`';
    }

    public function update(string $table, array $values, mixed $where = null, array $whereVals = []): int {
        if (empty($values)) { $this->rowCount = 0; return 0; }
        $setClauses = [];
        $vals       = [];
        foreach ($values as $key => $value) {
            $setClauses[] = $this->quoteIdentifier($key) . " = ?";
            $vals[]       = $value;
        }
        $sql = "UPDATE " . $this->quoteIdentifier($table) . " SET " . implode(", ", $setClauses);
        if (isset($where)) { $sql .= " WHERE $where"; $vals = array_merge($vals, $whereVals); }
        if ($this->DEBUG) error_log($sql . " :: " . implode(" ", $vals));
        $this->statement = $this->db->prepare($sql);
        $this->statement->execute($vals);
        $this->rowCount  = $this->statement->rowCount();
        return $this->rowCount;
    }

    public function insert(string $table, array $values): int {
        if (empty($values)) { $this->rowCount = 0; return 0; }
        $cols = $placeholders = $vals = [];
        foreach ($values as $key => $value) {
            $cols[]         = $this->quoteIdentifier($key);
            $placeholders[] = "?";
            $vals[]         = $value;
        }
        $sql = "INSERT INTO " . $this->quoteIdentifier($table)
             . " (" . implode(",", $cols) . ") VALUES (" . implode(",", $placeholders) . ")";
        if ($this->DEBUG || $this->INSERT_DEBUG) error_log($sql . "\n" . implode(" ", $values));
        $this->statement = $this->db->prepare($sql);
        $this->statement->execute($vals);
        $this->rowCount  = $this->statement->rowCount();
        return (int) $this->db->lastInsertId();
    }

    public function startTransaction(): bool {
        $this->db->setAttribute(PDO::ATTR_AUTOCOMMIT, 0);
        if (!$this->db->inTransaction()) return $this->db->beginTransaction();
        return true;
    }

    public function commit(): bool {
        if ($this->db->inTransaction()) $this->db->commit();
        $this->db->setAttribute(PDO::ATTR_AUTOCOMMIT, 1);
        return true;
    }

    public function rollBack(): void {
        if (isset($this->statement)) $this->statement->closeCursor();
        if ($this->db->inTransaction()) $this->db->rollBack();
        $this->db->setAttribute(PDO::ATTR_AUTOCOMMIT, 1);
    }
}
