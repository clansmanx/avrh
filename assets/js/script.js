// assets/js/script.js

// Aguardar DOM carregar
document.addEventListener('DOMContentLoaded', function() {
    
    // Inicializar tooltips
    initTooltips();
    
    // Inicializar máscaras de input
    initMasks();
    
    // Inicializar confirmações de exclusão
    initDeleteConfirmations();
    
    // Inicializar preview de imagem
    initImagePreview();
    
    // Inicializar contador de caracteres
    initCharCounters();
    
});

// Função para inicializar tooltips
function initTooltips() {
    const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltips.forEach(tooltip => {
        new bootstrap.Tooltip(tooltip);
    });
}

// Função para inicializar máscaras
function initMasks() {
    // Máscara de CPF
    const cpfInputs = document.querySelectorAll('.cpf-mask');
    cpfInputs.forEach(input => {
        input.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 11) value = value.slice(0, 11);
            
            if (value.length > 9) {
                value = value.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
            } else if (value.length > 6) {
                value = value.replace(/(\d{3})(\d{3})(\d{1,3})/, '$1.$2.$3');
            } else if (value.length > 3) {
                value = value.replace(/(\d{3})(\d{1,3})/, '$1.$2');
            }
            
            e.target.value = value;
        });
    });
    
    // Máscara de telefone
    const phoneInputs = document.querySelectorAll('.phone-mask');
    phoneInputs.forEach(input => {
        input.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 11) value = value.slice(0, 11);
            
            if (value.length > 6) {
                value = value.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
            } else if (value.length > 2) {
                value = value.replace(/(\d{2})(\d{1,5})/, '($1) $2');
            }
            
            e.target.value = value;
        });
    });
    
    // Máscara de CEP
    const cepInputs = document.querySelectorAll('.cep-mask');
    cepInputs.forEach(input => {
        input.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 8) value = value.slice(0, 8);
            
            if (value.length > 5) {
                value = value.replace(/(\d{5})(\d{3})/, '$1-$2');
            }
            
            e.target.value = value;
        });
    });
}

// Função para inicializar confirmações de exclusão
function initDeleteConfirmations() {
    const deleteButtons = document.querySelectorAll('[data-confirm]');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm(this.dataset.confirm || 'Tem certeza que deseja excluir este item?')) {
                e.preventDefault();
            }
        });
    });
}

// Função para inicializar preview de imagem
function initImagePreview() {
    const imageInputs = document.querySelectorAll('.image-preview-input');
    imageInputs.forEach(input => {
        input.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                const previewId = this.dataset.preview;
                const previewElement = document.getElementById(previewId);
                
                reader.onload = function(e) {
                    if (previewElement) {
                        previewElement.src = e.target.result;
                        previewElement.style.display = 'block';
                    }
                };
                
                reader.readAsDataURL(file);
            }
        });
    });
}

// Função para inicializar contadores de caracteres
function initCharCounters() {
    const textareas = document.querySelectorAll('[data-maxlength]');
    textareas.forEach(textarea => {
        const maxlength = parseInt(textarea.dataset.maxlength);
        const counterId = textarea.dataset.counter;
        const counterElement = document.getElementById(counterId);
        
        if (counterElement) {
            const updateCounter = function() {
                const currentLength = textarea.value.length;
                counterElement.textContent = `${currentLength}/${maxlength}`;
                
                if (currentLength > maxlength * 0.9) {
                    counterElement.classList.add('text-danger');
                } else {
                    counterElement.classList.remove('text-danger');
                }
            };
            
            textarea.addEventListener('input', updateCounter);
            updateCounter();
        }
    });
}

// Função para buscar CEP
function buscarCEP(cep) {
    cep = cep.replace(/\D/g, '');
    
    if (cep.length === 8) {
        fetch(`https://viacep.com.br/ws/${cep}/json/`)
            .then(response => response.json())
            .then(data => {
                if (!data.erro) {
                    document.querySelectorAll('[data-cep-logradouro]').forEach(el => {
                        el.value = data.logradouro || '';
                    });
                    document.querySelectorAll('[data-cep-bairro]').forEach(el => {
                        el.value = data.bairro || '';
                    });
                    document.querySelectorAll('[data-cep-cidade]').forEach(el => {
                        el.value = data.localidade || '';
                    });
                    document.querySelectorAll('[data-cep-uf]').forEach(el => {
                        el.value = data.uf || '';
                    });
                } else {
                    alert('CEP não encontrado');
                }
            })
            .catch(error => {
                console.error('Erro ao buscar CEP:', error);
                alert('Erro ao buscar CEP');
            });
    }
}

// Função para ordenar tabelas
function sortTable(tableId, columnIndex) {
    const table = document.getElementById(tableId);
    const tbody = table.tBodies[0];
    const rows = Array.from(tbody.rows);
    
    const isAscending = table.dataset.sortColumn === columnIndex.toString() && 
                       table.dataset.sortDirection === 'asc';
    
    rows.sort((a, b) => {
        const aValue = a.cells[columnIndex].textContent.trim();
        const bValue = b.cells[columnIndex].textContent.trim();
        
        if (!isNaN(aValue) && !isNaN(bValue)) {
            return isAscending ? bValue - aValue : aValue - bValue;
        }
        
        return isAscending ? 
            bValue.localeCompare(aValue) : 
            aValue.localeCompare(bValue);
    });
    
    tbody.innerHTML = '';
    rows.forEach(row => tbody.appendChild(row));
    
    table.dataset.sortColumn = columnIndex;
    table.dataset.sortDirection = isAscending ? 'desc' : 'asc';
    
    // Atualizar ícones de ordenação
    const headers = table.querySelectorAll('th');
    headers.forEach((header, index) => {
        const icon = header.querySelector('.sort-icon');
        if (icon) {
            icon.className = 'sort-icon bi bi-arrow-down-up ms-1';
        }
    });
    
    const currentHeader = headers[columnIndex];
    const currentIcon = currentHeader.querySelector('.sort-icon');
    if (currentIcon) {
        currentIcon.className = `sort-icon bi bi-arrow-${isAscending ? 'up' : 'down'} ms-1`;
    }
}

// Função para filtrar tabela
function filterTable(inputId, tableId) {
    const input = document.getElementById(inputId);
    const filter = input.value.toLowerCase();
    const table = document.getElementById(tableId);
    const rows = table.tBodies[0].rows;
    
    for (let i = 0; i < rows.length; i++) {
        let found = false;
        const cells = rows[i].cells;
        
        for (let j = 0; j < cells.length; j++) {
            const cellValue = cells[j].textContent || cells[j].innerText;
            if (cellValue.toLowerCase().indexOf(filter) > -1) {
                found = true;
                break;
            }
        }
        
        rows[i].style.display = found ? '' : 'none';
    }
}

// Função para exportar tabela para CSV
function exportTableToCSV(tableId, filename) {
    const table = document.getElementById(tableId);
    const rows = table.querySelectorAll('tr');
    const csv = [];
    
    for (let i = 0; i < rows.length; i++) {
        const row = [], cols = rows[i].querySelectorAll('td, th');
        
        for (let j = 0; j < cols.length; j++) {
            let data = cols[j].innerText.replace(/"/g, '""');
            row.push('"' + data + '"');
        }
        
        csv.push(row.join(','));
    }
    
    const csvFile = new Blob([csv.join('\n')], {type: 'text/csv'});
    const downloadLink = document.createElement('a');
    
    downloadLink.download = filename || 'export.csv';
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = 'none';
    
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
}

// Função para mostrar/esconder senha
function togglePassword(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById(iconId);
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'bi bi-eye';
    }
}

// Função para validar formulário
function validateForm(formId) {
    const form = document.getElementById(formId);
    const inputs = form.querySelectorAll('[required]');
    let isValid = true;
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.classList.add('is-invalid');
            isValid = false;
        } else {
            input.classList.remove('is-invalid');
        }
    });
    
    return isValid;
}

// Função para formatar moeda
function formatCurrency(value) {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    }).format(value);
}

// Função para formatar data
function formatDate(date) {
    return new Intl.DateTimeFormat('pt-BR').format(new Date(date));
}

// Função para mostrar loading
function showLoading(show = true) {
    const loader = document.getElementById('global-loader');
    if (loader) {
        loader.style.display = show ? 'flex' : 'none';
    }
}

// Função para fazer requisições AJAX
async function ajaxRequest(url, method = 'GET', data = null) {
    showLoading(true);
    
    const options = {
        method: method,
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    };
    
    if (data && (method === 'POST' || method === 'PUT')) {
        options.body = JSON.stringify(data);
    }
    
    try {
        const response = await fetch(url, options);
        const result = await response.json();
        showLoading(false);
        return result;
    } catch (error) {
        showLoading(false);
        console.error('Erro na requisição:', error);
        throw error;
    }
}
