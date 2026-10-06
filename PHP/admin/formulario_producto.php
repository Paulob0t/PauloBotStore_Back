<?php
require_once __DIR__ . '/../app/Core/Database.php';
require_once __DIR__ . '/../app/Models/Category.php';
require_once __DIR__ . '/../app/Models/Product.php';
require_once __DIR__ . '/../app/Controllers/ProductController.php';

use App\Controllers\ProductController;

$controller = new ProductController();
$data = $controller->getCreateViewData();
$categories = $data['categories'];
$userId = $data['userId'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Agregar Producto | PauloBot Store</title>

    <!-- Google Fonts & FontAwesome 6 (HTTPS CDN) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Vendor Styles (HTTPS CDN) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    <!-- Layout & Component CSS -->
    <link rel="stylesheet" href="./assets/css/layout.css">
    <link rel="stylesheet" href="./assets/css/formulario_producto.css">
</head>
<body>

    <div class="container-scroller">
        <!-- Navbar Component -->
        <?php include 'navbar.php'; ?>

        <div class="container-fluid page-body-wrapper">
            <!-- Sidebar Component -->
            <?php include 'menu.php'; ?>

            <!-- Panel Principal -->
            <div class="main-panel">
                <div class="content-wrapper p-4">

                    <div class="fp-page">
                        <!-- Header -->
                        <header class="fp-header">
                            <div class="fp-header-icon"><i class="fas fa-box-open"></i></div>
                            <div class="fp-header-text">
                                <h1>Agregar Nuevo Producto</h1>
                                <p>Registra un producto en el catálogo del sistema PauloBot Store</p>
                            </div>
                        </header>

                        <!-- Formulario de Producto -->
                        <form id="formProducto" autocomplete="off" enctype="multipart/form-data">
                            <input type="hidden" name="user_id" value="<?php echo htmlspecialchars((string)$userId); ?>" />
                            <input type="hidden" name="imagen_principal" id="imagen_principal" />
                            <input type="hidden" name="imagen_secundaria_1" id="imagen_secundaria_1" />
                            <input type="hidden" name="imagen_secundaria_2" id="imagen_secundaria_2" />
                            <input type="hidden" name="imagen_secundaria_3" id="imagen_secundaria_3" />

                            <div class="row g-4">
                                <div class="col-lg-8">
                                    <!-- 1. Datos Básicos -->
                                    <section class="fp-card">
                                        <header class="fp-card-head">
                                            <span class="fp-card-num">1</span>
                                            <div>
                                                <h2><i class="fas fa-info-circle"></i> Datos del Producto</h2>
                                                <span>Información general y descripción</span>
                                            </div>
                                        </header>
                                        <div class="fp-card-body">
                                            <div class="row g-3 mb-3">
                                                <div class="col-md-7">
                                                    <div class="form-group mb-0">
                                                        <label for="nombreProducto"><i class="fas fa-tag"></i> Nombre <span class="text-danger">*</span></label>
                                                        <input type="text" class="form-control" id="nombreProducto" name="nombre_producto" placeholder="Ej: Coca Cola 600ml" required>
                                                    </div>
                                                </div>
                                                <div class="col-md-5">
                                                    <div class="form-group mb-0">
                                                        <label for="sku"><i class="fas fa-barcode"></i> SKU / Código</label>
                                                        <input type="text" class="form-control" id="sku" name="sku" placeholder="COD-001">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="form-group mb-0">
                                                <label for="descripcionProducto"><i class="fas fa-align-left"></i> Descripción <span class="text-danger">*</span></label>
                                                <textarea class="form-control" id="descripcionProducto" name="descripcion" rows="3" placeholder="Describe brevemente el producto..." required></textarea>
                                            </div>
                                        </div>
                                    </section>

                                    <!-- 2. Clasificación -->
                                    <section class="fp-card">
                                        <header class="fp-card-head">
                                            <span class="fp-card-num">2</span>
                                            <div>
                                                <h2><i class="fas fa-folder-tree"></i> Clasificación</h2>
                                                <span>Categoría y subcategoría del producto</span>
                                            </div>
                                        </header>
                                        <div class="fp-card-body">
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <div class="form-group mb-0">
                                                        <label for="categoriaProducto"><i class="fas fa-folder"></i> Categoría <span class="text-danger">*</span></label>
                                                        <select class="form-select" id="categoriaProducto" name="id_categoria" required>
                                                            <option value="">-- Seleccionar Categoría --</option>
                                                            <?php foreach ($categories as $cat): ?>
                                                                <option value="<?php echo $cat['id']; ?>" data-subcategorias="<?php echo htmlspecialchars(json_encode($cat['subcategorias'])); ?>">
                                                                    <?php echo htmlspecialchars($cat['nombre']); ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group mb-0">
                                                        <label for="subcategoriaProducto"><i class="fas fa-folder-open"></i> Subcategoría</label>
                                                        <select class="form-select" id="subcategoriaProducto" name="id_subcategoria">
                                                            <option value="">-- Seleccionar Subcategoría (Opcional) --</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </section>

                                    <!-- 3. Precios y Stock -->
                                    <section class="fp-card">
                                        <header class="fp-card-head">
                                            <span class="fp-card-num">3</span>
                                            <div>
                                                <h2><i class="fas fa-coins"></i> Precios e Inventario</h2>
                                                <span>Precios, descuentos, ubicación y stock disponible</span>
                                            </div>
                                        </header>
                                        <div class="fp-card-body">
                                            <div class="row g-3 mb-3">
                                                <div class="col-md-4">
                                                    <div class="form-group mb-0">
                                                        <label for="precio"><i class="fas fa-dollar-sign"></i> Precio ($) <span class="text-danger">*</span></label>
                                                        <input type="number" step="0.01" min="0" class="form-control" id="precio" name="precio" placeholder="0.00" required>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group mb-0">
                                                        <label for="descuento"><i class="fas fa-percent"></i> Descuento ($)</label>
                                                        <input type="number" step="0.01" min="0" class="form-control" id="descuento" name="descuento" placeholder="0.00">
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group mb-0">
                                                        <label for="stock"><i class="fas fa-cubes"></i> Stock Inicial <span class="text-danger">*</span></label>
                                                        <input type="number" min="0" class="form-control" id="stock" name="stock" placeholder="10" required>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <div class="form-group mb-0">
                                                        <label for="ubicacion"><i class="fas fa-location-dot"></i> Ubicación (Letra + Número) <span class="text-danger">*</span></label>
                                                        <input type="text" class="form-control" id="ubicacion" name="ubicacion" placeholder="Ej: A1, B2" maxlength="3" required>
                                                    </div>
                                                </div>
                                                <div class="col-md-6 d-flex align-items-center mt-4">
                                                    <div class="form-check form-switch fs-5">
                                                        <input class="form-check-input" type="checkbox" id="activo" name="activo" checked>
                                                        <label class="form-check-input-label text-white fs-6 ms-2" for="activo">Producto Activo</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </section>
                                </div>

                                <div class="col-lg-4">
                                    <!-- 4. Imágenes del Producto -->
                                    <section class="fp-card">
                                        <header class="fp-card-head">
                                            <span class="fp-card-num">4</span>
                                            <div>
                                                <h2><i class="fas fa-image"></i> Imágenes</h2>
                                                <span>Imagen principal y secundarias</span>
                                            </div>
                                        </header>
                                        <div class="fp-card-body">
                                            <!-- Imagen Principal -->
                                            <div class="form-group">
                                                <label><i class="fas fa-star text-warning"></i> Imagen Principal <span class="text-danger">*</span></label>
                                                <div class="image-upload-box" onclick="document.getElementById('imgPrincipalFile').click();">
                                                    <i class="fas fa-cloud-arrow-up fs-2 text-primary mb-2"></i>
                                                    <p class="mb-0 text-muted small">Haz clic para subir imagen principal</p>
                                                    <input type="file" id="imgPrincipalFile" accept="image/*" class="d-none">
                                                    <img id="imgPrincipalPreview" class="image-upload-preview img-fluid d-none" alt="Previsualización" />
                                                </div>
                                            </div>

                                            <!-- Imágenes Secundarias -->
                                            <div class="row g-2">
                                                <div class="col-4">
                                                    <div class="image-upload-box p-2" onclick="document.getElementById('imgSec1File').click();">
                                                        <i class="fas fa-plus text-muted"></i>
                                                        <input type="file" id="imgSec1File" accept="image/*" class="d-none">
                                                        <img id="imgSec1Preview" class="image-upload-preview img-fluid d-none" alt="Previsualización 1" />
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="image-upload-box p-2" onclick="document.getElementById('imgSec2File').click();">
                                                        <i class="fas fa-plus text-muted"></i>
                                                        <input type="file" id="imgSec2File" accept="image/*" class="d-none">
                                                        <img id="imgSec2Preview" class="image-upload-preview img-fluid d-none" alt="Previsualización 2" />
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="image-upload-box p-2" onclick="document.getElementById('imgSec3File').click();">
                                                        <i class="fas fa-plus text-muted"></i>
                                                        <input type="file" id="imgSec3File" accept="image/*" class="d-none">
                                                        <img id="imgSec3Preview" class="image-upload-preview img-fluid d-none" alt="Previsualización 3" />
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </section>

                                    <!-- 5. Opciones Destacadas -->
                                    <section class="fp-card">
                                        <header class="fp-card-head">
                                            <span class="fp-card-num">5</span>
                                            <div>
                                                <h2><i class="fas fa-award"></i> Destacar</h2>
                                                <span>Aparecer en sección destacada</span>
                                            </div>
                                        </header>
                                        <div class="fp-card-body">
                                            <div class="form-check form-switch fs-5 mb-3">
                                                <input class="form-check-input" type="checkbox" id="destacado" name="destacado">
                                                <label class="form-check-input-label text-white fs-6 ms-2" for="destacado">Destacar Producto</label>
                                            </div>
                                            <div class="form-group mb-0 d-none" id="groupOrdenDestacado">
                                                <label for="orden_destacado">Número de Orden</label>
                                                <input type="number" min="1" class="form-control" id="orden_destacado" name="orden_destacado" placeholder="Ej: 1, 2, 3">
                                            </div>
                                        </div>
                                    </section>

                                    <!-- Botones de Acción -->
                                    <div class="fp-actions">
                                        <button type="submit" class="btn-save-product w-100" id="btnSubmitProducto">
                                            <i class="fas fa-save me-2"></i> GUARDAR PRODUCTO
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>

                    </div>

                </div>
            </div>
        </div>
    </div>

    <!-- Scripts JS Externos (HTTPS CDN) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Scripts JS Separados -->
    <script src="./assets/js/layout.js" defer></script>
    <script src="./assets/js/formulario_producto.js" defer></script>
</body>
</html>
