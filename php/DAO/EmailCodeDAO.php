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
        $sql = "SELECT id, id_usuario, usado, expires_at FROM email_verificaciones WHERE email = :email AND codigo = :codigo AND tipo = :tipo LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':email'=>$email, ':codigo'=>$codigo, ':tipo'=>$tipo]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return false;
        if ($row['usado']) return false;
        if (strtotime($row['expires_at']) < time()) return false;
        return $row;
    }

    public function marcarUsado($id) {
        $sql = "UPDATE email_verificaciones SET usado = true WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':id'=>$id]);
    }
}
