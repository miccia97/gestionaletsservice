<?php
// Gestione Categorie e Sottocategorie (Frontend HTML/JS)

// Abilita la visualizzazione degli errori per il debug (RIMUOVERE IN PRODUZIONE!)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Questo file ora serve solo l'HTML e il JavaScript.
// La logica di recupero dati PHP è stata spostata in api/get_categories_data.php
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Categorie</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        :root {
            --brand-green: #22c55e;
            --brand-blue: #3b82f6;
            --brand-red: #ef4444;
            --bg-page: #f8fafc;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --border-color: #e2e8f0;
            --card-bg: #ffffff;
            --card-radius: 0.75rem;
            --card-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
        }
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-page);
            color: var(--text-primary);
            padding-top: 80px;
        }
        .page-container { max-width: 1400px; margin: 2rem auto; padding: 0 2rem; }
        .page-header h1 { font-size: 2.25rem; font-weight: 800; }
        .card { background-color: var(--card-bg); border-radius: var(--card-radius); box-shadow: var(--card-shadow); padding: 2rem; }
        .main-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 2rem; align-items: flex-start; }

        .form-card h2 { font-size: 1.5rem; font-weight: 600; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid var(--border-color); }
        .form-card label { display: block; font-weight: 500; margin-bottom: 0.5rem; color: var(--text-secondary); }
        .form-card input, .form-card select { width: 100%; padding: 0.75rem; border-radius: 0.5rem; border: 1px solid var(--border-color); background-color: #f8fafc; transition: all 0.2s ease; }
        .form-card input:focus, .form-card select:focus { outline: none; border-color: var(--brand-blue); box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2); background-color: white; }
        .btn { display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.75rem 1.5rem; border-radius: 0.5rem; font-weight: 600; cursor: pointer; border: none; transition: all 0.2s ease; width: 100%; }
        .btn-primary { background-color: var(--brand-blue); color: white; } .btn-primary:hover { background-color: #2563eb; }
        .btn-secondary { background-color: var(--text-secondary); color: white; } .btn-secondary:hover { background-color: #475569; }

        /* Category Item Styling (User's working logic adapted to new style) */
        #categoriesList {
            list-style-type: none;
            padding: 0;
        }
        .category-item {
            padding: 1rem;
            margin-bottom: 0.5rem;
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: var(--card-radius);
            display: flex;
            align-items: center;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
            transition: box-shadow 0.2s ease, transform 0.2s ease;
        }
        .category-item:hover {
             box-shadow: 0 4px 8px rgba(0,0,0,0.1);
             transform: translateY(-2px);
        }
        .category-level-1 { margin-left: 2rem; background-color: #f8fafc; }
        .category-level-2 { margin-left: 4rem; background-color: #f1f5f9; }

        .drag-handle { cursor: grab; color: #9ca3af; padding: 0 0.75rem; }
        .drag-handle:active { cursor: grabbing; }
        .category-name { flex-grow: 1; font-weight: 500; }
        .category-actions button {
            background: none; border: none; cursor: pointer; color: var(--text-secondary); padding: 0.5rem;
            border-radius: 50%; width: 2rem; height: 2rem; display: flex; align-items: center; justify-content: center;
        }
        .category-actions button:hover { background-color: var(--border-color); color: var(--text-primary); }
        
        /* Drag & Drop Visuals */
        .is-dragging {
            opacity: 0.5;
            background: #dbeafe;
        }
        .drag-placeholder {
            height: 3.5rem;
            background-color: rgba(59, 130, 246, 0.1);
            border: 2px dashed var(--brand-blue);
            border-radius: var(--card-radius);
            margin: 0.5rem 0;
        }
        .drop-target-highlight {
            background-color: #dbeafe !important;
            border-color: var(--brand-blue);
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="page-container">
        <div class="page-header">
            <h1>Gestione Categorie</h1>
            <p class="text-lg text-gray-500">Organizza, sposta e modifica la struttura delle categorie dei tuoi prodotti.</p>
        </div>

        <div class="main-grid">
            <div class="card">
                <ul id="categoriesList" class="category-list">
                    <div class="text-gray-500 text-center py-4"><i class="fas fa-spinner fa-spin mr-2"></i>Caricamento categorie...</div>
                </ul>
            </div>

            <div class="card form-card sticky top-24">
                <h2 id="formTitle">Aggiungi Categoria</h2>
                <form id="categoryForm" class="space-y-6">
                    <input type="hidden" id="categoryId">
                    <input type="hidden" id="categoryType">
                    <div>
                        <label for="categoryName">Nome Categoria:</label>
                        <input type="text" id="categoryName" placeholder="Es. Smartphone" required>
                    </div>
                    <div>
                        <label for="parentCategory">Categoria Genitore:</label>
                        <select id="parentCategory">
                            <option value="" data-parent-type="none">(Nessun genitore)</option>
                        </select>
                    </div>
                    <div class="flex gap-4">
                        <button type="button" id="cancelBtn" class="btn btn-secondary" style="display: none;">Annulla</button>
                        <button type="submit" id="addUpdateBtn" class="btn btn-primary">Aggiungi Categoria</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
<script>
let categoriesData = [];
let draggedElement = null;
const placeholder = document.createElement('li');
placeholder.className = 'drag-placeholder';

document.addEventListener('DOMContentLoaded', () => {
    initApp();
    document.getElementById('categoryForm').addEventListener('submit', handleFormSubmit);
    document.getElementById('cancelBtn').addEventListener('click', resetForm);
});

async function initApp() { await loadCategories(); }

async function loadCategories() {
    try {
        const response = await fetch('api/get_categories_data.php');
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
        const result = await response.json();

        if (result.success) {
            categoriesData = result.data;
            renderCategories();
            populateParentDropdown();
        } else {
            showNotification(`Errore: ${result.message}`, 'error');
        }
    } catch (error) {
        showNotification("Errore di comunicazione con il server.", 'error');
        console.error("Errore caricamento categorie:", error);
    }
}

function renderCategories() {
    const listContainer = document.getElementById('categoriesList');
    listContainer.innerHTML = '';

    function buildCategoryTree(categories, parentElement, level = 0) {
        categories.forEach(category => {
            const li = document.createElement('li');
            li.className = `category-item category-level-${level}`;
            li.dataset.id = category.id;
            li.dataset.type = category.type;
            li.dataset.depth = level;
            li.draggable = true;

            li.innerHTML = `
                <div class="drag-handle"><i class="fas fa-grip-vertical"></i></div>
                <span class="category-name">${category.nome}</span>
                <div class="category-actions flex items-center">
                    <button class="edit-btn"><i class="fas fa-pencil-alt"></i></button>
                    <button class="delete-btn"><i class="fas fa-trash-alt"></i></button>
                </div>
            `;
            
            parentElement.appendChild(li);

            li.addEventListener('dragstart', handleDragStart);
            li.addEventListener('dragend', handleDragEnd);
            li.addEventListener('dragover', handleDragOver);
            li.addEventListener('dragleave', handleDragLeave);
            li.addEventListener('drop', handleDrop);

            li.querySelector('.edit-btn').addEventListener('click', () => setupEditForm(category));
            li.querySelector('.delete-btn').addEventListener('click', () => confirmDelete(category.id, category.type));

            if (category.children && category.children.length > 0) {
                const childrenContainer = document.createElement('ul');
                childrenContainer.className = 'children-container';
                parentElement.appendChild(childrenContainer);
                buildCategoryTree(category.children, childrenContainer, level + 1);
            }
        });
    }
    buildCategoryTree(categoriesData, listContainer);
}

function handleDragStart(e) {
    draggedElement = e.currentTarget;
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/plain', draggedElement.dataset.id);
    setTimeout(() => {
        if (draggedElement) draggedElement.classList.add('is-dragging');
    }, 0);
}

function handleDragEnd() {
    if (draggedElement) {
        draggedElement.classList.remove('is-dragging');
        draggedElement = null;
    }
    if (placeholder.parentNode) {
        placeholder.remove();
    }
    document.querySelectorAll('.drop-target-highlight').forEach(el => el.classList.remove('drop-target-highlight'));
}

function handleDragOver(e) {
    e.preventDefault();
    const target = e.target.closest('.category-item');
    if (!target || target === draggedElement) return;

    document.querySelectorAll('.drop-target-highlight').forEach(el => el.classList.remove('drop-target-highlight'));
    
    const targetRect = target.getBoundingClientRect();
    const isOverTopHalf = e.clientY < targetRect.top + targetRect.height / 2;
    const targetDepth = parseInt(target.dataset.depth);
    const draggedDepth = parseInt(draggedElement.dataset.depth);

    // Nesting logic
    if (targetDepth < 2 && draggedDepth <= targetDepth) {
        const dropZoneThreshold = targetRect.height * 0.25;
        if (e.clientY > targetRect.top + dropZoneThreshold && e.clientY < targetRect.bottom - dropZoneThreshold) {
            target.classList.add('drop-target-highlight');
            if (placeholder.parentNode) placeholder.remove();
            return;
        }
    }
    
    // Reordering logic
    if (isOverTopHalf) {
        target.parentElement.insertBefore(placeholder, target);
    } else {
        target.parentElement.insertBefore(placeholder, target.nextElementSibling);
    }
}

function handleDragLeave(e) {
    const target = e.target.closest('.category-item');
    if (target) {
        target.classList.remove('drop-target-highlight');
    }
}

async function handleDrop(e) {
    e.preventDefault();
    const highlightedTarget = document.querySelector('.drop-target-highlight');
    if (highlightedTarget) {
        highlightedTarget.classList.remove('drop-target-highlight');
        let childrenContainer = highlightedTarget.nextElementSibling;
        if (!childrenContainer || !childrenContainer.classList.contains('children-container')) {
            childrenContainer = document.createElement('ul');
            childrenContainer.className = 'children-container';
            highlightedTarget.insertAdjacentElement('afterend', childrenContainer);
        }
        childrenContainer.prepend(draggedElement);
    } else if (placeholder.parentNode) {
        placeholder.replaceWith(draggedElement);
    } else {
        return;
    }
    
    await updateStructure();
}

async function updateStructure() {
    const items = [];
    let orderCounter = { 0: 0, 1: 0, 2: 0 };

    function traverseDOM(container, parentId, parentType, depth) {
        const children = Array.from(container.children).filter(el => el.classList.contains('category-item'));
        
        children.forEach((item, index) => {
            const id = item.dataset.id;
            let currentParentId = parentId;
            let currentParentType = parentType;
            let currentType = '';

            if (depth === 0) {
                currentType = 'main_category';
            } else if (depth === 1) {
                currentType = 'sub_category';
            } else {
                currentType = 'sub_sub_category';
            }
            
            items.push({
                id: id,
                order: index,
                parent_id: currentParentId,
                type: currentType
            });
            
            const nextContainer = item.nextElementSibling;
            if (nextContainer && nextContainer.classList.contains('children-container')) {
                traverseDOM(nextContainer, id, currentType, depth + 1);
            }
        });
    }

    traverseDOM(document.getElementById('categoriesList'), null, 'none', 0);
    
    await updateOrdersOnServer(items);
}

// All other functions (form handling, delete, API calls) remain the same
async function updateOrdersOnServer(items) {
     try {
        const response = await fetch('update_orders.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ items })
        });
        const result = await response.json();
        if (result.success) {
            showNotification(result.message, 'success');
        } else {
            showNotification(`Errore: ${result.message}`, 'error');
        }
        await loadCategories(); 
    } catch (error) {
        showNotification("Errore durante il riordinamento.", 'error');
        console.error("Errore riordinamento:", error);
        await loadCategories();
    }
}

// --- Funzioni del form e di eliminazione (invariate) ---
function populateParentDropdown() {
    const parentSelect = document.getElementById('parentCategory');
    const currentId = document.getElementById('categoryId').value;
    parentSelect.innerHTML = '<option value="" data-parent-type="none">(Nessun genitore)</option>';

    function addOptions(categories, indent = '', depth = 0) {
        categories.forEach(cat => {
            if (cat.id == currentId || depth >= 2) return;
            const option = document.createElement('option');
            option.value = cat.id;
            option.textContent = indent + cat.nome;
            option.dataset.parentType = cat.type;
            parentSelect.appendChild(option);
            if (cat.children && cat.children.length > 0) {
                addOptions(cat.children, indent + '— ', depth + 1);
            }
        });
    }
    addOptions(categoriesData);
}

function resetForm() {
    document.getElementById('categoryForm').reset();
    document.getElementById('categoryId').value = '';
    document.getElementById('categoryType').value = '';
    document.getElementById('formTitle').textContent = 'Aggiungi Categoria';
    document.getElementById('addUpdateBtn').textContent = 'Aggiungi Categoria';
    document.getElementById('cancelBtn').style.display = 'none';
    populateParentDropdown();
}

function setupEditForm(category) {
    document.getElementById('formTitle').textContent = `Modifica: ${category.nome}`;
    document.getElementById('categoryId').value = category.id;
    document.getElementById('categoryType').value = category.type;
    document.getElementById('categoryName').value = category.nome;
    
    let parentId = null;
    if (category.type === 'sub_category') parentId = category.parent_category_id;
    else if (category.type === 'sub_sub_category') parentId = category.parent_subcategory_id;

    populateParentDropdown();
    document.getElementById('parentCategory').value = parentId || "";
    
    document.getElementById('addUpdateBtn').textContent = 'Aggiorna Categoria';
    document.getElementById('cancelBtn').style.display = 'inline-flex';
}

async function handleFormSubmit(e) {
    e.preventDefault();
    const id = document.getElementById('categoryId').value;
    const type = document.getElementById('categoryType').value;
    const name = document.getElementById('categoryName').value.trim();
    const parentSelect = document.getElementById('parentCategory');
    const selectedOption = parentSelect.options[parentSelect.selectedIndex];
    const parentId = selectedOption.value || null;
    const parentType = selectedOption.dataset.parentType || 'none';

    if (!name) return showNotification("Il nome della categoria è obbligatorio.", 'error');

    const endpoint = id ? 'update_category.php' : 'add_category.php';
    const bodyData = { nome: name, parent_id: parentId, parent_type: parentType };
    if (id) { bodyData.id = id; bodyData.type = type; }

    try {
        const response = await fetch(endpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(bodyData)
        });
        const result = await response.json();
        if (result.success) {
            showNotification(result.message, 'success');
            resetForm();
            await loadCategories();
        } else {
            showNotification(`Errore: ${result.message}`, 'error');
        }
    } catch (error) {
        showNotification("Errore di comunicazione con il server.", 'error');
    }
}

function confirmDelete(id, type) {
    Swal.fire({
        title: 'Sei sicuro?',
        text: "Eliminando questa categoria, eliminerai anche tutte le sue sottocategorie. L'azione non è reversibile.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: 'var(--brand-red)',
        cancelButtonColor: 'var(--text-secondary)',
        confirmButtonText: 'Sì, elimina!',
        cancelButtonText: 'Annulla'
    }).then(async (result) => { if (result.isConfirmed) await deleteCategory(id, type); });
}

async function deleteCategory(id, type) {
    try {
        const response = await fetch('delete_category.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, type })
        });
        const result = await response.json();
        if (result.success) {
            showNotification(result.message, 'success');
            await loadCategories();
        } else {
            showNotification(`Errore: ${result.message}`, 'error');
        }
    } catch (error) {
        showNotification("Errore di comunicazione con il server.", 'error');
    }
}

function handleSearch() { /* Implement if needed */ }

function showNotification(message, icon = 'success') {
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: icon,
        title: message,
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true
    });
}
</script>
</body>
</html>

