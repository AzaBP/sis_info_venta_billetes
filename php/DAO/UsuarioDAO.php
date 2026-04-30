<?php

require_once(__DIR__ . '/../VO/Usuario.php');
require_once(__DIR__ . '/../Conexion.php');

class UsuarioDAO {

    private $pdo;

    public function __construct() {
        $conexion = new Conexion();
        $this->pdo = $conexion->conectar();
    }

    // INSERTAR
    public function insertar(Usuario $usuario) {
        try {
            $sql = "INSERT INTO usuario 
                    (nombre, apellido, email, password, telefono, tipo_usuario) 
                    VALUES (:nombre, :apellido, :email, :password, :telefono, :tipo_usuario)
                    RETURNING id_usuario";

            $stmt = $this->pdo->prepare($sql);

            $stmt->execute([
                ':nombre' => $usuario->getNombre(),
                ':apellido' => $usuario->getApellido(),
                ':email' => $usuario->getEmail(),
                ':password' => $usuario->getPassword(),
                ':telefono' => $usuario->getTelefono(),
                ':tipo_usuario' => $usuario->getTipoUsuario()
            ]);

            return $stmt->fetchColumn();

        } catch (PDOException $e) {
            error_log('UsuarioDAO::insertar - Error al insertar: ' . $e->getMessage());
            return false;
        }
    }

    // OBTENER POR ID
    public function obtenerPorId($id) {
        try {
            $sql = "SELECT * FROM usuario WHERE id_usuario = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => $id]);

            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($resultado) {
                return new Usuario(
                    $resultado['id_usuario'],
                    $resultado['nombre'],
                    $resultado['apellido'],
                    $resultado['email'],
                    $resultado['password'],
                    $resultado['telefono'],
                    $resultado['tipo_usuario']
                );
            }

            return null;

        } catch (PDOException $e) {
            error_log('UsuarioDAO::obtenerPorId - Error: ' . $e->getMessage());
            return null;
        }
    }

    // OBTENER TODOS
    public function obtenerTodos() {
        try {
            $sql = "SELECT * FROM usuario";
            $stmt = $this->pdo->query($sql);

            $usuarios = [];

            while ($resultado = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $usuarios[] = new Usuario(
                    $resultado['id_usuario'],
                    $resultado['nombre'],
                    $resultado['apellido'],
                    $resultado['email'],
                    $resultado['password'],
                    $resultado['telefono'],
                    $resultado['tipo_usuario']
                );
            }

            return $usuarios;

        } catch (PDOException $e) {
            error_log('UsuarioDAO::obtenerTodos - Error: ' . $e->getMessage());
            return [];
        }
    }

    // ACTUALIZAR
    public function actualizar(Usuario $usuario) {
        try {
            $sql = "UPDATE usuario SET 
                    nombre = :nombre,
                    apellido = :apellido,
                    email = :email,
                    password = :password,
                    telefono = :telefono,
                    tipo_usuario = :tipo_usuario
                    WHERE id_usuario = :id";

            $stmt = $this->pdo->prepare($sql);

            return $stmt->execute([
                ':id' => $usuario->getIdUsuario(),
                ':nombre' => $usuario->getNombre(),
                ':apellido' => $usuario->getApellido(),
                ':email' => $usuario->getEmail(),
                ':password' => $usuario->getPassword(),
                ':telefono' => $usuario->getTelefono(),
                ':tipo_usuario' => $usuario->getTipoUsuario()
            ]);

        } catch (PDOException $e) {
            error_log('UsuarioDAO::actualizar - Error: ' . $e->getMessage());
            return false;
        }
    }

    // ELIMINAR
    public function eliminar($id) {
        try {
            $sql = "DELETE FROM usuario WHERE id_usuario = :id";
            $stmt = $this->pdo->prepare($sql);

            return $stmt->execute([':id' => $id]);

        } catch (PDOException $e) {
            error_log('UsuarioDAO::eliminar - Error: ' . $e->getMessage());
            return false;
        }
    }

    public function setEmailVerified($id, $valor = true) {
        try {
            $sql = "UPDATE usuario SET email_verificado = :valor WHERE id_usuario = :id";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([':valor' => $valor, ':id' => $id]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function existeEmail($email) {

        try {
            $sql = "SELECT id_usuario FROM usuario WHERE email = :email";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':email' => $email]);

            return $stmt->rowCount() > 0;

        } catch (PDOException $e) {
            return false;
        }
    }
}
?>