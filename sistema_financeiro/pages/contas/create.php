<?php
/**
 * Criar Conta
 * Sistema Financeiro
 */

define('SISTEMA_FINANCEIRO', true);
require_once '../../includes/init.php';
require_once '../../includes/auth_check.php';


$current_page = 'contas';
$page_title = 'Nova Conta';

$user_id = $_SESSION['user_id'];
$is_parcelado = isset($_GET['parcelado']) && $_GET['parcelado'] == '1';

$errors = [];
$form_data = [
    'descricao' => '',
    'categoria_id' => '',
    'tipo' => 'despesa',
    'valor' => '',
    'data_vencimento' => date('d/m/Y'),
    'observacoes' => '',
    'parcelado' => $is_parcelado ? 1 : 0,
    'total_parcelas' => 2
];

// Busca categorias
try {
    $categorias = $database->select("SELECT * FROM categorias WHERE ativo = 1 ORDER BY tipo, nome");
} catch (Exception $e) {
    $categorias = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form_data['descricao'] = sanitize($_POST['descricao'] ?? '');
    $form_data['categoria_id'] = (int)($_POST['categoria_id'] ?? 0);
    $form_data['tipo'] = sanitize($_POST['tipo'] ?? 'despesa');
    $form_data['valor'] = sanitize($_POST['valor'] ?? '');
    $form_data['data_vencimento'] = sanitize($_POST['data_vencimento'] ?? '');
    $form_data['observacoes'] = sanitize($_POST['observacoes'] ?? '');
    $form_data['parcelado'] = isset($_POST['parcelado']) ? 1 : 0;
    $form_data['total_parcelas'] = max(2, (int)($_POST['total_parcelas'] ?? 2));
    
    // Validações
    if (empty($form_data['descricao'])) {
        $errors[] = 'Descrição é obrigatória.';
    }
    
    if (empty($form_data['valor'])) {
        $errors[] = 'Valor é obrigatório.';
    } else {
        // Converte valor monetário
        $valor_numerico = str_replace(['R$', ' ', '.'], '', $form_data['valor']);
        $valor_numerico = str_replace(',', '.', $valor_numerico);
        
        if (!is_numeric($valor_numerico) || $valor_numerico <= 0) {
            $errors[] = 'Valor deve ser um número positivo.';
        }
    }
    
    if (empty($form_data['data_vencimento'])) {
        $errors[] = 'Data de vencimento é obrigatória.';
    } else {
        $data_mysql = dateToMysql($form_data['data_vencimento']);
        if (!$data_mysql) {
            $errors[] = 'Data de vencimento inválida.';
        }
    }
    
    if ($form_data['parcelado'] && $form_data['total_parcelas'] < 2) {
        $errors[] = 'Número de parcelas deve ser pelo menos 2.';
    }
    
    // Salva conta
    if (empty($errors)) {
        try {
            $database->beginTransaction();
            
            if ($form_data['parcelado']) {
                // Conta parcelada
                $valor_parcela = $valor_numerico / $form_data['total_parcelas'];
                $datas_vencimento = generateInstallmentDates($data_mysql, $form_data['total_parcelas']);
                
                for ($i = 0; $i < $form_data['total_parcelas']; $i++) {
                    $descricao_parcela = $form_data['descricao'] . ' (' . ($i + 1) . '/' . $form_data['total_parcelas'] . ')';
                    
                    $query = "INSERT INTO contas (usuario_id, categoria_id, descricao, valor, tipo, data_vencimento, observacoes, parcelado, parcela_atual, total_parcelas) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?, ?)";
                    $params = [
                        $user_id,
                        $form_data['categoria_id'] ?: null,
                        $descricao_parcela,
                        $valor_parcela,
                        $form_data['tipo'],
                        $datas_vencimento[$i],
                        $form_data['observacoes'],
                        $i + 1,
                        $form_data['total_parcelas']
                    ];
                    
                    $database->execute($query, $params);
                }
                
                showSuccess('Conta parcelada criada com sucesso! ' . $form_data['total_parcelas'] . ' parcelas de ' . formatMoney($valor_parcela) . ' cada.');
                
            } else {
                // Conta simples
                $query = "INSERT INTO contas (usuario_id, categoria_id, descricao, valor, tipo, data_vencimento, observacoes) 
                          VALUES (?, ?, ?, ?, ?, ?, ?)";
                $params = [
                    $user_id,
                    $form_data['categoria_id'] ?: null,
                    $form_data['descricao'],
                    $valor_numerico,
                    $form_data['tipo'],
                    $data_mysql,
                    $form_data['observacoes']
                ];
                
                $database->execute($query, $params);
                
                showSuccess('Conta criada com sucesso!');
            }
            
            $database->commit();
            redirect('index.php');
            
        } catch (Exception $e) {
            $database->rollback();
            $errors[] = 'Erro ao criar conta: ' . $e->getMessage();
        }
    }
}

include '../../templates/header.php';
?>
    <link rel="stylesheet" href="../../assets/css/style.css">
<style>
/* Botões principais */
.btn-success {
    background: #22c55e;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    transition: background 0.2s;
}
.btn-success:hover {
    background: #16a34a;
}
.btn-secondary {
    background: #6b7280;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    transition: background 0.2s;
    color: #fff;
}
.btn-secondary:hover {
    background: #4b5563;
    color: #fff;
}
.btn-voltar {
    background: #f3f4f6;
    color: #374151;
    border: none;
    border-radius: 8px;
    font-weight: 500;
    padding: 0.6rem 1.4rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    transition: background 0.2s, color 0.2s;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.btn-voltar:hover {
    background: #e5e7eb;
    color: #111827;
    text-decoration: none;
}
.btn-voltar i {
    font-size: 1.1em;
}

/* Ajuste dos cards */
.card {
    border-radius: 14px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    border: none;
}
.card-header {
    background: #f8f9fa;
    border-bottom: 1px solid #eee;
    border-radius: 14px 14px 0 0;
    font-weight: 600;
}

/* Inputs e selects */
.form-control, .form-select {
    border-radius: 8px;
    border: 1px solid #d1d5db;
    background: #f9fafb;
    transition: border 0.2s;
}
.form-control:focus, .form-select:focus {
    border-color: #2563eb;
    background: #fff;
}
.form-label {
    font-weight: 500;
}
.form-check-input {
    border-radius: 4px;
}

/* Valor por parcela */
#valor_parcela {
    font-weight: 600;
    color: #2563eb;
    background: #f4f6fa;
    border-radius: 8px;
    padding: 0.5em 1em;
    display: inline-block;
    min-width: 110px;
}

/* Badge categorias */
.badge {
    border-radius: 8px;
    padding: 0.4em 0.8em;
    font-size: 0.95em;
    color: #fff !important;
    font-weight: 500;
    display: inline-block;
}
.badge-success { background: #22c55e !important; }
.badge-danger { background: #ef4444 !important; }
.me-1 { margin-right: 0.5rem !important; }
.mb-1 { margin-bottom: 0.3rem !important; }

/* Responsividade */
@media (max-width: 991px) {
    .col-md-8, .col-md-4 {
        flex: 0 0 100%;
        max-width: 100%;
    }
    .card {
        margin-bottom: 1.5rem;
    }
}
</style>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
            <div>
                <h1 class="mb-1"><?php echo $is_parcelado ? 'Nova Conta Parcelada' : 'Nova Conta'; ?></h1>
                <p class="text-muted mb-0">Adicionar nova <?php echo $is_parcelado ? 'conta parcelada' : 'receita ou despesa'; ?></p>
            </div>
            <a href="index.php" class="btn btn-voltar mt-3 mt-md-0">
                <i class="fa fa-arrow-left"></i> Voltar
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5>Dados da Conta</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form method="POST" class="needs-validation" id="conta-form" novalidate>
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="descricao" class="form-label">Descrição *</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="descricao" 
                                       name="descricao" 
                                       value="<?php echo htmlspecialchars($form_data['descricao']); ?>"
                                       required>
                                <div class="invalid-feedback">
                                    Por favor, informe a descrição.
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="categoria_id" class="form-label">Categoria</label>
                                <select class="form-control form-select" id="categoria_id" name="categoria_id">
                                    <option value="">Selecione uma categoria</option>
                                    <?php
                                    $current_type = '';
                                    foreach ($categorias as $categoria):
                                        if ($categoria['tipo'] !== $current_type):
                                            if ($current_type !== '') echo '</optgroup>';
                                            echo '<optgroup label="' . ucfirst($categoria['tipo']) . 's">';
                                            $current_type = $categoria['tipo'];
                                        endif;
                                    ?>
                                        <option value="<?php echo $categoria['id']; ?>" 
                                                data-tipo="<?php echo $categoria['tipo']; ?>"
                                                <?php echo $form_data['categoria_id'] == $categoria['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($categoria['nome']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                    <?php if ($current_type !== '') echo '</optgroup>'; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="tipo" class="form-label">Tipo *</label>
                                <select class="form-control form-select" id="tipo" name="tipo" required>
                                    <option value="receita" <?php echo $form_data['tipo'] === 'receita' ? 'selected' : ''; ?>>
                                        Receita
                                    </option>
                                    <option value="despesa" <?php echo $form_data['tipo'] === 'despesa' ? 'selected' : ''; ?>>
                                        Despesa
                                    </option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="valor" class="form-label">Valor *</label>
                                <input type="text" 
                                       class="form-control money-input" 
                                       id="valor" 
                                       name="valor" 
                                       value="<?php echo htmlspecialchars($form_data['valor']); ?>"
                                       placeholder="R$ 0,00"
                                       required>
                                <div class="invalid-feedback">
                                    Por favor, informe o valor.
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="data_vencimento" class="form-label">Data de Vencimento *</label>
                                <input type="text" 
                                       class="form-control date-input" 
                                       id="data_vencimento" 
                                       name="data_vencimento" 
                                       value="<?php echo htmlspecialchars($form_data['data_vencimento']); ?>"
                                       placeholder="dd/mm/aaaa"
                                       required>
                                <div class="invalid-feedback">
                                    Por favor, informe a data de vencimento.
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Opções de Parcelamento -->
                    <div class="row">
                        <div class="col-12">
                            <div class="form-group">
                                <div class="form-check">
                                    <input type="checkbox" 
                                           class="form-check-input" 
                                           id="parcelado" 
                                           name="parcelado" 
                                           <?php echo $form_data['parcelado'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="parcelado">
                                        Conta parcelada
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div id="parcelamento-options" style="display: <?php echo $form_data['parcelado'] ? 'block' : 'none'; ?>;">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="total_parcelas" class="form-label">Número de Parcelas</label>
                                    <select class="form-control form-select" id="total_parcelas" name="total_parcelas">
                                        <?php for ($i = 2; $i <= 60; $i++): ?>
                                            <option value="<?php echo $i; ?>" 
                                                    <?php echo $form_data['total_parcelas'] == $i ? 'selected' : ''; ?>>
                                                <?php echo $i; ?>x
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label class="form-label">Valor por Parcela</label>
                                    <div class="form-control-static" id="valor_parcela">
                                        R$ 0,00
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="observacoes" class="form-label">Observações</label>
                        <textarea class="form-control" 
                                  id="observacoes" 
                                  name="observacoes" 
                                  rows="3"><?php echo htmlspecialchars($form_data['observacoes']); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-success">Criar Conta</button>
                        <a href="index.php" class="btn btn-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h6>Dicas</h6>
            </div>
            <div class="card-body">
                <ul>
                    <li><strong>Receitas:</strong> Salários, vendas, investimentos</li>
                    <li><strong>Despesas:</strong> Contas, compras, gastos</li>
                    <li><strong>Parcelamento:</strong> Divide o valor total em parcelas mensais</li>
                    <li><strong>Categorias:</strong> Ajudam a organizar e gerar relatórios</li>
                </ul>
            </div>
        </div>
        
        <?php if (!empty($categorias)): ?>
            <div class="card">
                <div class="card-header">
                    <h6>Categorias Disponíveis</h6>
                </div>
                <div class="card-body">
                    <?php
                    $receitas = array_filter($categorias, function($c) { return $c['tipo'] === 'receita'; });
                    $despesas = array_filter($categorias, function($c) { return $c['tipo'] === 'despesa'; });
                    ?>
                    
                    <p><strong>Receitas:</strong></p>
                    <div class="mb-3">
                        <?php foreach ($receitas as $categoria): ?>
                            <span class="badge badge-success me-1 mb-1" style="background-color: <?php echo $categoria['cor']; ?>">
                                <?php echo htmlspecialchars($categoria['nome']); ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                    
                    <p><strong>Despesas:</strong></p>
                    <div>
                        <?php foreach ($despesas as $categoria): ?>
                            <span class="badge badge-danger me-1 mb-1" style="background-color: <?php echo $categoria['cor']; ?>">
                                <?php echo htmlspecialchars($categoria['nome']); ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const parceladoCheckbox = document.getElementById('parcelado');
    const parcelamentoOptions = document.getElementById('parcelamento-options');
    const valorInput = document.getElementById('valor');
    const parcelasSelect = document.getElementById('total_parcelas');
    const valorParcelaDiv = document.getElementById('valor_parcela');
    const tipoSelect = document.getElementById('tipo');
    const categoriaSelect = document.getElementById('categoria_id');
    
    // Mostra/esconde opções de parcelamento
    parceladoCheckbox.addEventListener('change', function() {
        parcelamentoOptions.style.display = this.checked ? 'block' : 'none';
        calculateInstallment();
    });
    
    // Calcula valor da parcela
    function calculateInstallment() {
        if (!parceladoCheckbox.checked) {
            valorParcelaDiv.textContent = 'R$ 0,00';
            return;
        }
        
        const valorText = valorInput.value.replace(/[^\d,]/g, '').replace(',', '.');
        const valor = parseFloat(valorText) || 0;
        const parcelas = parseInt(parcelasSelect.value) || 1;
        const valorParcela = valor / parcelas;
        
        valorParcelaDiv.textContent = 'R$ ' + valorParcela.toLocaleString('pt-BR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }
    
    valorInput.addEventListener('input', calculateInstallment);
    parcelasSelect.addEventListener('change', calculateInstallment);
    
    // Filtra categorias por tipo
    tipoSelect.addEventListener('change', function() {
        const tipoSelecionado = this.value;
        const options = categoriaSelect.querySelectorAll('option[data-tipo]');
        
        options.forEach(option => {
            if (option.dataset.tipo === tipoSelecionado || tipoSelecionado === '') {
                option.style.display = 'block';
            } else {
                option.style.display = 'none';
            }
        });
        
        // Reset selection if current category doesn't match type
        const selectedOption = categoriaSelect.querySelector('option:checked');
        if (selectedOption && selectedOption.dataset.tipo && selectedOption.dataset.tipo !== tipoSelecionado) {
            categoriaSelect.value = '';
        }
    });
    
    // Trigger initial calculation
    calculateInstallment();
    tipoSelect.dispatchEvent(new Event('change'));
});
</script>

<?php include '../../templates/footer.php'; ?>

