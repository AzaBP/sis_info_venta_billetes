<?php
require_once __DIR__ . '/../Conexion.php';

class EmailCodeDAO {
    private $pdo;

    public function __construct() {
        $this->pdo = (new Conexion())->conectar();
    }

    public function crearCodigo($idUsuario, $email, $codigo, $tipo = 'verification', $expiresAt = null) {
        if (!$expiresAt) $expiresAt = date('Y-m-d H:i:s', time() + 60*60); // 1 hora
        $sql = "INSERT INTO email_verificaciones (id_usuario, email, codigo, tipo, usado, expires_at) VALUES (:id_usuario, :email, :codigo, :tipo, false, :expires_at) RETURNING id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id_usuario' => $idUsuario,
            ':email' => $email,
            ':codigo' => $codigo,
            ':tipo' => $tipo,
            ':expires_at' => $expiresAt
        ]);
        return $stmt->fetchColumn();
    }

    public function validarCodigo($email, $codigo, $tipo = 'verification') {
        // Normalize inputs
        $email = trim((string)$email);
        $codigo = strtoupper((string)$codigo);
        // Remove spaces and separators so copied codes still validate.
        $codigo = preg_replace('/[^A-Z0-9]/', '', $codigo);

        // Use case-insensitive match for codigo to avoid user input case issues
        $sql = "SELECT id, id_usuario, usado, expires_at FROM email_verificaciones WHERE email = :email AND REGEXP_REPLACE(UPPER(codigo), '[^A-Z0-9]', '', 'g') = :codigo AND tipo = :tipo LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':email'=>$email, ':codigo'=>$codigo, ':tipo'=>$tipo]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            error_log("EmailCodeDAO::validarCodigo - No matching code for email={$email} tipo={$tipo} codigo={$codigo}");
            return false;
        }
        if (!empty($row['usado'])) {
            error_log("EmailCodeDAO::validarCodigo - Code already used id={$row['id']}");
            return false;
        }
        if (isset($row['expires_at']) && strtotime($row['expires_at']) < time()) {
            error_log("EmailCodeDAO::validarCodigo - Code expired id={$row['id']} expires_at={$row['expires_at']}");
            return false;
        }
        return $row;
    }

    public function marcarUsado($id) {
        $sql = "UPDATE email_verificaciones SET usado = true WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':id'=>$id]);
    }
}
