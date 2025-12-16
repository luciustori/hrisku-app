<?php
require_once __DIR__ . '/migration_helper.php';

$config = include __DIR__ . '/../config/migration_config.php';
$migrator = new MigrationHelper($config);

echo "\n═══════════════════════════════════════\n";
echo "  HRISKU APP - Migration Manager\n";
echo "═══════════════════════════════════════\n\n";

echo "Creating migrations table...\n";
$migrator->createMigrationsTable();
echo "✓ Migrations table ready\n\n";

$pending = $migrator->getPendingMigrations();

if (empty($pending)) {
    echo "✓ No pending migrations\n\n";
} else {
    echo "Found " . count($pending) . " pending migration(s)\n\n";
    
    foreach ($pending as $migration) {
        if (!$migrator->executeMigration($migration)) {
            echo "\n✗ Migration stopped due to error\n\n";
            exit(1);
        }
    }
    
    echo "\n✓ All migrations completed successfully\n\n";
}
