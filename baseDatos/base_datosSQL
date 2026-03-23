-- sis_info_venta_billetes_bd
-- ================================================
-- USUARIO (superclase)
-- ================================================
CREATE TABLE USUARIO (
    id_usuario SERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    telefono VARCHAR(20),
    tipo_usuario VARCHAR(20) NOT NULL CHECK (tipo_usuario IN ('pasajero', 'empleado')),
    CONSTRAINT ck_tipo_usuario CHECK (tipo_usuario IN ('pasajero', 'empleado'))
);

-- ================================================
--  PASAJERO
-- ================================================
CREATE TABLE PASAJERO (
    id_pasajero SERIAL PRIMARY KEY,
    id_usuario INT NOT NULL,
    fecha_nacimiento DATE NOT NULL,
    genero VARCHAR(20) NOT NULL CHECK (genero IN ('masculino','femenino','otro','no_especificar')),
    tipo_documento VARCHAR(20) NOT NULL CHECK (tipo_documento IN ('dni','nie','pasaporte')),
    numero_documento VARCHAR(20),
    calle VARCHAR(255),
    ciudad VARCHAR(100),
    codigo_postal VARCHAR(40),
    pais VARCHAR(50),
    metodo_pago VARCHAR(50),
    acepta_terminos BOOLEAN DEFAULT FALSE,
    acepta_privacidad BOOLEAN DEFAULT FALSE,
    newsletter BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (id_usuario) REFERENCES USUARIO(id_usuario)
);

-- ================================================
--  EMPLEADO (superclase)
-- ================================================
CREATE TABLE EMPLEADO (
    id_empleado SERIAL PRIMARY KEY,
    id_usuario INT NOT NULL,
    tipo_empleado VARCHAR(20) NOT NULL CHECK (tipo_empleado IN ('maquinista', 'mantenimiento', 'vendedor')),
    CONSTRAINT ck_tipo_empleado CHECK (tipo_empleado IN ('maquinista', 'mantenimiento', 'vendedor')),
    FOREIGN KEY (id_usuario) REFERENCES USUARIO(id_usuario)
);

-- ================================================
-- Subtablas de EMPLEADO
-- ================================================
-- VENDEDOR
CREATE TABLE VENDEDOR (
    id_empleado INT PRIMARY KEY,
    comision_porcentaje DECIMAL(5,2),
    region VARCHAR(100),
    FOREIGN KEY (id_empleado) REFERENCES EMPLEADO(id_empleado)
);

-- MAQUINISTA
CREATE TABLE MAQUINISTA (
    id_empleado INT PRIMARY KEY,
    licencia VARCHAR(50),
    experiencia_años INT,
    horario_preferido VARCHAR(50),
    FOREIGN KEY (id_empleado) REFERENCES EMPLEADO(id_empleado)
);

-- MANTENIMIENTO
CREATE TABLE MANTENIMIENTO (
    id_empleado INT PRIMARY KEY,
    especialidad VARCHAR(100),
    turno VARCHAR(50),
    certificaciones VARCHAR(255),
    FOREIGN KEY (id_empleado) REFERENCES EMPLEADO(id_empleado)
);

-- ================================================
-- 5️⃣ RUTA
-- ================================================
CREATE TABLE RUTA (
    id_ruta SERIAL PRIMARY KEY,
    origen VARCHAR(100) NOT NULL,
    destino VARCHAR(100) NOT NULL,
    duracion INTERVAL,
    id_vendedor INT,
    FOREIGN KEY (id_vendedor) REFERENCES VENDEDOR(id_empleado)
);

-- ================================================
--  TREN
-- ================================================
CREATE TABLE TREN (
    id_tren SERIAL PRIMARY KEY,
    modelo VARCHAR(50),
    capacidad INT
);

-- ================================================
--ASIENTO
-- ================================================
CREATE TABLE ASIENTO (
    numero_asiento INT PRIMARY KEY,
    id_tren INT NOT NULL,
    clase VARCHAR(20) NOT NULL CHECK (clase IN ('primera','segunda')),
    estado VARCHAR(20) DEFAULT 'disponible' CHECK (estado IN ('disponible','ocupado','reservado')),
    FOREIGN KEY (id_tren) REFERENCES TREN(id_tren)
);

-- ================================================
-- ABONO / SUSCRIPCIÓN
-- ================================================
-- Creamos la tabla, vinculada al catálogo de abonos
CREATE TABLE ABONO (
    id_abono SERIAL PRIMARY KEY,
    id_pasajero INT NOT NULL,
    tipo VARCHAR(50) NOT NULL, 
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NOT NULL,
    viajes_totales INT,
    viajes_restantes INT,
    
    FOREIGN KEY (id_pasajero) REFERENCES PASAJERO(id_pasajero) ON DELETE CASCADE,
    FOREIGN KEY (tipo) REFERENCES TIPO_ABONO(tipo_codigo) ON DELETE RESTRICT
);
-- ================================================
-- VIAJE
-- ================================================
CREATE TABLE VIAJE (
    id_viaje SERIAL PRIMARY KEY,
    id_vendedor INT NOT NULL,
    id_ruta INT NOT NULL,
    id_tren INT NOT NULL,
    id_maquinista INT NOT NULL,
    fecha DATE NOT NULL,
    hora_salida TIME NOT NULL,
    hora_llegada TIME NOT NULL,
    precio DECIMAL(10,2) NOT NULL,
    estado VARCHAR(30) NOT NULL CHECK (estado IN ('programado', 'en_curso', 'completado', 'cancelado')),
    FOREIGN KEY (id_vendedor) REFERENCES VENDEDOR(id_empleado),
    FOREIGN KEY (id_ruta) REFERENCES RUTA(id_ruta),
    FOREIGN KEY (id_tren) REFERENCES TREN(id_tren),
    FOREIGN KEY (id_maquinista) REFERENCES MAQUINISTA(id_empleado)
);

-- ================================================
-- INCIDENCIA
-- ================================================
CREATE TABLE INCIDENCIA (
    id_incidencia SERIAL PRIMARY KEY,
    id_viaje INT NOT NULL,
    id_mantenimiento INT NOT NULL,
    id_maquinista INT NOT NULL,
    tipo_incidencia VARCHAR(50) NOT NULL DEFAULT 'otro',
    origen VARCHAR(20) NOT NULL DEFAULT 'maquinista' CHECK (origen IN ('maquinista', 'iot')),
    descripcion TEXT NOT NULL,
    fecha_reporte TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    estado VARCHAR(30) NOT NULL CHECK (estado IN ('reportado', 'en_proceso', 'resuelto')),
    afecta_pasajero BOOLEAN NOT NULL DEFAULT false,
    resolucion TEXT,
    fecha_resolucion TIMESTAMP,
    FOREIGN KEY (id_viaje) REFERENCES VIAJE(id_viaje),
    FOREIGN KEY (id_mantenimiento) REFERENCES MANTENIMIENTO(id_empleado),
    FOREIGN KEY (id_maquinista) REFERENCES MAQUINISTA(id_empleado)
);

-- ================================================
-- PROMOCION
-- ================================================
CREATE TABLE PROMOCION (
    id_promocion SERIAL PRIMARY KEY,
    codigo VARCHAR(20) UNIQUE NOT NULL,
    descuento_porcentaje DECIMAL(5,2) DEFAULT 0,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NOT NULL,
    usos_maximos INT DEFAULT NULL,
    usos_actuales INT DEFAULT 0
);

-- ==========================================
-- TIPOS DE ABONOS
-- ==========================================
-- Esta tabla guardará lo que se muestra en la web
CREATE TABLE TIPO_ABONO (
    tipo_codigo VARCHAR(50) PRIMARY KEY, -- Debe coincidir con el CHECK de tu tabla ABONO
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT NOT NULL,
    precio DECIMAL(10,2) NOT NULL,
    icono VARCHAR(50) DEFAULT 'fa-ticket',
    color VARCHAR(20) DEFAULT '#0a2a66'
);
