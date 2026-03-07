<?php include "basedatos.php";?>
<?php require_once "assets/header.php"; ?>
<?php require_once "assets/footer.php"; ?>

<!-- Subida de bug a la BD -->
<?php
if (isset($_POST['submit_bug'])) {
    $titulo = trim($_POST['titulo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');

    if ($titulo === '' || $descripcion === '') {
        echo "<script>alert('Por favor, complete todos los campos.');</script>";
    } else {
        $stmt = $conn->prepare("INSERT INTO bugs (titulo, detalles) VALUES (?, ?)");
        $stmt->bind_param("ss", $titulo, $descripcion);
        if ($stmt->execute()) {
            echo "<script>alert('Bug reportado exitosamente.');</script>";
        } else {
            echo "<script>alert('Error al reportar el bug. Intente nuevamente.');</script>";
        }
        $stmt->close();
    }
}

if (isset($_POST['delete_bug'])) {
    $bugId = $_POST['bug_id'];
    $stmt = $conn->prepare("DELETE FROM bugs WHERE id = ?");
    $stmt->bind_param("i", $bugId);
    if ($stmt->execute()) {
        echo "<script>alert('Bug eliminado exitosamente.');</script>";
    } else {
        echo "<script>alert('Error al eliminar el bug. Intente nuevamente.');</script>";
    }
    $stmt->close();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/reportes.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <title>Reporte Bugs</title>
    </head>
<body>
    <!-- BUGS -->
    <div class="bugs-section">
        <h2 class="bugs-titulo">Reporte de Bugs</h2>
        <table class="bugs-table">
            <thead>
                <tr>
                    <th>Título</th>
                    <th>Descripción</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $result = mysqli_query($conn, "SELECT id, titulo, detalles FROM bugs ORDER BY id DESC");
                if ($result && mysqli_num_rows($result) > 0):
                    while ($row = mysqli_fetch_assoc($result)):
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['titulo']); ?></td>
                    <td><?php echo htmlspecialchars($row['detalles']); ?></td>
                    <td>
                        <form action="reportes.php" method="POST" style="display: inline;">
                            <input type="hidden" name="bug_id" value="<?php echo $row['id']; ?>">
                            <button type="submit" name="delete_bug" class="form-button">Eliminar</button>
                        </form>
                    </td>
                </tr>
                <?php
                    endwhile;
                else:
                ?>
                <tr>
                    <td colspan="2" class="changelog-empty">No hay entradas registradas.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <!-- INGRESAR BUGS -->
    <div class="form-container">
        <h2 class="form-titulo">Avisar Error</h2>
        <form action="reportes.php" method="POST" class="bug-form">
            <input type="text" name="titulo" placeholder="Título del Bug" required class="form-entrada">
            <textarea name="descripcion" placeholder="Descripción del Bug" required class="form-entrada"></textarea>
            <button type="submit" name="submit_bug" class="form-button">Enviar Bug</button>
        </form>
    </div>

</body>
<?php require_once "assets/footer.php"; ?>
</html>