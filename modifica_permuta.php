<?php
session_start();

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

include 'db.php';

if (!isset($conn) || $conn === null) {
    die("Errore critico: Connessione al database non stabilita.");
}

// Recupera l'ID della permuta da modificare
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    die("ID permuta non valido.");
}

// Recupera i dati della permuta dalla tabella corretta (permute_nuovo)
$stmt = $conn->prepare("SELECT * FROM permute_nuovo WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$permuta = $result->fetch_assoc();
$stmt->close();

if (!$permuta) {
    die("Permuta non trovata.");
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <title>Modifica Permuta #<?php echo htmlspecialchars($permuta['progressivo'] ?? $permuta['id']); ?></title>
    <link rel="stylesheet" href="assets/header-styles.css?v=1">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --brand-green: #22c55e;
            --brand-green-dark: #16a34a;
        }
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
            margin: 0;
            padding: 20px;
            padding-top: 100px;
            color: #334155;
        }
        .contenitore {
            max-width: 900px;
            margin: 20px auto;
            padding: 30px;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.08);
        }
        h2 {
            color: var(--brand-green-dark);
            margin-bottom: 30px;
            font-size: 1.6rem;
        }
        .edit-section {
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid #e2e8f0;
        }
        .edit-section:last-of-type { border-bottom: none; }
        .edit-section h3 {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--brand-green-dark);
            margin-bottom: 16px;
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
        }
        .form-grid.cols-3 { grid-template-columns: repeat(3, 1fr); }
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .form-group.full-width { grid-column: 1 / -1; }
        label {
            font-size: 0.8rem;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        input[type="text"], input[type="number"], input[type="date"], textarea, select {
            width: 100%;
            padding: 10px 14px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 0.95rem;
            background: #f8fafc;
            box-sizing: border-box;
            transition: border-color 0.2s;
        }
        input:focus, textarea:focus, select:focus {
            outline: none;
            border-color: var(--brand-green);
            box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.15);
            background: #fff;
        }
        input[readonly] { background: #e2e8f0; color: #64748b; cursor: not-allowed; }
        textarea { min-height: 80px; resize: vertical; }
        .btn-group {
            display: flex;
            gap: 12px;
            margin-top: 24px;
            justify-content: flex-end;
        }
        .btn {
            padding: 12px 28px;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-save {
            background: linear-gradient(135deg, var(--brand-green), var(--brand-green-dark));
            color: white;
            box-shadow: 0 4px 12px rgba(34, 197, 94, 0.3);
        }
        .btn-save:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(34, 197, 94, 0.4); }
        .btn-cancel {
            background: #f1f5f9;
            color: #64748b;
            border: 2px solid #e2e8f0;
        }
        .btn-cancel:hover { background: #e2e8f0; color: #334155; }
        .msg-success { background: #dcfce7; color: #166534; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; display: none; }
        .msg-error { background: #fee2e2; color: #991b1b; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; display: none; }
        @media (max-width: 768px) {
            .form-grid, .form-grid.cols-3 { grid-template-columns: 1fr; }
            .contenitore { padding: 20px; }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    <div class="contenitore">
        <h2><i class="fas fa-edit"></i> Modifica Permuta #<?php echo htmlspecialchars($permuta['progressivo'] ?? $permuta['id']); ?></h2>
        <div id="msgSuccess" class="msg-success"></div>
        <div id="msgError" class="msg-error"></div>
        <form id="editPermutaForm">
            <input type="hidden" name="id" value="<?php echo intval($permuta['id']); ?>">

            <div class="edit-section">
                <h3><i class="fas fa-user"></i> Dati Generali</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="progressivo">Progressivo</label>
                        <input type="text" id="progressivo" name="progressivo" value="<?php echo htmlspecialchars($permuta['progressivo'] ?? ''); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label for="data">Data Permuta</label>
                        <input type="date" id="data" name="data" value="<?php echo htmlspecialchars($permuta['data'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="cliente">Cliente</label>
                        <input type="text" id="cliente" name="cliente" value="<?php echo htmlspecialchars($permuta['cliente'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="telefono">Telefono</label>
                        <input type="text" id="telefono" name="telefono" value="<?php echo htmlspecialchars($permuta['telefono'] ?? $permuta['telefono_cliente'] ?? ''); ?>">
                    </div>
                </div>
            </div>

            <div class="edit-section">
                <h3><i class="fas fa-mobile-alt"></i> Dispositivo Nuovo (Venduto)</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="modello_nuovo">Modello Nuovo</label>
                        <input type="text" id="modello_nuovo" name="modello_nuovo" value="<?php echo htmlspecialchars($permuta['modello_nuovo'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="imei_nuovo">IMEI Nuovo</label>
                        <input type="text" id="imei_nuovo" name="imei_nuovo" value="<?php echo htmlspecialchars($permuta['imei_nuovo'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="prezzo_nuovo">Prezzo Nuovo (€)</label>
                        <input type="number" step="0.01" id="prezzo_nuovo" name="prezzo_nuovo" value="<?php echo number_format((float)($permuta['prezzo_nuovo'] ?? 0), 2, '.', ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="costo_prodotto">Costo Prodotto (€)</label>
                        <input type="number" step="0.01" id="costo_prodotto" name="costo_prodotto" value="<?php echo number_format((float)($permuta['costo_prodotto'] ?? 0), 2, '.', ''); ?>">
                    </div>
                    <div class="form-group full-width">
                        <label for="note_nuovo">Note Nuovo</label>
                        <textarea id="note_nuovo" name="note_nuovo" rows="2"><?php echo htmlspecialchars($permuta['note_nuovo'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>

            <div class="edit-section">
                <h3><i class="fas fa-exchange-alt"></i> Dispositivo Usato (Ritirato)</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="modello_usato">Modello Usato</label>
                        <input type="text" id="modello_usato" name="modello_usato" value="<?php echo htmlspecialchars($permuta['modello_usato'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="imei_usato">IMEI Usato</label>
                        <input type="text" id="imei_usato" name="imei_usato" value="<?php echo htmlspecialchars($permuta['imei_usato'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="prezzo_permuta">Prezzo Permuta (€)</label>
                        <input type="number" step="0.01" id="prezzo_permuta" name="prezzo_permuta" value="<?php echo number_format((float)($permuta['prezzo_permuta'] ?? 0), 2, '.', ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="costo_riparazione">Costo Riparazione (€)</label>
                        <input type="number" step="0.01" id="costo_riparazione" name="costo_riparazione" value="<?php echo number_format((float)($permuta['costo_riparazione'] ?? 0), 2, '.', ''); ?>">
                    </div>
                    <div class="form-group full-width">
                        <label for="note_usato">Note Usato</label>
                        <textarea id="note_usato" name="note_usato" rows="2"><?php echo htmlspecialchars($permuta['note_usato'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>

            <div class="edit-section">
                <h3><i class="fas fa-euro-sign"></i> Riepilogo Finanziario e Stato</h3>
                <div class="form-grid cols-3">
                    <div class="form-group">
                        <label for="costo_accessori">Costo Accessori (€)</label>
                        <input type="number" step="0.01" id="costo_accessori" name="costo_accessori" value="<?php echo number_format((float)($permuta['costo_accessori'] ?? 0), 2, '.', ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="differenza">Differenza (€)</label>
                        <input type="number" step="0.01" id="differenza" name="differenza" value="<?php echo number_format((float)($permuta['differenza'] ?? 0), 2, '.', ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="prezzo_vendita">Prezzo Vendita Finale (€)</label>
                        <input type="number" step="0.01" id="prezzo_vendita" name="prezzo_vendita" value="<?php echo number_format((float)($permuta['prezzo_vendita'] ?? 0), 2, '.', ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <?php
                            $stati = ['In Trattativa', 'Accettata', 'Rifiutata', 'Completata', 'Annullata'];
                            foreach ($stati as $s) {
                                $selected = ($permuta['status'] ?? '') === $s ? 'selected' : '';
                                echo "<option value=\"$s\" $selected>$s</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="edit-section">
                <h3><i class="fas fa-clipboard-list"></i> Note e Test</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="test_ok">Test Effettuati</label>
                        <textarea id="test_ok" name="test_ok" rows="3"><?php echo htmlspecialchars($permuta['test_ok'] ?? ''); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="note_generali">Note Generali</label>
                        <textarea id="note_generali" name="note_generali" rows="3"><?php echo htmlspecialchars($permuta['note_generali'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>

            <div class="btn-group">
                <a href="storico_permute.php" class="btn btn-cancel">Annulla</a>
                <button type="submit" class="btn btn-save"><i class="fas fa-save"></i> Salva Modifiche</button>
            </div>
        </form>
    </div>
    <script>
    document.getElementById('editPermutaForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const msgSuccess = document.getElementById('msgSuccess');
        const msgError = document.getElementById('msgError');
        msgSuccess.style.display = 'none';
        msgError.style.display = 'none';
        const formData = new FormData(this);
        try {
            const response = await fetch('update_permuta.php', { method: 'POST', body: formData });
            if (!response.ok) throw new Error('Errore HTTP: ' + response.status);
            const result = await response.json();
            if (result.success) {
                msgSuccess.textContent = result.message;
                msgSuccess.style.display = 'block';
                setTimeout(() => { window.location.href = 'storico_permute.php'; }, 1500);
            } else {
                msgError.textContent = result.message;
                msgError.style.display = 'block';
            }
        } catch (error) {
            msgError.textContent = 'Errore durante il salvataggio: ' + error.message;
            msgError.style.display = 'block';
        }
    });
    </script>
</body>
</html>
<?php
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>
