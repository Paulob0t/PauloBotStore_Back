import os
import io
import re
import base64
from PIL import Image
from app.core.config import settings

def ensure_upload_dirs():
    """Garantiza que existan los directorios para almacenar archivos de forma persistente."""
    products_dir = os.path.join(settings.UPLOAD_DIR, "products")
    categories_dir = os.path.join(settings.UPLOAD_DIR, "categories")
    os.makedirs(products_dir, exist_ok=True)
    os.makedirs(categories_dir, exist_ok=True)
    return products_dir, categories_dir

def save_image_from_base64(base64_str: str, subfolder: str, filename_prefix: str, max_size: tuple = (800, 800), quality: int = 82) -> str:
    """
    Procesa un payload de imagen en Base64 o data URI, lo optimiza a WebP con Pillow
    y lo guarda en la carpeta local uploads/<subfolder>/.
    Retorna la ruta relativa: 'uploads/<subfolder>/<filename>.webp'
    """
    if not base64_str or len(base64_str) < 10:
        return None

    # Limpiar encabezado data:image/...;base64, si existe
    if "," in base64_str:
        base64_str = base64_str.split(",", 1)[1]

    try:
        image_bytes = base64.b64decode(base64_str)
        image = Image.open(io.BytesIO(image_bytes))

        # Convertir a RGBA o RGB según si tiene transparencia
        if image.mode in ("RGBA", "LA") or (image.mode == "P" and "transparency" in image.info):
            image = image.convert("RGBA")
        else:
            image = image.convert("RGB")

        # Redimensionar manteniendo proporción (thumbnail)
        image.thumbnail(max_size, Image.Resampling.LANCZOS)

        # Crear carpeta de destino
        target_dir = os.path.join(settings.UPLOAD_DIR, subfolder)
        os.makedirs(target_dir, exist_ok=True)

        filename = f"{filename_prefix}.webp"
        file_path = os.path.join(target_dir, filename)

        # Guardar en formato WebP optimizado
        image.save(file_path, format="WEBP", quality=quality, optimize=True)

        # Retornar ruta relativa normalizada
        return f"uploads/{subfolder}/{filename}"
    except Exception as e:
        print(f"Error procesando imagen para {filename_prefix}: {e}")
        return None

def get_full_image_path(relative_path: str) -> str:
    """Retorna la ruta absoluta completa para una imagen almacenada."""
    if not relative_path:
        return None
    if os.path.isabs(relative_path):
        return relative_path
    
    # Si viene como 'uploads/products/xyz.webp'
    base_dir = os.path.abspath(os.path.join(os.path.dirname(__file__), "../../"))
    return os.path.join(base_dir, relative_path)
