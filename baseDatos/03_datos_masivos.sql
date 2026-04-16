-- Script de inserción masiva de datos para pruebas
-- Tabla: USUARIO (base para otros tipos de usuarios)
INSERT INTO usuario (nombre, email, contraseña, telefono, fecha_creacion) VALUES
('Juan García', 'juan@trenes.com', 'pass123', '600111111', NOW()),
('María López', 'maria@trenes.com', 'pass123', '600222222', NOW()),
('Carlos Rodríguez', 'carlos@trenes.com', 'pass123', '600333333', NOW()),
('Ana Martínez', 'ana@trenes.com', 'pass123', '600444444', NOW()),
('Pedro Sánchez', 'pedro@trenes.com', 'pass123', '600555555', NOW()),
('Laura Fernández', 'laura@trenes.com', 'pass123', '600666666', NOW()),
('Diego Torres', 'diego@trenes.com', 'pass123', '600777777', NOW()),
('Isabel Ruiz', 'isabel@trenes.com', 'pass123', '600888888', NOW());

-- Tabla: PASAJERO
INSERT INTO pasajero (id_usuario, edad, dni, direccion) VALUES
(1, 35, '12345678A', 'Calle Principal 1, Madrid'),
(2, 28, '87654321B', 'Avenida Central 2, Barcelona'),
(3, 42, '11223344C', 'Plaza Mayor 3, Valencia'),
(4, 31, '44332211D', 'Carrera 4, Sevilla');

-- Tabla: EMPLEADO
INSERT INTO empleado (id_usuario, puesto, departamento, salario) VALUES
(5, 'Gerente', 'Administración', 2500.00),
(6, 'Técnico', 'Mantenimiento', 2000.00),
(7, 'Asistente', 'Atención al Cliente', 1800.00),
(8, 'Inspector', 'Operaciones', 2100.00);

-- Tabla: VENDEDOR (ya exist), solo asegurar que hay más
INSERT INTO vendedor (id_usuario, comision) VALUES
(5, 0.05);

-- Tabla: MAQUINISTA (ya existe), solo asegurar que hay más
INSERT INTO maquinista (id_usuario, numero_licencia, fecha_vencimiento_licencia) VALUES
(6, 'LIC001', '2025-12-31');

-- Tabla: RUTA
INSERT INTO ruta (origen, destino, distancia_km, tiempo_estimado_minutos) VALUES
('Madrid', 'Barcelona', 640, 480),
('Madrid', 'Valencia', 360, 240),
('Barcelona', 'Sevilla', 1286, 960),
('Valencia', 'Alicante', 160, 100),
('Madrid', 'Bilbao', 400, 280),
('Barcelona', 'Valencia', 360, 240),
('Sevilla', 'Córdoba', 160, 120),
('Madrid', 'Toledo', 80, 60),
('Bilbao', 'San Sebastián', 100, 70),
('Valencia', 'Tarragona', 270, 180),
('Madrid', 'Segovia', 120, 90),
('Barcelona', 'Girona', 100, 70),
('Sevilla', 'Cádiz', 260, 180),
('Valencia', 'Cuenca', 200, 150),
('Madrid', 'Guadalajara', 80, 50);

-- Tabla: TREN
INSERT INTO tren (marca, modelo, ano_fabricacion, capacidad_total, numero_vagones) VALUES
('Renfe', 'AVE-103', 2018, 450, 9),
('Renfe', 'AVLO-100', 2019, 400, 8),
('Talgo', 'Talgo-200', 2015, 350, 7),
('Renfe', 'Avant-Siemens', 2020, 500, 10),
('CAF', 'Avant-100', 2017, 550, 10),
('Renfe', 'AVE-S-103', 2021, 480, 9),
('Talgo', 'Talgo-360', 2016, 380, 8),
('SiE', 'Eurostar', 2019, 450, 9),
('Renfe', 'MD-300', 2014, 300, 6),
('CAF', 'Oaris', 2018, 520, 10),
('Renfe', 'AVE-R', 2022, 490, 9),
('Talgo', 'Talgo-250', 2017, 360, 7);

-- Tabla: VIAJE
INSERT INTO viaje (id_ruta, id_tren, id_maquinista, fecha_salida, hora_salida, precio_base, asientos_disponibles) VALUES
(1, 1, 1, '2025-04-20', '08:00:00', 89.99, 450),
(1, 1, 1, '2025-04-20', '10:30:00', 89.99, 450),
(1, 2, 1, '2025-04-20', '14:00:00', 65.99, 400),
(1, 1, 1, '2025-04-20', '18:00:00', 99.99, 450),
(2, 3, 1, '2025-04-20', '09:00:00', 49.99, 350),
(2, 4, 1, '2025-04-20', '13:00:00', 49.99, 500),
(3, 5, 1, '2025-04-21', '07:00:00', 129.99, 550),
(3, 6, 1, '2025-04-21', '15:00:00', 135.99, 480),
(4, 7, 1, '2025-04-21', '08:30:00', 35.99, 380),
(4, 2, 1, '2025-04-21', '16:00:00', 39.99, 400),
(5, 8, 1, '2025-04-21', '06:00:00', 79.99, 450),
(5, 9, 1, '2025-04-21', '12:00:00', 79.99, 300),
(6, 10, 1, '2025-04-22', '09:00:00', 45.99, 520),
(6, 1, 1, '2025-04-22', '14:00:00', 49.99, 450),
(7, 11, 1, '2025-04-22', '07:30:00', 39.99, 490),
(8, 3, 1, '2025-04-22', '10:00:00', 25.99, 350),
(9, 12, 1, '2025-04-22', '10:30:00', 29.99, 360),
(10, 2, 1, '2025-04-22', '11:00:00', 35.99, 400),
(11, 4, 1, '2025-04-22', '13:00:00', 29.99, 500),
(12, 5, 1, '2025-04-22', '15:00:00', 35.99, 550),
(1, 6, 1, '2025-04-23', '08:00:00', 89.99, 480),
(2, 7, 1, '2025-04-23', '09:30:00', 49.99, 380),
(3, 8, 1, '2025-04-23', '07:00:00', 129.99, 450),
(4, 9, 1, '2025-04-23', '08:00:00', 35.99, 300),
(5, 10, 1, '2025-04-23', '06:00:00', 79.99, 520);

-- Tabla: ASIENTO
-- Para cada viaje (25 viajes), crear asientos según capacidad del tren
-- Viajes 1-4: 450, 450, 400, 450 (AVE, AVE, AVLO, AVE)
INSERT INTO asiento (id_viaje, numero_asiento, clase, estado) 
SELECT 1, GENERATE_SERIES(1, 450), 'Turista', 'Disponible'
UNION ALL
SELECT 2, GENERATE_SERIES(1, 450), 'Turista', 'Disponible'
UNION ALL
SELECT 3, GENERATE_SERIES(1, 400), 'Turista', 'Disponible'
UNION ALL
SELECT 4, GENERATE_SERIES(1, 450), 'Turista', 'Disponible'
UNION ALL
SELECT 5, GENERATE_SERIES(1, 350), 'Turista', 'Disponible'
UNION ALL
SELECT 6, GENERATE_SERIES(1, 500), 'Turista', 'Disponible'
UNION ALL
SELECT 7, GENERATE_SERIES(1, 550), 'Turista', 'Disponible'
UNION ALL
SELECT 8, GENERATE_SERIES(1, 480), 'Turista', 'Disponible'
UNION ALL
SELECT 9, GENERATE_SERIES(1, 380), 'Turista', 'Disponible'
UNION ALL
SELECT 10, GENERATE_SERIES(1, 400), 'Turista', 'Disponible'
UNION ALL
SELECT 11, GENERATE_SERIES(1, 450), 'Turista', 'Disponible'
UNION ALL
SELECT 12, GENERATE_SERIES(1, 300), 'Turista', 'Disponible'
UNION ALL
SELECT 13, GENERATE_SERIES(1, 520), 'Turista', 'Disponible'
UNION ALL
SELECT 14, GENERATE_SERIES(1, 450), 'Turista', 'Disponible'
UNION ALL
SELECT 15, GENERATE_SERIES(1, 490), 'Turista', 'Disponible'
UNION ALL
SELECT 16, GENERATE_SERIES(1, 350), 'Turista', 'Disponible'
UNION ALL
SELECT 17, GENERATE_SERIES(1, 360), 'Turista', 'Disponible'
UNION ALL
SELECT 18, GENERATE_SERIES(1, 400), 'Turista', 'Disponible'
UNION ALL
SELECT 19, GENERATE_SERIES(1, 500), 'Turista', 'Disponible'
UNION ALL
SELECT 20, GENERATE_SERIES(1, 550), 'Turista', 'Disponible'
UNION ALL
SELECT 21, GENERATE_SERIES(1, 480), 'Turista', 'Disponible'
UNION ALL
SELECT 22, GENERATE_SERIES(1, 380), 'Turista', 'Disponible'
UNION ALL
SELECT 23, GENERATE_SERIES(1, 450), 'Turista', 'Disponible'
UNION ALL
SELECT 24, GENERATE_SERIES(1, 300), 'Turista', 'Disponible'
UNION ALL
SELECT 25, GENERATE_SERIES(1, 520), 'Turista', 'Disponible';

-- Tabla: TIPO_ABONO
INSERT INTO tipo_abono (nombre, descripcion, duracion_dias, precio) VALUES
('Mensual Madrid', 'Viajes ilimitados en Madrid por 30 días', 30, 89.99),
('Mensual España', 'Viajes ilimitados en toda España por 30 días', 30, 199.99),
('Trimestral', 'Viajes ilimitados por 90 días', 90, 449.99),
('Anual', 'Viajes ilimitados por 365 días', 365, 1299.99),
('10 viajes', '10 viajes válidos por 60 días', 60, 499.99),
('20 viajes', '20 viajes válidos por 90 días', 90, 899.99),
('Fin de semana', 'Viajes ilimitados viernes a domingo', 7, 79.99),
('Estudiante 3 meses', 'Abono estudiante por 90 días', 90, 199.99);

-- Tabla: BILLETE
INSERT INTO billete (id_pasajero, id_viaje, id_asiento, fecha_compra, precio_pagado, estado) VALUES
(1, 1, 1, NOW() - INTERVAL '5 days', 89.99, 'Activo'),
(1, 1, 2, NOW() - INTERVAL '5 days', 89.99, 'Activo'),
(2, 1, 3, NOW() - INTERVAL '4 days', 89.99, 'Activo'),
(2, 2, 4, NOW() - INTERVAL '4 days', 89.99, 'Activo'),
(3, 2, 5, NOW() - INTERVAL '3 days', 89.99, 'Activo'),
(3, 3, 6, NOW() - INTERVAL '3 days', 65.99, 'Activo'),
(4, 3, 7, NOW() - INTERVAL '2 days', 65.99, 'Activo'),
(1, 4, 8, NOW() - INTERVAL '2 days', 99.99, 'Activo'),
(2, 5, 9, NOW() - INTERVAL '1 day', 49.99, 'Activo'),
(3, 6, 10, NOW() - INTERVAL '1 day', 49.99, 'Activo'),
(4, 7, 11, NOW(), 129.99, 'Reservado'),
(1, 8, 12, NOW(), 135.99, 'Reservado'),
(2, 9, 13, NOW(), 35.99, 'Reservado'),
(3, 10, 14, NOW(), 39.99, 'Reservado'),
(4, 11, 15, NOW(), 79.99, 'Reservado'),
(1, 12, 16, NOW(), 79.99, 'Reservado'),
(2, 13, 17, NOW(), 45.99, 'Reservado'),
(3, 14, 18, NOW(), 49.99, 'Reservado'),
(4, 15, 19, NOW(), 39.99, 'Reservado'),
(1, 16, 20, NOW(), 25.99, 'Reservado');

-- Tabla: ABONO
INSERT INTO abono (id_pasajero, id_tipo_abono, fecha_compra, fecha_vencimiento, usos_restantes, estado) VALUES
(1, 1, NOW() - INTERVAL '20 days', NOW() + INTERVAL '10 days', 15, 'Activo'),
(2, 2, NOW() - INTERVAL '25 days', NOW() + INTERVAL '5 days', 25, 'Activo'),
(3, 3, NOW() - INTERVAL '45 days', NOW() + INTERVAL '45 days', 50, 'Activo'),
(4, 4, NOW() - INTERVAL '150 days', NOW() + INTERVAL '215 days', 200, 'Activo'),
(1, 5, NOW() - INTERVAL '30 days', NOW() + INTERVAL '30 days', 7, 'Activo'),
(2, 6, NOW() - INTERVAL '60 days', NOW() + INTERVAL '30 days', 12, 'Activo'),
(3, 7, NOW() - INTERVAL '2 days', NOW() + INTERVAL '5 days', 8, 'Activo'),
(4, 8, NOW() - INTERVAL '80 days', NOW() + INTERVAL '10 days', 30, 'Activo');

-- Tabla: PROMOCION
INSERT INTO promocion (codigo, descripcion, descuento_porcentaje, descuento_fijo, fecha_inicio, fecha_fin, usos_limite, usos_realizados, activo) VALUES
('PROMO20', 'Descuento 20% en viajes', 20, NULL, '2025-04-01', '2025-04-30', 1000, 45, true),
('VIAJE10', 'Descuento 10 euros fijo', NULL, 10.00, '2025-04-10', '2025-12-31', 500, 23, true),
('ESTUDIANTE15', 'Descuento 15% estudiantes', 15, NULL, '2025-04-01', '2025-06-30', 2000, 120, true),
('REPATRIADO25', 'Descuento 25% para repatriados', 25, NULL, '2025-04-15', '2025-05-31', 300, 8, true),
('ABRIL5EUROS', '5 euros de descuento en abril', NULL, 5.00, '2025-04-01', '2025-04-30', 10000, 876, true),
('BIENVENIDA30', '30% descuento primer viaje', 30, NULL, '2025-01-01', '2025-12-31', 500, 45, true),
('VERANO50', '50% descuento viajes verano', 50, NULL, '2025-06-01', '2025-08-31', 1000, 0, true),
('BLACKFRIDAY', '40% Black Friday', 40, NULL, '2025-11-24', '2025-11-30', 2000, 0, true);

-- Tabla: INCIDENCIA
INSERT INTO incidencia (id_pasajero, tipo_incidencia, origen, descripcion, fecha_reporte, estado, afecta_pasajero, resolucion, fecha_resolucion) VALUES
(1, 'Retraso', 'Pasajero', 'Tren llegó con 45 minutos de retraso', NOW() - INTERVAL '10 days', 'Resuelto', true, 'Se compensó con bono de 15 euros', NOW() - INTERVAL '5 days'),
(2, 'Equipaje Perdido', 'Pasajero', 'No encontré mi maleta en destino', NOW() - INTERVAL '8 days', 'Resuelto', true, 'Se recuperó la maleta y se devolvió', NOW() - INTERVAL '3 days'),
(3, 'Asiento Defectuoso', 'Pasajero', 'El esquinero de mi asiento estaba roto', NOW() - INTERVAL '6 days', 'Resuelto', true, 'Se cambió de asiento y se ofreció 10 euros', NOW() - INTERVAL '2 days'),
(4, 'Falta de Higiene', 'Pasajero', 'Vagón muy sucio y desordenado', NOW() - INTERVAL '5 days', 'En Revisión', true, 'Pendiente de inspección', NULL),
(1, 'Servicio Deficiente', 'Pasajero', 'Personal de tren fue poco amable', NOW() - INTERVAL '4 days', 'En Revisión', true, 'Se está investigando el incidente', NULL),
(2, 'Accidente Menor', 'Maquinista', 'Pequeño roze al entrar en estación', NOW() - INTERVAL '3 days', 'En Mantenimiento', false, 'Tren enviado a revisión técnica', NULL),
(3, 'Fallo Catenaria', 'Sistema', 'Pérdida de conexión con catenaria', NOW() - INTERVAL '2 days', 'En Mantenimiento', true, 'Reparación en progreso', NULL),
(4, 'Billete No Válido', 'Pasajero', 'Código de billete no se reconocía', NOW() - INTERVAL '1 day', 'Resuelto', true, 'Se emitió nuevo billete', NOW() - INTERVAL '1 day');

-- Tabla: MANTENIMIENTO
INSERT INTO mantenimiento (id_tren, tipo_mantenimiento, descripcion, fecha_programada, fecha_realizacion, estado, costo) VALUES
(1, 'Revisión General', 'Inspección completa de sistemas', '2025-05-15', NULL, 'Programado', 5000.00),
(2, 'Cambio Pastillas Freno', 'Reemplazo de pastillas de freno desgastadas', '2025-04-25', NULL, 'Programado', 1200.00),
(3, 'Limpieza Profunda', 'Limpieza integral de interior y exterior', '2025-04-22', NOW(), 'Completado', 800.00),
(4, 'Reparación Catenaria', 'Reparación de conexiones eléctricas', '2025-04-20', NOW(), 'Completado', 3500.00),
(5, 'Control Técnico', 'Control de sistemas de tracción y frenado', '2025-04-23', NULL, 'Pendiente', 2000.00),
(6, 'Cambio Aceite', 'Cambio de aceite en todos sistemas', '2025-04-26', NULL, 'Programado', 600.00),
(7, 'Revisión Puertas', 'Inspección y ajuste de mecanismos de puertas', '2025-04-25', NULL, 'Programado', 1500.00),
(8, 'Limpieza Ventilación', 'Limpieza de filtros y sistema de aire', '2025-04-22', NOW(), 'Completado', 400.00),
(9, 'Control Eje', 'Inspección de ejes y ruedas', '2025-04-27', NULL, 'Programado', 2200.00),
(10, 'Reparación Calefacción', 'Reparación del sistema de calefacción', '2025-04-24', NULL, 'Programado', 1800.00),
(11, 'Pintura y Detalle', 'Repintado de áreas dañadas', '2025-05-01', NULL, 'Programado', 900.00),
(12, 'Revisión Completa Post-Accidente', 'Inspección exhaustiva tras incidente', '2025-04-21', NOW(), 'Completado', 6000.00);

-- Estadísticas finales
SELECT COUNT(*) as total_usuarios FROM usuario;
SELECT COUNT(*) as total_pasajeros FROM pasajero;
SELECT COUNT(*) as total_empleados FROM empleado;
SELECT COUNT(*) as total_viajes FROM viaje;
SELECT COUNT(*) as total_billetes FROM billete;
SELECT COUNT(*) as total_abonos FROM abono;
SELECT COUNT(*) as total_incidencias FROM incidencia;
