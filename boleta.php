<?php
ob_start();
session_start();
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

require('fpdf.php');
include('conexion.php');

// Función segura y estandarizada para tildes y caracteres especiales en FPDF
if (!function_exists('txt')) {
    function txt($str) {
        if ($str === null || $str === '') return '';
        // Evitar doble conversión si la cadena ya no es UTF-8
        if (!mb_check_encoding($str, 'UTF-8')) {
            return $str;
        }
        if (function_exists('iconv')) {
            $conv = @iconv('UTF-8', 'windows-1252//TRANSLIT', $str);
            if ($conv !== false) return $conv;
        }
        return mb_convert_encoding($str, 'windows-1252', 'UTF-8');
    }
}

if (!isset($_SESSION['correo'])) {
    header("location: index.php");
    exit();
}

$correo_actual = $_SESSION['correo'];

// Buscamos los datos del alumno que inició sesión con SENTENCIA PREPARADA
$stmt_al = $conexion->prepare("SELECT a.Matricula, a.Nombre, a.Apellidos FROM Alumnos a JOIN Usuarios u ON a.IdUsuario = u.IdUsuario WHERE u.Correo = ?");
if ($stmt_al) {
    $stmt_al->bind_param("s", $correo_actual);
    $stmt_al->execute();
    $query = $stmt_al->get_result();

    if ($query && $query->num_rows > 0) {
        $alumno = $query->fetch_assoc();
        $matricula = $alumno['Matricula'];
        $nombre_completo = $alumno['Nombre'] . " " . $alumno['Apellidos']; 
    } else {
        die("No se encontraron datos del alumno.");
    }
    $stmt_al->close();
} else {
    die("Error al consultar datos del alumno.");
}

// Consultamos sus calificaciones con SENTENCIA PREPARADA
$stmt_cal = $conexion->prepare("SELECT m.NombreMateria, p.Nombre AS NomProfe, p.Apellidos AS ApeProfe, c.Calificacion, c.FechaRegistro 
        FROM Calificaciones c
        JOIN Materias m ON c.IdMateria = m.IdMateria
        JOIN Profesores p ON c.IdProfesor = p.IdProfesor
        WHERE c.Matricula = ?
        ORDER BY c.FechaRegistro DESC");
if ($stmt_cal) {
    $stmt_cal->bind_param("s", $matricula);
    $stmt_cal->execute();
    $resultados = $stmt_cal->get_result();
} else {
    die("Error al consultar calificaciones.");
}

// Diseñamos el PDF
class PDF extends FPDF {
    // Cabecera de página
    function Header() {
        $this->SetFont('Arial','B',16);
        $this->Cell(0,10,txt('Sistema de Gestión Escolar'),0,1,'C');
        $this->SetFont('Arial','B',12);
        $this->Cell(0,10,txt('Boleta Oficial de Calificaciones'),0,1,'C');
        $this->Ln(5);
    }
    // Pie de página
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->Cell(0,10,txt('Página ').$this->PageNo().'/{nb} - Sistema Escolar',0,0,'C');
    }
}

// Creación del documento
$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Arial','',12);

// Datos del estudiante
$pdf->Cell(0,10,txt('Matrícula: ' . $matricula),0,1);
$pdf->Cell(0,10,txt('Estudiante: ' . $nombre_completo),0,1);
$pdf->Cell(0,10,txt('Fecha de emisión: ' . date('d/m/Y')),0,1);
$pdf->Ln(5);

// Colores, grosor de línea y fuente en negrita para los encabezados de la tabla
$pdf->SetFillColor(41, 128, 185);
$pdf->SetTextColor(255);
$pdf->SetDrawColor(31, 97, 141);
$pdf->SetFont('Arial','B',10);

// Encabezados
$pdf->Cell(60,10,txt('Materia'),1,0,'C',true);
$pdf->Cell(60,10,txt('Profesor'),1,0,'C',true);
$pdf->Cell(30,10,txt('Calificación'),1,0,'C',true);
$pdf->Cell(40,10,txt('Fecha'),1,1,'C',true);

// Restauración de colores y fuentes para los datos
$pdf->SetFillColor(224, 235, 255);
$pdf->SetTextColor(0);
$pdf->SetFont('Arial','',10);

$promedio = 0;
$total_materias = 0;
$fill = false;

if($resultados && $resultados->num_rows > 0) {
    while($row = $resultados->fetch_assoc()) {
        $pdf->Cell(60,10,txt($row['NombreMateria']),'LR',0,'L',$fill);
        $pdf->Cell(60,10,txt($row['NomProfe'] . ' ' . $row['ApeProfe']),'LR',0,'L',$fill);
        $pdf->Cell(30,10,$row['Calificacion'],'LR',0,'C',$fill);
        $pdf->Cell(40,10,date("d/m/Y", strtotime($row['FechaRegistro'])),'LR',1,'C',$fill);
        
        $promedio += $row['Calificacion'];
        $total_materias++;
        $fill = !$fill;
    }
    $pdf->Cell(190,0,'','T');
    $pdf->Ln(2);

    $promedio = $promedio / $total_materias;
    $pdf->SetFont('Arial','B',10);
    $pdf->Cell(120,10,txt('PROMEDIO GENERAL'),1,0,'R');
    
    if($promedio < 70) { $pdf->SetTextColor(200,0,0); } 
    else { $pdf->SetTextColor(0,100,0); }
    
    $pdf->Cell(30,10,number_format($promedio, 2),1,1,'C');
} else {
    $pdf->Cell(190,10,txt('No hay calificaciones registradas.'),1,1,'C');
}

ob_end_clean();
$pdf->Output('I', 'Boleta_'.$matricula.'.pdf');
exit();
?>