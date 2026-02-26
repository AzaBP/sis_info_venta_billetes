<?php

require_once 'DAO/UsuarioDAO.php';
require_once 'DAO/PasajeroDAO.php';
require_once 'VO/Usuario.php';
require_once 'VO/Pasajero.php';


if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // DATOS USUARIO
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $telefono = $_POST['telefono'];
    $tipoUsuario = "pasajero";

    // DATOS PASAJERO
    $fechaNacimiento = $_POST['nacimiento'];
    $genero = $_POST['genero'];
    $tipoDocumento = $_POST['tipo_documento'];
    $numeroDocumento = $_POST['numero_documento'];
    $calle = $_POST['calle'];
    $ciudad = $_POST['ciudad'];
    $codigoPostal = $_POST['codigo_postal'];
    $pais = $_POST['pais'];
    $aceptaTerminos = isset($_POST['terminos']);
    $aceptaPrivacidad = isset($_POST['privacidad']);
    $newsletter = isset($_POST['newsletter']);

    // 1️⃣ Crear e insertar Usuario
    $usuario = new Usuario(
        null,
        $nombre,
        $apellido,
        $email,
        $password,
        $telefono,
        $tipoUsuario
    );

    $usuarioDAO = new UsuarioDAO();
    $idUsuario = $usuarioDAO->insertar($usuario);

    if (!$idUsuario) {
        echo "Error al crear el usuario";
        exit;
    }

    // Crear e insertar Pasajero
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
        echo "Registro completado correctamente";
    } else {
        echo "Error al crear el pasajero";
    }
}
?>