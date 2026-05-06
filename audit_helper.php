<?php
/**
 * Audit Log Helper
 * Include questo file e chiama registra_log() per tracciare le azioni utente.
 */

function registra_log($conn, $azione, $tabella = null, $record_id = null, $dettagli = null) {
    if (!$conn) return false;
    
    $user_id = $_SESSION['user_id'] ?? null;
    $nome_utente = $_SESSION['nome_utente'] ?? $_SESSION['nome'] ?? 'Sistema';
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    
    $stmt = $conn->prepare("INSERT INTO audit_log (user_id, nome_utente, azione, tabella, record_id, dettagli, ip_address) VALUES (?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) return false;
    
    $stmt->bind_param("isssiss", $user_id, $nome_utente, $azione, $tabella, $record_id, $dettagli, $ip);
    $result = $stmt->execute();
    $stmt->close();
    
    return $result;
}
