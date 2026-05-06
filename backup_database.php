<?php
session_start();
if (!isset($_SESSION['user_id'])) { http_response_code(403); exit('Non autorizzato'); }

// Solo admin può fare backup
$ruolo = $_SESSION['ruolo'] ?? 'utente';
if ($ruolo !== 'admin') { http_response_code(403); exit('Solo gli amministratori possono eseguire il backup.'); }

include 'db.php';
include 'audit_helper.php';

$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'gestionale_tsservice';
$mysqldump = '/Applications/XAMPP/xamppfiles/bin/mysqldump';

$backupDir = __DIR__ . '/backups';
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
    // Crea .htaccess per proteggere la cartella
    file_put_contents($backupDir . '/.htaccess', "Deny from all\n");
}

$filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
$filepath = $backupDir . '/' . $filename;

// Esegui mysqldump
$cmd = sprintf('%s --user=%s --host=%s %s > %s 2>&1',
    escapeshellarg($mysqldump),
    escapeshellarg($user),
    escapeshellarg($host),
    escapeshellarg($dbname),
    escapeshellarg($filepath)
);

if (!empty($password)) {
    $cmd = sprintf('%s --user=%s --password=%s --host=%s %s > %s 2>&1',
        escapeshellarg($mysqldump),
        escapeshellarg($user),
        escapeshellarg($password),
        escapeshellarg($host),
        escapeshellarg($dbname),
        escapeshellarg($filepath)
    );
}

exec($cmd, $output, $returnVar);

if ($returnVar === 0 && file_exists($filepath) && filesize($filepath) > 0) {
    // Registra nel log
    registra_log($conn, 'Backup database', 'database', null, 'File: ' . $filename . ' (' . round(filesize($filepath)/1024, 1) . ' KB)');
    
    // Scarica il file
    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($filepath));
    header('Cache-Control: no-cache, must-revalidate');
    readfile($filepath);
    
    // Rimuovi file locale dopo download (opzionale, tieni gli ultimi 5)
    $backups = glob($backupDir . '/backup_*.sql');
    usort($backups, function($a, $b) { return filemtime($b) - filemtime($a); });
    foreach (array_slice($backups, 5) as $old) {
        @unlink($old);
    }
    exit;
} else {
    http_response_code(500);
    $error = implode("\n", $output);
    header('Content-Type: application/json');
    echo json_encode(['error' => true, 'message' => 'Errore backup: ' . $error]);
}
