-- Reparacion incremental tras ejecutar 03_datos_masivos.sql
-- 1) Inserta asientos sin chocar con la PK numero_asiento
-- 2) Verifica ASIENTO y TIPO_ABONO

-- 1) Completar asientos evitando duplicados
-- PK de ASIENTO es numero_asiento (global), por eso usamos numeracion global por tren.
INSERT INTO ASIENTO (id_tren, numero_asiento, clase, estado)
SELECT
    t.id_tren,
    ((t.id_tren - 1) * 100) + s.n AS numero_asiento,
    CASE WHEN s.n <= 10 THEN 'primera' ELSE 'segunda' END AS clase,
    'disponible' AS estado
FROM TREN t
CROSS JOIN generate_series(1, 100) AS s(n)
ON CONFLICT (numero_asiento) DO NOTHING;

-- 2) Verificacion
SELECT 'ASIENTOS' AS tabla, COUNT(*) AS total FROM ASIENTO
UNION ALL
SELECT 'TIPO_ABONO' AS tabla, COUNT(*) AS total FROM TIPO_ABONO;