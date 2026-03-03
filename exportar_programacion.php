<?php
/**
 * exportar_programacion.php
 * Exporta la programación de partidos en un rango de fechas a Excel (XML SpreadsheetML).
 * Sin dependencias externas — compatible con Excel y LibreOffice.
 *
 * Parámetros GET:
 *   fechaInicio  YYYY-MM-DD
 *   fechaFin     YYYY-MM-DD
 *   torneos[]    IDs opcionales (sin selección = todos los torneos)
 */

session_start();
require_once 'config.php';

// ── 1. Validar parámetros ─────────────────────────────────────
$fi = $_GET['fechaInicio'] ?? '';
$ff = $_GET['fechaFin']    ?? '';

if (!$fi || !$ff || $ff < $fi) {
    http_response_code(400);
    die('Rango de fechas inválido.');
}

$torneosIds = isset($_GET['torneos']) ? (array)$_GET['torneos'] : [];

// ── 2. Consulta a la BD ───────────────────────────────────────
$conexion = getDBConnection();

$torneoWhere = '';
$params      = [$fi, $ff];
$types       = 'ss';

if (!empty($torneosIds)) {
    $placeholders = implode(',', array_fill(0, count($torneosIds), '?'));
    $torneoWhere  = "AND p.idTorneoPartido IN ($placeholders)";
    foreach ($torneosIds as $id) {
        $params[] = (int)$id;
        $types   .= 'i';
    }
}

$sql = "
    SELECT
        p.fecha                                             AS fecha,
        t.nombreTorneo                                      AS torneo,
        p.categoriaText                                     AS categoria,
        DATE_FORMAT(p.hora, '%h:%i %p')                    AS hora,
        p.canchaLugar                                       AS cancha,
        CONCAT(a1.nombre, ' ', a1.apellido)                AS central,
        CONCAT(a2.nombre, ' ', a2.apellido)                AS linea1,
        CONCAT(a3.nombre, ' ', a3.apellido)                AS linea2
    FROM partido p
    INNER JOIN torneo t   ON t.idTorneo   = p.idTorneoPartido
    LEFT  JOIN arbitro a1 ON a1.idArbitro = p.idArbitro1
    LEFT  JOIN arbitro a2 ON a2.idArbitro = p.idArbitro2
    LEFT  JOIN arbitro a3 ON a3.idArbitro = p.idArbitro3
    WHERE p.fecha BETWEEN ? AND ?
    $torneoWhere
    ORDER BY p.fecha, p.hora, t.nombreTorneo, p.categoriaText
";

$stmt = $conexion->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$rows   = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conexion->close();

// ── 3. Helpers ────────────────────────────────────────────────
function xmlEsc($s) {
    return htmlspecialchars($s ?? '', ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

$dias  = ['DOMINGO','LUNES','MARTES','MIERCOLES','JUEVES','VIERNES','SABADO'];
$meses = ['ENERO','FEBRERO','MARZO','ABRIL','MAYO','JUNIO',
          'JULIO','AGOSTO','SEPTIEMBRE','OCTUBRE','NOVIEMBRE','DICIEMBRE'];

// ── 4. Cabecera HTTP ──────────────────────────────────────────
$filename = "Programacion_desde_{$fi}_al_{$ff}.xls";
header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header("Content-Disposition: attachment; filename=\"{$filename}\"");
header('Cache-Control: max-age=0');

// ── 5. Generar XML SpreadsheetML ──────────────────────────────
echo "\xEF\xBB\xBF";
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<?mso-application progid="Excel.Sheet"?>' . "\n";
?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
          xmlns:o="urn:schemas-microsoft-com:office:office"
          xmlns:x="urn:schemas-microsoft-com:office:excel"
          xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
          xmlns:html="http://www.w3.org/TR/REC-html40">
  <Styles>
    <Style ss:ID="header">
      <Font ss:Bold="1" ss:Color="#FFFFFF" ss:Size="10"/>
      <Interior ss:Color="#858585" ss:Pattern="Solid"/>
      <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
    </Style>
    <Style ss:ID="dateRow">
      <Font ss:Bold="1" ss:Color="#000000" ss:Size="10"/>
      <Interior ss:Color="#ececec" ss:Pattern="Solid"/>
      <Alignment ss:Horizontal="Left" ss:Vertical="Center"/>
    </Style>
    <Style ss:ID="dataOdd">
      <Font ss:Size="9"/>
      <Alignment ss:Vertical="Center"/>
    </Style>
    <Style ss:ID="dataEven">
      <Font ss:Size="9"/>
      <Interior ss:Color="#c5c5c5" ss:Pattern="Solid"/>
      <Alignment ss:Vertical="Center"/>
    </Style>
  </Styles>
  <Worksheet ss:Name="Programacion">
    <Table ss:DefaultRowHeight="16">
      <Column ss:Width="130"/>
      <Column ss:Width="120"/>
      <Column ss:Width="75"/>
      <Column ss:Width="210"/>
      <Column ss:Width="145"/>
      <Column ss:Width="145"/>
      <Column ss:Width="145"/>
      <Row ss:Height="20">
        <Cell ss:StyleID="header"><Data ss:Type="String">Torneo</Data></Cell>
        <Cell ss:StyleID="header"><Data ss:Type="String">Categoria</Data></Cell>
        <Cell ss:StyleID="header"><Data ss:Type="String">Hora</Data></Cell>
        <Cell ss:StyleID="header"><Data ss:Type="String">Cancha</Data></Cell>
        <Cell ss:StyleID="header"><Data ss:Type="String">Central</Data></Cell>
        <Cell ss:StyleID="header"><Data ss:Type="String">Linea 1</Data></Cell>
        <Cell ss:StyleID="header"><Data ss:Type="String">Linea 2</Data></Cell>
      </Row>
<?php
$currentDate = null;
$rowNum      = 0;

foreach ($rows as $r) {

    if ($r['fecha'] !== $currentDate) {
        $currentDate = $r['fecha'];
        $ts          = strtotime($currentDate);
        $label       = $dias[(int)date('w', $ts)]
                     . ' ' . (int)date('j', $ts)
                     . ' DE ' . $meses[(int)date('n', $ts) - 1]
                     . ' DE ' . date('Y', $ts);

        echo "      <Row ss:Height=\"18\">\n";
        echo "        <Cell ss:StyleID=\"dateRow\" ss:MergeAcross=\"6\">"
           . "<Data ss:Type=\"String\">" . xmlEsc($label) . "</Data></Cell>\n";
        echo "      </Row>\n";

        $rowNum = 0;
    }

    $rowNum++;
    $style   = ($rowNum % 2 === 1) ? 'dataOdd' : 'dataEven';
    $central = trim($r['central'] ?? '');
    $linea1  = trim($r['linea1']  ?? '');
    $linea2  = trim($r['linea2']  ?? '');

    // Si el árbitro no existe, el CONCAT devuelve " " — limpiarlo
    if ($central === '') $central = '';
    if ($linea1  === '') $linea1  = '';
    if ($linea2  === '') $linea2  = '';

    echo "      <Row>\n";
    echo "        <Cell ss:StyleID=\"{$style}\"><Data ss:Type=\"String\">" . xmlEsc($r['torneo'])    . "</Data></Cell>\n";
    echo "        <Cell ss:StyleID=\"{$style}\"><Data ss:Type=\"String\">" . xmlEsc($r['categoria']) . "</Data></Cell>\n";
    echo "        <Cell ss:StyleID=\"{$style}\"><Data ss:Type=\"String\">" . xmlEsc($r['hora'])      . "</Data></Cell>\n";
    echo "        <Cell ss:StyleID=\"{$style}\"><Data ss:Type=\"String\">" . xmlEsc($r['cancha'])    . "</Data></Cell>\n";
    echo "        <Cell ss:StyleID=\"{$style}\"><Data ss:Type=\"String\">" . xmlEsc($central)        . "</Data></Cell>\n";
    echo "        <Cell ss:StyleID=\"{$style}\"><Data ss:Type=\"String\">" . xmlEsc($linea1)         . "</Data></Cell>\n";
    echo "        <Cell ss:StyleID=\"{$style}\"><Data ss:Type=\"String\">" . xmlEsc($linea2)         . "</Data></Cell>\n";
    echo "      </Row>\n";
}
?>
    </Table>
  </Worksheet>
</Workbook>