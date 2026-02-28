<?php

require_once __DIR__ . '/php/DAO/UsuarioDAO.php';
require_once __DIR__ . '/php/DAO/PasajeroDAO.php';
require_once __DIR__ . '/php/VO/Usuario.php';
require_once __DIR__ . '/php/VO/Pasajero.php';
require_once __DIR__ . '/php/Conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $telefono = $_POST['telefono'];
    $tipoUsuario = "pasajero";

    $fechaNacimiento = $_POST['nacimiento'];
    $genero = $_POST['genero'];
    $tipoDocumento = $_POST['tipo_documento'];
    $numeroDocumento = $_POST['numero_documento'];
    $calle = $_POST['calle'];
    $ciudad = $_POST['ciudad'];
    $codigoPostal = $_POST['codigo_postal'];
    $pais = $_POST['pais'];

    $aceptaTerminos = true;
    $aceptaPrivacidad = true;
    $newsletter = true;

    $usuarioDAO = new UsuarioDAO();

    // 🔎 Verificar si el email ya existe
    if ($usuarioDAO->existeEmail($email)) {
        header("Location: registro.html?error=usuario_existente");
        exit;
    }

    // 1️⃣ Insertar Usuario
    $usuario = new Usuario(
        null,
        $nombre,
        $apellido,
        $email,
        $password,
        $telefono,
        $tipoUsuario
    );

    $idUsuario = $usuarioDAO->insertar($usuario);

    if (!$idUsuario) {
        header("Location: registro.html?error=error_usuario");
        exit;
    }

    // 2️⃣ Insertar Pasajero
    $pasajero = new Pasajero(
        null,
        $idUsuario,
        $apellido,
        $fechaNacimiento,
        $genero,
        $tipoDocumento,
        $numeroDocumento,
        $calle,
        $ciudad,
        $codigoPostal,
        $pais,
        $aceptaTerminos,
        $aceptaPrivacidad,
        $newsletter
    );

    $pasajeroDAO = new PasajeroDAO();
    $resultado = $pasajeroDAO->insertar($pasajero);

    if ($resultado) {
        header("Location: inicio_sesion.html?registro=ok");
        exit;
    } else {
        header("Location: registro.html?error=error_pasajero");
        exit;
    }
}
?>