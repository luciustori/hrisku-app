<?php

class MigrationHelper {
    private $db;
    private $config;
    
    public function __construct($config) {
        $this->config = $config;
        $this->connect();
    }
    
    private function connect() {
        try {
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                $this->config['db']['host'],
                $this->config['db']['name'],
                $this->config['db']['charset']
            );
            
            $this->db = new PDO(
                $dsn,
                $this->config['db']['user'],
                $this->config['db']['pass'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );
        } catch (PDOException $e) {
            die("Connection failed: " . $e->getMessage() . "\n");
        }
    }
    
    public function createMigrationsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS migrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            version VARCHAR(50) UNIQUE NOT NULL,
            migration_name VARCHAR(255) NOT NULL,
            executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            execution_time DECIMAL(10,3) NULL,
            status ENUM('success', 'failed', 'rolled_back') DEFAULT 'success',
            checksum VARCHAR(64) NULL,
            INDEX idx_version (version),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        try {
            $this->db->exec($sql);
            echo "✓ Migrations table ready\n";
        } catch (PDOException $e) {
            echo "✗ Failed to create migrations table: " . $e->getMessage() . "\n";
            exit(1);
        }
    }
    
    public function getExecutedMigrations() {
        try {
            $stmt = $this->db->query("SELECT version FROM {$this->config['table']} WHERE status = 'success' ORDER BY version ASC");
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    public function getPendingMigrations() {
        $executed = $this->getExecutedMigrations();
        $allFiles = glob($this->config['paths']['migrations'] . '*.sql');
        $pending = [];
        
        foreach ($allFiles as $file) {
            $filename = basename($file);
            preg_match('/^(\d{8}_\d{3})_/', $filename, $matches);
            $version = $matches[1] ?? null;
            
            if ($version && !in_array($version, $executed)) {
                preg_match('/^\d{8}_\d{3}_(.+)\.sql$/', $filename, $nameMatches);
                $name = str_replace('_', ' ', $nameMatches[1] ?? $filename);
                
                $pending[] = [
                    'version' => $version,
                    'file' => $file,
                    'name' => $name
                ];
            }
        }
        
        sort($pending);
        return $pending;
    }
    
    public function executeMigration($migration) {
        $startTime = microtime(true);
        
        try {
            // Baca file SQL
            $sql = file_get_contents($migration['file']);
            $checksum = hash('sha256', $sql);
            
            // Pecah SQL menjadi statement-statement individual
            $statements = $this->parseSQL($sql);
            
            // Eksekusi setiap statement
            foreach ($statements as $statement) {
                if (!empty(trim($statement))) {
                    $this->db->exec($statement);
                }
            }
            
            $executionTime = round(microtime(true) - $startTime, 3);
            
            // Catat migration ke database (tanpa transaction karena DDL sudah auto-commit)
            $stmt = $this->db->prepare(
                "INSERT INTO {$this->config['table']} (version, migration_name, execution_time, status, checksum) 
                 VALUES (:version, :name, :time, 'success', :checksum)"
            );
            
            $stmt->execute([
                'version' => $migration['version'],
                'name' => $migration['name'],
                'time' => $executionTime,
                'checksum' => $checksum
            ]);
            
            echo "✓ [{$migration['version']}] {$migration['name']} ({$executionTime}s)\n";
            return true;
            
        } catch (Exception $e) {
            echo "✗ [{$migration['version']}] FAILED: " . $e->getMessage() . "\n";
            
            // Catat migration gagal
            try {
                $stmt = $this->db->prepare(
                    "INSERT INTO {$this->config['table']} (version, migration_name, status) 
                     VALUES (:version, :name, 'failed')
                     ON DUPLICATE KEY UPDATE status = 'failed'"
                );
                $stmt->execute([
                    'version' => $migration['version'],
                    'name' => $migration['name']
                ]);
            } catch (Exception $e2) {
                // Abaikan error saat mencatat kegagalan
            }
            
            return false;
        }
    }
    
    /**
     * Parse SQL file menjadi statements individual
     * Memisahkan berdasarkan semicolon, tapi mengabaikan yang ada dalam string
     */
    private function parseSQL($sql) {
        // Hapus komentar
        $sql = preg_replace('/--.*$/m', '', $sql);
        $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
        
        // Split berdasarkan semicolon
        $statements = [];
        $current = '';
        $inString = false;
        $stringChar = '';
        
        for ($i = 0; $i < strlen($sql); $i++) {
            $char = $sql[$i];
            
            // Check untuk string delimiter
            if (($char === '"' || $char === "'") && ($i === 0 || $sql[$i-1] !== '\\')) {
                if (!$inString) {
                    $inString = true;
                    $stringChar = $char;
                } elseif ($char === $stringChar) {
                    $inString = false;
                }
            }
            
            // Jika ketemu semicolon di luar string
            if ($char === ';' && !$inString) {
                $statements[] = trim($current);
                $current = '';
                continue;
            }
            
            $current .= $char;
        }
        
        // Tambahkan statement terakhir jika ada
        if (!empty(trim($current))) {
            $statements[] = trim($current);
        }
        
        return $statements;
    }
    
    public function rollback($version) {
        $rollbackFile = $this->config['paths']['rollback'] . "{$version}_rollback.sql";
        
        if (!file_exists($rollbackFile)) {
            echo "✗ Rollback file not found for version {$version}\n";
            return false;
        }
        
        try {
            $sql = file_get_contents($rollbackFile);
            $statements = $this->parseSQL($sql);
            
            foreach ($statements as $statement) {
                if (!empty(trim($statement))) {
                    $this->db->exec($statement);
                }
            }
            
            // Update status migration
            $stmt = $this->db->prepare(
                "UPDATE {$this->config['table']} SET status = 'rolled_back' WHERE version = :version"
            );
            $stmt->execute(['version' => $version]);
            
            echo "✓ Rolled back: {$version}\n";
            return true;
            
        } catch (Exception $e) {
            echo "✗ Rollback FAILED: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    public function getStatus() {
        $executed = $this->getExecutedMigrations();
        $pending = $this->getPendingMigrations();
        
        return [
            'executed' => count($executed),
            'pending' => count($pending),
            'total' => count($executed) + count($pending)
        ];
    }
    
    public function getConnection() {
        return $this->db;
    }
}
