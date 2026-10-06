-- GESTIÓN COMERCIAL - Oportunidades y Contratos
-- Base de datos completa
-- =====================================================

-- =====================================================
-- TABLA: roles
-- =====================================================
CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL,
    descripcion VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================
-- TABLA: usuarios
-- =====================================================
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100),
    email VARCHAR(150) UNIQUE NOT NULL,
    password_hash VARCHAR(255) DEFAULT NULL,
    google_id VARCHAR(100) DEFAULT NULL,
    rol ENUM('superadmin','admin','usuario') DEFAULT 'usuario',
    rol_id INT DEFAULT NULL,
    avatar VARCHAR(500) DEFAULT NULL,
    activo TINYINT(1) DEFAULT 1,
    ultimo_acceso DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================
-- TABLA: lineas_negocio
-- =====================================================
CREATE TABLE IF NOT EXISTS lineas_negocio (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    icono VARCHAR(50) DEFAULT 'briefcase',
    color VARCHAR(20) DEFAULT '#6C5CE7',
    activo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================
-- TABLA: clientes (entidades)
-- =====================================================
CREATE TABLE IF NOT EXISTS clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    razon_social VARCHAR(255) NOT NULL,
    ruc VARCHAR(20),
    direccion TEXT,
    telefono VARCHAR(50),
    email VARCHAR(150),
    contacto_nombre VARCHAR(150),
    contacto_cargo VARCHAR(100),
    contacto_telefono VARCHAR(50),
    contacto_email VARCHAR(150),
    tipo ENUM('publico','privado') DEFAULT 'publico',
    departamento VARCHAR(100),
    provincia VARCHAR(100),
    distrito VARCHAR(100),
    activo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================
-- TABLA: proveedores
-- =====================================================
CREATE TABLE IF NOT EXISTS proveedores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    razon_social VARCHAR(255) NOT NULL,
    ruc VARCHAR(20),
    direccion TEXT,
    telefono VARCHAR(50),
    email VARCHAR(150),
    contacto_nombre VARCHAR(150),
    especialidad VARCHAR(255),
    calificacion DECIMAL(3,2) DEFAULT 0,
    activo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================
-- TABLA: convocatorias (detecciones de concursos)
-- =====================================================
CREATE TABLE IF NOT EXISTS convocatorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) UNIQUE NOT NULL,
    nombre VARCHAR(500) NOT NULL,
    entidad VARCHAR(255),
    cliente_id INT DEFAULT NULL,
    linea_negocio_id INT DEFAULT NULL,
    tipo_gestion VARCHAR(100),
    monto_referencial DECIMAL(15,2) DEFAULT 0,
    moneda VARCHAR(10) DEFAULT 'PEN',
    fecha_publicacion DATE,
    fecha_limite_oferta DATE,
    plazo_ejecucion VARCHAR(100),
    requisitos_profesionales VARCHAR(255),
    departamento VARCHAR(100),
    fuente VARCHAR(100) DEFAULT 'SEACE',
    url_fuente TEXT,
    estado ENUM('detectada','en_revision','convertida','descartada') DEFAULT 'detectada',
    motivo_descarte TEXT,
    observaciones TEXT,
    detectada_por INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE SET NULL,
    FOREIGN KEY (linea_negocio_id) REFERENCES lineas_negocio(id) ON DELETE SET NULL,
    FOREIGN KEY (detectada_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================
-- TABLA: oportunidades
-- =====================================================
CREATE TABLE IF NOT EXISTS oportunidades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) UNIQUE NOT NULL,
    nombre VARCHAR(500) NOT NULL,
    convocatoria_id INT DEFAULT NULL,
    cliente_id INT DEFAULT NULL,
    linea_negocio_id INT DEFAULT NULL,
    responsable_id INT DEFAULT NULL,
    monto_estimado DECIMAL(15,2) DEFAULT 0,
    moneda VARCHAR(10) DEFAULT 'PEN',
    fecha_limite DATE,
    estado ENUM('detectada','en_evaluacion','participar','no_participar','en_propuesta','adjudicada','no_adjudicada','cerrada') DEFAULT 'detectada',
    prioridad ENUM('alta','media','baja') DEFAULT 'media',
    probabilidad INT DEFAULT 50,
    descripcion TEXT,
    observaciones TEXT,
    evaluacion_gerencial TEXT,
    fecha_evaluacion DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (convocatoria_id) REFERENCES convocatorias(id) ON DELETE SET NULL,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE SET NULL,
    FOREIGN KEY (linea_negocio_id) REFERENCES lineas_negocio(id) ON DELETE SET NULL,
    FOREIGN KEY (responsable_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================
-- TABLA: licitaciones
-- =====================================================
CREATE TABLE IF NOT EXISTS licitaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) UNIQUE NOT NULL,
    oportunidad_id INT NOT NULL,
    responsable_id INT DEFAULT NULL,
    fecha_presentacion DATE,
    monto_ofertado DECIMAL(15,2) DEFAULT 0,
    estado ENUM('en_preparacion','revisada','presentada','en_evaluacion','ganada','perdida') DEFAULT 'en_preparacion',
    documentos_requeridos TEXT,
    documentos_presentados TEXT,
    observaciones TEXT,
    resultado TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (oportunidad_id) REFERENCES oportunidades(id) ON DELETE CASCADE,
    FOREIGN KEY (responsable_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================
-- TABLA: propuestas
-- =====================================================
CREATE TABLE IF NOT EXISTS propuestas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) UNIQUE NOT NULL,
    licitacion_id INT NOT NULL,
    responsable_id INT DEFAULT NULL,
    monto_propuesta DECIMAL(15,2) DEFAULT 0,
    fecha_envio DATE,
    estado ENUM('borrador','en_revision','enviada','aceptada','rechazada') DEFAULT 'borrador',
    propuesta_tecnica TEXT,
    propuesta_economica TEXT,
    observaciones TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (licitacion_id) REFERENCES licitaciones(id) ON DELETE CASCADE,
    FOREIGN KEY (responsable_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================
-- TABLA: contratos
-- =====================================================
CREATE TABLE IF NOT EXISTS contratos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) UNIQUE NOT NULL,
    oportunidad_id INT DEFAULT NULL,
    cliente_id INT DEFAULT NULL,
    responsable_id INT DEFAULT NULL,
    objeto VARCHAR(500),
    monto_contrato DECIMAL(15,2) DEFAULT 0,
    moneda VARCHAR(10) DEFAULT 'PEN',
    fecha_firma DATE,
    fecha_inicio DATE,
    fecha_fin DATE,
    plazo_dias INT DEFAULT 0,
    estado ENUM('en_elaboracion','firmado','en_ejecucion','finalizado','resuelto') DEFAULT 'en_elaboracion',
    tipo_contrato VARCHAR(100),
    garantias TEXT,
    penalidades TEXT,
    observaciones TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (oportunidad_id) REFERENCES oportunidades(id) ON DELETE SET NULL,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE SET NULL,
    FOREIGN KEY (responsable_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================
-- TABLA: proyectos
-- =====================================================
CREATE TABLE IF NOT EXISTS proyectos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) UNIQUE NOT NULL,
    contrato_id INT DEFAULT NULL,
    nombre VARCHAR(500) NOT NULL,
    responsable_id INT DEFAULT NULL,
    fecha_inicio DATE,
    fecha_fin_planificada DATE,
    fecha_fin_real DATE,
    avance_porcentaje DECIMAL(5,2) DEFAULT 0,
    monto_presupuesto DECIMAL(15,2) DEFAULT 0,
    monto_ejecutado DECIMAL(15,2) DEFAULT 0,
    estado ENUM('planificacion','en_ejecucion','suspendido','finalizado','liquidado') DEFAULT 'planificacion',
    observaciones TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (contrato_id) REFERENCES contratos(id) ON DELETE SET NULL,
    FOREIGN KEY (responsable_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================
-- TABLA: valorizaciones
-- =====================================================
CREATE TABLE IF NOT EXISTS valorizaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    proyecto_id INT NOT NULL,
    numero INT NOT NULL,
    periodo VARCHAR(50),
    monto DECIMAL(15,2) DEFAULT 0,
    avance_periodo DECIMAL(5,2) DEFAULT 0,
    avance_acumulado DECIMAL(5,2) DEFAULT 0,
    estado ENUM('borrador','presentada','aprobada','pagada') DEFAULT 'borrador',
    fecha_presentacion DATE,
    fecha_aprobacion DATE,
    observaciones TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (proyecto_id) REFERENCES proyectos(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- TABLA: seguimientos (historial de acciones)
-- =====================================================
CREATE TABLE IF NOT EXISTS seguimientos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entidad_tipo ENUM('convocatoria','oportunidad','licitacion','propuesta','contrato','proyecto') NOT NULL,
    entidad_id INT NOT NULL,
    usuario_id INT DEFAULT NULL,
    accion VARCHAR(255) NOT NULL,
    detalle TEXT,
    fecha_accion DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================
-- TABLA: documentos
-- =====================================================
CREATE TABLE IF NOT EXISTS documentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entidad_tipo ENUM('convocatoria','oportunidad','licitacion','propuesta','contrato','proyecto') NOT NULL,
    entidad_id INT NOT NULL,
    nombre VARCHAR(255) NOT NULL,
    archivo VARCHAR(500),
    tipo_archivo VARCHAR(50),
    tamano INT DEFAULT 0,
    subido_por INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (subido_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================
-- DATOS DE DEMOSTRACIÓN
-- =====================================================

-- Roles
INSERT INTO roles (nombre, descripcion) VALUES
('Superadministrador', 'Acceso total al sistema'),
('Gerente', 'Evaluación y decisión sobre oportunidades'),
('Encargado Licitaciones', 'Gestión de licitaciones y propuestas'),
('Ingeniero', 'Soporte técnico en propuestas'),
('Administrador', 'Gestión administrativa y contratos');

-- Usuarios
INSERT INTO usuarios (nombre, apellido, email, password, rol_id) VALUES
('Jorge', 'Palomino', 'jorge.palomino@empresa.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1),
('María', 'Gutiérrez', 'maria.gutierrez@empresa.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2),
('Carlos', 'Mendoza', 'carlos.mendoza@empresa.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 3),
('Ana', 'Torres', 'ana.torres@empresa.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 4),
('Luis', 'Vargas', 'luis.vargas@empresa.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 5);

-- Líneas de negocio
INSERT INTO lineas_negocio (nombre, descripcion, icono, color) VALUES
('Negocios propios', 'Iniciativas de inversión originadas y evaluadas por la empresa.', 'building', '#6C5CE7'),
('Consultoría', 'Supervisión, expedientes técnicos, estudios y entregables especializados.', 'clipboard-check', '#00B894'),
('Ejecución de obra', 'Contratos orientados a construcción, producción y entrega de obra.', 'hard-hat', '#E17055');

-- Clientes
INSERT INTO clientes (razon_social, ruc, tipo, departamento) VALUES
('Municipalidad de Atalaya', '20123456789', 'publico', 'Ucayali'),
('Gobierno Regional de Ucayali', '20234567890', 'publico', 'Ucayali'),
('Municipalidad Provincial', '20345678901', 'publico', 'Lima'),
('Entidad pública regional', '20456789012', 'publico', 'Junín'),
('Gobierno local', '20567890123', 'publico', 'Cusco'),
('Iniciativa empresarial', '20678901234', 'privado', 'Lima'),
('Inversión propia', '20789012345', 'privado', 'Ucayali'),
('Municipalidad Distrital', '20890123456', 'publico', 'Ucayali'),
('Municipalidad Distrital de San Marcos', '20901234567', 'publico', 'Ancash');

-- Convocatorias
INSERT INTO convocatorias (codigo, nombre, entidad, cliente_id, linea_negocio_id, tipo_gestion, monto_referencial, fecha_publicacion, fecha_limite_oferta, plazo_ejecucion, requisitos_profesionales, departamento, estado) VALUES
('CONV-2026-C143F3', 'Vías carrozables', 'Municipalidad de Atalaya', 1, 3, 'Ejecución de obra', 1500000, '2026-09-20', '2026-09-26', '400 días calendario', '11 profesionales', 'Ucayali', 'detectada'),
('CONV-2026-F46463', 'Construcción Vial', 'Municipalidad de Atalaya', 1, 3, 'Ejecución de obra', 50000000, '2026-09-20', '2026-09-26', '250 días calendario', '8 profesionales', 'Ucayali', 'detectada'),
('CONV-2026-021', 'Consultoría para expediente técnico de infraestructura educativa', 'Gobierno Regional de Ucayali', 2, 2, 'Consultoría', 780000, '2026-09-22', '2026-10-18', '120 días calendario', '5 profesionales', 'Ucayali', 'detectada'),
('CONV-2026-020', 'Ejecución de sistema de agua potable y saneamiento', 'Municipalidad Distrital', 8, 3, 'Ejecución de obra', 9350000, '2026-09-22', '2026-10-25', '240 días calendario', '7 profesionales', 'Ucayali', 'detectada');

-- Oportunidades
INSERT INTO oportunidades (codigo, nombre, convocatoria_id, cliente_id, linea_negocio_id, responsable_id, monto_estimado, fecha_limite, estado, prioridad) VALUES
('OP-2026-82832F', 'Construcción e implementación de Centro educativo 3030', NULL, 1, 3, 3, 74500200, '2026-09-23', 'detectada', 'alta'),
('OP-2026-F703E0', 'Construcción e implementación de Centro educativo 3030', NULL, 1, 3, 3, 74500200, '2026-09-23', 'detectada', 'alta'),
('OP-2026-980AB0', 'Construcción e implementación de Centro educativo 3030', NULL, 1, 3, 3, 74500200, '2026-09-23', 'detectada', 'alta'),
('NP-2026-002', 'Parque comercial y almacenes logísticos en Yarinacocha', NULL, 6, 1, 1, 4200000, '2026-11-15', 'en_evaluacion', 'media'),
('NP-2026-001', 'Desarrollo inmobiliario multifamiliar en Pucallpa', NULL, 7, 1, 1, 6800000, '2026-12-10', 'en_evaluacion', 'media'),
('OP-2026-014', 'Supervisión de obra de infraestructura educativa', NULL, 2, 2, 4, 1850000, '2026-09-30', 'en_evaluacion', 'alta'),
('OP-2026-013', 'Mejoramiento de vía urbana y drenaje pluvial', NULL, 3, 3, 3, 8420000, '2026-10-05', 'participar', 'alta'),
('OP-2026-012', 'Elaboración de expediente técnico de saneamiento', NULL, 4, 2, 4, 620000, '2026-10-11', 'en_evaluacion', 'media'),
('OP-2026-011', 'Construcción y equipamiento de centro de salud', NULL, 5, 3, 3, 12600000, '2026-10-20', 'en_evaluacion', 'alta');

-- Licitaciones
INSERT INTO licitaciones (codigo, oportunidad_id, responsable_id, fecha_presentacion, monto_ofertado, estado) VALUES
('LIC-2026-001', 7, 3, '2026-10-05', 8200000, 'en_preparacion'),
('LIC-2026-002', 4, 1, '2026-11-10', 4100000, 'en_preparacion'),
('LIC-2026-003', 6, 4, '2026-09-28', 1800000, 'presentada'),
('LIC-2026-004', 1, 3, '2026-09-22', 74000000, 'en_evaluacion'),
('LIC-2026-005', 9, 3, '2026-10-18', 12400000, 'en_preparacion'),
('LIC-2026-006', 8, 4, '2026-10-09', 600000, 'en_preparacion');

-- Propuestas
INSERT INTO propuestas (codigo, licitacion_id, responsable_id, monto_propuesta, fecha_envio, estado) VALUES
('PROP-2026-001', 3, 4, 1780000, '2026-09-28', 'enviada'),
('PROP-2026-002', 4, 3, 73500000, '2026-09-22', 'enviada'),
('PROP-2026-003', 1, 3, 8150000, NULL, 'borrador'),
('PROP-2026-004', 2, 1, 4050000, NULL, 'en_revision');

-- Contratos
INSERT INTO contratos (codigo, oportunidad_id, cliente_id, responsable_id, objeto, monto_contrato, fecha_firma, fecha_inicio, fecha_fin, plazo_dias, estado) VALUES
('CONT-2026-001', 7, 3, 3, 'Mejoramiento de vía urbana y drenaje pluvial', 8200000, '2026-10-15', '2026-10-20', '2027-04-20', 180, 'en_ejecucion'),
('CONT-2026-002', 6, 2, 4, 'Supervisión de obra de infraestructura educativa', 1800000, '2026-10-05', '2026-10-10', '2027-02-10', 120, 'firmado'),
('CONT-2025-003', NULL, 1, 3, 'Construcción de infraestructura vial rural', 5400000, '2025-06-01', '2025-06-15', '2026-06-15', 365, 'en_ejecucion');

-- Proyectos
INSERT INTO proyectos (codigo, contrato_id, nombre, responsable_id, fecha_inicio, fecha_fin_planificada, avance_porcentaje, monto_presupuesto, monto_ejecutado, estado) VALUES
('PROY-2026-001', 1, 'Mejoramiento de vía urbana y drenaje pluvial', 3, '2026-10-20', '2027-04-20', 15.00, 8200000, 1230000, 'en_ejecucion'),
('PROY-2025-002', 3, 'Construcción de infraestructura vial rural', 3, '2025-06-15', '2026-06-15', 72.50, 5400000, 3915000, 'en_ejecucion');
