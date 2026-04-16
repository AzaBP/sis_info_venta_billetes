-- Script de inserción masiva de datos para pruebas
-- Ajustado al esquema real de la BD

-- ================================================
-- 1. TIPO_ABONO (se debe insertar primero)
-- ================================================
INSERT INTO TIPO_ABONO (tipo_codigo, nombre, descripcion, precio, icono, color) VALUES
('MENSUAL_MADRID', 'Mensual Madrid', 'Viajes ilimitados en Madrid por 30 días', 89.99, 'fa-calendar', '#0066cc'),
('MENSUAL_ESPAÑA', 'Mensual España', 'Viajes ilimitados en toda España por 30 días', 199.99, 'fa-map', '#009966'),
('TRIMESTRAL', 'Trimestral', 'Viajes ilimitados por 90 días', 449.99, 'fa-star', '#ff6600'),
('ANUAL', 'Anual', 'Viajes ilimitados por 365 días', 1299.99, 'fa-crown', '#cc0000'),
('10_VIAJES', '10 viajes', '10 viajes válidos por 60 días', 499.99, 'fa-ticket', '#0066cc'),
('20_VIAJES', '20 viajes', '20 viajes válidos por 90 días', 899.99, 'fa-tickets', '#0066cc'),
('WEEKEND', 'Fin de semana', 'Viajes ilimitados viernes a domingo', 79.99, 'fa-heart', '#ff0066'),
('ESTUDIANTE', 'Estudiante', 'Abono estudiante por 90 días', 199.99, 'fa-graduation-cap', '#0066cc');

-- ================================================
-- 2. USUARIOS (varios tipos)
-- ================================================
INSERT INTO USUARIO (nombre, apellido, email, password, telefono, tipo_usuario) VALUES
('Juan', 'García', 'juan@trenes.com', 'pass123', '600111111', 'pasajero'),
('María', 'López', 'maria@trenes.com', 'pass123', '600222222', 'pasajero'),
('Carlos', 'Rodríguez', 'carlos@trenes.com', 'pass123', '600333333', 'pasajero'),
('Ana', 'Martínez', 'ana@trenes.com', 'pass123', '600444444', 'pasajero'),
('Luis', 'Fernández', 'luis@trenes.com', 'pass123', '600555555', 'empleado'),
('Laura', 'Torres', 'laura@trenes.com', 'pass123', '600666666', 'empleado'),
('Diego', 'Ruiz', 'diego@trenes.com', 'pass123', '600777777', 'empleado'),
('Isabel', 'Sánchez', 'isabel@trenes.com', 'pass123', '600888888', 'empleado'),
('Roberto', 'Pérez', 'roberto@trenes.com', 'pass123', '600999999', 'empleado'),
('Sandra', 'Campos', 'sandra@trenes.com', 'pass123', '601000000', 'empleado');

-- ================================================
-- 3. PASAJEROS
-- ================================================
INSERT INTO PASAJERO (id_usuario, fecha_nacimiento, genero, tipo_documento, numero_documento, calle, ciudad, codigo_postal, pais, metodo_pago, acepta_terminos, acepta_privacidad, newsletter) VALUES
((SELECT id_usuario FROM USUARIO WHERE email = 'juan@trenes.com'), '1988-05-15', 'masculino', 'dni', '12345678A', 'Calle Principal 1', 'Madrid', '28001', 'España', 'tarjeta', true, true, true),
((SELECT id_usuario FROM USUARIO WHERE email = 'maria@trenes.com'), '1995-03-22', 'femenino', 'dni', '87654321B', 'Avenida Central 2', 'Barcelona', '08002', 'España', 'tarjeta', true, true, false),
((SELECT id_usuario FROM USUARIO WHERE email = 'carlos@trenes.com'), '1982-07-10', 'masculino', 'dni', '11223344C', 'Plaza Mayor 3', 'Valencia', '46001', 'España', 'paypal', true, true, true),
((SELECT id_usuario FROM USUARIO WHERE email = 'ana@trenes.com'), '1990-11-08', 'femenino', 'nie', '44332211D', 'Carrera 4', 'Sevilla', '41001', 'España', 'tarjeta', true, false, false);

-- ================================================
-- 4. EMPLEADOS (base para vendedor, maquinista, mantenimiento)
-- ================================================
INSERT INTO EMPLEADO (id_usuario, tipo_empleado) VALUES
((SELECT id_usuario FROM USUARIO WHERE email = 'luis@trenes.com'), 'vendedor'),
((SELECT id_usuario FROM USUARIO WHERE email = 'laura@trenes.com'), 'maquinista'),
((SELECT id_usuario FROM USUARIO WHERE email = 'diego@trenes.com'), 'maquinista'),
((SELECT id_usuario FROM USUARIO WHERE email = 'isabel@trenes.com'), 'mantenimiento'),
((SELECT id_usuario FROM USUARIO WHERE email = 'roberto@trenes.com'), 'mantenimiento'),
((SELECT id_usuario FROM USUARIO WHERE email = 'sandra@trenes.com'), 'vendedor');

-- ================================================
-- 5. VENDEDORES (subtabla de EMPLEADO)
-- ================================================
INSERT INTO VENDEDOR (id_empleado, comision_porcentaje, region) VALUES
((SELECT id_empleado FROM EMPLEADO WHERE id_usuario = (SELECT id_usuario FROM USUARIO WHERE email = 'luis@trenes.com')), 5.0, 'Centro'),
((SELECT id_empleado FROM EMPLEADO WHERE id_usuario = (SELECT id_usuario FROM USUARIO WHERE email = 'sandra@trenes.com')), 4.5, 'Sur');

-- ================================================
-- 6. MAQUINISTAS (subtabla de EMPLEADO)
-- ================================================
INSERT INTO MAQUINISTA (id_empleado, licencia, experiencia_años, horario_preferido) VALUES
((SELECT id_empleado FROM EMPLEADO WHERE id_usuario = (SELECT id_usuario FROM USUARIO WHERE email = 'laura@trenes.com')), 'A1', 10, 'Diurno'),
((SELECT id_empleado FROM EMPLEADO WHERE id_usuario = (SELECT id_usuario FROM USUARIO WHERE email = 'diego@trenes.com')), 'A2', 8, 'Nocturno');

-- ================================================
-- 7. MANTENIMIENTO (subtabla de EMPLEADO)
-- ================================================
INSERT INTO MANTENIMIENTO (id_empleado, especialidad, turno, certificaciones) VALUES
((SELECT id_empleado FROM EMPLEADO WHERE id_usuario = (SELECT id_usuario FROM USUARIO WHERE email = 'isabel@trenes.com')), 'Sistema Eléctrico', 'Mañana', 'ISO-9001'),
((SELECT id_empleado FROM EMPLEADO WHERE id_usuario = (SELECT id_usuario FROM USUARIO WHERE email = 'roberto@trenes.com')), 'Mecánica', 'Tarde', 'ISO-9001,ISO-45001');

-- ================================================
-- 8. RUTAS
-- ================================================
INSERT INTO RUTA (origen, destino, duracion, id_vendedor) VALUES
('Madrid Puerta de Atocha', 'Barcelona Sants', '02:30:00', (SELECT id_empleado FROM VENDEDOR LIMIT 1)),
('Madrid Puerta de Atocha', 'Valencia Joaquín Sorolla', '01:50:00', (SELECT id_empleado FROM VENDEDOR LIMIT 1)),
('Barcelona Sants', 'Sevilla Santa Justa', '03:00:00', (SELECT id_empleado FROM VENDEDOR ORDER BY id_empleado DESC LIMIT 1)),
('Valencia Joaquín Sorolla', 'Alicante Término', '01:00:00', (SELECT id_empleado FROM VENDEDOR LIMIT 1)),
('Madrid Puerta de Atocha', 'Bilbao Abando', '02:30:00', (SELECT id_empleado FROM VENDEDOR LIMIT 1)),
('Barcelona Sants', 'Valencia Joaquín Sorolla', '01:30:00', (SELECT id_empleado FROM VENDEDOR ORDER BY id_empleado DESC LIMIT 1)),
('Sevilla Santa Justa', 'Córdoba Central', '01:00:00', (SELECT id_empleado FROM VENDEDOR LIMIT 1)),
('Madrid Puerta de Atocha', 'Toledo Estación', '00:45:00', (SELECT id_empleado FROM VENDEDOR LIMIT 1)),
('Bilbao Abando', 'San Sebastián Donostia', '01:30:00', (SELECT id_empleado FROM VENDEDOR ORDER BY id_empleado DESC LIMIT 1)),
('Barcelona Sants', 'Girona Estación', '01:00:00', (SELECT id_empleado FROM VENDEDOR LIMIT 1)),
('Madrid Puerta de Atocha', 'Segovia-Guadarrama', '01:00:00', (SELECT id_empleado FROM VENDEDOR LIMIT 1)),
('Valencia Joaquín Sorolla', 'Tarragona Central', '01:30:00', (SELECT id_empleado FROM VENDEDOR ORDER BY id_empleado DESC LIMIT 1)),
('Sevilla Santa Justa', 'Cádiz Término', '02:00:00', (SELECT id_empleado FROM VENDEDOR LIMIT 1)),
('Valencia Joaquín Sorolla', 'Cuenca Fernando Zóbel', '01:30:00', (SELECT id_empleado FROM VENDEDOR LIMIT 1)),
('Madrid Puerta de Atocha', 'Guadalajara Central', '00:40:00', (SELECT id_empleado FROM VENDEDOR ORDER BY id_empleado DESC LIMIT 1));

-- ================================================
-- 9. TRENES
-- ================================================
INSERT INTO TREN (modelo, capacidad) VALUES
('AVE-103', 450),
('AVLO', 400),
('ALVIA', 350),
('AVE S-103', 500),
('Talgo-200', 320),
('Avant-Siemens', 550),
('Talgo-360', 380),
('Eurostar', 450),
('MD-300', 300),
('OARIS', 520),
('AVE-R', 490),
('Talgo-250', 360);

-- ================================================
-- 10. VIAJES
-- ================================================
INSERT INTO VIAJE (id_vendedor, id_ruta, id_tren, id_maquinista, fecha, hora_salida, hora_llegada, precio, estado) VALUES
-- Viajes Madrid → Barcelona
((SELECT id_empleado FROM VENDEDOR LIMIT 1), (SELECT id_ruta FROM RUTA WHERE origen = 'Madrid Puerta de Atocha' AND destino = 'Barcelona Sants' LIMIT 1), 1, (SELECT id_empleado FROM MAQUINISTA LIMIT 1), '2026-04-20', '08:00:00', '10:30:00', 89.99, 'programado'),
((SELECT id_empleado FROM VENDEDOR LIMIT 1), (SELECT id_ruta FROM RUTA WHERE origen = 'Madrid Puerta de Atocha' AND destino = 'Barcelona Sants' LIMIT 1), 2, (SELECT id_empleado FROM MAQUINISTA LIMIT 1), '2026-04-20', '10:30:00', '13:00:00', 65.99, 'programado'),
((SELECT id_empleado FROM VENDEDOR LIMIT 1), (SELECT id_ruta FROM RUTA WHERE origen = 'Madrid Puerta de Atocha' AND destino = 'Barcelona Sants' LIMIT 1), 3, (SELECT id_empleado FROM MAQUINISTA ORDER BY id_empleado DESC LIMIT 1), '2026-04-20', '14:00:00', '16:30:00', 75.99, 'programado'),
-- Viajes Madrid → Valencia
((SELECT id_empleado FROM VENDEDOR LIMIT 1), (SELECT id_ruta FROM RUTA WHERE origen = 'Madrid Puerta de Atocha' AND destino = 'Valencia Joaquín Sorolla' LIMIT 1), 4, (SELECT id_empleado FROM MAQUINISTA LIMIT 1), '2026-04-20', '09:00:00', '10:50:00', 49.99, 'programado'),
((SELECT id_empleado FROM VENDEDOR LIMIT 1), (SELECT id_ruta FROM RUTA WHERE origen = 'Madrid Puerta de Atocha' AND destino = 'Valencia Joaquín Sorolla' LIMIT 1), 5, (SELECT id_empleado FROM MAQUINISTA ORDER BY id_empleado DESC LIMIT 1), '2026-04-20', '13:00:00', '14:50:00', 49.99, 'programado'),
-- Viajes Barcelona → Sevilla
((SELECT id_empleado FROM VENDEDOR ORDER BY id_empleado DESC LIMIT 1), (SELECT id_ruta FROM RUTA WHERE origen = 'Barcelona Sants' AND destino = 'Sevilla Santa Justa' LIMIT 1), 6, (SELECT id_empleado FROM MAQUINISTA LIMIT 1), '2026-04-21', '07:00:00', '10:00:00', 129.99, 'programado'),
((SELECT id_empleado FROM VENDEDOR ORDER BY id_empleado DESC LIMIT 1), (SELECT id_ruta FROM RUTA WHERE origen = 'Barcelona Sants' AND destino = 'Sevilla Santa Justa' LIMIT 1), 1, (SELECT id_empleado FROM MAQUINISTA ORDER BY id_empleado DESC LIMIT 1), '2026-04-21', '15:00:00', '18:00:00', 135.99, 'programado'),
-- Viajes Valencia → Alicante
((SELECT id_empleado FROM VENDEDOR LIMIT 1), (SELECT id_ruta FROM RUTA WHERE origen = 'Valencia Joaquín Sorolla' AND destino = 'Alicante Término' LIMIT 1), 2, (SELECT id_empleado FROM MAQUINISTA LIMIT 1), '2026-04-21', '08:30:00', '09:30:00', 35.99, 'programado'),
((SELECT id_empleado FROM VENDEDOR LIMIT 1), (SELECT id_ruta FROM RUTA WHERE origen = 'Valencia Joaquín Sorolla' AND destino = 'Alicante Término' LIMIT 1), 3, (SELECT id_empleado FROM MAQUINISTA ORDER BY id_empleado DESC LIMIT 1), '2026-04-21', '16:00:00', '17:00:00', 39.99, 'programado'),
-- Viajes Madrid → Bilbao
((SELECT id_empleado FROM VENDEDOR LIMIT 1), (SELECT id_ruta FROM RUTA WHERE origen = 'Madrid Puerta de Atocha' AND destino = 'Bilbao Abando' LIMIT 1), 4, (SELECT id_empleado FROM MAQUINISTA LIMIT 1), '2026-04-21', '06:00:00', '08:30:00', 79.99, 'programado'),
((SELECT id_empleado FROM VENDEDOR LIMIT 1), (SELECT id_ruta FROM RUTA WHERE origen = 'Madrid Puerta de Atocha' AND destino = 'Bilbao Abando' LIMIT 1), 5, (SELECT id_empleado FROM MAQUINISTA ORDER BY id_empleado DESC LIMIT 1), '2026-04-21', '12:00:00', '14:30:00', 79.99, 'programado'),
-- Viajes Barcelona → Valencia
((SELECT id_empleado FROM VENDEDOR ORDER BY id_empleado DESC LIMIT 1), (SELECT id_ruta FROM RUTA WHERE origen = 'Barcelona Sants' AND destino = 'Valencia Joaquín Sorolla' LIMIT 1), 6, (SELECT id_empleado FROM MAQUINISTA LIMIT 1), '2026-04-22', '09:00:00', '10:30:00', 45.99, 'programado'),
((SELECT id_empleado FROM VENDEDOR ORDER BY id_empleado DESC LIMIT 1), (SELECT id_ruta FROM RUTA WHERE origen = 'Barcelona Sants' AND destino = 'Valencia Joaquín Sorolla' LIMIT 1), 7, (SELECT id_empleado FROM MAQUINISTA ORDER BY id_empleado DESC LIMIT 1), '2026-04-22', '14:00:00', '15:30:00', 49.99, 'programado'),
-- Viajes Sevilla → Córdoba
((SELECT id_empleado FROM VENDEDOR LIMIT 1), (SELECT id_ruta FROM RUTA WHERE origen = 'Sevilla Santa Justa' AND destino = 'Córdoba Central' LIMIT 1), 8, (SELECT id_empleado FROM MAQUINISTA LIMIT 1), '2026-04-22', '07:30:00', '08:30:00', 39.99, 'programado'),
-- Viajes Madrid → Toledo
((SELECT id_empleado FROM VENDEDOR LIMIT 1), (SELECT id_ruta FROM RUTA WHERE origen = 'Madrid Puerta de Atocha' AND destino = 'Toledo Estación' LIMIT 1), 1, (SELECT id_empleado FROM MAQUINISTA LIMIT 1), '2026-04-22', '10:00:00', '10:45:00', 25.99, 'programado'),
-- Viajes Bilbao → San Sebastián
((SELECT id_empleado FROM VENDEDOR ORDER BY id_empleado DESC LIMIT 1), (SELECT id_ruta FROM RUTA WHERE origen = 'Bilbao Abando' AND destino = 'San Sebastián Donostia' LIMIT 1), 2, (SELECT id_empleado FROM MAQUINISTA LIMIT 1), '2026-04-22', '10:30:00', '12:00:00', 29.99, 'programado'),
-- Viajes Barcelona → Girona
((SELECT id_empleado FROM VENDEDOR LIMIT 1), (SELECT id_ruta FROM RUTA WHERE origen = 'Barcelona Sants' AND destino = 'Girona Estación' LIMIT 1), 3, (SELECT id_empleado FROM MAQUINISTA ORDER BY id_empleado DESC LIMIT 1), '2026-04-22', '11:00:00', '12:00:00', 35.99, 'programado'),
-- Viajes Madrid → Segovia
((SELECT id_empleado FROM VENDEDOR LIMIT 1), (SELECT id_ruta FROM RUTA WHERE origen = 'Madrid Puerta de Atocha' AND destino = 'Segovia-Guadarrama' LIMIT 1), 4, (SELECT id_empleado FROM MAQUINISTA LIMIT 1), '2026-04-22', '13:00:00', '14:00:00', 29.99, 'programado'),
-- Viajes Valencia → Tarragona
((SELECT id_empleado FROM VENDEDOR ORDER BY id_empleado DESC LIMIT 1), (SELECT id_ruta FROM RUTA WHERE origen = 'Valencia Joaquín Sorolla' AND destino = 'Tarragona Central' LIMIT 1), 5, (SELECT id_empleado FROM MAQUINISTA LIMIT 1), '2026-04-22', '15:00:00', '16:30:00', 35.99, 'programado'),
-- Viajes Sevilla → Cádiz
((SELECT id_empleado FROM VENDEDOR LIMIT 1), (SELECT id_ruta FROM RUTA WHERE origen = 'Sevilla Santa Justa' AND destino = 'Cádiz Término' LIMIT 1), 6, (SELECT id_empleado FROM MAQUINISTA ORDER BY id_empleado DESC LIMIT 1), '2026-04-23', '08:00:00', '10:00:00', 99.99, 'programado'),
-- Viajes Valencia → Cuenca
((SELECT id_empleado FROM VENDEDOR ORDER BY id_empleado DESC LIMIT 1), (SELECT id_ruta FROM RUTA WHERE origen = 'Valencia Joaquín Sorolla' AND destino = 'Cuenca Fernando Zóbel' LIMIT 1), 7, (SELECT id_empleado FROM MAQUINISTA LIMIT 1), '2026-04-23', '09:30:00', '11:00:00', 45.99, 'programado'),
-- Viajes Madrid → Guadalajara
((SELECT id_empleado FROM VENDEDOR ORDER BY id_empleado DESC LIMIT 1), (SELECT id_ruta FROM RUTA WHERE origen = 'Madrid Puerta de Atocha' AND destino = 'Guadalajara Central' LIMIT 1), 8, (SELECT id_empleado FROM MAQUINISTA ORDER BY id_empleado DESC LIMIT 1), '2026-04-23', '14:00:00', '14:40:00', 35.99, 'programado');

-- ================================================
-- 11. ASIENTOS (para cada viaje según capacidad del tren)
-- ================================================
INSERT INTO ASIENTO (id_tren, numero_asiento, clase, estado)
SELECT
	t.id_tren,
	((t.id_tren - 1) * 100) + s.n AS numero_asiento,
	CASE WHEN s.n <= 10 THEN 'primera' ELSE 'segunda' END,
	'disponible'
FROM TREN t
CROSS JOIN generate_series(1, 100) AS s(n)
ON CONFLICT (numero_asiento) DO NOTHING;

-- ================================================
-- 12. ABONOS DE USUARIO
-- ================================================
-- En esta BD solo se usa el catálogo TIPO_ABONO para ofertas.
-- No se insertan compras en una tabla ABONO.

-- ================================================
-- 13. PROMOCIONES
-- ================================================
INSERT INTO PROMOCION (codigo, descuento_porcentaje, fecha_inicio, fecha_fin, usos_maximos, usos_actuales) VALUES
('PROMO20', 20.00, '2026-04-01', '2026-04-30', 1000, 45),
('VIAJE10', 0.00, '2026-04-10', '2026-12-31', 500, 23),
('ESTUDIANTE15', 15.00, '2026-04-01', '2026-06-30', 2000, 120),
('REPATRIADO25', 25.00, '2026-04-15', '2026-05-31', 300, 8),
('BIENVENIDA30', 30.00, '2026-01-01', '2026-12-31', 500, 45),
('VERANO50', 50.00, '2026-06-01', '2026-08-31', 1000, 0),
('BLACKFRIDAY', 40.00, '2026-11-24', '2026-11-30', 2000, 0);

-- ================================================
-- 14. INCIDENCIAS
-- ================================================
INSERT INTO INCIDENCIA (id_viaje, id_mantenimiento, id_maquinista, tipo_incidencia, origen, descripcion, fecha_reporte, estado, afecta_pasajero, resolucion, fecha_resolucion) VALUES
((SELECT id_viaje FROM VIAJE LIMIT 1), (SELECT id_empleado FROM MANTENIMIENTO LIMIT 1), (SELECT id_empleado FROM MAQUINISTA LIMIT 1), 'Retraso', 'maquinista', 'Tren llegó con 45 minutos de retraso', NOW() - INTERVAL '10 days', 'resuelto', true, 'Se compensó con bono de 15 euros', NOW() - INTERVAL '5 days'),
((SELECT id_viaje FROM VIAJE LIMIT 1 OFFSET 1), (SELECT id_empleado FROM MANTENIMIENTO LIMIT 1), (SELECT id_empleado FROM MAQUINISTA LIMIT 1), 'Avería', 'iot', 'Fallo en sistema de aire acondicionado', NOW() - INTERVAL '8 days', 'resuelto', true, 'Se reparó el sistema', NOW() - INTERVAL '3 days'),
((SELECT id_viaje FROM VIAJE LIMIT 1 OFFSET 2), (SELECT id_empleado FROM MANTENIMIENTO LIMIT 1 OFFSET 1), (SELECT id_empleado FROM MAQUINISTA ORDER BY id_empleado DESC LIMIT 1), 'Limpieza', 'maquinista', 'Vagón sucio al inicio de viaje', NOW() - INTERVAL '6 days', 'resuelto', true, 'Se limpió completo', NOW() - INTERVAL '2 days'),
((SELECT id_viaje FROM VIAJE LIMIT 1 OFFSET 3), (SELECT id_empleado FROM MANTENIMIENTO LIMIT 1), (SELECT id_empleado FROM MAQUINISTA ORDER BY id_empleado DESC LIMIT 1), 'Otro', 'maquinista', 'Ruido inusual en Sistema de Tracción', NOW() - INTERVAL '5 days', 'en_proceso', false, NULL, NULL),
((SELECT id_viaje FROM VIAJE LIMIT 1 OFFSET 4), (SELECT id_empleado FROM MANTENIMIENTO LIMIT 1 OFFSET 1), (SELECT id_empleado FROM MAQUINISTA LIMIT 1), 'Fallo Eléctrico', 'iot', 'Pérdida de conexión con catenaria', NOW() - INTERVAL '3 days', 'en_proceso', true, NULL, NULL);

-- ================================================
-- RESUMEN FINAL
-- ================================================
SELECT 'USUARIOS' as tabla, COUNT(*) as total FROM USUARIO
UNION ALL
SELECT 'PASAJEROS', COUNT(*) FROM PASAJERO
UNION ALL
SELECT 'EMPLEADOS', COUNT(*) FROM EMPLEADO
UNION ALL
SELECT 'VENDEDORES', COUNT(*) FROM VENDEDOR
UNION ALL
SELECT 'MAQUINISTAS', COUNT(*) FROM MAQUINISTA
UNION ALL
SELECT 'MANTENIMIENTO', COUNT(*) FROM MANTENIMIENTO
UNION ALL
SELECT 'RUTAS', COUNT(*) FROM RUTA
UNION ALL
SELECT 'TRENES', COUNT(*) FROM TREN
UNION ALL
SELECT 'VIAJES', COUNT(*) FROM VIAJE
UNION ALL
SELECT 'ASIENTOS', COUNT(*) FROM ASIENTO
UNION ALL
SELECT 'PROMOCIONES', COUNT(*) FROM PROMOCION
UNION ALL
SELECT 'INCIDENCIAS', COUNT(*) FROM INCIDENCIA
UNION ALL
SELECT 'TIPO_ABONO', COUNT(*) FROM TIPO_ABONO;
