<?php
declare(strict_types=1);

final class DB
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo) {
            return self::$pdo;
        }

        $db = Config::get('db', []);
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $db['host'] ?? 'localhost',
            (int) ($db['port'] ?? 3306),
            $db['name'] ?? ''
        );

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_STRINGIFY_FETCHES => false,
        ];

        // Remote databases that require TLS (e.g. Aiven) need the CA certificate.
        if (!empty($db['ssl_ca'])) {
            $options[PDO::MYSQL_ATTR_SSL_CA] = $db['ssl_ca'];
            $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = (bool) ($db['ssl_verify'] ?? true);
        }

        self::$pdo = new PDO($dsn, $db['user'] ?? '', $db['pass'] ?? '', $options);
        self::$pdo->exec("SET time_zone = '+00:00'");

        return self::$pdo;
    }

    public static function run(string $sql, array $params = []): PDOStatement
    {
        $statement = self::pdo()->prepare($sql);
        $statement->execute(array_map([self::class, 'value'], array_values($params)));
        return $statement;
    }

    public static function all(string $sql, array $params = []): array
    {
        return array_map([self::class, 'normalize'], self::run($sql, $params)->fetchAll());
    }

    public static function one(string $sql, array $params = []): ?array
    {
        $row = self::run($sql, $params)->fetch();
        return $row ? self::normalize($row) : null;
    }

    public static function scalar(string $sql, array $params = [])
    {
        return self::run($sql, $params)->fetchColumn();
    }

    public static function insert(string $table, array $data): string
    {
        $columns = array_keys($data);
        $sql = sprintf(
            'INSERT INTO `%s` (%s) VALUES (%s)',
            $table,
            implode(', ', array_map(fn ($c) => "`$c`", $columns)),
            implode(', ', array_fill(0, count($columns), '?'))
        );
        self::run($sql, array_values($data));
        return self::pdo()->lastInsertId();
    }

    public static function update(string $table, array $data, string $where, array $params): void
    {
        if (!$data) {
            return;
        }
        $set = implode(', ', array_map(fn ($c) => "`$c` = ?", array_keys($data)));
        self::run("UPDATE `$table` SET $set WHERE $where", [...array_values($data), ...$params]);
    }

    public static function transaction(callable $callback)
    {
        $pdo = self::pdo();
        $pdo->beginTransaction();
        try {
            $result = $callback();
            $pdo->commit();
            return $result;
        } catch (Throwable $error) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $error;
        }
    }

    // `%` and `_` are matched literally, like Prisma's `contains`.
    public static function like(string $term): string
    {
        return '%' . addcslashes($term, '%_\\') . '%';
    }

    public static function isDuplicate(Throwable $error): bool
    {
        return $error instanceof PDOException && (int) ($error->errorInfo[1] ?? 0) === 1062;
    }

    private static function value($value)
    {
        if ($value instanceof DateTimeInterface) {
            return Dates::toDb($value);
        }
        if (is_bool($value)) {
            return $value ? 1 : 0;
        }
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        return $value;
    }

    private const DATE_COLUMNS = [
        'dateOfBirth', 'dateOfJoining', 'validityFrom', 'validityTo', 'createdAt',
        'updatedAt', 'statusActionAt', 'paymentDate',
    ];

    // Gives rows the same JSON types Prisma returns: ISO dates, booleans,
    // parsed JSON and Decimal as a string.
    public static function normalize(array $row): array
    {
        foreach ($row as $column => $value) {
            if ($value === null) {
                continue;
            }
            if (in_array($column, self::DATE_COLUMNS, true)) {
                $row[$column] = Dates::fromDb((string) $value);
            } elseif ($column === 'isActive' || $column === 'isShow') {
                $row[$column] = (bool) $value;
            } elseif ($column === 'specialDates') {
                $row[$column] = json_decode((string) $value, true);
            } elseif ($column === 'amount') {
                $row[$column] = self::decimal((string) $value);
            }
        }
        return $row;
    }

    private static function decimal(string $value): string
    {
        return str_contains($value, '.') ? rtrim(rtrim($value, '0'), '.') : $value;
    }
}
