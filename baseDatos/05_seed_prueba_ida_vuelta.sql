-- Seed de prueba para flujo ida y vuelta
-- Crea rutas y viajes Madrid <-> Barcelona para una fecha concreta.
-- Es idempotente: no duplica viajes si ya existen con mismo horario/fecha.

BEGIN;

-- 1) Crear rutas si no existen
INSERT INTO RUTA (origen, destino, duracion, id_vendedor)
SELECT
    'Madrid Puerta de Atocha',
    'Barcelona Sants',
    INTERVAL '02:30:00',
    (SELECT id_empleado FROM VENDEDOR ORDER BY id_empleado LIMIT 1)
WHERE NOT EXISTS (
    SELECT 1
    FROM RUTA
    WHERE origen = 'Madrid Puerta de Atocha'
      AND destino = 'Barcelona Sants'
);

INSERT INTO RUTA (origen, destino, duracion, id_vendedor)
SELECT
    'Barcelona Sants',
    'Madrid Puerta de Atocha',
    INTERVAL '02:30:00',
    (SELECT id_empleado FROM VENDEDOR ORDER BY id_empleado LIMIT 1)
WHERE NOT EXISTS (
    SELECT 1
    FROM RUTA
    WHERE origen = 'Barcelona Sants'
      AND destino = 'Madrid Puerta de Atocha'
);

-- 2) Crear viaje de ida (Madrid -> Barcelona)
INSERT INTO VIAJE (
    id_vendedor,
    id_ruta,
    id_tren,
    id_maquinista,
    fecha,
    hora_salida,
    hora_llegada,
    precio,
    estado
)
SELECT
    (SELECT id_empleado FROM VENDEDOR ORDER BY id_empleado LIMIT 1),
    (SELECT id_ruta FROM RUTA WHERE origen = 'Madrid Puerta de Atocha' AND destino = 'Barcelona Sants' ORDER BY id_ruta LIMIT 1),
    (SELECT id_tren FROM TREN ORDER BY id_tren LIMIT 1),
    (SELECT id_empleado FROM MAQUINISTA ORDER BY id_empleado LIMIT 1),
    DATE '2026-05-15',
    TIME '08:00:00',
    TIME '10:30:00',
    79.99,
    'programado'
WHERE NOT EXISTS (
    SELECT 1
    FROM VIAJE v
    JOIN RUTA r ON r.id_ruta = v.id_ruta
    WHERE r.origen = 'Madrid Puerta de Atocha'
      AND r.destino = 'Barcelona Sants'
      AND v.fecha = DATE '2026-05-15'
      AND v.hora_salida = TIME '08:00:00'
);

-- 3) Crear viaje de vuelta (Barcelona -> Madrid)
INSERT INTO VIAJE (
    id_vendedor,
    id_ruta,
    id_tren,
    id_maquinista,
    fecha,
    hora_salida,
    hora_llegada,
    precio,
    estado
)
SELECT
    (SELECT id_empleado FROM VENDEDOR ORDER BY id_empleado LIMIT 1),
    (SELECT id_ruta FROM RUTA WHERE origen = 'Barcelona Sants' AND destino = 'Madrid Puerta de Atocha' ORDER BY id_ruta LIMIT 1),
    (SELECT id_tren FROM TREN ORDER BY id_tren LIMIT 1),
    (SELECT id_empleado FROM MAQUINISTA ORDER BY id_empleado LIMIT 1),
    DATE '2026-05-20',
    TIME '18:00:00',
    TIME '20:30:00',
    82.50,
    'programado'
WHERE NOT EXISTS (
    SELECT 1
    FROM VIAJE v
    JOIN RUTA r ON r.id_ruta = v.id_ruta
    WHERE r.origen = 'Barcelona Sants'
      AND r.destino = 'Madrid Puerta de Atocha'
      AND v.fecha = DATE '2026-05-20'
      AND v.hora_salida = TIME '18:00:00'
);

COMMIT;

-- Verificación rápida
SELECT
    v.id_viaje,
    r.origen,
    r.destino,
    v.fecha,
    v.hora_salida,
    v.hora_llegada,
    v.precio,
    v.estado
FROM VIAJE v
JOIN RUTA r ON r.id_ruta = v.id_ruta
WHERE
    (r.origen = 'Madrid Puerta de Atocha' AND r.destino = 'Barcelona Sants' AND v.fecha = DATE '2026-05-15')
    OR
    (r.origen = 'Barcelona Sants' AND r.destino = 'Madrid Puerta de Atocha' AND v.fecha = DATE '2026-05-20')
ORDER BY v.fecha, v.hora_salida;
