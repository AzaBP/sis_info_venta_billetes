<?php
$email = $_GET['email'] ?? '';
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Restablecer contraseña</title></head>
<body>
<h2>Restablecer contraseña</h2>
<form action="procesar_reset_password.php" method="post">
    <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
    <label>Código: <input type="text" name="codigo" required></label><br>
    <label>Nueva contraseña: <input type="password" name="password" required></label><br>
    <button type="submit">Cambiar contraseña</button>
  </form>
</body>
</html>
