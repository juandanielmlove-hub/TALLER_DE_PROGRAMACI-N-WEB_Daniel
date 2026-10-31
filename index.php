<?php
require_once "conexion.php";

// Categorías exigidas para el SELECT
$categorias_disponibles = [
    "Literatura y Novela",
    "Ciencia y Tecnología",
    "Historia y Geografía",
    "Desarrollo Personal",
    "Ciencias Exactas",
    "Arte y Diseño"
];

// PROCESO CREATE: Registrar libro
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["accion"]) && $_POST["accion"] === "registrar") {
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
        $sql = "INSERT INTO libros (codigo, titulo, autor, categoria, precio, stock) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("ssssdi", $codigo, $titulo, $autor, $categoria, $precio, $stock);
        $stmt->execute();
        $stmt->close();
        header("Location: index.php");
        exit;
    } else {
        $error = "Verifica los datos ingresados o la categoría seleccionada.";
    }
}

// PROCESO BUSCADOR (Reto 1)
$busqueda = trim($_GET["busqueda"] ?? "");
if ($busqueda !== "") {
    $sql_read = "SELECT * FROM libros WHERE titulo LIKE ? OR autor LIKE ? ORDER BY id DESC";
    $stmt_b = $conexion->prepare($sql_read);
    $param_like = "%" . $busqueda . "%";
    $stmt_b->bind_param("ss", $param_like, $param_like);
    $stmt_b->execute();
    $resultado = $stmt_b->get_result();
} else {
    $resultado = $conexion->query("SELECT * FROM libros ORDER BY id DESC");
}

// PROCESO ESTADÍSTICAS E INVENTARIO (Reto 3 y Reto 5)
$sql_stats = "SELECT COUNT(*) as total_libros, SUM(stock) as total_ejemplares, SUM(precio * stock) as valor_inventario FROM libros";
$res_stats = $conexion->query($sql_stats)->fetch_assoc();
$total_libros = $res_stats['total_libros'] ?? 0;
$total_ejemplares = $res_stats['total_ejemplares'] ?? 0;
$valor_inventario = $res_stats['valor_inventario'] ?? 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Archivo y Biblioteca Central - Gestión</title>
    <link rel="stylesheet" href="estilos.css">
</head>
<body>
<div class="contenedor">
    <div class="header-biblioteca">
        <div class="logo-biblioteca">📜</div>
        <div>
            <h1>Biblioteca y Archivo Histórico "El Saber"</h1>
            <p class="subtitulo">Registro y Custodia de Tomos Literarios</p>
        </div>
    </div>

    <!-- Tarjetas con Estadísticas (Reto 5) -->
    <div class="tarjetas-stats">
        <div class="card-stat">
            <h3>Títulos Catalogados</h3>
            <p><?= $total_libros ?></p>
        </div>
        <div class="card-stat">
            <h3>Ejemplares en Estantería</h3>
            <p><?= $total_ejemplares ?></p>
        </div>
        <div class="card-stat">
            <h3>Valor del Acervo</h3>
            <p>S/ <?= number_format($valor_inventario, 2) ?></p>
        </div>
    </div>

    <?php if (isset($error)): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <!-- Formulario de Registro -->
    <div class="seccion-formulario">
        <h2>Registro de Nuevo Tomo</h2>
        <form method="POST" action="index.php">
            <input type="hidden" name="accion" value="registrar">
            <div class="form-grid">
                <div>
                    <label>Código de Catálogo:</label>
                    <input type="text" name="codigo" placeholder="Ej. TOMO-001" maxlength="30" required>
                </div>
                <div>
                    <label>Título de la Obra:</label>
                    <input type="text" name="titulo" placeholder="Ej. El Señor de los Anillos" maxlength="150" required>
                </div>
                <div>
                    <label>Autor / Escritor:</label>
                    <input type="text" name="autor" placeholder="Ej. J.R.R. Tolkien" maxlength="100" required>
                </div>
                <div>
                    <label>Categoría:</label>
                    <select name="categoria" required>
                        <option value="">Seleccione sección...</option>
                        <?php foreach ($categorias_disponibles as $cat): ?>
                            <option value="<?= $cat ?>"><?= $cat ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>Precio Estimado (S/):</label>
                    <input type="number" name="precio" min="0" step="0.01" required>
                </div>
                <div>
                    <label>Stock (Ejemplares):</label>
                    <input type="number" name="stock" min="0" step="1" required>
                </div>
            </div>
            <button type="submit">Guardar en Archivo</button>
        </form>
    </div>

    <!-- Buscador de Productos (Reto 1) -->
    <div class="seccion-busqueda">
        <form method="GET" action="index.php">
            <label><strong>Búsqueda en Archivos (Título o Autor):</strong></label>
            <div style="display: flex; gap: 10px; margin-top: 5px;">
                <input type="text" name="busqueda" value="<?= htmlspecialchars($busqueda) ?>" placeholder="Buscar por título o autor antiguo...">
                <button type="submit" style="margin-top:0;">Buscar</button>
                <?php if ($busqueda !== ""): ?>
                    <a href="index.php" class="btn-secundario" style="padding: 10px 15px; display:flex; align-items:center;">Restaurar Vista</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <h2>Catálogo General de Libros</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Código</th>
                <th>Título</th>
                <th>Autor</th>
                <th>Categoría</th>
                <th>Precio</th>
                <th>Stock</th>
                <th>Fecha de Alta</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($resultado->num_rows > 0): ?>
            <?php while ($fila = $resultado->fetch_assoc()): ?>
                <!-- Reto 2: Alerta si el stock es menor a 5 -->
                <?php $es_stock_bajo = ($fila["stock"] < 5); ?>
                <tr class="<?= $es_stock_bajo ? 'stock-bajo' : '' ?>">
                    <td><?= $fila["id"] ?></td>
                    <td><?= htmlspecialchars($fila["codigo"]) ?></td>
                    <td>
                        <?= htmlspecialchars($fila["titulo"]) ?>
                        <?php if ($es_stock_bajo): ?>
                            <span class="badge-alerta" title="¡Ejemplares escasos!">Escaso</span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($fila["autor"]) ?></td>
                    <td><?= htmlspecialchars($fila["categoria"]) ?></td>
                    <td>S/ <?= number_format($fila["precio"], 2) ?></td>
                    <td><?= $fila["stock"] ?></td>
                    <td><?= $fila["fecha_registro"] ?></td>
                    <td>
                        <div class="acciones-tabla">
                            <a href="editar.php?id=<?= $fila["id"] ?>" class="btn-editar">Editar</a>
                            <form method="POST" action="eliminar.php" class="form-eliminar" onsubmit="return confirm('¿Seguro que desea retirar este libro del archivo?');">
                                <input type="hidden" name="id" value="<?= $fila["id"] ?>">
                                <button type="submit" class="btn-eliminar">Eliminar</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="9" style="text-align: center; padding: 25px; color: #665; font-style: italic;">No se encuentran registros en los archivos de la biblioteca.</td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>