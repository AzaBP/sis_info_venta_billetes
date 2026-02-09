-- Creación de tipos ENUM
CREATE TYPE clase_asiento AS ENUM ('primera', 'segunda');
CREATE TYPE estado_asiento AS ENUM ('disponible', 'ocupado', 'reservado');
CREATE TYPE tipo_abono AS ENUM ('mensual', 'trimestral', 'anual', 'viajes_limitados', 'estudiante');

-- 1. USUARIO
CREATE TABLE USUARIO (
    id_usuario SERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    metodo_pago VARCHAR(50)
);

-- 2. PASAJERO
CREATE TABLE PASAJERO (
    id_pasajero SERIAL PRIMARY KEY,
    id_usuario INT NOT NULL REFERENCES USUARIO(id_usuario) ON DELETE CASCADE,
    fecha_nacimiento DATE
);

-- 3. EMPLEADO
CREATE TABLE EMPLEADO (
    id_empleado SERIAL PRIMARY KEY,
    id_usuario INT NOT NULL REFERENCES USUARIO(id_usuario) ON DELETE CASCADE,
    email_corporativo VARCHAR(100)
);

-- 4. SUBTABLAS EMPLEADO
CREATE TABLE VENDEDOR (
    id_empleado INT PRIMARY KEY REFERENCES EMPLEADO(id_empleado) ON DELETE CASCADE,
    comision_porcentaje DECIMAL(5,2),
    region VARCHAR(100)
);

CREATE TABLE MAQUINISTA (
    id_empleado INT PRIMARY KEY REFERENCES EMPLEADO(id_empleado) ON DELETE CASCADE,
    licencia VARCHAR(50),
    experiencia_anos INT,
    horario_preferido VARCHAR(50)
);

CREATE TABLE MANTENIMIENTO (
    id_empleado INT PRIMARY KEY REFERENCES EMPLEADO(id_empleado) ON DELETE CASCADE,
    especialidad VARCHAR(100),
    turno VARCHAR(50),
    certificaciones VARCHAR(255)
);

-- 5. RUTA
CREATE TABLE RUTA (
    id_ruta SERIAL PRIMARY KEY,
    origen VARCHAR(100) NOT NULL,
    destino VARCHAR(100) NOT NULL,
    duracion INTERVAL,
    id_vendedor INT REFERENCES VENDEDOR(id_empleado)
);

-- 6. TREN
CREATE TABLE TREN (
    id_tren SERIAL PRIMARY KEY,
    modelo VARCHAR(50),
    capacidad INT
);

-- 7. ASIENTO
CREATE TABLE ASIENTO (
    id_asiento SERIAL PRIMARY KEY,
    id_tren INT NOT NULL REFERENCES TREN(id_tren) ON DELETE CASCADE,
    numero VARCHAR(10) NOT NULL,
    clase clase_asiento NOT NULL,
    estado estado_asiento DEFAULT 'disponible'
);

-- 8. ABONO
CREATE TABLE ABONO (
    id_abono SERIAL PRIMARY KEY,
    id_pasajero INT NOT NULL REFERENCES PASAJERO(id_pasajero),
    tipo tipo_abono NOT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NOT NULL,
    viajes_totales INT,
    viajes_restantes INT,
    CONSTRAINT check_fechas CHECK (fecha_fin > fecha_inicio)
);

-- 9. BILLETE 
CREATE TABLE BILLETE (
    id_billete INT AUTO_INCREMENT PRIMARY KEY,
    id_pasajero INT NOT NULL,
    id_asiento INT,
    id_abono INT,
    id_viaje_mongo VARCHAR(24),   -- ObjectId de VIAJE en MongoDB que incluye promociones
    id_promocion_mongo VARCHAR(24), -- ObjectId de MongoDB de la promoción aplicada
    FOREIGN KEY (id_pasajero) REFERENCES PASAJERO(id_pasajero),
    FOREIGN KEY (id_asiento) REFERENCES ASIENTO(id_asiento),
    FOREIGN KEY (id_abono) REFERENCES ABONO(id_abono)
);