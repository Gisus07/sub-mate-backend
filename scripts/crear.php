<?php

/**
 * Script de Inicialización de Base de Datos - SubMate (V3 - Final)
 * =====================================================
 * Este script crea la base de datos, tablas, triggers, stored procedures
 * y datos de prueba (seeding) para el sistema SubMate.
 * 
 * Nomenclatura:
 * - Base de datos: db_[nombre]_ahjr
 * - Tablas: td_[nombre]_ahjr
 * - Triggers: tr_[nombre]_ahjr
 * - Stored Procedures: sp_[nombre]_ahjr
 */

// Cargar autoload de Composer
require_once __DIR__ . '/../vendor/autoload.php';

// Cargar variables de entorno
$dotenv_ahjr = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv_ahjr->load();

// Obtener configuración desde variables de entorno
$host_ahjr = $_ENV['DB_HOST'] ?? 'localhost';
$db_ahjr = $_ENV['DB_NAME'] ?? 'db_submate_ahjr';
$user_ahjr = $_ENV['DB_USER'] ?? 'root';
$pass_ahjr = $_ENV['DB_PASS'] ?? '';

// Configuración de PDO
$options_ahjr = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    echo "=== Iniciando configuración de base de datos ===\n\n";

    // Conexión inicial al servidor MySQL
    echo "► Conectando al servidor MySQL...\n";
    $pdoServer_ahjr = new PDO("mysql:host={$host_ahjr};charset=utf8mb4", $user_ahjr, $pass_ahjr, $options_ahjr);
    echo "✓ Conexión al servidor exitosa\n\n";

    // Crear base de datos si no existe
    echo "► Verificando base de datos '{$db_ahjr}'...\n";
    try {
        $pdo_ahjr = new PDO("mysql:host={$host_ahjr};dbname={$db_ahjr};charset=utf8mb4", $user_ahjr, $pass_ahjr, $options_ahjr);
        echo "✓ Base de datos ya existe\n\n";
    } catch (PDOException $e) {
        echo "  Creando base de datos '{$db_ahjr}'...\n";
        $pdoServer_ahjr->exec("CREATE DATABASE `{$db_ahjr}` DEFAULT CHARACTER SET utf8mb4 DEFAULT COLLATE utf8mb4_unicode_ci");
        $pdo_ahjr = new PDO("mysql:host={$host_ahjr};dbname={$db_ahjr};charset=utf8mb4", $user_ahjr, $pass_ahjr, $options_ahjr);
        echo "✓ Base de datos creada exitosamente\n\n";
    }

    // ================================================================
    // TABLA 1: td_usuarios_ahjr
    // ================================================================
    echo "► Creando tabla 'td_usuarios_ahjr'...\n";
    $pdo_ahjr->exec("CREATE TABLE IF NOT EXISTS `td_usuarios_ahjr` (
        `id_ahjr` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `nombre_ahjr` VARCHAR(80) NOT NULL,
        `apellido_ahjr` VARCHAR(100) NOT NULL,
        `email_ahjr` VARCHAR(120) NOT NULL,
        `clave_ahjr` VARCHAR(255) NOT NULL,
        `fecha_registro_ahjr` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `estado_ahjr` ENUM('activo','inactivo') NOT NULL DEFAULT 'inactivo',
        `rol_ahjr` ENUM('admin','beta','user') NOT NULL DEFAULT 'user',
        PRIMARY KEY (`id_ahjr`),
        UNIQUE KEY `uniq_email_ahjr` (`email_ahjr`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✓ Tabla 'td_usuarios_ahjr' creada\n\n";

    // ================================================================
    // TABLA 2: td_registro_pendiente_ahjr
    // ================================================================
    echo "► Creando tabla 'td_registro_pendiente_ahjr'...\n";
    $pdo_ahjr->exec("CREATE TABLE IF NOT EXISTS `td_registro_pendiente_ahjr` (
        `id_pendiente_ahjr` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `nombre_ahjr` VARCHAR(80) NOT NULL,
        `apellido_ahjr` VARCHAR(100) NOT NULL,
        `email_ahjr` VARCHAR(120) NOT NULL,
        `clave_ahjr` VARCHAR(255) NOT NULL,
        `otp_hash_ahjr` VARCHAR(255) NOT NULL,
        `otp_expira_ahjr` DATETIME NOT NULL,
        `creado_ahjr` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `usado_ahjr` TINYINT(1) NOT NULL DEFAULT 0,
        PRIMARY KEY (`id_pendiente_ahjr`),
        UNIQUE KEY `uniq_email_pendiente_ahjr` (`email_ahjr`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✓ Tabla 'td_registro_pendiente_ahjr' creada\n\n";

    // ================================================================
    // TABLA 3: td_reset_clave_ahjr (RECUPERADA)
    // ================================================================
    echo "► Creando tabla 'td_reset_clave_ahjr'...\n";
    $pdo_ahjr->exec("CREATE TABLE IF NOT EXISTS `td_reset_clave_ahjr` (
        `id_reset_ahjr` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `email_ahjr` VARCHAR(120) NOT NULL,
        `otp_hash_ahjr` VARCHAR(255) NOT NULL,
        `otp_expira_ahjr` DATETIME NOT NULL,
        `creado_ahjr` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `usado_ahjr` TINYINT(1) NOT NULL DEFAULT 0,
        PRIMARY KEY (`id_reset_ahjr`),
        UNIQUE KEY `uniq_email_reset_ahjr` (`email_ahjr`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✓ Tabla 'td_reset_clave_ahjr' creada\n\n";

    // ================================================================
    // TABLA 4: td_suscripciones_ahjr
    // ================================================================
    echo "► Creando tabla 'td_suscripciones_ahjr'...\n";
    $pdo_ahjr->exec("CREATE TABLE IF NOT EXISTS `td_suscripciones_ahjr` (
        `id_suscripcion_ahjr` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `id_usuario_suscripcion_ahjr` INT UNSIGNED NOT NULL,
        `nombre_servicio_ahjr` VARCHAR(100) NOT NULL,
        `costo_ahjr` DECIMAL(10,2) NOT NULL,
        `estado_ahjr` ENUM('activa','inactiva') NOT NULL DEFAULT 'activa',
        `frecuencia_ahjr` ENUM('mensual','anual') NOT NULL,
        `metodo_pago_ahjr` ENUM('MasterCard','Visa','GPay','PayPal') NOT NULL,
        `dia_cobro_ahjr` TINYINT UNSIGNED NOT NULL COMMENT 'Día del mes (1-31)',
        `mes_cobro_ahjr` TINYINT UNSIGNED NULL COMMENT 'Mes del año (1-12), solo para anuales',
        `fecha_ultimo_pago_ahjr` DATE NOT NULL,
        `fecha_creacion_ahjr` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `fecha_actualizacion_ahjr` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id_suscripcion_ahjr`),
        KEY `idx_usuario_suscripcion_ahjr` (`id_usuario_suscripcion_ahjr`),
        CONSTRAINT `fk_usuario_suscripcion_ahjr` FOREIGN KEY (`id_usuario_suscripcion_ahjr`) 
            REFERENCES `td_usuarios_ahjr`(`id_ahjr`) ON DELETE CASCADE,
        CONSTRAINT `chk_dia_cobro_ahjr` CHECK (`dia_cobro_ahjr` BETWEEN 1 AND 31),
        CONSTRAINT `chk_mes_cobro_ahjr` CHECK (`mes_cobro_ahjr` IS NULL OR `mes_cobro_ahjr` BETWEEN 1 AND 12)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✓ Tabla 'td_suscripciones_ahjr' creada\n\n";

    // ================================================================
    // TABLA 5: td_historial_pagos_ahjr (NUEVA)
    // ================================================================
    echo "► Creando tabla 'td_historial_pagos_ahjr' (NUEVA - para gráficas)...\n";
    $pdo_ahjr->exec("CREATE TABLE IF NOT EXISTS `td_historial_pagos_ahjr` (
        `id_historial_ahjr` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `id_suscripcion_historial_ahjr` INT UNSIGNED NOT NULL,
        `monto_pagado_ahjr` DECIMAL(10,2) NOT NULL,
        `fecha_pago_ahjr` DATE NOT NULL COMMENT 'Fecha del pago - Vital para gráficas',
        `metodo_pago_snapshot_ahjr` VARCHAR(20) NOT NULL COMMENT 'Método usado en ese momento',
        `creado_en_ahjr` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id_historial_ahjr`),
        KEY `idx_suscripcion_historial_ahjr` (`id_suscripcion_historial_ahjr`),
        KEY `idx_fecha_pago_ahjr` (`fecha_pago_ahjr`),
        CONSTRAINT `fk_suscripcion_historial_ahjr` FOREIGN KEY (`id_suscripcion_historial_ahjr`) 
            REFERENCES `td_suscripciones_ahjr`(`id_suscripcion_ahjr`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✓ Tabla 'td_historial_pagos_ahjr' creada\n\n";

    // ================================================================
    // TRIGGER: tr_actualizar_fecha_ahjr (RECUPERADO)
    // ================================================================
    echo "► Creando trigger 'tr_actualizar_fecha_ahjr'...\n";
    $pdo_ahjr->exec("DROP TRIGGER IF EXISTS `tr_actualizar_fecha_ahjr`");
    $pdo_ahjr->exec("
    CREATE TRIGGER `tr_actualizar_fecha_ahjr`
    BEFORE UPDATE ON `td_suscripciones_ahjr`
    FOR EACH ROW
    BEGIN
        SET NEW.fecha_actualizacion_ahjr = CURRENT_TIMESTAMP;
    END
    ");
    echo "✓ Trigger 'tr_actualizar_fecha_ahjr' creado\n\n";

    // ================================================================
    // STORED PROCEDURE: sp_crear_suscripcion_ahjr (RECUPERADO)
    // ================================================================
    echo "► Creando stored procedure 'sp_crear_suscripcion_ahjr'...\n";
    $pdo_ahjr->exec("DROP PROCEDURE IF EXISTS `sp_crear_suscripcion_ahjr`");
    $pdo_ahjr->exec("
    CREATE PROCEDURE `sp_crear_suscripcion_ahjr`(
        IN p_id_usuario INT UNSIGNED,
        IN p_nombre_servicio VARCHAR(100),
        IN p_costo DECIMAL(10,2),
        IN p_frecuencia ENUM('mensual','anual'),
        IN p_metodo_pago ENUM('MasterCard','Visa','GPay','PayPal'),
        IN p_dia_cobro TINYINT UNSIGNED,
        IN p_mes_cobro TINYINT UNSIGNED
    )
    BEGIN
        DECLARE v_fecha_ultimo_pago DATE;
        DECLARE v_dia_actual INT;
        DECLARE v_mes_actual INT;
        DECLARE v_anio_actual INT;
        
        SET v_dia_actual = DAY(CURDATE());
        SET v_mes_actual = MONTH(CURDATE());
        SET v_anio_actual = YEAR(CURDATE());
        
        IF p_frecuencia = 'mensual' THEN
            IF v_dia_actual >= p_dia_cobro THEN
                SET v_fecha_ultimo_pago = DATE(CONCAT(v_anio_actual, '-', LPAD(v_mes_actual, 2, '0'), '-', LPAD(p_dia_cobro, 2, '0')));
            ELSE
                SET v_fecha_ultimo_pago = DATE_SUB(
                    DATE(CONCAT(v_anio_actual, '-', LPAD(v_mes_actual, 2, '0'), '-', LPAD(p_dia_cobro, 2, '0'))),
                    INTERVAL 1 MONTH
                );
            END IF;
        ELSEIF p_frecuencia = 'anual' THEN
            IF v_mes_actual > p_mes_cobro OR (v_mes_actual = p_mes_cobro AND v_dia_actual >= p_dia_cobro) THEN
                SET v_fecha_ultimo_pago = DATE(CONCAT(v_anio_actual, '-', LPAD(p_mes_cobro, 2, '0'), '-', LPAD(p_dia_cobro, 2, '0')));
            ELSE
                SET v_fecha_ultimo_pago = DATE(CONCAT(v_anio_actual - 1, '-', LPAD(p_mes_cobro, 2, '0'), '-', LPAD(p_dia_cobro, 2, '0')));
            END IF;
        END IF;
        
        INSERT INTO `td_suscripciones_ahjr` (
            `id_usuario_suscripcion_ahjr`, `nombre_servicio_ahjr`, `costo_ahjr`, `frecuencia_ahjr`, 
            `metodo_pago_ahjr`, `dia_cobro_ahjr`, `mes_cobro_ahjr`, `fecha_ultimo_pago_ahjr`
        ) VALUES (
            p_id_usuario, p_nombre_servicio, p_costo, p_frecuencia, 
            p_metodo_pago, p_dia_cobro, p_mes_cobro, v_fecha_ultimo_pago
        );
        
        SELECT LAST_INSERT_ID() AS id_suscripcion_ahjr;
    END
    ");
    echo "✓ Stored procedure 'sp_crear_suscripcion_ahjr' creado\n\n";

    // ================================================================
    // DATA SEEDING - USUARIOS Y DATOS DE PRUEBA
    // ================================================================
    echo "► Verificando/Insertando usuarios de prueba...\n";

    // Usuario 1: Admin
    $stmt_check = $pdo_ahjr->prepare("SELECT id_ahjr FROM td_usuarios_ahjr WHERE email_ahjr = :email");
    $stmt_check->execute(['email' => 'admin@submate.app']);

    if (!$stmt_check->fetch()) {
        $hash_admin = password_hash('Admin123!', PASSWORD_BCRYPT);
        $pdo_ahjr->prepare("INSERT INTO td_usuarios_ahjr (nombre_ahjr, apellido_ahjr, email_ahjr, clave_ahjr, estado_ahjr, rol_ahjr)
                            VALUES (:nombre, :apellido, :email, :clave, 'activo', 'admin')")
            ->execute([
                'nombre' => 'Admin',
                'apellido' => 'SubMate',
                'email' => 'admin@submate.app',
                'clave' => $hash_admin
            ]);
        echo "  ✓ Usuario admin@submate.app creado\n";
    }

    // Usuario 2: Beta (con suscripciones y historial)
    $stmt_check->execute(['email' => 'beta@submate.app']);
    $beta_user = $stmt_check->fetch();

    if (!$beta_user) {
        $hash_beta = password_hash('Beta123!', PASSWORD_BCRYPT);
        $pdo_ahjr->prepare("INSERT INTO td_usuarios_ahjr (nombre_ahjr, apellido_ahjr, email_ahjr, clave_ahjr, estado_ahjr, rol_ahjr)
                            VALUES (:nombre, :apellido, :email, :clave, 'activo', 'beta')")
            ->execute([
                'nombre' => 'Beta',
                'apellido' => 'Tester',
                'email' => 'beta@submate.app',
                'clave' => $hash_beta
            ]);
        $beta_id = $pdo_ahjr->lastInsertId();
        echo "  ✓ Usuario beta@submate.app creado (ID: $beta_id)\n";

        // Crear suscripciones para Beta
        echo "  ► Creando suscripciones de prueba para Beta...\n";

        // Netflix
        $pdo_ahjr->prepare("INSERT INTO td_suscripciones_ahjr 
            (id_usuario_suscripcion_ahjr, nombre_servicio_ahjr, costo_ahjr, frecuencia_ahjr, metodo_pago_ahjr, dia_cobro_ahjr, mes_cobro_ahjr, fecha_ultimo_pago_ahjr)
            VALUES (:id_user, :nombre, :costo, :frecuencia, :metodo, :dia, :mes, :fecha_pago)")
            ->execute([
                'id_user' => $beta_id,
                'nombre' => 'Netflix',
                'costo' => 7.99,
                'frecuencia' => 'mensual',
                'metodo' => 'Visa',
                'dia' => 15,
                'mes' => null,
                'fecha_pago' => date('Y-m-15')
            ]);
        $netflix_id = $pdo_ahjr->lastInsertId();

        // Spotify
        $pdo_ahjr->prepare("INSERT INTO td_suscripciones_ahjr 
            (id_usuario_suscripcion_ahjr, nombre_servicio_ahjr, costo_ahjr, frecuencia_ahjr, metodo_pago_ahjr, dia_cobro_ahjr, mes_cobro_ahjr, fecha_ultimo_pago_ahjr)
            VALUES (:id_user, :nombre, :costo, :frecuencia, :metodo, :dia, :mes, :fecha_pago)")
            ->execute([
                'id_user' => $beta_id,
                'nombre' => 'Spotify',
                'costo' => 11.49,
                'frecuencia' => 'mensual',
                'metodo' => 'PayPal',
                'dia' => 5,
                'mes' => null,
                'fecha_pago' => date('Y-m-05')
            ]);
        $spotify_id = $pdo_ahjr->lastInsertId();

        // Crear historial de pagos (últimos 6 meses)
        echo "  ► Creando historial de pagos (6 meses)...\n";
        for ($i = 5; $i >= 0; $i--) {
            // Historial Netflix
            $pdo_ahjr->prepare("INSERT INTO td_historial_pagos_ahjr (id_suscripcion_historial_ahjr, monto_pagado_ahjr, fecha_pago_ahjr, metodo_pago_snapshot_ahjr) VALUES (?, ?, ?, ?)")
                ->execute([$netflix_id, 7.99, date('Y-m-15', strtotime("-$i months")), 'Visa']);
            // Historial Spotify
            $pdo_ahjr->prepare("INSERT INTO td_historial_pagos_ahjr (id_suscripcion_historial_ahjr, monto_pagado_ahjr, fecha_pago_ahjr, metodo_pago_snapshot_ahjr) VALUES (?, ?, ?, ?)")
                ->execute([$spotify_id, 11.49, date('Y-m-05', strtotime("-$i months")), 'PayPal']);
        }
        echo "    ✓ 12 registros de historial creados\n";
    }

    // Usuario 3: Usuario normal
    $stmt_check->execute(['email' => 'usuario@submate.app']);
    if (!$stmt_check->fetch()) {
        $hash_user = password_hash('User123!', PASSWORD_BCRYPT);
        $pdo_ahjr->prepare("INSERT INTO td_usuarios_ahjr (nombre_ahjr, apellido_ahjr, email_ahjr, clave_ahjr, estado_ahjr, rol_ahjr)
                            VALUES (:nombre, :apellido, :email, :clave, 'activo', 'user')")
            ->execute(['nombre' => 'Usuario', 'apellido' => 'Normal', 'email' => 'usuario@submate.app', 'clave' => $hash_user]);
        echo "  ✓ Usuario usuario@submate.app creado\n";
    }

    echo "\n==============================================\n";
    echo "✓ INICIALIZACIÓN COMPLETADA EXITOSAMENTE\n";
    echo "==============================================\n";
    echo "\nBase de datos: {$db_ahjr}\n";
    echo "Tablas creadas: 5\n";
    echo "  - td_usuarios_ahjr\n";
    echo "  - td_registro_pendiente_ahjr\n";
    echo "  - td_reset_clave_ahjr\n";
    echo "  - td_suscripciones_ahjr\n";
    echo "  - td_historial_pagos_ahjr\n\n";
    echo "Triggers: 1\n";
    echo "  - tr_actualizar_fecha_ahjr\n\n";
    echo "Stored Procedures: 1\n";
    echo "  - sp_crear_suscripcion_ahjr\n\n";
    echo "Usuarios de prueba: 3\n";
    echo "  1. admin@submate.app (admin) - Pass: Admin123!\n";
    echo "  2. beta@submate.app (beta) - Pass: Beta123!\n";
    echo "  3. usuario@submate.app (user) - Pass: User123!\n";
    echo "==============================================\n";

    exit(0);
} catch (Throwable $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "Línea: " . $e->getLine() . "\n\n";
    exit(1);
}
