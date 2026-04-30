<?php
// Formulario simple para introducir código de verificación
$email = $_GET['email'] ?? '';
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Verificar email</title></head>
<body>
<h2>Verificar tu correo</h2>
<p>Hemos enviado un código a <strong><?php echo htmlspecialchars($email); ?></strong>. Introduce el código:</p>
<form action="procesar_verificacion_email.php" method="post">
    <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
    <label>Código: <input type="text" name="codigo" required></label>
    <button type="submit">Verificar</button>
    <p><a href="inicio_sesion.html">Volver</a></p>
  </form>
</body>
</html>
