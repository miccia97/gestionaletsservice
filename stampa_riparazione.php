<?php
// stampa_riparazione.php — V4 Super WOW da Negozio
include 'db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { echo "ID riparazione non valido."; exit; }

$sql = "SELECT r.*, c.nome, c.cognome, c.telefono, c.email
        FROM riparazioni AS r
        LEFT JOIN clienti_nuovo AS c ON r.cliente_id = c.id
        WHERE r.id = $id";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    $r = $result->fetch_assoc();
} else {
    echo "Riparazione #$id non trovata."; exit;
}
$conn->close();

$nome    = htmlspecialchars(trim(($r['nome'] ?? '') . ' ' . ($r['cognome'] ?? '')));
$tel     = htmlspecialchars($r['telefono'] ?? '');
$email   = htmlspecialchars($r['email'] ?? '');
$mod     = htmlspecialchars($r['modello'] ?? '');
$imei    = htmlspecialchars($r['imei'] ?? '');
$pin     = htmlspecialchars($r['codice_sblocco'] ?? '');
$pattern = htmlspecialchars($r['codice_sblocco_grafico'] ?? '');
$acct    = htmlspecialchars($r['account'] ?? '');
$backup  = ($r['salva_dati'] ?? 0) ? 'Sì' : 'No';
$diag    = htmlspecialchars($r['diagnosi'] ?? '');
$diag_br = nl2br($diag);
$cprev   = number_format($r['costo_preventivato'] ?? 0, 2, ',', '.');
$ceff    = number_format($r['costo_effettivo'] ?? 0, 2, ',', '.');
$hw      = htmlspecialchars($r['hardware_ritirato'] ?? 'Nessuno');
$dsost   = htmlspecialchars($r['dispositivo_sostitutivo'] ?? 'Nessuno');
$stato   = htmlspecialchars($r['stato'] ?? $r['stato_riparazione'] ?? 'In lavorazione');
$data    = !empty($r['data_creazione']) ? date('d/m/Y', strtotime($r['data_creazione'])) : date('d/m/Y');
$ora     = !empty($r['data_creazione']) ? date('H:i', strtotime($r['data_creazione'])) : date('H:i');

/* ─── Genera mezza scheda ─── */
function scheda($v, $tipo) { extract($v); $is_uff = ($tipo === 'UFFICIO'); ob_start(); ?>
<div class="half">

  <!-- ▌HEADER ▌ -->
  <div class="hdr">
    <div class="hdr-bg"></div>
    <div class="hdr-inner">
      <div class="hdr-left">
        <img src="images/LOGO PNG2.png" class="logo" onerror="this.style.display='none'" alt="">
        <div>
          <div class="brand">TS SERVICE</div>
          <div class="addr">C.da Castromurro 217 · 87021 Belvedere M.mo (CS) · Tel. 342 033 0279</div>
        </div>
      </div>
      <div class="hdr-right">
        <div class="id-num">#<?= $id ?></div>
        <div class="id-date"><?= $data ?> — <?= $ora ?></div>
      </div>
    </div>
  </div>

  <!-- ▌TIPO COPIA + STATO ▌ -->
  <div class="tag-row">
    <span class="tag-copy <?= $is_uff ? 'uf' : 'cl' ?>">COPIA <?= $tipo ?></span>
    <span class="tag-stato"><?= $stato ?></span>
  </div>

  <!-- ▌CONTENUTO ▌ -->
  <div class="body">

    <!-- Riga 1: Cliente + Dispositivo -->
    <div class="row2">
      <div class="sect">
        <div class="sh">CLIENTE</div>
        <div class="grid2">
          <div class="field"><div class="fl">Nome</div><div class="fv big"><?= $nome ?></div></div>
          <div class="field"><div class="fl">Telefono</div><div class="fv big"><?= $tel ?></div></div>
        </div>
      </div>
      <div class="sect">
        <div class="sh">DISPOSITIVO</div>
        <div class="grid2">
          <div class="field"><div class="fl">Modello</div><div class="fv big bold"><?= $mod ?></div></div>
          <div class="field"><div class="fl">IMEI / SN</div><div class="fv mono"><?= $imei ?: '—' ?></div></div>
        </div>
      </div>
    </div>

    <!-- Riga 2: Sblocco + Costi -->
    <div class="row2">
      <div class="sect">
        <div class="sh">CODICI DI SBLOCCO</div>
        <div class="grid4">
          <div class="field"><div class="fl">PIN</div><div class="fv mono"><?= $pin ?: '—' ?></div></div>
          <div class="field"><div class="fl">Pattern</div><div class="fv mono"><?= $pattern ?: '—' ?></div></div>
          <div class="field"><div class="fl">Account</div><div class="fv"><?= $acct ?: '—' ?></div></div>
          <div class="field"><div class="fl">Backup dati</div><div class="fv"><?= $backup ?></div></div>
        </div>
      </div>
      <div class="sect">
        <div class="sh">COSTI &amp; MATERIALE</div>
        <div class="grid2">
          <div class="field"><div class="fl">Preventivo</div><div class="fv price">&euro; <?= $cprev ?></div></div>
          <div class="field"><div class="fl">Effettivo</div><div class="fv price">&euro; <?= $ceff ?></div></div>
        </div>
        <div class="grid2" style="margin-top:0">
          <div class="field"><div class="fl">HW Ritirato</div><div class="fv sm"><?= $hw ?></div></div>
          <div class="field"><div class="fl">Disp. Sost.</div><div class="fv sm"><?= $dsost ?></div></div>
        </div>
      </div>
    </div>

    <!-- Riga 3: Diagnosi (piena larghezza) -->
    <div class="sect sect-diag">
      <div class="sh sh-warm">DIAGNOSI / NOTE</div>
      <div class="diag"><?= $diag_br ?: '<span style="color:#bbb">Nessuna diagnosi indicata.</span>' ?></div>
    </div>

    <!-- Firme -->
    <div class="firme">
      <div class="firma"><div class="firma-line"></div><span>Firma Cliente</span></div>
      <div class="firma"><div class="firma-line"></div><span>Firma Tecnico</span></div>
    </div>

  </div><!-- .body -->

  <div class="legal">Condizioni: il cliente accetta le condizioni di assistenza tecnica. Non si risponde per tempi e contenuti del dispositivo. Consenso GDPR – Reg. UE 2016/679. Reclami entro 7 gg lavorativi.</div>
</div>
<?php return ob_get_clean(); }

$v = compact('id','nome','tel','email','mod','imei','pin','pattern','acct','backup','diag_br','cprev','ceff','hw','dsost','stato','data','ora');
?><!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="icon" type="image/svg+xml" href="favicon.svg">
<title>Assistenza #<?= $id ?> — TS Service</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
/* =========================================================
   CSS VARIABLES
   ========================================================= */
:root{
  --g900:#0b2618;--g800:#14532d;--g700:#1b4332;--g600:#2d6a4f;
  --g500:#40916c;--g400:#52b788;--g300:#74c69d;--g200:#95d5b2;
  --g100:#d1fae5;--g50:#f0fdf4;
  --warm:#fffbeb;--warm-bdr:#fde68a;
  --ink:#111827;--mute:#6b7280;--line:#e5e7eb;--faint:#f9fafb;
}

/* =========================================================
   RESET
   ========================================================= */
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Inter',-apple-system,BlinkMacSystemFont,sans-serif;color:var(--ink);background:#ddd;line-height:1.3}
@page{size:A4 portrait;margin:5mm 6mm}

/* =========================================================
   SCREEN
   ========================================================= */
.wrap{width:210mm;margin:20px auto}
.toolbar{text-align:center;padding:16px 0}
.toolbar button{
  background:linear-gradient(135deg,var(--g700),var(--g500));
  color:#fff;border:none;padding:14px 44px;border-radius:14px;cursor:pointer;
  font:700 11pt/1 'Inter',sans-serif;letter-spacing:.4px;
  box-shadow:0 6px 24px rgba(27,67,50,.35);transition:.2s;
}
.toolbar button:hover{transform:translateY(-2px);box-shadow:0 8px 30px rgba(27,67,50,.45)}
.toolbar .sub{font-size:8pt;color:#aaa;margin-top:8px}

/* =========================================================
   A4 PAGE
   ========================================================= */
.a4,.a4b{
  width:210mm;min-height:297mm;background:#fff;
  box-shadow:0 4px 40px rgba(0,0,0,.1);
  display:flex;flex-direction:column;padding:5mm 6mm;
}
.a4b{margin-top:20px}

/* =========================================================
   HALF — each copy = exactly half of printable area
   Height: (297mm - 10mm page padding - 7mm tear) / 2 = 140mm
   ========================================================= */
.half{
  height:140mm;
  display:flex;flex-direction:column;
  overflow:hidden;
}

/* ─── HEADER ─── */
.hdr{position:relative;border-radius:6px 6px 0 0;overflow:hidden}
.hdr-bg{
  position:absolute;inset:0;
  background:linear-gradient(135deg,var(--g800) 0%,var(--g600) 55%,var(--g400) 100%);
}
.hdr-bg::after{
  content:'';position:absolute;right:-10mm;top:-12mm;
  width:55mm;height:55mm;border-radius:50%;
  background:rgba(255,255,255,.06);
}
.hdr-inner{
  position:relative;z-index:1;
  display:flex;justify-content:space-between;align-items:center;
  padding:3mm 4mm;color:#fff;
}
.hdr-left{display:flex;align-items:center;gap:3mm}
.logo{height:28mm;width:auto;filter:brightness(0) invert(1);opacity:.9}
.brand{font-size:14pt;font-weight:900;letter-spacing:2px;line-height:1}
.addr{font-size:5.5pt;opacity:.6;margin-top:1mm;line-height:1.3}
.hdr-right{text-align:right}
.id-num{font-size:26pt;font-weight:900;letter-spacing:2px;line-height:1}
.id-date{font-size:6.5pt;opacity:.55;margin-top:.5mm}

/* ─── TAG ROW ─── */
.tag-row{
  display:flex;justify-content:space-between;align-items:center;
  padding:1.5mm 4mm;
  background:var(--g50);
  border:1px solid var(--g100);
  border-top:none;
  border-radius:0 0 6px 6px;
  margin-bottom:2mm;
}
.tag-copy{
  font-size:8pt;font-weight:800;letter-spacing:2px;
  text-transform:uppercase;
}
.tag-copy.cl{color:var(--g600)}
.tag-copy.uf{color:var(--g800)}
.tag-stato{
  font-size:6.5pt;font-weight:700;text-transform:uppercase;letter-spacing:1px;
  background:var(--g700);color:#fff;
  padding:1mm 3.5mm;border-radius:20px;
}

/* ─── BODY ─── */
.body{flex:1;display:flex;flex-direction:column;gap:1.5mm;min-height:0}

/* 2-col row */
.row2{display:flex;gap:2.5mm}
.row2 > .sect{flex:1}

/* Section */
.sect{
  border:1px solid var(--line);
  border-radius:5px;
  overflow:hidden;
}
.sh{
  padding:1mm 3mm;
  font-size:6pt;font-weight:800;letter-spacing:1.5px;
  color:var(--g700);text-transform:uppercase;
  background:var(--faint);
  border-bottom:1px solid var(--line);
}
.sh-warm{background:var(--warm);border-bottom-color:var(--warm-bdr);color:#92400e}

/* Field grid inside sections */
.grid2{display:grid;grid-template-columns:1fr 1fr;gap:0}
.grid4{display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:0}
.field{padding:1.2mm 3mm;border-bottom:1px solid #f5f5f5}
.grid2 .field:nth-child(odd){border-right:1px solid #f0f0f0}
.grid4 .field:not(:last-child){border-right:1px solid #f0f0f0}

/* Label above, value below — LARGE AND CLEAR */
.fl{font-size:5pt;font-weight:600;color:var(--mute);text-transform:uppercase;letter-spacing:.8px;margin-bottom:.3mm}
.fv{font-size:8.5pt;font-weight:600;color:var(--ink);line-height:1.3;word-break:break-word}
.fv.big{font-size:10pt;font-weight:700}
.fv.bold{font-weight:800}
.fv.mono{font-family:'SF Mono','Cascadia Code','Consolas',monospace;font-size:8pt;letter-spacing:.5px}
.fv.price{font-size:11pt;font-weight:900;color:var(--g700)}
.fv.sm{font-size:7.5pt;font-weight:500}

/* Diagnosi full-width */
.sect-diag{flex:1;display:flex;flex-direction:column;min-height:0}
.diag{
  flex:1;padding:1.5mm 3mm;
  font-size:8pt;font-weight:500;color:#333;line-height:1.45;
  overflow:hidden;
}

/* Firme */
.firme{display:flex;gap:4mm;margin-top:auto;padding-top:1mm;flex-shrink:0}
.firma{flex:1;text-align:center}
.firma-line{height:8mm;border-bottom:1.5px solid #333}
.firma span{font-size:5.5pt;color:#999;letter-spacing:.4px;display:block;margin-top:.5mm}

/* Legal */
.legal{
  font-size:4pt;color:#ccc;line-height:1.25;
  border-top:1px solid #f0f0f0;padding-top:.5mm;margin-top:auto;
  flex-shrink:0;
}

/* =========================================================
   TEAR LINE
   ========================================================= */
.tear{
  height:7mm;display:flex;align-items:center;
  position:relative;flex-shrink:0;
}
.tear::before{
  content:'';position:absolute;
  left:-6mm;right:-6mm;top:50%;
  border-top:1.5px dashed #ccc;
}
.tear-c{
  position:relative;z-index:1;
  background:#fff;margin:0 auto;
  padding:0 4mm;display:flex;align-items:center;gap:2mm;
  font-size:5.5pt;color:#ccc;letter-spacing:1px;font-weight:600;
}
.tear-c svg{width:4mm;height:4mm;fill:none;stroke:#bbb;stroke-width:1.5}

/* =========================================================
   PAGE 2: BACK — SCHEDA TECNICO
   ========================================================= */
.bk-void{height:144mm}
.bk-body{
  flex:1;display:flex;flex-direction:column;
  padding:0;position:relative;
}

/* Back header */
.bk-hdr{
  background:linear-gradient(135deg,var(--g800) 0%,var(--g600) 55%,var(--g400) 100%);
  color:#fff;display:flex;justify-content:space-between;align-items:center;
  padding:4mm 5mm;border-radius:6px 6px 0 0;
  position:relative;overflow:hidden;
}
.bk-hdr::after{
  content:'';position:absolute;right:-12mm;top:-15mm;
  width:60mm;height:60mm;border-radius:50%;background:rgba(255,255,255,.05);
}
.bk-hdr-left{display:flex;align-items:center;gap:3mm}
.bk-logo{height:18mm;width:auto;filter:brightness(0) invert(1);opacity:.9}
.bk-hdr-info{}
.bk-hdr-brand{font-size:16pt;font-weight:900;letter-spacing:2px}
.bk-hdr-sub{font-size:6pt;opacity:.5;letter-spacing:1px;text-transform:uppercase;margin-top:.5mm}
.bk-hdr-right{text-align:right}
.bk-hdr-id{font-size:32pt;font-weight:900;letter-spacing:3px;line-height:1}
.bk-hdr-date{font-size:7pt;opacity:.5;margin-top:1mm}

/* Back tag */
.bk-tag{
  background:var(--g900);color:var(--g400);
  text-align:center;padding:1.5mm;
  font-size:8pt;font-weight:800;letter-spacing:3px;text-transform:uppercase;
}

/* Back content grid */
.bk-content{flex:1;display:flex;flex-direction:column;gap:3mm;padding:3mm 0}

/* Two-column rows */
.bk-row{display:flex;gap:3mm}
.bk-row > .bk-sect{flex:1}

/* Section */
.bk-sect{
  border:2px solid var(--line);border-radius:8px;
  overflow:hidden;
}
.bk-sh{
  padding:2mm 4mm;
  font-size:7pt;font-weight:800;letter-spacing:2px;text-transform:uppercase;
  color:var(--g700);background:var(--faint);
  border-bottom:2px solid var(--line);
}
.bk-sh-accent{background:var(--g100);border-bottom-color:var(--g200);color:var(--g800)}
.bk-sh-warm{background:var(--warm);border-bottom-color:var(--warm-bdr);color:#92400e}

/* Back fields */
.bk-grid{display:grid;gap:0}
.bk-grid-2{grid-template-columns:1fr 1fr}
.bk-grid-4{grid-template-columns:1fr 1fr 1fr 1fr}
.bk-field{padding:2.5mm 4mm;border-bottom:1px solid #f0f0f0}
.bk-grid-2 .bk-field:nth-child(odd){border-right:1px solid #f0f0f0}
.bk-grid-4 .bk-field:not(:last-child){border-right:1px solid #f0f0f0}
.bk-fl{font-size:6pt;font-weight:600;color:var(--mute);text-transform:uppercase;letter-spacing:1px;margin-bottom:.5mm}
.bk-fv{font-size:12pt;font-weight:700;color:var(--ink);line-height:1.3;word-break:break-word}
.bk-fv.huge{font-size:16pt;font-weight:900}
.bk-fv.code{font-family:'SF Mono','Cascadia Code','Consolas',monospace;font-size:14pt;font-weight:800;letter-spacing:1px;color:var(--g800)}
.bk-fv.price{font-size:14pt;font-weight:900;color:var(--g700)}

/* Back diagnosi */
.bk-diag-box{flex:1;display:flex;flex-direction:column;min-height:0}
.bk-diag-body{
  flex:1;padding:3mm 4mm;
  font-size:11pt;font-weight:500;color:#333;line-height:1.5;
  overflow:hidden;
}

/* Back footer */
.bk-foot{
  font-size:5pt;color:#ccc;text-align:center;
  border-top:1px solid #f0f0f0;padding-top:1mm;margin-top:auto;
}

/* =========================================================
   PRINT
   ========================================================= */
@media print{
  body{background:none!important}
  .wrap{width:auto;margin:0}
  .toolbar{display:none!important}
  .a4,.a4b{box-shadow:none;margin:0;min-height:0}
  .a4{page-break-after:always}
  .a4b{page-break-after:auto}
  .half{height:140mm}
  .bk-void{height:144mm}
  .tear::before{border-color:#999}
  /* Force all backgrounds to print */
  .hdr-bg,.hdr-bg::after,.tag-row,.tag-stato,.tag-copy,
  .sh,.sh-warm,.diag,.field,
  .bk-hdr,.bk-hdr::after,.bk-tag,.bk-sh,.bk-sh-accent,.bk-sh-warm,.bk-field,.bk-diag-body{
    -webkit-print-color-adjust:exact!important;
    print-color-adjust:exact!important;
  }
  .logo,.bk-logo{
    -webkit-print-color-adjust:exact!important;
    print-color-adjust:exact!important;
  }
}
</style>
</head>
<body>

<div class="wrap">
  <div class="toolbar">
    <button onclick="window.print()">&#128424; Stampa Scheda Assistenza</button>
    <div class="sub">Stampa fronte/retro per il sommario sul retro della copia ufficio</div>
  </div>

  <!-- ═══════ PAGINA 1 — FRONTE ═══════ -->
  <div class="a4">
    <?= scheda($v, 'CLIENTE') ?>

    <div class="tear">
      <div class="tear-c">
        <svg viewBox="0 0 24 24"><circle cx="6" cy="12" r="3"/><path d="M6 15l12 6M6 9l12-6"/></svg>
        TAGLIARE QUI
        <svg viewBox="0 0 24 24"><circle cx="6" cy="12" r="3"/><path d="M6 15l12 6M6 9l12-6"/></svg>
      </div>
    </div>

    <?= scheda($v, 'UFFICIO') ?>
  </div>

  <!-- ═══════ PAGINA 2 — RETRO: SCHEDA TECNICO ═══════ -->
  <div class="a4b">
    <div class="bk-void"></div>
    <div class="bk-body">

      <!-- Header tecnico -->
      <div class="bk-hdr">
        <div class="bk-hdr-left">
          <img src="images/LOGO PNG2.png" class="bk-logo" onerror="this.style.display='none'" alt="">
          <div class="bk-hdr-info">
            <div class="bk-hdr-brand">TS SERVICE</div>
            <div class="bk-hdr-sub">Assistenza Tecnica Professionale</div>
          </div>
        </div>
        <div class="bk-hdr-right">
          <div class="bk-hdr-id">#<?= $id ?></div>
          <div class="bk-hdr-date"><?= $data ?> — <?= $ora ?></div>
        </div>
      </div>
      <div class="bk-tag">&#128736; Scheda Tecnico — Uso Interno</div>

      <div class="bk-content">

        <!-- Riga 1: Cliente + Dispositivo -->
        <div class="bk-row">
          <div class="bk-sect">
            <div class="bk-sh">Cliente</div>
            <div class="bk-grid bk-grid-2">
              <div class="bk-field"><div class="bk-fl">Nome</div><div class="bk-fv huge"><?= $nome ?></div></div>
              <div class="bk-field"><div class="bk-fl">Telefono</div><div class="bk-fv huge"><?= $tel ?></div></div>
            </div>
          </div>
          <div class="bk-sect">
            <div class="bk-sh">Dispositivo</div>
            <div class="bk-grid bk-grid-2">
              <div class="bk-field"><div class="bk-fl">Modello</div><div class="bk-fv huge"><?= $mod ?></div></div>
              <div class="bk-field"><div class="bk-fl">IMEI / SN</div><div class="bk-fv code"><?= $imei ?: '—' ?></div></div>
            </div>
          </div>
        </div>

        <!-- Riga 2: Codici Sblocco (evidenziata!) -->
        <div class="bk-sect">
          <div class="bk-sh bk-sh-accent">&#128274; Codici di Sblocco</div>
          <div class="bk-grid bk-grid-4">
            <div class="bk-field"><div class="bk-fl">PIN</div><div class="bk-fv code"><?= $pin ?: '—' ?></div></div>
            <div class="bk-field"><div class="bk-fl">Pattern</div><div class="bk-fv code"><?= $pattern ?: '—' ?></div></div>
            <div class="bk-field"><div class="bk-fl">Account</div><div class="bk-fv"><?= $acct ?: '—' ?></div></div>
            <div class="bk-field"><div class="bk-fl">Backup Dati</div><div class="bk-fv"><?= $backup ?></div></div>
          </div>
        </div>

        <!-- Riga 3: Costi + Materiale -->
        <div class="bk-row">
          <div class="bk-sect">
            <div class="bk-sh">Costi</div>
            <div class="bk-grid bk-grid-2">
              <div class="bk-field"><div class="bk-fl">Preventivo</div><div class="bk-fv price">&euro; <?= $cprev ?></div></div>
              <div class="bk-field"><div class="bk-fl">Effettivo</div><div class="bk-fv price">&euro; <?= $ceff ?></div></div>
            </div>
          </div>
          <div class="bk-sect">
            <div class="bk-sh">Materiale</div>
            <div class="bk-grid bk-grid-2">
              <div class="bk-field"><div class="bk-fl">HW Ritirato</div><div class="bk-fv"><?= $hw ?></div></div>
              <div class="bk-field"><div class="bk-fl">Disp. Sostitutivo</div><div class="bk-fv"><?= $dsost ?></div></div>
            </div>
          </div>
        </div>

        <!-- Riga 4: Diagnosi (grande, piena larghezza) -->
        <div class="bk-sect bk-diag-box">
          <div class="bk-sh bk-sh-warm">Diagnosi / Note</div>
          <div class="bk-diag-body"><?= $diag_br ?: '<span style="color:#bbb">Nessuna diagnosi indicata.</span>' ?></div>
        </div>

      </div><!-- .bk-content -->

      <div class="bk-foot">RETRO COPIA UFFICIO &mdash; Scheda ad uso interno del tecnico</div>
    </div>
  </div>

</div>

<script>window.onload=function(){window.print()};</script>
</body>
</html>