<?php

declare(strict_types=1);

if ($argc !== 4 || !in_array($argv[1], ['prepare', 'seed', 'verify'], true)) {
    fwrite(STDERR, "Usage: php tools/rehearse-database-upgrade.php prepare|seed|verify <phinx-config.php> <environment>\n");
    exit(2);
}

[$script, $mode, $configPath, $environmentName] = $argv;
unset($script);

$resolvedConfigPath = realpath($configPath);
if ($resolvedConfigPath === false || !is_file($resolvedConfigPath)) {
    throw new RuntimeException('Phinx configuration file was not found.');
}

$config = require $resolvedConfigPath;
if (!is_array($config)) {
    throw new RuntimeException('Phinx configuration must return an array.');
}

$environment = $config['environments'][$environmentName] ?? null;
if (!is_array($environment)) {
    throw new RuntimeException("Phinx environment '$environmentName' was not found.");
}

$databaseName = (string) ($environment['name'] ?? '');
$host = (string) ($environment['host'] ?? '');
if (!str_ends_with($databaseName, '_test')) {
    throw new RuntimeException('Refusing to modify a database whose name does not end with _test.');
}
if (!in_array($host, ['127.0.0.1', 'localhost'], true)) {
    throw new RuntimeException('Refusing to run the upgrade rehearsal against a non-local database host.');
}

$port = (int) ($environment['port'] ?? 3306);
$dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $host, $port, $databaseName);
$pdo = new PDO(
    $dsn,
    (string) ($environment['user'] ?? ''),
    (string) ($environment['pass'] ?? ''),
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ],
);

/** @var Closure(PDO, string, string): ?string $columnType */
$columnType = static function (PDO $pdo, string $table, string $column): ?string {
    $statement = $pdo->prepare(<<<'SQL'
SELECT COLUMN_TYPE
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = :table_name
  AND COLUMN_NAME = :column_name
SQL);
    $statement->execute(['table_name' => $table, 'column_name' => $column]);
    $type = $statement->fetchColumn();

    return $type === false ? null : strtolower((string) $type);
};

/** @var Closure(mixed, mixed, string): void $assertSameValue */
$assertSameValue = static function (mixed $expected, mixed $actual, string $message): void {
    if ($expected !== $actual) {
        throw new RuntimeException($message . sprintf(' Expected %s, got %s.', var_export($expected, true), var_export($actual, true)));
    }
};

$transitionVersions = ['202608201200', '202609071500'];
$sentinelUsername = 'upgrade-sentinel';
$sentinelSong = 'Upgrade sentinel';

if ($mode === 'prepare') {
    $assertSameValue(null, $columnType($pdo, 'users', 'deprecated_role'), 'Expected the current users schema before preparing the legacy transition.');
    $assertSameValue('tinyint(1)', $columnType($pdo, 'skladby', 'active'), 'Expected the current song flag type before preparing the legacy transition.');

    $pdo->exec("ALTER TABLE users ADD deprecated_role VARCHAR(100) NOT NULL DEFAULT ''");
    $pdo->exec('ALTER TABLE skladby MODIFY active BIT(1) NOT NULL');

    $placeholders = implode(', ', array_fill(0, count($transitionVersions), '?'));
    $deleteVersions = $pdo->prepare("DELETE FROM _phinxlog WHERE version IN ($placeholders)");
    $deleteVersions->execute($transitionVersions);

    $assertSameValue('varchar(100)', $columnType($pdo, 'users', 'deprecated_role'), 'Failed to prepare the deprecated role column.');
    $assertSameValue('bit(1)', $columnType($pdo, 'skladby', 'active'), 'Failed to prepare the legacy song flag type.');
    fwrite(STDOUT, "Synthetic legacy-like transition fixture prepared in a local _test database.\n");
    exit(0);
}

if ($mode === 'seed') {
    $assertSameValue('varchar(100)', $columnType($pdo, 'users', 'deprecated_role'), 'Expected the prepared deprecated role column.');
    $assertSameValue('bit(1)', $columnType($pdo, 'skladby', 'active'), 'Expected the prepared legacy song flag type.');

    $deleteSongs = $pdo->prepare('DELETE FROM skladby WHERE nazev = :title');
    $deleteSongs->execute(['title' => $sentinelSong]);
    $deleteUsers = $pdo->prepare('DELETE FROM users WHERE username = :username');
    $deleteUsers->execute(['username' => $sentinelUsername]);

    $insertUser = $pdo->prepare(<<<'SQL'
INSERT INTO users (username, email, password, name, surname, opravneni, deprecated_role)
VALUES (:username, :email, :password, :name, :surname, :permissions, :deprecated_role)
SQL);
    $insertUser->execute([
        'username' => $sentinelUsername,
        'email' => 'upgrade-sentinel@example.test',
        'password' => str_repeat('x', 60),
        'name' => 'Upgrade',
        'surname' => 'Sentinel',
        'permissions' => '',
        'deprecated_role' => 'legacy-user',
    ]);
    $userId = (int) $pdo->lastInsertId();

    $insertSong = $pdo->prepare(<<<'SQL'
INSERT INTO skladby (nazev, autor, active, createdAt, createdBy)
VALUES (:title, :author, b'1', :created_at, :created_by)
SQL);
    $insertSong->execute([
        'title' => $sentinelSong,
        'author' => 'Synthetic Composer',
        'created_at' => '2026-09-23 12:00:00',
        'created_by' => $userId,
    ]);

    fwrite(STDOUT, "Synthetic sentinel records created before transition migrations.\n");
    exit(0);
}

$assertSameValue(null, $columnType($pdo, 'users', 'deprecated_role'), 'The deprecated role column survived the upgrade.');
$assertSameValue('tinyint(1)', $columnType($pdo, 'skladby', 'active'), 'The song flag type was not normalized.');

$userStatement = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = :username');
$userStatement->execute(['username' => $sentinelUsername]);
$assertSameValue(1, (int) $userStatement->fetchColumn(), 'The synthetic user did not survive the upgrade.');

$songStatement = $pdo->prepare('SELECT active + 0 FROM skladby WHERE nazev = :title');
$songStatement->execute(['title' => $sentinelSong]);
$assertSameValue(1, (int) $songStatement->fetchColumn(), 'The synthetic boolean value did not survive the upgrade.');

$placeholders = implode(', ', array_fill(0, count($transitionVersions), '?'));
$versionStatement = $pdo->prepare("SELECT COUNT(*) FROM _phinxlog WHERE version IN ($placeholders)");
$versionStatement->execute($transitionVersions);
$assertSameValue(count($transitionVersions), (int) $versionStatement->fetchColumn(), 'The transition migrations were not both recorded.');

$deleteSongs = $pdo->prepare('DELETE FROM skladby WHERE nazev = :title');
$deleteSongs->execute(['title' => $sentinelSong]);
$deleteUsers = $pdo->prepare('DELETE FROM users WHERE username = :username');
$deleteUsers->execute(['username' => $sentinelUsername]);

fwrite(STDOUT, "Synthetic database transition and sentinel data verified.\n");
