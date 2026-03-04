<?php
// stampa_buono.php — Stampa Buono Regalo Professionale
ini_set('display_errors', 0);
require_once 'db.php';

$buono = null;
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $buono_id = (int)$_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM buoni_regalo WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param('i', $buono_id);
        $stmt->execute();
        $buono = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
}
if (!$buono) { echo "Buono regalo non trovato."; exit; }
$conn->close();

$codice      = htmlspecialchars($buono['nome']);
$valore      = number_format($buono['valore'], 2, ',', '.');
$destinatario= !empty($buono['destinatario']) ? htmlspecialchars($buono['destinatario']) : '—';
$mittente    = !empty($buono['note']) ? htmlspecialchars($buono['note']) : '—';
$scadenza    = $buono['data_scadenza'] ? date('d/m/Y', strtotime($buono['data_scadenza'])) : '—';
$emissione   = date('d/m/Y', strtotime($buono['data_creazione']));
$stato       = htmlspecialchars($buono['stato'] ?? 'Attivo');
?><!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="icon" type="image/svg+xml" href="favicon.svg">
<title>Buono Regalo <?= $codice ?> — TS Service</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/gh/davidshimjs/qrcodejs/qrcode.min.js"></script>
<style>
/* ── Variables ── */
:root {
  --g900:#0b2618;--g800:#14532d;--g700:#1b4332;--g600:#2d6a4f;
  --g500:#40916c;--g400:#52b788;--g300:#74c69d;--g200:#95d5b2;
  --g100:#d1fae5;--g50:#f0fdf4;
  --ink:#111827;--mute:#6b7280;--line:#e5e7eb;--faint:#f9fafb;
  --gold:#f59e0b;--gold-bg:#fffbeb;--gold-bdr:#fde68a;
}

/* ── Reset ── */
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Inter',-apple-system,sans-serif;color:var(--ink);background:#e5e7eb;line-height:1.35}
@page{size:A4 portrait;margin:12mm}

/* ── Screen toolbar ── */
.toolbar{text-align:center;padding:24px 0}
.toolbar button, .toolbar a{
  display:inline-flex;align-items:center;gap:8px;
  padding:12px 28px;border-radius:12px;border:none;cursor:pointer;
  font:600 .9rem/1 'Inter',sans-serif;text-decoration:none;
  transition:all .2s;box-shadow:0 4px 16px rgba(0,0,0,.1);
}
.toolbar button:hover,.toolbar a:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(0,0,0,.15)}
.btn-print{background:linear-gradient(135deg,var(--g700),var(--g500));color:#fff}
.btn-back{background:#f1f5f9;color:#475569;border:2px solid var(--line);margin-right:12px}
.toolbar .hint{font-size:.72rem;color:#aaa;margin-top:8px}

/* ── Card Container ── */
.card-page{
  width:180mm;margin:0 auto 30px;background:#fff;
  border-radius:16px;overflow:hidden;
  box-shadow:0 8px 40px rgba(0,0,0,.1);
}

/* ── Header ── */
.hdr{
  position:relative;overflow:hidden;
  background:linear-gradient(135deg,var(--g800) 0%,var(--g600) 55%,var(--g400) 100%);
  color:#fff;padding:8mm 8mm 6mm;
}
.hdr::after{
  content:'';position:absolute;right:-15mm;top:-18mm;
  width:70mm;height:70mm;border-radius:50%;
  background:rgba(255,255,255,.06);pointer-events:none;
}
.hdr-top{
  display:flex;justify-content:space-between;align-items:flex-start;
  position:relative;z-index:1;margin-bottom:5mm;
}
.hdr-brand{display:flex;align-items:center;gap:3mm}
.hdr-logo{height:14mm;width:auto;filter:brightness(0) invert(1);opacity:.9}
.brand-text .name{font-size:16pt;font-weight:900;letter-spacing:2px;line-height:1}
.brand-text .sub{font-size:6.5pt;opacity:.55;margin-top:1mm;letter-spacing:.5px}
.hdr-badge{
  font-size:7pt;font-weight:700;letter-spacing:2px;text-transform:uppercase;
  background:rgba(255,255,255,.18);backdrop-filter:blur(8px);
  padding:2mm 4mm;border-radius:20px;
}
.hdr-main{
  display:flex;justify-content:space-between;align-items:flex-end;
  position:relative;z-index:1;
}
.hdr-value{
  font-size:42pt;font-weight:900;line-height:1;letter-spacing:-1px;
  text-shadow:0 3px 15px rgba(0,0,0,.2);
}
.hdr-value small{font-size:20pt;font-weight:700;vertical-align:super;margin-left:1mm}
.hdr-qr{
  background:#fff;padding:2.5mm;border-radius:3mm;
  box-shadow:0 4px 16px rgba(0,0,0,.2);flex-shrink:0;
}
#qrcode canvas,#qrcode img{width:26mm!important;height:26mm!important;display:block}

/* ── Code Strip ── */
.code-strip{
  background:var(--g900);color:#fff;
  padding:3mm 8mm;
  display:flex;align-items:center;justify-content:center;gap:4mm;
}
.code-strip .lbl{font-size:6pt;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:var(--g400)}
.code-strip .val{font-family:'SF Mono','Cascadia Code','Consolas',monospace;font-size:14pt;font-weight:800;letter-spacing:4px}

/* ── Body ── */
.card-body{padding:6mm 8mm 8mm}

/* ── Sections ── */
.sect{border:1.5px solid var(--line);border-radius:8px;overflow:hidden;margin-bottom:4mm}
.sh{
  padding:2mm 4mm;font-size:6.5pt;font-weight:800;
  letter-spacing:1.5px;text-transform:uppercase;
  color:var(--g700);background:var(--faint);
  border-bottom:1.5px solid var(--line);
  display:flex;align-items:center;gap:2mm;
}
.sh svg{width:3.5mm;height:3.5mm;stroke:var(--g500);fill:none;stroke-width:2}

/* ── Grid ── */
.grid{display:grid;gap:0}
.grid-2{grid-template-columns:1fr 1fr}
.grid-3{grid-template-columns:1fr 1fr 1fr}
.field{padding:3mm 4mm;border-bottom:1px solid #f5f5f5}
.grid-2 .field:nth-child(odd),.grid-3 .field:not(:nth-child(3n)){border-right:1px solid #f0f0f0}
.fl{font-size:5.5pt;font-weight:700;color:var(--mute);text-transform:uppercase;letter-spacing:.8px;margin-bottom:1mm}
.fv{font-size:10pt;font-weight:600;color:var(--ink);word-break:break-word}
.fv.big{font-size:11pt;font-weight:700}
.fv.mono{font-family:'SF Mono','Cascadia Code','Consolas',monospace;font-size:9pt;letter-spacing:.5px}
.fv.price{font-size:13pt;font-weight:900;color:var(--g700)}
.fv.accent{color:var(--g600)}

/* ── Stato Badge ── */
.stato-badge{
  display:inline-flex;align-items:center;gap:1.5mm;
  font-size:8pt;font-weight:700;text-transform:uppercase;letter-spacing:1px;
  padding:1.5mm 4mm;border-radius:20px;
}
.stato-attivo{background:var(--g100);color:var(--g700)}
.stato-usato{background:#fef3c7;color:#92400e}
.stato-scaduto{background:#fee2e2;color:#991b1b}

/* ── Divider ── */
.divider{
  border:none;margin:4mm 0;
  border-top:2px dashed var(--line);
}

/* ── Note bar ── */
.note-bar{
  background:var(--gold-bg);border:1.5px solid var(--gold-bdr);
  border-radius:8px;padding:3mm 4mm;
  text-align:center;margin-bottom:3mm;
}
.note-bar p{font-size:8pt;font-weight:500;color:#92400e;line-height:1.4;margin:0}
.note-bar strong{font-weight:700;color:#78350f}

/* ── Footer ── */
.card-footer{
  text-align:center;padding-top:2mm;
}
.card-footer .legal{font-size:5.5pt;color:#aaa;line-height:1.4;margin-bottom:2mm}
.card-footer .store{font-size:6pt;color:#bbb;letter-spacing:.3px}

/* ── Print ── */
@media print{
  body{background:#fff;padding:0}
  .toolbar{display:none!important}
  .card-page{
    width:100%;max-width:180mm;margin:0 auto;
    box-shadow:none;border-radius:0;
    -webkit-print-color-adjust:exact;print-color-adjust:exact;
  }
}
</style>
</head>
<body>

<div class="toolbar">
  <a href="visualizza_buoni.php" class="btn-back">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
    Indietro
  </a>
  <button class="btn-print" onclick="window.print()">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
    Stampa Buono
  </button>
  <div class="hint">La stampa si avvia automaticamente</div>
</div>

<div class="card-page">

  <!-- ▌HEADER ▌ -->
  <div class="hdr">
    <div class="hdr-top">
      <div class="hdr-brand">
        <img src="images/LOGO PNG2.png" class="hdr-logo" onerror="this.style.display='none'" alt="">
        <div class="brand-text">
          <div class="name">TS SERVICE</div>
          <div class="sub">Buono Regalo</div>
        </div>
      </div>
      <div class="hdr-badge"><?= $stato ?></div>
    </div>
    <div class="hdr-main">
      <div class="hdr-value">&euro; <?= $valore ?></div>
      <div class="hdr-qr"><div id="qrcode"></div></div>
    </div>
  </div>

  <!-- ▌CODE STRIP ▌ -->
  <div class="code-strip">
    <span class="lbl">Codice</span>
    <span class="val"><?= $codice ?></span>
  </div>

  <!-- ▌BODY ▌ -->
  <div class="card-body">

    <!-- Sezione: Dettagli -->
    <div class="sect">
      <div class="sh">
        <svg viewBox="0 0 24 24"><path d="M20 12v10H4V12"/><path d="M2 7h20v5H2z"/><path d="M12 22V7"/></svg>
        Dettagli Buono
      </div>
      <div class="grid grid-3">
        <div class="field">
          <div class="fl">Valore</div>
          <div class="fv price">&euro; <?= $valore ?></div>
        </div>
        <div class="field">
          <div class="fl">Data Emissione</div>
          <div class="fv"><?= $emissione ?></div>
        </div>
        <div class="field">
          <div class="fl">Valido Fino Al</div>
          <div class="fv"><?= $scadenza ?></div>
        </div>
      </div>
    </div>

    <!-- Sezione: Destinatario -->
    <div class="sect">
      <div class="sh">
        <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        Destinatario & Mittente
      </div>
      <div class="grid grid-2">
        <div class="field">
          <div class="fl">Per</div>
          <div class="fv big"><?= $destinatario ?></div>
        </div>
        <div class="field">
          <div class="fl">Da parte di</div>
          <div class="fv big"><?= $mittente ?></div>
        </div>
      </div>
    </div>

    <hr class="divider">

    <!-- Avviso utilizzo -->
    <div class="note-bar">
      <p>Presenta questo buono in negozio per utilizzarlo.<br>
      <strong>Grazie per aver scelto TS Service!</strong></p>
    </div>

    <!-- Footer -->
    <div class="card-footer">
      <div class="legal">Il buono non è rimborsabile, non è cumulabile con altre promozioni e non è convertibile in denaro. Valido fino alla data di scadenza indicata.</div>
      <div class="store">C.da Castromurro 217 · 87021 Belvedere M.mo (CS) · Tel. 342 033 0279</div>
    </div>

  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    new QRCode(document.getElementById("qrcode"), {
        text: "<?= $codice ?>",
        width: 100, height: 100,
        colorDark: "#14532d",
        colorLight: "#ffffff",
        correctLevel: QRCode.CorrectLevel.H
    });
    setTimeout(() => window.print(), 600);
    window.onafterprint = () => window.location.href = 'visualizza_buoni.php';
});
</script>

</body>
</html>

