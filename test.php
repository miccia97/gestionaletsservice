<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<link rel="icon" type="image/svg+xml" href="favicon.svg">
  <title>Gestione Cliente e Buono Spesa</title>
<style>
/* Stile originale dai blocchi inviati */
body {
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  background-color: #f7f9fa;
  margin: 20px;
  color: #222;
}
.barra-verde {
  background-color: #117a37;
  color: white;
  font-weight: 700;
  font-size: 1.3rem;
  padding: 12px 25px;
  border-radius: 6px;
  user-select: none;
  margin-bottom: 20px;
}
label {
  font-weight: 600;
  margin-bottom: 6px;
  display: block;
  color: #444;
}
input[type=text], input[type=email], input[type=tel], input[type=number], input[type=date] {
  width: 100%;
  padding: 10px 14px;
  font-size: 1rem;
  border: 1.8px solid #117a37;
  border-radius: 10px;
  outline: none;
  box-sizing: border-box;
  transition: border-color 0.3s ease;
  color: #333;
}
input[type=text]:focus, input[type=email]:focus, input[type=tel]:focus, input[type=number]:focus, input[type=date]:focus {
  border-color: #0e5c26;
  box-shadow: 0 0 8px #0e5c26aa;
}
button, .submit-btn {
  background-color: #117a37;
  color: white;
  border: none;
  border-radius: 20px;
  padding: 12px 26px;
  font-weight: 700;
  font-size: 1.1rem;
  cursor: pointer;
  margin-top: 15px;
  transition: background-color 0.3s ease;
}
button:hover, .submit-btn:hover {
  background-color: #0e5c26;
}

/* Modal generale */
.modal {
  display: none;
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.5);
  justify-content: center;
  align-items: center;
  z-index: 9999;
}
.modal.active {
  display: flex;
}
.modal-content {
  background: white;
  padding: 28px 35px;
  border-radius: 15px;
  box-shadow: 0 2px 12px rgb(0 0 0 / 0.18);
  width: 440px;
  max-width: 90vw;
  position: relative;
  font-size: 1rem;
}
.close-btn {
  position: absolute;
  top: 14px;
  right: 18px;
  font-size: 26px;
  background: none;
  border: none;
  cursor: pointer;
  color: #666;
  transition: color 0.3s ease;
}
.close-btn:hover {
  color: #117a37;
}
.modal-header {
  font-weight: 800;
  font-size: 1.5rem;
  margin-bottom: 20px;
  color: #117a37;
}
/* Tab switcher */
.tab-switcher {
  display: flex;
  border-bottom: 2.5px solid #eee;
  margin-bottom: 18px;
}
.tab-btn {
  flex: 1;
  padding: 12px 0;
  border: none;
  background: none;
  font-weight: 700;
  font-size: 1.1rem;
  cursor: pointer;
  color: #117a37aa;
  border-bottom: 3.5px solid transparent;
  transition: border-color 0.3s ease, color 0.3s ease;
}
.tab-btn.active {
  color: #117a37;
  border-bottom-color: #117a37;
}
.tab-content {
  display: none;
}
.tab-content.active {
  display: block;
}
/* Dropdown risultati */
#clienteRisultati, #buonoRisultati {
  position: absolute;
  background: #fff;
  border: 1.8px solid #117a37;
  border-radius: 0 0 15px 15px;
  max-height: 180px;
  overflow-y: auto;
  width: 100%;
  box-sizing: border-box;
  display: none;
  font-size: 1rem;
  z-index: 10000;
}
#clienteRisultati div, #buonoRisultati div {
  padding: 10px 16px;
  cursor: pointer;
  border-bottom: 1px solid #eee;
  color: #117a37dd;
  font-weight: 600;
}
#clienteRisultati div:last-child, #buonoRisultati div:last-child {
  border-bottom: none;
}
#clienteRisultati div:hover, #buonoRisultati div:hover {
  background-color: #117a37cc;
  color: white;
}

/* Residuo */
#residuo {
  margin-top: 10px;
  font-weight: 700;
  font-size: 1.15rem;
}
#totale-vendita {
  font-weight: 700;
  font-size: 1.2rem;
  color: #117a37;
}

/* Tabella carrello */
table {
  border-collapse: collapse;
  width: 100%;
  margin-top: 22px;
  font-size: 1rem;
}
table, th, td {
  border: 1.5px solid #117a37;
}
th, td {
  padding: 12px 18px;
  text-align: center;
}
th {
  background-color: #e5f1e7;
  color: #117a37;
  font-weight: 700;
}
td input {
  width: 80px;
  border-radius: 10px;
  border: 1.8px solid #117a37;
  padding: 6px 8px;
  font-size: 1rem;
  text-align: right;
  outline: none;
  transition: border-color 0.3s ease;
}
td input:focus {
  border-color: #0e5c26;
  box-shadow: 0 0 8px #0e5c26aa;
}

/* Pagamenti */
.pagamento-wrapper {
  margin-top: 18px;
  font-weight: 600;
  color: #117a37dd;
}
.pagamento-wrapper label {
  margin-right: 40px;
}
.pagamento-wrapper input {
  width: 130px;
  font-weight: 700;
  font-size: 1rem;
  border: 1.8px solid #117a37;
  border-radius: 15px;
  padding: 8px 12px;
  color: #117a37cc;
}
.pagamento-wrapper input:focus {
  border-color: #0e5c26;
  box-shadow: 0 0 8px #0e5c26aa;
}

/* Totali */
.totali-wrapper {
  margin-top: 18px;
  color: #117a37;
  font-weight: 700;
  font-size: 1.1rem;
  line-height: 1.6em;
}

/* Pulsanti piccoli */
button.small {
  padding: 8px 16px;
  font-size: 1rem;
  border-radius: 15px;
  margin-top: 10px;
}
</style>
</head>
<body>

<div class="barra-verde">Gestione Cliente e Buono Spesa - TSService</div>

<!-- CLIENTE -->
<div style="position: relative; max-width: 350px;">
  <label for="clienteInput">Cliente</label>
  <input
    type="text"
    id="clienteInput"
    name="nome_cliente"
    placeholder="Seleziona o aggiungi cliente"
    autocomplete="off"
  />
  <input type="hidden" id="idCliente" name="id_cliente" />
  <div id="clienteRisultati"></div>
</div>
<button class="small" type="button" onclick="openModalCliente()">Nuovo Cliente</button>

<!-- BUONO SPESA -->
<div style="position: relative; max-width: 350px; margin-top: 24px;">
  <label for="buono-spesa">Buono Spesa</label>
  <input
    type="text"
    id="buono-spesa"
    placeholder="Seleziona o aggiungi buono spesa"
    autocomplete="off"
  />
  <input type="hidden" id="idBuono" />
  <div id="buonoRisultati"></div>
</div>
<button id="btnApriBuono" class="small" type="button">Nuovo Buono Spesa</button>

<!-- MODALE CLIENTE -->
<div id="modalCliente" class="modal" role="dialog" aria-modal="true" aria-labelledby="modalClienteTitle">
  <div class="modal-content">
    <button class="close-btn" aria-label="Chiudi" onclick="closeModalCliente()">&times;</button>
    <div class="modal-header" id="modalClienteTitle">Aggiungi Cliente</div>
    <div class="tab-switcher">
      <button class="tab-btn active" data-tab="persona" type="button">Persona</button>
      <button class="tab-btn" data-tab="azienda" type="button">Azienda</button>
    </div>

    <form id="formCliente" onsubmit="return submitCliente(event)" novalidate>
      <div class="tab-content active" data-tab="persona">
        <label for="cognome">Cognome</label>
        <input type="text" id="cognome" name="cognome" autocomplete="family-name" />
        <label for="nome">Nome</label>
        <input type="text" id="nome" name="nome" autocomplete="given-name" />
        <label for="email">Email</label>
        <input type="email" id="email" name="email" autocomplete="email" />
        <label for="telefono">Telefono</label>
        <input type="tel" id="telefono" name="telefono" autocomplete="tel" />
      </div>
      <div class="tab-content" data-tab="azienda">
        <label for="partitaIva">Partita IVA</label>
        <input type="text" id="partitaIva" name="partitaIva" />
        <label for="ragioneSociale">Ragione Sociale</label>
        <input type="text" id="ragioneSociale" name="ragioneSociale" />
        <label for="emailAzienda">Email Azienda</label>
        <input type="email" id="emailAzienda" name="emailAzienda" />
        <label for="telefonoAzienda">Telefono Azienda</label>
        <input type="tel" id="telefonoAzienda" name="telefonoAzienda" />
      </div>
      <button type="submit" class="submit-btn">Salva Cliente</button>
    </form>
  </div>
</div>

<!-- MODALE BUONO SPESA -->
<div id="modalBuono" class="modal" role="dialog" aria-modal="true" aria-labelledby="modalBuonoTitle">
  <div class="modal-content" style="width: 400px;">
    <button id="btnChiudiBuono" class="close-btn" aria-label="Chiudi">&times;</button>
    <div class="modal-header" id="modalBuonoTitle">Nuovo Buono Spesa</div>
    <form id="formBuonoSpesa" novalidate>
      <label for="codiceBuono">Codice Buono</label>
      <input type="text" id="codiceBuono" name="codiceBuono" required />
      <label for="importoBuono">Importo (€)</label>
      <input type="number" id="importoBuono" name="importoBuono" min="0.01" step="0.01" required />
      <label for="dataScadenza">Data Scadenza</label>
      <input type="date" id="dataScadenza" name="dataScadenza" required />
      <button type="submit" class="submit-btn">Salva Buono</button>
    </form>
  </div>
</div>

<!-- FORM VENDITA / CARRELLO DEMO -->
<h2>Carrello Demo</h2>
<form id="form-vendita" action="#" method="POST" style="max-width: 650px;">
  <table>
    <thead>
      <tr>
        <th>Prodotto</th>
        <th>Quantità</th>
        <th>Prezzo Unitario (€)</th>
        <th>Prezzo Scontato (€)</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td>Telefono XYZ <input type="hidden" name="prodotti[0][id]" value="101" /></td>
        <td>2</td>
        <td><input type="text" class="prezzo-vendita" data-qty="2" value="299,99" /></td>
        <td><input type="text" name="prodotti[0][prezzo_scontato]" value="279,99" /></td>
      </tr>
      <tr>
        <td>Action Figure ABC <input type="hidden" name="prodotti[1][id]" value="102" /></td>
        <td>1</td>
        <td><input type="text" class="prezzo-vendita" data-qty="1" value="59,99" /></td>
        <td><input type="text" name="prodotti[1][prezzo_scontato]" value="49,99" /></td>
      </tr>
    </tbody>
  </table>

  <div class="pagamento-wrapper">
    <label>Pagamento 1 (€): <input type="text" id="pagamento1" value="0" /></label>
    <label>Pagamento 2 (€): <input type="text" id="pagamento2" value="0" /></label>
  </div>

  <div class="totali-wrapper">
    <div><strong>Totale Quantità:</strong> <span id="totale-quantita">0</span></div>
    <div><strong>Totale Articoli:</strong> <span id="totale-articoli">0</span></div>
    <div><strong>Imponibile:</strong> € <span id="imponibile">0,00</span></div>
    <div><strong>Totale Vendita:</strong> <span id="totale-vendita">€ 0,00</span></div>
  </div>

  <div id="residuo"></div>

  <!-- Campi nascosti -->
  <input type="hidden" id="input_carrello" name="carrello_json" />
  <input type="hidden" id="input_pagamento1" name="pagamento1" />
  <input type="hidden" id="input_pagamento2" name="pagamento2" />
  <input type="hidden" id="input_residuo" name="residuo" />
  <input type="hidden" id="input_nome_cliente" name="nome_cliente" />

  <button type="submit" class="submit-btn">Completa Vendita</button>
</form>

<script>
document.addEventListener("DOMContentLoaded", function() {
  // CLIENTE AUTOCOMPLETE
  const clienteInput = document.getElementById('clienteInput');
  const clienteRisultati = document.getElementById('clienteRisultati');
  const idCliente = document.getElementById('idCliente');

  clienteInput.addEventListener('input', function() {
    const query = this.value.trim().toLowerCase();
    if (query.length < 2) {
      clienteRisultati.style.display = 'none';
      clienteRisultati.innerHTML = '';
      idCliente.value = '';
      return;
    }
    // Simulazione fetch clienti
    const clientiFake = [
      {id: 1, nome: "Mario", cognome: "Rossi"},
      {id: 2, nome: "Maria", cognome: "Bianchi"},
      {id: 3, nome: "Luca", cognome: "Verdi"},
      {id: 4, nome: "Anna", cognome: "Neri"}
    ];
    const risultati = clientiFake.filter(c => (c.nome + " " + c.cognome).toLowerCase().includes(query));
    clienteRisultati.innerHTML = '';
    if (risultati.length === 0) {
      clienteRisultati.style.display = 'none';
      idCliente.value = '';
      return;
    }
    clienteRisultati.style.display = 'block';
    risultati.forEach(c => {
      const div = document.createElement('div');
      div.textContent = c.nome + ' ' + c.cognome;
      div.addEventListener('click', () => {
        clienteInput.value = c.nome + ' ' + c.cognome;
        idCliente.value = c.id;
        clienteRisultati.style.display = 'none';
      });
      clienteRisultati.appendChild(div);
    });
  });
  document.addEventListener('click', e => {
    if (!clienteRisultati.contains(e.target) && e.target !== clienteInput) {
      clienteRisultati.style.display = 'none';
    }
  });

  // BUONO SPESA AUTOCOMPLETE
  const buonoInput = document.getElementById("buono-spesa");
  const buonoRisultati = document.getElementById("buonoRisultati");
  const idBuono = document.getElementById("idBuono");

  buonoRisultati.style.display = "none";

  buonoInput.addEventListener("input", function() {
    const query = this.value.trim();

    if (query.length === 0) {
      buonoRisultati.innerHTML = "";
      buonoRisultati.style.display = "none";
      idBuono.value = "";
      return;
    }

    // Demo dati finti buoni
    const buoniFake = [
      {id: 11, codice_buono: "SCONTO10", valore: 10},
      {id: 12, codice_buono: "ESTATE22", valore: 25},
      {id: 13, codice_buono: "PROMO5", valore: 5}
    ];

    const risultati = buoniFake.filter(b => b.codice_buono.toLowerCase().includes(query.toLowerCase()));

    buonoRisultati.innerHTML = "";
    if (risultati.length === 0) {
      buonoRisultati.style.display = "none";
      idBuono.value = "";
      return;
    }
    buonoRisultati.style.display = "block";

    risultati.forEach(buono => {
      const div = document.createElement("div");
      div.textContent = `${buono.codice_buono} - Valore: €${buono.valore}`;
      div.style.cursor = "pointer";
      div.style.padding = "6px 10px";
      div.style.borderBottom = "1px solid #ddd";

      div.addEventListener("click", () => {
        buonoInput.value = buono.codice_buono;
        idBuono.value = buono.id;
        buonoRisultati.innerHTML = "";
        buonoRisultati.style.display = "none";
      });

      buonoRisultati.appendChild(div);
    });
  });
  document.addEventListener('click', e => {
    if (!buonoRisultati.contains(e.target) && e.target !== buonoInput) {
      buonoRisultati.style.display = 'none';
    }
  });

  // MODAL CLIENTE
  window.openModalCliente = function() {
    document.getElementById("modalCliente").classList.add("active");
    document.getElementById("cognome").focus();
  };
  window.closeModalCliente = function() {
    document.getElementById("modalCliente").classList.remove("active");
  };
  document.addEventListener("keydown", function(e) {
    if (e.key === "Escape") closeModalCliente();
  });

  // Cambio tab modal cliente
  document.querySelectorAll(".tab-btn").forEach(button => {
    button.addEventListener("click", () => {
      const tab = button.getAttribute("data-tab");
      document.querySelectorAll(".tab-btn").forEach(btn => btn.classList.remove("active"));
      button.classList.add("active");
      document.querySelectorAll(".tab-content").forEach(content => {
        content.classList.remove("active");
        if (content.getAttribute("data-tab") === tab) {
          content.classList.add("active");
          const firstInput = content.querySelector("input,textarea");
          if (firstInput) firstInput.focus();
        }
      });
    });
  });

  // Submit form cliente
  window.submitCliente = function(event) {
    event.preventDefault();
    const personaActive = document.querySelector(".tab-content.active").getAttribute("data-tab") === "persona";

    if (personaActive) {
      const cognome = document.getElementById("cognome").value.trim();
      const nome = document.getElementById("nome").value.trim();
      if (!cognome || !nome) {
        alert("Compila cognome e nome");
        return false;
      }
      alert(`Aggiunto cliente persona: ${cognome} ${nome}`);
      clienteInput.value = cognome + " " + nome;
      idCliente.value = ""; // nuovo cliente non ancora nel DB
    } else {
      const pIva = document.getElementById("partitaIva").value.trim();
      const ragione = document.getElementById("ragioneSociale").value.trim();
      if (!pIva || !ragione) {
        alert("Compila partita IVA e ragione sociale");
        return false;
      }
      alert(`Aggiunta azienda: ${ragione}`);
      clienteInput.value = ragione;
      idCliente.value = ""; // nuovo cliente non ancora nel DB
    }
    closeModalCliente();
    return false;
  };

  // MODAL BUONO SPESA
  const modalBuono = document.getElementById("modalBuono");
  const btnApriBuono = document.getElementById("btnApriBuono");
  const btnChiudiBuono = document.getElementById("btnChiudiBuono");

  if (btnApriBuono && modalBuono) {
    btnApriBuono.addEventListener("click", () => {
      modalBuono.classList.add("active");
    });
  }
  if (btnChiudiBuono && modalBuono) {
    btnChiudiBuono.addEventListener("click", () => {
      modalBuono.classList.remove("active");
    });
  }
  modalBuono?.addEventListener("click", (e) => {
    if (e.target === modalBuono) modalBuono.classList.remove("active");
  });

  // Submit Buono Spesa demo
  document.getElementById('formBuonoSpesa')?.addEventListener('submit', function(event) {
    event.preventDefault();
    const codice = document.getElementById('codiceBuono').value.trim();
    const importo = document.getElementById('importoBuono').value.trim();
    const scadenza = document.getElementById('dataScadenza').value;

    if(!codice || !importo || !scadenza) {
      alert('Compila tutti i campi');
      return;
    }

    alert('Buono spesa creato con successo!');
    modalBuono.classList.remove("active");
    this.reset();
  });

  // Calcoli totali e residuo
  function aggiornaTotali() {
    let totaleQuantita = 0;
    let totaleArticoli = 0;
    let totaleVendita = 0;

    document.querySelectorAll('.prezzo-vendita').forEach(input => {
      const qty = parseInt(input.dataset.qty) || 0;
      const prezzo = parseFloat(input.value.replace(',', '.')) || 0;

      totaleQuantita += qty;
      totaleArticoli += 1;
      totaleVendita += qty * prezzo;
    });

    const imponibile = totaleVendita / 1.22;

    // Simula buono sconto (esempio 0)
    let buono = 0;

    const pagamento1 = parseFloat(document.getElementById('pagamento1')?.value.replace(',', '.') || 0);
    const pagamento2 = parseFloat(document.getElementById('pagamento2')?.value.replace(',', '.') || 0);
    const totalePagato = pagamento1 + pagamento2;

    const totaleDaPagare = totaleVendita - buono;

    let residuo = totaleDaPagare - totalePagato;

    const residuoSpan = document.getElementById('residuo');
    const totaleDaPagareSpan = document.getElementById('totale-vendita');

    // Totali visualizzati
    document.getElementById('totale-quantita').textContent = totaleQuantita;
    document.getElementById('totale-articoli').textContent = totaleArticoli.toFixed(0);
    document.getElementById('imponibile').textContent = imponibile.toFixed(2).replace('.', ',');

    totaleDaPagareSpan.textContent = '€ ' + totaleDaPagare.toFixed(2).replace('.', ',');

    if (residuo < 0) {
      residuoSpan.style.color = '#d9534f'; // rosso soft bootstrap
      residuoSpan.style.fontWeight = '600';
      residuoSpan.style.fontSize = '1.1em';
      residuoSpan.textContent = `Resto da dare: € ${Math.abs(residuo).toFixed(2).replace('.', ',')}`;
    } else {
      residuoSpan.style.color = '#212529'; // colore testo scuro standard
      residuoSpan.style.fontWeight = '500';
      residuoSpan.style.fontSize = '1em';
      residuoSpan.textContent = `Residuo da pagare: € ${residuo.toFixed(2).replace('.', ',')}`;
    }
  }

  // Eventi input per ricalcolo
  aggiornaTotali();

  document.querySelectorAll('.prezzo-vendita').forEach(input => {
    input.addEventListener('input', aggiornaTotali);
  });
  ['pagamento1', 'pagamento2'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.addEventListener('input', aggiornaTotali);
  });

  // Submit form vendita - prepara dati nascosti
  document.getElementById('form-vendita').addEventListener('submit', function (e) {
    e.preventDefault();
    // Nome cliente visibile nel campo nascosto
    const nomeCliente = clienteInput.value.trim();
    const inputNomeCliente = document.getElementById('input_nome_cliente');
    if (inputNomeCliente) {
      inputNomeCliente.value = nomeCliente;
    }

    // Prepara carrello JSON
    const carrello = [];
    document.querySelectorAll('table tbody tr').forEach((row, idx) => {
      const idProd = row.querySelector('input[type="hidden"]').value;
      const nomeProd = row.querySelector('td:first-child').textContent.trim();
      const qty = parseInt(row.querySelector('td:nth-child(2)').textContent.trim()) || 0;
      const prezzoInput = row.querySelector('.prezzo-vendita');
      const prezzo = parseFloat(prezzoInput.value.replace(',', '.')) || 0;
      const prezzoScontatoInput = row.querySelector(`input[name="prodotti[${idx}][prezzo_scontato]"]`);
      const prezzoScontato = parseFloat(prezzoScontatoInput.value.replace(',', '.') || 0);

      carrello.push({
        id_prodotto: idProd,
        nome: nomeProd,
        qty: qty,
        prezzo_unitario: prezzo,
        prezzo_scontato: prezzoScontato
      });
    });

    document.getElementById('input_carrello').value = JSON.stringify(carrello);
    document.getElementById('input_pagamento1').value = document.getElementById('pagamento1').value;
    document.getElementById('input_pagamento2').value = document.getElementById('pagamento2').value;
    const residuoText = document.getElementById('residuo').textContent.replace(/[^\d.,-]/g, '').replace(',', '.');
    document.getElementById('input_residuo').value = residuoText;

    alert('Dati pronti per l\'invio (demo). Carrello JSON:\n' + JSON.stringify(carrello, null, 2));
  });
});
</script>

</body>
</html>
