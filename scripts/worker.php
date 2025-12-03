<?php

/**
 * SubMate Daily Worker (Queue Based)
 * ==================================
 * Ejecutar diariamente (Cron Job)
 * php scripts/worker.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use App\core\Database;
use App\services\AlertsService;

// Cargar variables de entorno
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

echo "=== SubMate Worker: " . date('Y-m-d H:i:s') . " ===\n";

try {
    $db = Database::getDB_AHJR();
    $alerts = new AlertsService();

    // ==================================================================
    // FASE A: GENERACIÓN DE TAREAS (Llenar la Cola)
    // ==================================================================
    echo "► FASE A: Generando tareas de recordatorio...\n";

    $sql = "
        SELECT 
            s.id_suscripcion_ahjr,
            s.id_usuario_suscripcion_ahjr,
            s.fecha_proximo_pago_ahjr,
            s.frecuencia_ahjr,
            s.dia_cobro_ahjr,
            s.mes_cobro_ahjr
        FROM td_suscripciones_ahjr s
        WHERE s.estado_ahjr = 'activa'
    ";

    $stmt = $db->query($sql);
    $suscripciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $tareasGeneradas = 0;
    foreach ($suscripciones as $sub) {
        $hoyTs = strtotime(date('Y-m-d'));
        $targetDate = $sub['fecha_proximo_pago_ahjr'];
        if (!$targetDate) {
            $frecuencia = strtoupper($sub['frecuencia_ahjr']);
            $dia = (int)$sub['dia_cobro_ahjr'];
            $mes = $sub['mes_cobro_ahjr'] ? (int)$sub['mes_cobro_ahjr'] : null;

            $base = new DateTime();
            if ($frecuencia === 'MENSUAL') {
                $year = (int)$base->format('Y');
                $month = (int)$base->format('n');
                $dayToday = (int)$base->format('j');
                if ($dayToday >= $dia) {
                    $base->modify('+1 month');
                    $year = (int)$base->format('Y');
                    $month = (int)$base->format('n');
                }
                $lastDay = (int)$base->format('t');
                $useDay = $dia > $lastDay ? $lastDay : $dia;
                $targetDate = sprintf('%04d-%02d-%02d', $year, $month, $useDay);
            } elseif ($frecuencia === 'ANUAL') {
                $year = (int)$base->format('Y');
                $month = $mes ?: (int)$base->format('n');
                $dayToday = (int)$base->format('j');
                $monthToday = (int)$base->format('n');
                if ($monthToday > $month || ($monthToday === $month && $dayToday >= $dia)) {
                    $year += 1;
                }
                $lastDay = (int)date('t', strtotime(sprintf('%04d-%02d-01', $year, $month)));
                $useDay = $dia > $lastDay ? $lastDay : $dia;
                $targetDate = sprintf('%04d-%02d-%02d', $year, $month, $useDay);
            } else {
                $targetDate = date('Y-m-d', strtotime('+1 week'));
            }
        }

        $targetTs = strtotime($targetDate);
        if ($targetTs === false || $targetTs < $hoyTs) {
            continue;
        }
        $dias = (int)ceil(($targetTs - $hoyTs) / 86400);
        if (!in_array($dias, [3, 7, 15], true)) {
            continue;
        }
        $tipoAlerta = "RECORDATORIO_{$dias}";
        $fechaProgramada = date('Y-m-d');

        $checkSql = "SELECT id_ahjr FROM td_email_pendientes_ahjr 
                     WHERE id_suscripcion_ahjr = :subId 
                     AND tipo_alerta_ahjr = :tipo 
                     AND fecha_envio_programada_ahjr = :fecha";
        $checkStmt = $db->prepare($checkSql);
        $checkStmt->execute([
            'subId' => $sub['id_suscripcion_ahjr'],
            'tipo' => $tipoAlerta,
            'fecha' => $fechaProgramada
        ]);
        if (!$checkStmt->fetch()) {
            $insertSql = "INSERT INTO td_email_pendientes_ahjr 
                          (id_usuario_ahjr, id_suscripcion_ahjr, tipo_alerta_ahjr, fecha_envio_programada_ahjr, estado_ahjr)
                          VALUES (:userId, :subId, :tipo, :fecha, 'PENDIENTE')";
            $insertStmt = $db->prepare($insertSql);
            $insertStmt->execute([
                'userId' => $sub['id_usuario_suscripcion_ahjr'],
                'subId' => $sub['id_suscripcion_ahjr'],
                'tipo' => $tipoAlerta,
                'fecha' => $fechaProgramada
            ]);
            $tareasGeneradas++;
        }
    }
    echo "  ✓ {$tareasGeneradas} tareas nuevas generadas.\n";


    // ==================================================================
    // FASE B: PROCESAMIENTO DE COLA (Enviar Emails)
    // ==================================================================
    echo "► FASE B: Procesando cola de envíos...\n";

    // Buscar tareas pendientes para hoy o antes
    $queueSql = "
        SELECT 
            q.id_ahjr as id_queue,
            q.tipo_alerta_ahjr,
            s.*,
            DATEDIFF(s.fecha_proximo_pago_ahjr, CURDATE()) as dias_restantes
        FROM td_email_pendientes_ahjr q
        JOIN td_suscripciones_ahjr s ON q.id_suscripcion_ahjr = s.id_suscripcion_ahjr
        WHERE q.estado_ahjr = 'PENDIENTE'
        AND q.fecha_envio_programada_ahjr <= CURDATE()
    ";

    $queueStmt = $db->query($queueSql);
    $cola = $queueStmt->fetchAll(PDO::FETCH_ASSOC);
    $procesados = 0;

    foreach ($cola as $item) {
        $queueId = $item['id_queue'];
        $nombre = $item['nombre_servicio_ahjr'];
        $dias = (int)$item['dias_restantes']; // Recalcular real o usar el tipo de alerta

        // Mapear ID para AlertsService
        $item['id_usuario_ahjr'] = $item['id_usuario_suscripcion_ahjr'];

        echo "  • Procesando ID {$queueId}: {$nombre} ({$item['tipo_alerta_ahjr']})... ";

        try {
            $enviado = false;

            // Determinar acción según tipo
            if (strpos($item['tipo_alerta_ahjr'], 'RECORDATORIO') !== false) {
                // Extraer días del tipo si es necesario, o usar dias_restantes
                $enviado = $alerts->enviarRecordatorio_AHJR($item, $dias);
            }

            if ($enviado) {
                // Marcar como ENVIADO
                $updateSql = "UPDATE td_email_pendientes_ahjr SET estado_ahjr = 'ENVIADO' WHERE id_ahjr = :id";
                $db->prepare($updateSql)->execute(['id' => $queueId]);
                echo "✓ Enviado\n";
            } else {
                // Marcar como FALLIDO (o dejar pendiente para reintento, aquí marcamos fallido para no buclear)
                $updateSql = "UPDATE td_email_pendientes_ahjr SET estado_ahjr = 'FALLIDO' WHERE id_ahjr = :id";
                $db->prepare($updateSql)->execute(['id' => $queueId]);
                echo "✗ Falló (Mailer)\n";
            }
        } catch (Exception $e) {
            // Error de excepción
            $updateSql = "UPDATE td_email_pendientes_ahjr SET estado_ahjr = 'FALLIDO' WHERE id_ahjr = :id";
            $db->prepare($updateSql)->execute(['id' => $queueId]);
            echo "✗ Error: " . $e->getMessage() . "\n";
        }
        $procesados++;
    }

    echo "✓ Worker finalizado. {$procesados} tareas procesadas.\n";
} catch (Exception $e) {
    echo "\n❌ ERROR CRÍTICO WORKER: " . $e->getMessage() . "\n";
    exit(1);
}
