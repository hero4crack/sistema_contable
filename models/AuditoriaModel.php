<?php
declare(strict_types=1);

namespace Models;

use Core\AuditLogger;
use Core\Database;
use PDO;

/**
 * Modelo de Auditoría y Bitácora Global del Sistema
 */
final class AuditoriaModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
        AuditLogger::ensureTable($this->db);
    }

    /**
     * Construye cláusula WHERE y parámetros según filtros
     * @param array<string, mixed> $filtros
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function buildWhere(array $filtros): array
    {
        $conds  = ['1=1'];
        $params = [];

        $q = trim((string)($filtros['q'] ?? ''));
        if ($q !== '') {
            $conds[] = '(a.descripcion LIKE :q OR a.usuario_nombre LIKE :q2 OR a.usuario_email LIKE :q3 OR a.modulo LIKE :q4 OR a.accion LIKE :q5 OR a.ip_address LIKE :q6 OR a.datos_json LIKE :q7)';
            $like = '%' . $q . '%';
            $params[':q']  = $like;
            $params[':q2'] = $like;
            $params[':q3'] = $like;
            $params[':q4'] = $like;
            $params[':q5'] = $like;
            $params[':q6'] = $like;
            $params[':q7'] = $like;
        }

        $usuarioId = (int)($filtros['usuario_id'] ?? 0);
        if ($usuarioId > 0) {
            $conds[] = 'a.usuario_id = :usuario_id';
            $params[':usuario_id'] = $usuarioId;
        }

        $empresaId = (int)($filtros['empresa_id'] ?? 0);
        if ($empresaId > 0) {
            $conds[] = 'a.empresa_id = :empresa_id';
            $params[':empresa_id'] = $empresaId;
        }

        $modulo = trim((string)($filtros['modulo'] ?? ''));
        if ($modulo !== '') {
            $conds[] = 'a.modulo = :modulo';
            $params[':modulo'] = $modulo;
        }

        $tipo = trim((string)($filtros['tipo_evento'] ?? ''));
        if ($tipo === 'CAMBIOS') {
            $conds[] = "a.tipo_evento IN ('CREACION', 'ACTUALIZACION', 'ELIMINACION', 'SISTEMA')";
        } elseif ($tipo !== '') {
            $conds[] = 'a.tipo_evento = :tipo_evento';
            $params[':tipo_evento'] = $tipo;
        }

        $desde = trim((string)($filtros['fecha_desde'] ?? ''));
        if ($desde !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde)) {
            $conds[] = 'a.created_at >= :fecha_desde';
            $params[':fecha_desde'] = $desde . ' 00:00:00';
        }

        $hasta = trim((string)($filtros['fecha_hasta'] ?? ''));
        if ($hasta !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta)) {
            $conds[] = 'a.created_at <= :fecha_hasta';
            $params[':fecha_hasta'] = $hasta . ' 23:59:59';
        }

        return [implode(' AND ', $conds), $params];
    }

    /**
     * Lista registros paginados de auditoría
     * @param array<string, mixed> $filtros
     * @return array<string, mixed>
     */
    public function listar(array $filtros, int $page = 1, int $perPage = 50): array
    {
        $page    = max(1, $page);
        $perPage = max(10, min(200, $perPage));
        $offset  = ($page - 1) * $perPage;

        [$where, $params] = $this->buildWhere($filtros);

        $stmtCount = $this->db->prepare("SELECT COUNT(*) FROM auditoria_logs a WHERE {$where}");
        $stmtCount->execute($params);
        $total = (int)$stmtCount->fetchColumn();

        $sql = "SELECT a.*
                FROM auditoria_logs a
                WHERE {$where}
                ORDER BY a.id DESC
                LIMIT {$perPage} OFFSET {$offset}";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        return [
            'rows'        => $rows,
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $perPage,
            'total_pages' => max(1, (int)ceil($total / $perPage)),
        ];
    }

    /**
     * Obtiene contadores KPI según los filtros actuales
     * @param array<string, mixed> $filtros
     * @return array<string, int>
     */
    public function obtenerResumen(array $filtros): array
    {
        [$where, $params] = $this->buildWhere($filtros);

        $sql = "SELECT
                    COUNT(*) AS total,
                    SUM(CASE WHEN a.tipo_evento IN ('CREACION', 'ACTUALIZACION', 'ELIMINACION', 'SISTEMA') THEN 1 ELSE 0 END) AS cambios,
                    SUM(CASE WHEN a.tipo_evento = 'IMPRESION_EXPORTACION' THEN 1 ELSE 0 END) AS impresiones,
                    SUM(CASE WHEN a.tipo_evento = 'AUTENTICACION' THEN 1 ELSE 0 END) AS autenticacion,
                    SUM(CASE WHEN a.tipo_evento = 'CONSULTA' THEN 1 ELSE 0 END) AS consultas,
                    COUNT(DISTINCT a.usuario_id) AS usuarios_distintos
                FROM auditoria_logs a
                WHERE {$where}";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch() ?: [];

        return [
            'total'              => (int)($row['total'] ?? 0),
            'cambios'            => (int)($row['cambios'] ?? 0),
            'impresiones'        => (int)($row['impresiones'] ?? 0),
            'autenticacion'      => (int)($row['autenticacion'] ?? 0),
            'consultas'          => (int)($row['consultas'] ?? 0),
            'usuarios_distintos' => (int)($row['usuarios_distintos'] ?? 0),
        ];
    }

    /**
     * Obtiene opciones para los selectores de filtros
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function listarOpcionesFiltros(): array
    {
        $usuarios = $this->db->query(
            'SELECT id, nombre, email, rol FROM usuarios ORDER BY nombre ASC'
        )->fetchAll();

        $empresas = $this->db->query(
            'SELECT id, rif, razon_social FROM empresas ORDER BY razon_social ASC'
        )->fetchAll();

        $modulos = $this->db->query(
            'SELECT DISTINCT modulo FROM auditoria_logs ORDER BY modulo ASC'
        )->fetchAll();

        return [
            'usuarios' => $usuarios,
            'empresas' => $empresas,
            'modulos'  => $modulos,
        ];
    }

    /**
     * Obtiene registros para exportación CSV
     * @param array<string, mixed> $filtros
     * @return array<int, array<string, mixed>>
     */
    public function listarParaExportar(array $filtros, int $limit = 5000): array
    {
        [$where, $params] = $this->buildWhere($filtros);
        $limit = max(1, min(10000, $limit));

        $stmt = $this->db->prepare(
            "SELECT a.id, a.created_at, a.usuario_nombre, a.usuario_email, a.usuario_rol,
                    a.empresa_nombre, a.modulo, a.tipo_evento, a.metodo_http,
                    a.controlador, a.accion, a.descripcion, a.entidad_id,
                    a.codigo_http, a.ip_address
             FROM auditoria_logs a
             WHERE {$where}
             ORDER BY a.id DESC
             LIMIT {$limit}"
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
