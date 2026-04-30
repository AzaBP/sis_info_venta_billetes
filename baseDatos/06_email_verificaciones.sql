-- Añade tabla para códigos de verificación / recuperación
CREATE TABLE IF NOT EXISTS email_verificaciones (
    id SERIAL PRIMARY KEY,
    id_usuario INTEGER,
    email VARCHAR(255) NOT NULL,
    codigo VARCHAR(50) NOT NULL,
    tipo VARCHAR(50) NOT NULL,
    usado BOOLEAN DEFAULT false,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT NOW()
);

-- Añade columna email_verificado en usuario si no existe
ALTER TABLE usuario ADD COLUMN IF NOT EXISTS email_verificado BOOLEAN DEFAULT false;
