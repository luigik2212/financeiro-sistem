# Sistema Financeiro

Sistema completo de controle financeiro desenvolvido em PHP, HTML, CSS, JavaScript e MySQL.

## Funcionalidades

- ✅ **Sistema de Autenticação**: Login/logout com controle de sessões
- ✅ **Gerenciamento de Usuários**: CRUD completo (apenas para administradores)
- ✅ **Controle de Contas**: Receitas e despesas com categorização
- ✅ **Sistema de Parcelamento**: Criação automática de parcelas mensais
- ✅ **Relatórios**: Análises financeiras com gráficos e estatísticas
- ✅ **Dashboard**: Visão geral das finanças com estatísticas em tempo real
- ✅ **Interface Responsiva**: Compatível com desktop e mobile

## Requisitos do Sistema

- PHP 7.4 ou superior
- MySQL 5.7 ou superior
- Servidor web (Apache/Nginx)
- Extensões PHP: PDO, PDO_MySQL

## Instalação

### 1. Configuração do Banco de Dados

1. Crie um banco de dados MySQL
2. Execute o script `database_structure.sql` para criar as tabelas
3. Ou use o instalador web (recomendado)

### 2. Configuração do Sistema

1. Faça upload dos arquivos para seu servidor web
2. Acesse `http://seudominio.com/install.php`
3. Preencha os dados de conexão com o banco
4. Clique em "Instalar Sistema"

### 3. Primeiro Acesso

Após a instalação, use as credenciais padrão:
- **Email**: admin@sistema.com
- **Senha**: admin123

⚠️ **IMPORTANTE**: Altere a senha padrão após o primeiro login!

## Estrutura do Projeto

```
sistema_financeiro/
├── assets/
│   ├── css/style.css          # Estilos principais
│   └── js/script.js           # JavaScript principal
├── classes/
│   └── Database.php           # Classe de conexão com BD
├── config/
│   └── database.php           # Configurações do sistema
├── includes/
│   ├── init.php              # Inicialização do sistema
│   ├── functions.php         # Funções auxiliares
│   └── auth_check.php        # Middleware de autenticação
├── pages/
│   ├── auth/                 # Páginas de autenticação
│   ├── usuarios/             # Gerenciamento de usuários
│   ├── contas/               # Controle de contas
│   └── relatorios/           # Relatórios e análises
├── templates/
│   ├── header.php            # Cabeçalho das páginas
│   └── footer.php            # Rodapé das páginas
├── index.php                 # Dashboard principal
├── install.php               # Instalador do sistema
└── database_structure.sql    # Estrutura do banco de dados
```

## Uso do Sistema

### Dashboard
- Visão geral das finanças do mês
- Estatísticas de receitas, despesas e saldo
- Próximas contas a vencer
- Últimas transações

### Contas
- **Criar Conta**: Adicionar receitas ou despesas
- **Conta Parcelada**: Dividir valor em parcelas mensais
- **Editar/Excluir**: Gerenciar contas existentes
- **Marcar como Pago**: Controlar pagamentos
- **Filtros**: Buscar por período, categoria, status, etc.

### Usuários (Apenas Administradores)
- Criar novos usuários
- Definir níveis de acesso (usuário/admin)
- Ativar/desativar usuários
- Alterar senhas

### Relatórios
- Análise por período (mês, ano, últimos 30 dias)
- Gráficos de evolução mensal
- Relatórios por categoria
- Estatísticas detalhadas

## Categorias Padrão

### Receitas
- Salário
- Freelance
- Investimentos

### Despesas
- Alimentação
- Transporte
- Moradia
- Saúde
- Educação
- Lazer
- Outros

## Segurança

- Senhas criptografadas com hash seguro
- Proteção contra SQL Injection
- Controle de sessões com timeout
- Validação de dados de entrada
- Proteção CSRF

## Personalização

### Cores e Estilos
Edite o arquivo `assets/css/style.css` para personalizar:
- Cores do tema
- Layout e espaçamentos
- Fontes e tipografia

### Funcionalidades
- Adicione novas categorias no banco de dados
- Customize relatórios em `pages/relatorios/`
- Modifique validações em `includes/functions.php`

## Backup

Recomenda-se fazer backup regular de:
1. Banco de dados MySQL
2. Arquivos do sistema
3. Configurações personalizadas

## Suporte

Para dúvidas ou problemas:
1. Verifique os logs de erro do PHP
2. Confirme as configurações do banco
3. Teste as permissões de arquivo

## Licença

Este sistema foi desenvolvido para uso pessoal e comercial.

---

**Desenvolvido com PHP, MySQL, HTML, CSS e JavaScript**

