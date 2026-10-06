import os
from typing import List, Optional
from fastapi import APIRouter, Depends, HTTPException, Query, status, Response
from fastapi.responses import FileResponse
from sqlalchemy.orm import Session
from app.core.database import get_db
from app.modules.auth.dependencies import get_current_user
from app.modules.users.models import User
from app.modules.products.service import ProductService
from app.modules.products.schemas import (
    ProductDto,
    CategoryDto,
    SubcategoryDto,
    SubcategoryDetailDto,
    CreateProductRequest,
    UpdateProductRequest,
    CreateCategoryRequest,
    UpdateCategoryRequest,
    CreateSubcategoryRequest,
    UpdateSubcategoryRequest,
    CheckOrderResponse,
    ProductResponse,
    CategoryResponse
)

router = APIRouter(tags=["Productos & Catálogo"])

# ----------------- Rutas de Categorías ----------------- #

@router.get("/categories", response_model=List[CategoryDto], summary="Obtener todas las categorías con subcategorías")
def get_categories(db: Session = Depends(get_db)):
    service = ProductService(db)
    return service.get_all_categories_tree()


@router.post("/categories", response_model=CategoryResponse, summary="Crear nueva categoría")
def create_category(
    req: CreateCategoryRequest,
    db: Session = Depends(get_db),
    current_user: User = Depends(get_current_user)
):
    service = ProductService(db)
    return service.create_category(req)


@router.put("/categories/{id}", response_model=CategoryResponse, summary="Actualizar categoría")
def update_category(
    id: int,
    req: UpdateCategoryRequest,
    db: Session = Depends(get_db),
    current_user: User = Depends(get_current_user)
):
    service = ProductService(db)
    return service.update_category(id, req)


@router.delete("/categories/{id}", response_model=CategoryResponse, summary="Eliminar categoría")
def delete_category(
    id: int,
    db: Session = Depends(get_db),
    current_user: User = Depends(get_current_user)
):
    service = ProductService(db)
    return service.delete_category(id)


@router.get("/categories/{id}/image", summary="Obtener imagen de categoría con caché")
def get_category_image(id: int, db: Session = Depends(get_db)):
    service = ProductService(db)
    file_path = service.get_category_image_path(id)
    if not file_path or not os.path.exists(file_path):
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Imagen no encontrada.")
    return FileResponse(file_path, media_type="image/webp", headers={"Cache-Control": "public, max-age=86400"})


# ----------------- Rutas de Subcategorías ----------------- #

@router.get("/subcategories", response_model=List[SubcategoryDetailDto], summary="Obtener todas las subcategorías")
def get_subcategories(db: Session = Depends(get_db)):
    service = ProductService(db)
    return service.get_all_subcategories()


@router.post("/subcategories", response_model=CategoryResponse, summary="Crear subcategoría")
def create_subcategory(
    req: CreateSubcategoryRequest,
    db: Session = Depends(get_db),
    current_user: User = Depends(get_current_user)
):
    service = ProductService(db)
    return service.create_subcategory(req)


@router.post("/categories/{id}/subcategories", response_model=CategoryResponse, summary="Crear subcategoría para una categoría")
def create_category_subcategory(
    id: int,
    req: CreateSubcategoryRequest,
    db: Session = Depends(get_db),
    current_user: User = Depends(get_current_user)
):
    service = ProductService(db)
    return service.create_subcategory(req, id_categoria=id)


@router.put("/subcategories/{id}", response_model=CategoryResponse, summary="Actualizar subcategoría")
def update_subcategory(
    id: int,
    req: UpdateSubcategoryRequest,
    db: Session = Depends(get_db),
    current_user: User = Depends(get_current_user)
):
    service = ProductService(db)
    return service.update_subcategory(id, req)


@router.delete("/subcategories/{id}", response_model=CategoryResponse, summary="Eliminar subcategoría")
def delete_subcategory(
    id: int,
    db: Session = Depends(get_db),
    current_user: User = Depends(get_current_user)
):
    service = ProductService(db)
    return service.delete_subcategory(id)


@router.get("/subcategories/{id}/image", summary="Obtener imagen de subcategoría con caché")
def get_subcategory_image(id: int, db: Session = Depends(get_db)):
    service = ProductService(db)
    file_path = service.get_subcategory_image_path(id)
    if not file_path or not os.path.exists(file_path):
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Imagen no encontrada.")
    return FileResponse(file_path, media_type="image/webp", headers={"Cache-Control": "public, max-age=86400"})


# ----------------- Rutas de Productos ----------------- #

@router.get("/products", response_model=List[ProductDto], summary="Listado general de productos")
def get_products(db: Session = Depends(get_db)):
    service = ProductService(db)
    return service.get_all_products()


@router.get("/products/featured", response_model=List[ProductDto], summary="Productos destacados para carrusel")
def get_featured_products(db: Session = Depends(get_db)):
    service = ProductService(db)
    return service.get_featured_products()


@router.get("/products/featured-order/{order}", response_model=CheckOrderResponse, summary="Verificar si posición de orden está ocupada")
def check_featured_order(order: int, exclude_id: Optional[int] = Query(None), db: Session = Depends(get_db)):
    service = ProductService(db)
    occupied = service.check_featured_order_occupied(order, exclude_id)
    return CheckOrderResponse(occupied=occupied, order=order)


@router.get("/products/{id}", response_model=ProductDto, summary="Obtener detalle de producto por ID")
def get_product_by_id(id: int, db: Session = Depends(get_db)):
    service = ProductService(db)
    return service.get_product_by_id(id)


@router.post("/products", response_model=ProductResponse, summary="Crear un nuevo producto en el catálogo")
def create_product(
    req: CreateProductRequest,
    db: Session = Depends(get_db),
    current_user: User = Depends(get_current_user)
):
    service = ProductService(db)
    return service.create_product(req, id_usuario=current_user.id)


@router.put("/products/{id}", response_model=ProductResponse, summary="Actualizar producto existente")
def update_product(
    id: int,
    req: UpdateProductRequest,
    db: Session = Depends(get_db),
    current_user: User = Depends(get_current_user)
):
    service = ProductService(db)
    return service.update_product(id, req)


@router.delete("/products/{id}", response_model=ProductResponse, summary="Eliminar producto")
def delete_product(
    id: int,
    db: Session = Depends(get_db),
    current_user: User = Depends(get_current_user)
):
    service = ProductService(db)
    return service.delete_product(id)


@router.get("/products/{id}/image", summary="Servir imagen de producto ultra-rápida desde disco con caché")
def get_product_image(
    id: int,
    type: str = Query("main", pattern="^(main|sec1|sec2|sec3)$"),
    db: Session = Depends(get_db)
):
    service = ProductService(db)
    file_path = service.get_product_image_path(id, type)

    # Si no existe archivo físico en disco, devolver 404
    if not file_path or not os.path.exists(file_path):
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail=f"Imagen ({type}) para el producto #{id} no encontrada."
        )

    return FileResponse(
        file_path,
        media_type="image/webp",
        headers={
            "Cache-Control": "public, max-age=604800, immutable"  # 7 días de caché en cliente y CDN
        }
    )
