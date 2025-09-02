/**
 * Sistema Financeiro - JavaScript Principal
 */

// Aguarda o DOM carregar
document.addEventListener('DOMContentLoaded', function() {
    
    // Inicializa funcionalidades
    initializeForms();
    initializeModals();
    initializeTables();
    initializeCharts();
    
});

/**
 * Inicializa formulários
 */
function initializeForms() {
    // Máscara para valores monetários
    const moneyInputs = document.querySelectorAll('.money-input');
    moneyInputs.forEach(input => {
        input.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            value = (value / 100).toFixed(2);
            value = value.replace('.', ',');
            value = value.replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');
            e.target.value = 'R$ ' + value;
        });
    });
    
    // Máscara para datas
    const dateInputs = document.querySelectorAll('.date-input');
    dateInputs.forEach(input => {
        input.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            value = value.replace(/(\d{2})(\d)/, '$1/$2');
            value = value.replace(/(\d{2})(\d)/, '$1/$2');
            e.target.value = value;
        });
    });
    
    // Validação de formulários
    const forms = document.querySelectorAll('.needs-validation');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });
    
    // Cálculo automático de parcelas
    const installmentForm = document.getElementById('installment-form');
    if (installmentForm) {
        const totalValue = installmentForm.querySelector('#total_value');
        const installments = installmentForm.querySelector('#installments');
        const installmentValue = installmentForm.querySelector('#installment_value');
        
        function calculateInstallments() {
            if (totalValue.value && installments.value) {
                const total = parseFloat(totalValue.value.replace(/[^\d,]/g, '').replace(',', '.'));
                const numInstallments = parseInt(installments.value);
                const value = (total / numInstallments).toFixed(2);
                installmentValue.textContent = 'R$ ' + value.replace('.', ',');
            }
        }
        
        totalValue.addEventListener('input', calculateInstallments);
        installments.addEventListener('change', calculateInstallments);
    }
}

/**
 * Inicializa modais
 */
function initializeModals() {
    // Confirmação de exclusão
    const deleteButtons = document.querySelectorAll('.btn-delete');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const message = this.dataset.message || 'Tem certeza que deseja excluir este item?';
            if (confirm(message)) {
                window.location.href = this.href;
            }
        });
    });
    
    // Modal simples
    window.openModal = function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
    };
    
    window.closeModal = function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
    };
    
    // Fechar modal clicando fora
    window.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal')) {
            e.target.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
    });
}

/**
 * Inicializa tabelas
 */
function initializeTables() {
    // Ordenação de tabelas
    const sortableHeaders = document.querySelectorAll('.sortable');
    sortableHeaders.forEach(header => {
        header.addEventListener('click', function() {
            const table = this.closest('table');
            const tbody = table.querySelector('tbody');
            const rows = Array.from(tbody.querySelectorAll('tr'));
            const column = Array.from(this.parentNode.children).indexOf(this);
            const isAscending = this.classList.contains('asc');
            
            // Remove classes de ordenação
            sortableHeaders.forEach(h => h.classList.remove('asc', 'desc'));
            
            // Ordena as linhas
            rows.sort((a, b) => {
                const aValue = a.children[column].textContent.trim();
                const bValue = b.children[column].textContent.trim();
                
                if (isAscending) {
                    this.classList.add('desc');
                    return bValue.localeCompare(aValue, 'pt-BR', { numeric: true });
                } else {
                    this.classList.add('asc');
                    return aValue.localeCompare(bValue, 'pt-BR', { numeric: true });
                }
            });
            
            // Reinsere as linhas ordenadas
            rows.forEach(row => tbody.appendChild(row));
        });
    });
    
    // Filtro de tabelas
    const searchInputs = document.querySelectorAll('.table-search');
    searchInputs.forEach(input => {
        input.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const table = document.querySelector(this.dataset.target);
            const rows = table.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
    });
}

/**
 * Inicializa gráficos (usando Chart.js se disponível)
 */
function initializeCharts() {
    if (typeof Chart !== 'undefined') {
        // Gráfico de receitas vs despesas
        const revenueExpenseChart = document.getElementById('revenueExpenseChart');
        if (revenueExpenseChart) {
            new Chart(revenueExpenseChart, {
                type: 'doughnut',
                data: {
                    labels: ['Receitas', 'Despesas'],
                    datasets: [{
                        data: [
                            parseFloat(revenueExpenseChart.dataset.revenue || 0),
                            parseFloat(revenueExpenseChart.dataset.expense || 0)
                        ],
                        backgroundColor: ['#28a745', '#dc3545']
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }
        
        // Gráfico mensal
        const monthlyChart = document.getElementById('monthlyChart');
        if (monthlyChart) {
            const months = JSON.parse(monthlyChart.dataset.months || '[]');
            const revenues = JSON.parse(monthlyChart.dataset.revenues || '[]');
            const expenses = JSON.parse(monthlyChart.dataset.expenses || '[]');
            
            new Chart(monthlyChart, {
                type: 'line',
                data: {
                    labels: months,
                    datasets: [{
                        label: 'Receitas',
                        data: revenues,
                        borderColor: '#28a745',
                        backgroundColor: 'rgba(40, 167, 69, 0.1)',
                        tension: 0.4
                    }, {
                        label: 'Despesas',
                        data: expenses,
                        borderColor: '#dc3545',
                        backgroundColor: 'rgba(220, 53, 69, 0.1)',
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return 'R$ ' + value.toLocaleString('pt-BR');
                                }
                            }
                        }
                    }
                }
            });
        }
    }
}

/**
 * Utilitários
 */

// Formatar valor monetário
function formatMoney(value) {
    return 'R$ ' + parseFloat(value).toLocaleString('pt-BR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

// Formatar data
function formatDate(date) {
    return new Date(date).toLocaleDateString('pt-BR');
}

// Mostrar loading
function showLoading() {
    const loading = document.createElement('div');
    loading.id = 'loading';
    loading.innerHTML = '<div class="spinner"></div>';
    loading.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
    `;
    document.body.appendChild(loading);
}

// Esconder loading
function hideLoading() {
    const loading = document.getElementById('loading');
    if (loading) {
        loading.remove();
    }
}

// Notificações toast
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 1rem 1.5rem;
        background: var(--${type === 'success' ? 'success' : type === 'error' ? 'danger' : 'info'}-color);
        color: white;
        border-radius: 0.25rem;
        z-index: 9999;
        animation: slideIn 0.3s ease;
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Animações CSS
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    
    @keyframes slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
    
    .spinner {
        width: 40px;
        height: 40px;
        border: 4px solid #f3f3f3;
        border-top: 4px solid #007bff;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    .sortable {
        cursor: pointer;
        user-select: none;
    }
    
    .sortable:hover {
        background-color: rgba(0,0,0,0.05);
    }
    
    .sortable.asc::after {
        content: ' ↑';
    }
    
    .sortable.desc::after {
        content: ' ↓';
    }
`;
document.head.appendChild(style);

