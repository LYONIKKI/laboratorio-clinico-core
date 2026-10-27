-- databasepg/schema.sql
-- Estructura de base de datos PostgreSQL para ACL / Sesiones Laboratorio

CREATE TABLE IF NOT EXISTS medicos_informantes (
    id_medico SERIAL PRIMARY KEY,
    nombres VARCHAR(150) NOT NULL,
    cmp VARCHAR(20),
    rne VARCHAR(20),
    estado INTEGER DEFAULT 1
);

CREATE TABLE IF NOT EXISTS usuarios_sigh (
    id SERIAL PRIMARY KEY,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nombre VARCHAR(150),
    rol VARCHAR(20) DEFAULT 'admin',
    estado INTEGER DEFAULT 1,
    slug_destino VARCHAR(50),
    id_medico_asociado INTEGER,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT usuarios_sigh_id_medico_asociado_fkey 
        FOREIGN KEY (id_medico_asociado) 
        REFERENCES medicos_informantes(id_medico) 
        ON DELETE SET NULL
);

-- Índices adicionales para autenticación rápida
CREATE INDEX IF NOT EXISTS idx_usuarios_sigh_usuario ON usuarios_sigh(usuario);
CREATE INDEX IF NOT EXISTS idx_usuarios_sigh_estado ON usuarios_sigh(estado);

-- Usuario inicial administrador por defecto (password: admin123 hash o texto según tu lógica)
-- Reemplazar el hash con el algoritmo usado (BCRYPT / SHA)
INSERT INTO usuarios_sigh (usuario, password, nombre, rol, estado, slug_destino)
VALUES ('admin', '$2y$10$YourHashedPasswordHere', 'ADMINISTRADOR SISTEMA', 'admin', 1, 'laboratorio')
ON CONFLICT (usuario) DO NOTHING;