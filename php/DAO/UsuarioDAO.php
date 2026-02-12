<?php

require_once(__DIR__ . '/../VO/Usuario.php');
require_once(__DIR__ . '/../Conexion.php');

class UsuarioDAO {
    private $conexion;
    private $pdo;

    public function __construct() {
        $this->conexion = new Conexion();
        $this->pdo = $this->conexion->conectar();
    }

    // INSERTAR
    public function insertar(Usuario $usuario) {
        try {
            $sql = "INSERT INTO usuario (nombre, email, password, telefono, metodo_pago) 
                    VALUES (:nombre, :email, :password, :telefono, :metodo_pago)";
            $stmt = $this->pdo->prepare($sql);
            
            $stmt->bindParam(':nombre', $usuario->getNombre());
            $stmt->bindParam(':email', $usuario->getEmail());
            $stmt->bindParam(':password', $usuario->getPassword());
            $stmt->bindParam(':telefono', $usuario->getTelefono());
            $stmt->bindParam(':metodo_pago', $usuario->getMetodoPago());
            
            return $stmt->execute();
        } catch (PDOException $e) {
            echo "Error al insertar: " . $e->getMessage();
            return false;
        }
    }

    // OBTENER POR ID
    public function obtenerPorId($id) {
        try {
            $sql = "SELECT * FROM usuario WHERE id_usuario = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($resultado) {
                return new Usuario(
                    $resultado['id_usuario'],
                    $resultado['nombre'],
                    $resultado['email'],
                    $resultado['password'],
                    $resultado['telefono'],
                    $resultado['metodo_pago']
                );
            }
            return null;
        } catch (PDOException $e) {
            echo "Error al obtener: " . $e->getMessage();
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
                    $resultado['email'],
                    $resultado['password'],
                    $resultado['telefono'],
                    $resultado['metodo_pago']
                );
            }
            return $usuarios;
        } catch (PDOException $e) {
            echo "Error al obtener todos: " . $e->getMessage();
            return [];
        }
    }

    // ACTUALIZAR
    public function actualizar(Usuario $usuario) {
        try {
            $sql = "UPDATE usuario SET nombre = :nombre, email = :email, password = :password, 
                    telefono = :telefono, metodo_pago = :metodo_pago WHERE id_usuario = :id";
            $stmt = $this->pdo->prepare($sql);
            
            $stmt->bindParam(':id', $usuario->getIdUsuario());
            $stmt->bindParam(':nombre', $usuario->getNombre());
            $stmt->bindParam(':email', $usuario->getEmail());
            $stmt->bindParam(':password', $usuario->getPassword());
            $stmt->bindParam(':telefono', $usuario->getTelefono());
            $stmt->bindParam(':metodo_pago', $usuario->getMetodoPago());
            
            return $stmt->execute();
        } catch (PDOException $e) {
            echo "Error al actualizar: " . $e->getMessage();
            return false;
        }
    }

    // ELIMINAR
    public function eliminar($id) {
        try {
            $sql = "DELETE FROM usuario WHERE id_usuario = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id', $id);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            echo "Error al eliminar: " . $e->getMessage();
            return false;
        }
    }
}

?>
