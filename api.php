<?php
error_reporting(0);
require_once 'config.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

function jp() { $c = file_get_contents('php://input'); return $c ? json_decode($c, true) : []; }
function ok($d = []) { echo json_encode($d, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE); exit; }
function er($m, $c = 400) { http_response_code($c); echo json_encode(['error' => $m]); exit; }

switch ($action) {

    // LISTAR todos los contactos (con filtros opcionales)
    case 'list':
        $sql = 'SELECT * FROM contactos WHERE 1=1';
        $types = '';
        $params = [];

        $fi = trim($_GET['indicativo'] ?? '');
        $fp = trim($_GET['pais'] ?? '');
        $fm = trim($_GET['modalidad'] ?? '');
        $fd = trim($_GET['desde'] ?? '');
        $fh = trim($_GET['hasta'] ?? '');

        if ($fi !== '') { $sql .= ' AND indicativo LIKE ?'; $types .= 's'; $params[] = '%' . $fi . '%'; }
        if ($fp !== '') { $sql .= ' AND pais LIKE ?'; $types .= 's'; $params[] = '%' . $fp . '%'; }
        if ($fm !== '') { $sql .= ' AND modalidad = ?'; $types .= 's'; $params[] = $fm; }
        if ($fd !== '') { $sql .= ' AND hora_utc >= ?'; $types .= 's'; $params[] = $fd; }
        if ($fh !== '') { $sql .= ' AND hora_utc <= ?'; $types .= 's'; $params[] = $fh . ' 23:59:59'; }

        $sql .= ' ORDER BY hora_utc DESC';

        $st = $conn->prepare($sql);
        if (!empty($params)) { $st->bind_param($types, ...$params); }
        $st->execute();
        $rs = $st->get_result();
        $rows = [];
        while ($r = $rs->fetch_assoc()) {
            $r['id'] = intval($r['id']);
            $rows[] = $r;
        }
        $st->close();
        ok($rows);
        break;

    // OBTENER un contacto por ID
    case 'get':
        $id = intval($_GET['id'] ?? 0);
        if (!$id) er('ID invalido');
        $st = $conn->prepare('SELECT * FROM contactos WHERE id = ?');
        $st->bind_param('i', $id);
        $st->execute();
        $rs = $st->get_result();
        $row = $rs->fetch_assoc();
        $st->close();
        if (!$row) er('Contacto no encontrado', 404);
        $row['id'] = intval($row['id']);
        ok($row);
        break;

    // CREAR nuevo contacto
    case 'create':
        if ($method !== 'POST') er('Metodo no permitido', 405);
        $d = jp();
        $ind = strtoupper(trim($d['indicativo'] ?? ''));
        $nom = trim($d['nombre'] ?? '');
        $pai = trim($d['pais'] ?? '');
        $hor = trim($d['hora_utc'] ?? '');
        $fre = trim($d['frecuencia'] ?? '');
        $mod = strtoupper(trim($d['modalidad'] ?? ''));
        $sen = trim($d['senal'] ?? '');
        $obs = trim($d['observacion'] ?? '');

        if (!$ind || !$fre || !$mod || !$hor) er('Faltan campos obligatorios (indicativo, frecuencia, modalidad, hora UTC)');

        $st = $conn->prepare('INSERT INTO contactos (indicativo, nombre, pais, hora_utc, frecuencia, modalidad, senal, observacion) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $st->bind_param('ssssssss', $ind, $nom, $pai, $hor, $fre, $mod, $sen, $obs);
        $st->execute();
        $newId = $st->insert_id;
        $st->close();
        ok(['id' => $newId, 'msg' => 'Contacto guardado: ' . $ind]);
        break;

    // MODIFICAR contacto
    case 'update':
        if ($method !== 'POST') er('Metodo no permitido', 405);
        $d = jp();
        $id = intval($d['id'] ?? 0);
        if (!$id) er('ID invalido');

        $ind = strtoupper(trim($d['indicativo'] ?? ''));
        $nom = trim($d['nombre'] ?? '');
        $pai = trim($d['pais'] ?? '');
        $hor = trim($d['hora_utc'] ?? '');
        $fre = trim($d['frecuencia'] ?? '');
        $mod = strtoupper(trim($d['modalidad'] ?? ''));
        $sen = trim($d['senal'] ?? '');
        $obs = trim($d['observacion'] ?? '');

        if (!$ind || !$fre || !$mod || !$hor) er('Faltan campos obligatorios');

        $st = $conn->prepare('UPDATE contactos SET indicativo=?, nombre=?, pais=?, hora_utc=?, frecuencia=?, modalidad=?, senal=?, observacion=? WHERE id=?');
        $st->bind_param('ssssssssi', $ind, $nom, $pai, $hor, $fre, $mod, $sen, $obs, $id);
        $st->execute();
        $aff = $st->affected_rows;
        $st->close();
        ok(['msg' => 'Contacto actualizado', 'affected' => $aff]);
        break;

    // ELIMINAR contacto
    case 'delete':
        if ($method !== 'POST') er('Metodo no permitido', 405);
        $d = jp();
        $id = intval($d['id'] ?? 0);
        if (!$id) er('ID invalido');

        $st = $conn->prepare('DELETE FROM contactos WHERE id = ?');
        $st->bind_param('i', $id);
        $st->execute();
        $st->close();
        ok(['msg' => 'Contacto eliminado']);
        break;

    // ESTADISTICAS
    case 'stats':
        $rs = $conn->query('SELECT COUNT(*) as total, COUNT(DISTINCT indicativo) as indicativos, COUNT(DISTINCT pais) as paises, COUNT(DISTINCT modalidad) as modalidades FROM contactos');
        $s = $rs->fetch_assoc();
        $s['total'] = intval($s['total']);
        $s['indicativos'] = intval($s['indicativos']);
        $s['paises'] = intval($s['paises']);
        $s['modalidades'] = intval($s['modalidades']);

        $rs2 = $conn->query('SELECT modalidad, COUNT(*) as cant FROM contactos GROUP BY modalidad ORDER BY cant DESC');
        $s['por_modalidad'] = [];
        while ($r = $rs2->fetch_assoc()) $s['por_modalidad'][] = $r;

        ok($s);
        break;

    // EXPORTAR CSV
    case 'export_csv':
        $rs = $conn->query('SELECT indicativo, nombre, pais, hora_utc, frecuencia, modalidad, senal, observacion FROM contactos ORDER BY hora_utc DESC');
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=libro_guardia_' . date('Y-m-d') . '.csv');
        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
        fputcsv($out, ['Indicativo', 'Nombre', 'Pais', 'Hora UTC', 'Frecuencia', 'Modalidad', 'Senal', 'Observacion']);
        while ($r = $rs->fetch_assoc()) {
            fputcsv($out, [$r['indicativo'], $r['nombre'], $r['pais'], $r['hora_utc'], $r['frecuencia'], $r['modalidad'], $r['senal'], $r['observacion']]);
        }
        fclose($out);
        exit;

    default:
        er('Accion no valida: ' . $action, 404);
}

$conn->close();
