<?php
/**
 * Classe Database
 * Gerencia conexão com banco de dados usando PDO
 */

class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $charset;
    private $conn;
    
    public function __construct() {
        $this->host = DB_HOST;
        $this->db_name = DB_NAME;
        $this->username = DB_USER;
        $this->password = DB_PASS;
        $this->charset = DB_CHARSET;
    }
    
    /**
     * Conecta ao banco de dados
     */
    public function connect() {
        $this->conn = null;
        
        try {
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=" . $this->charset;
            $this->conn = new PDO($dsn, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            echo "Erro de conexão: " . $e->getMessage();
            die();
        }
        
        return $this->conn;
    }
    
    /**
     * Executa uma query SELECT
     */
    public function select($query, $params = []) {
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            throw new Exception("Erro na consulta: " . $e->getMessage());
        }
    }
    
    /**
     * Executa uma query SELECT retornando apenas um registro
     */
    public function selectOne($query, $params = []) {
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            return $stmt->fetch();
        } catch(PDOException $e) {
            throw new Exception("Erro na consulta: " . $e->getMessage());
        }
    }
    
    /**
     * Executa uma query INSERT, UPDATE ou DELETE
     */
    public function execute($query, $params = []) {
        try {
            $stmt = $this->conn->prepare($query);
            return $stmt->execute($params);
        } catch(PDOException $e) {
            throw new Exception("Erro na execução: " . $e->getMessage());
        }
    }
    
    /**
     * Retorna o ID do último registro inserido
     */
    public function lastInsertId() {
        return $this->conn->lastInsertId();
    }
    
    /**
     * Inicia uma transação
     */
    public function beginTransaction() {
        return $this->conn->beginTransaction();
    }
    
    /**
     * Confirma uma transação
     */
    public function commit() {
        return $this->conn->commit();
    }
    
    /**
     * Desfaz uma transação
     */
    public function rollback() {
        return $this->conn->rollback();
    }
    
    /**
     * Fecha a conexão
     */
    public function close() {
        $this->conn = null;
    }
}
?>

