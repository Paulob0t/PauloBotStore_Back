import os
from typing import List, Optional
from sqlalchemy.orm import Session
from fastapi import HTTPException, status
from app.modules.products.repository import ProductRepository
from app.modules.products.models import Producto, Categoria, Subcategoria
from app.modules.products.schemas import (
    CreateProductRequest,
    UpdateProductRequest,
    CreateCategoryRequest,
    UpdateCategoryRequest,
    CreateSubcategoryRequest,
    UpdateSubcategoryRequest,
    ProductDto,
    CategoryDto,
    SubcategoryDto,
    SubcategoryDetailDto,
    ProductResponse,
    CategoryResponse
)
from app.core.image_storage import save_image_from_base64, get_full_image_path


class ProductService:
    def __init__(self, db: Session):
        self.repository = ProductRepository(db)

    # ------------------ Servicios de Categorías ------------------ #

    def get_all_categories_tree(self) -> List[CategoryDto]:
        categories = self.repository.get_categories_with_subcategories()
        result = []
        for cat in categories:
            sub_dtos = [
                SubcategoryDto(
                    id=sub.id_subcategoria,
                    id_categoria=sub.id_categoria,
                    nombre=sub.nombre_subcategoria,
                    tiene_imagen=1 if sub.imagen_subcategoria else 0
                )
                for sub in cat.subcategorias
            ]
            result.append(
                CategoryDto(
                    id=cat.id_categoria,
                    nombre=cat.nombre_categoria,
                    subcategorias=sub_dtos,
                    tiene_imagen=1 if cat.imagen_categoria else 0
                )
            )
        return result

    def get_category_image_path(self, id_categoria: int) -> Optional[str]:
        cat = self.repository.get_category_by_id(id_categoria)
        if not cat or not cat.imagen_categoria:
            return None
        return get_full_image_path(cat.imagen_categoria)

    def create_category(self, req: CreateCategoryRequest) -> CategoryResponse:
        name = (req.nombre_categoria or req.nombre or "").strip()
        if not name:
            raise HTTPException(status_code=status.HTTP_400_BAD_REQUEST, detail="El nombre de la categoría es obligatorio.")

        cat = self.repository.create_category(nombre=name)

        if req.imagen and req.imagen.startswith("data:"):
            cat.imagen_categoria = save_image_from_base64(
                req.imagen,
                subfolder="categories",
                filename_prefix=f"cat_{cat.id_categoria}"
            )
            self.repository.update_category(cat)

        # Crear subcategorías iniciales si vienen
        if req.subcategorias:
            for sub_name in req.subcategorias:
                sub_clean = sub_name.strip()
                if sub_clean:
                    self.repository.create_subcategory(id_categoria=cat.id_categoria, nombre=sub_clean)

        return CategoryResponse(
            success=True,
            message=f"Categoría '{cat.nombre_categoria}' creada exitosamente.",
            id_categoria=cat.id_categoria
        )

    def update_category(self, id_categoria: int, req: UpdateCategoryRequest) -> CategoryResponse:
        cat = self.repository.get_category_by_id(id_categoria)
        if not cat:
            raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail=f"Categoría #{id_categoria} no encontrada.")

        name = (req.nombre_categoria or req.nombre)
        if name and name.strip():
            cat.nombre_categoria = name.strip()

        if req.imagen and req.imagen.startswith("data:"):
            cat.imagen_categoria = save_image_from_base64(
                req.imagen,
                subfolder="categories",
                filename_prefix=f"cat_{id_categoria}"
            )

        self.repository.update_category(cat)
        return CategoryResponse(
            success=True,
            message="Categoría actualizada exitosamente.",
            id_categoria=id_categoria
        )

    def delete_category(self, id_categoria: int) -> CategoryResponse:
        cat = self.repository.get_category_by_id(id_categoria)
        if not cat:
            raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail=f"Categoría #{id_categoria} no encontrada.")

        self.repository.delete_category(cat)
        return CategoryResponse(
            success=True,
            message=f"Categoría #{id_categoria} y sus subcategorías asociadas han sido eliminadas.",
            id_categoria=id_categoria
        )

    # ------------------ Servicios de Subcategorías ------------------ #

    def get_all_subcategories(self) -> List[SubcategoryDetailDto]:
        subcategories = self.repository.get_all_subcategories()
        return [
            SubcategoryDetailDto(
                id_subcategoria=s.id_subcategoria,
                id_categoria=s.id_categoria,
                nombre_subcategoria=s.nombre_subcategoria,
                nombre_categoria=s.categoria.nombre_categoria if s.categoria else None,
                tiene_imagen=1 if s.imagen_subcategoria else 0
            )
            for s in subcategories
        ]

    def create_subcategory(self, req: CreateSubcategoryRequest, id_categoria: Optional[int] = None) -> CategoryResponse:
        cat_id = id_categoria or req.id_categoria
        if not cat_id:
            raise HTTPException(status_code=status.HTTP_400_BAD_REQUEST, detail="Se requiere el ID de la categoría principal.")

        name = (req.nombre_subcategoria or req.nombre or "").strip()
        if not name:
            raise HTTPException(status_code=status.HTTP_400_BAD_REQUEST, detail="El nombre de la subcategoría es obligatorio.")

        sub = self.repository.create_subcategory(id_categoria=cat_id, nombre=name)

        if req.imagen and req.imagen.startswith("data:"):
            sub.imagen_subcategoria = save_image_from_base64(
                req.imagen,
                subfolder="categories",
                filename_prefix=f"sub_{sub.id_subcategoria}"
            )
            self.repository.update_subcategory(sub)

        return CategoryResponse(
            success=True,
            message=f"Subcategoría '{sub.nombre_subcategoria}' creada exitosamente.",
            id_subcategoria=sub.id_subcategoria,
            id_categoria=cat_id
        )

    def update_subcategory(self, id_subcategoria: int, req: UpdateSubcategoryRequest) -> CategoryResponse:
        sub = self.repository.get_subcategory_by_id(id_subcategoria)
        if not sub:
            raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail=f"Subcategoría #{id_subcategoria} no encontrada.")

        name = (req.nombre_subcategoria or req.nombre)
        if name and name.strip():
            sub.nombre_subcategoria = name.strip()

        if req.id_categoria:
            sub.id_categoria = req.id_categoria

        if req.imagen and req.imagen.startswith("data:"):
            sub.imagen_subcategoria = save_image_from_base64(
                req.imagen,
                subfolder="categories",
                filename_prefix=f"sub_{id_subcategoria}"
            )

        self.repository.update_subcategory(sub)
        return CategoryResponse(
            success=True,
            message="Subcategoría actualizada exitosamente.",
            id_subcategoria=id_subcategoria,
            id_categoria=sub.id_categoria
        )

    def delete_subcategory(self, id_subcategoria: int) -> CategoryResponse:
        sub = self.repository.get_subcategory_by_id(id_subcategoria)
        if not sub:
            raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail=f"Subcategoría #{id_subcategoria} no encontrada.")

        self.repository.delete_subcategory(sub)
        return CategoryResponse(
            success=True,
            message=f"Subcategoría #{id_subcategoria} eliminada exitosamente.",
            id_subcategoria=id_subcategoria
        )

    def get_subcategory_image_path(self, id_subcategoria: int) -> Optional[str]:
        sub = self.repository.get_subcategory_by_id(id_subcategoria)
        if not sub or not sub.imagen_subcategoria:
            return None
        return get_full_image_path(sub.imagen_subcategoria)

    # ------------------ Servicios de Productos ------------------ #

    def get_all_products(self) -> List[ProductDto]:
        products = self.repository.get_all_products()
        return [self._map_to_dto(p) for p in products]

    def get_featured_products(self) -> List[ProductDto]:
        products = self.repository.get_featured_products()
        return [self._map_to_dto(p) for p in products]

    def get_product_by_id(self, id_producto: int) -> ProductDto:
        product = self.repository.get_product_by_id(id_producto)
        if not product:
            raise HTTPException(
                status_code=status.HTTP_404_NOT_FOUND,
                detail=f"Producto #{id_producto} no encontrado."
            )
        return self._map_to_dto(product)

    def check_featured_order_occupied(self, order: int, exclude_id: Optional[int] = None) -> bool:
        return self.repository.is_featured_order_occupied(order, exclude_id)

    def create_product(self, req: CreateProductRequest, id_usuario: Optional[int] = None) -> ProductResponse:
        # Validar si el slot de destacado ya está ocupado
        if req.destacado and req.orden_destacado:
            if self.repository.is_featured_order_occupied(req.orden_destacado):
                raise HTTPException(
                    status_code=status.HTTP_400_BAD_REQUEST,
                    detail=f"La posición #{req.orden_destacado} de destacados ya está ocupada por otro producto."
                )

        # Crear entidad inicial sin imágenes para obtener el id_producto
        nuevo_producto = Producto(
            id_usuario=id_usuario,
            id_categoria=req.id_categoria,
            id_subcategoria=req.id_subcategoria,
            nombre_producto=req.nombre_producto,
            sku=req.sku,
            descripcion=req.descripcion,
            precio=req.precio,
            descuento=req.descuento or 0.0,
            stock=req.stock,
            ubicacion=req.ubicacion,
            destacado=1 if req.destacado else 0,
            orden_destacado=req.orden_destacado if req.destacado else None,
            activo=1 if req.activo else 0
        )
        saved_prod = self.repository.create_product(nuevo_producto)

        # ⚡ Procesar y almacenar imágenes en DISCO en formato WebP optimizado
        prod_id = saved_prod.id_producto

        if req.imagen_principal:
            saved_prod.imagen_principal = save_image_from_base64(
                req.imagen_principal,
                subfolder="products",
                filename_prefix=f"{prod_id}_main"
            )

        if req.imagen_secundaria_1:
            saved_prod.imagen_secundaria_1 = save_image_from_base64(
                req.imagen_secundaria_1,
                subfolder="products",
                filename_prefix=f"{prod_id}_sec1"
            )

        if req.imagen_secundaria_2:
            saved_prod.imagen_secundaria_2 = save_image_from_base64(
                req.imagen_secundaria_2,
                subfolder="products",
                filename_prefix=f"{prod_id}_sec2"
            )

        if req.imagen_secundaria_3:
            saved_prod.imagen_secundaria_3 = save_image_from_base64(
                req.imagen_secundaria_3,
                subfolder="products",
                filename_prefix=f"{prod_id}_sec3"
            )

        # Actualizar producto con las rutas persistidas
        self.repository.update_product(saved_prod)

        return ProductResponse(
            success=True,
            message="¡Producto guardado exitosamente en el catálogo!",
            id_producto=saved_prod.id_producto
        )

    def update_product(self, id_producto: int, req: UpdateProductRequest) -> ProductResponse:
        product = self.repository.get_product_by_id(id_producto)
        if not product:
            raise HTTPException(
                status_code=status.HTTP_404_NOT_FOUND,
                detail=f"Producto #{id_producto} no encontrado."
            )

        # Validar orden de destacado si se actualiza
        if req.destacado is not None:
            if req.destacado and req.orden_destacado:
                if self.repository.is_featured_order_occupied(req.orden_destacado, exclude_id=id_producto):
                    raise HTTPException(
                        status_code=status.HTTP_400_BAD_REQUEST,
                        detail=f"La posición #{req.orden_destacado} de destacados ya está ocupada por otro producto."
                    )
                product.destacado = 1
                product.orden_destacado = req.orden_destacado
            elif not req.destacado:
                product.destacado = 0
                product.orden_destacado = None

        if req.nombre_producto is not None:
            product.nombre_producto = req.nombre_producto
        if req.sku is not None:
            product.sku = req.sku
        if req.descripcion is not None:
            product.descripcion = req.descripcion
        if req.id_categoria is not None:
            product.id_categoria = req.id_categoria
        if req.id_subcategoria is not None:
            product.id_subcategoria = req.id_subcategoria if req.id_subcategoria > 0 else None
        if req.precio is not None:
            product.precio = req.precio
        if req.descuento is not None:
            product.descuento = req.descuento
        if req.stock is not None:
            product.stock = req.stock
        if req.ubicacion is not None:
            product.ubicacion = req.ubicacion
        if req.activo is not None:
            product.activo = 1 if req.activo else 0

        # Si vienen imágenes nuevas en Base64 o data URI, guardarlas en disco
        if req.imagen_principal and req.imagen_principal.startswith("data:"):
            product.imagen_principal = save_image_from_base64(
                req.imagen_principal,
                subfolder="products",
                filename_prefix=f"{id_producto}_main"
            )

        if req.imagen_secundaria_1 and req.imagen_secundaria_1.startswith("data:"):
            product.imagen_secundaria_1 = save_image_from_base64(
                req.imagen_secundaria_1,
                subfolder="products",
                filename_prefix=f"{id_producto}_sec1"
            )

        if req.imagen_secundaria_2 and req.imagen_secundaria_2.startswith("data:"):
            product.imagen_secundaria_2 = save_image_from_base64(
                req.imagen_secundaria_2,
                subfolder="products",
                filename_prefix=f"{id_producto}_sec2"
            )

        if req.imagen_secundaria_3 and req.imagen_secundaria_3.startswith("data:"):
            product.imagen_secundaria_3 = save_image_from_base64(
                req.imagen_secundaria_3,
                subfolder="products",
                filename_prefix=f"{id_producto}_sec3"
            )

        self.repository.update_product(product)
        return ProductResponse(
            success=True,
            message=f"¡Producto #{id_producto} actualizado exitosamente!",
            id_producto=id_producto
        )

    def delete_product(self, id_producto: int) -> ProductResponse:
        product = self.repository.get_product_by_id(id_producto)
        if not product:
            raise HTTPException(
                status_code=status.HTTP_404_NOT_FOUND,
                detail=f"Producto #{id_producto} no encontrado."
            )

        # Eliminar archivos de imagen asociados del disco
        for img_rel in [product.imagen_principal, product.imagen_secundaria_1, product.imagen_secundaria_2, product.imagen_secundaria_3]:
            if img_rel:
                abs_path = get_full_image_path(img_rel)
                if abs_path and os.path.exists(abs_path):
                    try:
                        os.remove(abs_path)
                    except Exception as e:
                        print(f"Error borrando archivo {abs_path}: {e}")

        self.repository.delete_product(product)
        return ProductResponse(
            success=True,
            message=f"Producto #{id_producto} eliminado correctamente.",
            id_producto=id_producto
        )

    def get_product_image_path(self, id_producto: int, image_type: str = "main") -> Optional[str]:
        product = self.repository.get_product_by_id(id_producto)
        if not product:
            return None

        rel_path = None
        if image_type == "main":
            rel_path = product.imagen_principal
        elif image_type == "sec1":
            rel_path = product.imagen_secundaria_1
        elif image_type == "sec2":
            rel_path = product.imagen_secundaria_2
        elif image_type == "sec3":
            rel_path = product.imagen_secundaria_3

        if not rel_path:
            return None

        return get_full_image_path(rel_path)

    def _map_to_dto(self, p: Producto) -> ProductDto:
        return ProductDto(
            id_producto=p.id_producto,
            id_categoria=p.id_categoria,
            id_subcategoria=p.id_subcategoria,
            nombre_categoria=p.categoria.nombre_categoria if p.categoria else None,
            nombre_subcategoria=p.subcategoria.nombre_subcategoria if p.subcategoria else None,
            nombre_producto=p.nombre_producto,
            sku=p.sku,
            descripcion=p.descripcion,
            precio=float(p.precio),
            descuento=float(p.descuento) if p.descuento else 0.0,
            stock=p.stock,
            ubicacion=p.ubicacion,
            destacado=p.destacado,
            orden_destacado=p.orden_destacado,
            activo=p.activo,
            tiene_imagen=1 if p.imagen_principal else 0
        )
