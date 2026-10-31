<?php
require_once "conexion.php";

$categorias_disponibles = [
    "Literatura y Novela",
    "Ciencia y Tecnología",
    "Historia y Geografía",
    "Desarrollo Personal",
    "Ciencias Exactas",
    "Arte y Diseño"
];

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    exit("ID de libro inválido.");
}

// Buscar libro para editar
$stmt = $conexion->prepare("SELECT * FROM libros WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$resultado = $stmt->get_result();
$libro = $resultado->fetch_assoc();
$stmt->close();

if (!$libro) {
    exit("Libro no encontrado.");
}

// Actualizar libro
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $codigo = trim($_POST["codigo"] ?? "");
    $titulo = trim($_POST["titulo"] ?? "");
    $autor = trim($_POST["autor"] ?? "");
    $categoria = trim($_POST["categoria"] ?? "");
    $precio = $_POST["precio"] ?? "";
    $stock = $_POST["stock"] ?? "";

    if (
        $codigo !== "" &&
        $titulo !== "" &&
        $autor !== "" &&
        in_array($categoria, $categorias_disponibles) &&
        is_numeric($precio) && $precio >= 0 &&
        filter_var($stock, FILTER_VALIDATE_INT) !== false && $stock >= 0
    ) {
        $sql = "UPDATE libros SET codigo = ?, titulo = ?, autor = ?, categoria = ?, precio = ?, stock = ? WHERE id = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("ssssdii", $codigo, $titulo, $autor, $categoria, $precio, $stock, $id);
        $stmt->execute();
        $stmt->close();
        header("Location: index.php");
        exit;
    } else {
        $error = "Verifica los datos ingresados.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Libro - Biblioteca</title>
    <link rel="stylesheet" href="estilos.css">
</head>
<body>
<div class="contenedor">
    <div class="header-biblioteca">
        <div class="logo-biblioteca">📖</div>
        <div>
            <h1>Editar Información del Libro</h1>
            <p class="subtitulo">Actualizar datos de catálogo</p>
        </div>
    </div>

    <?php if (isset($error)): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <div class="seccion-formulario">
        <form method="POST" action="editar.php?id=<?= $id ?>">
            <div class="form-grid">
                <div>
                    <label>Código:</label>
                    <input type="text" name="codigo" maxlength="30" required value="<?= htmlspecialchars($_POST["codigo"] ?? $libro["codigo"]) ?>">
                </div>
                <div>
                    <label>Título:</label>
                    <input type="text" name="titulo" maxlength="150" required value="<?= htmlspecialchars($_POST["titulo"] ?? $libro["titulo"]) ?>">
                </div>
                <div>
                    <label>Autor:</label>
                    <input type="text" name="autor" maxlength="100" required value="<?= htmlspecialchars($_POST["autor"] ?? $libro["autor"]) ?>">
                </div>
                <div>
                    <label>Categoría:</label>
                    <select name="categoria" required>
                        <option value="">Seleccione...</option>
                        <?php foreach ($categorias_disponibles as $cat): ?>
                            <option value="<?= $cat ?>" <?= ((($_POST["categoria"] ?? $libro["categoria"]) === $cat) ? "selected" : "") ?>><?= $cat ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>Precio (S/):</label>
                    <input type="number" name="precio" min="0" step="0.01" required value="<?= htmlspecialchars((string)($_POST["precio"] ?? $libro["precio"])) ?>">
                </div>
                <div>
                    <label>Stock:</label>
                    <input type="number" name="stock" min="0" step="1" required value="<?= htmlspecialchars((string)($_POST["stock"] ?? $libro["stock"])) ?>">
                </div>
            </div>
            <div style="margin-top: 20px; display: flex; gap: 10px;">
                <button type="submit">Guardar Cambios</button>
                <a href="index.php" class="btn-secundario" style="margin-top: 15px; display: inline-block; text-align: center;">Volver al Listado</a>
            </div>
        </form>
    </div>
</div>
</body>
</html>