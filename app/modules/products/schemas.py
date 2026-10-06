from typing import Optional, List
from pydantic import BaseModel, Field


# ---------------------- DTOs de Subcategorías y Categorías ---------------------- #

class SubcategoryDto(BaseModel):
    id: int
    id_categoria: Optional[int] = None
    nombre: str
    tiene_imagen: Optional[int] = 0

    class Config:
        from_attributes = True


class CategoryDto(BaseModel):
    id: int
    nombre: str
    subcategorias: List[SubcategoryDto] = []
    tiene_imagen: Optional[int] = 0

    class Config:
        from_attributes = True


class CreateCategoryRequest(BaseModel):
    nombre: str
    imagen: Optional[str] = None


class CreateSubcategoryRequest(BaseModel):
    id_categoria: int
    nombre: str
    imagen: Optional[str] = None


# ---------------------- DTOs de Productos ---------------------- #

class ProductDto(BaseModel):
    id_producto: int
    id_categoria: Optional[int] = None
    id_subcategoria: Optional[int] = None
    nombre_categoria: Optional[str] = None
    nombre_subcategoria: Optional[str] = None
    nombre_producto: str
    sku: Optional[str] = None
    descripcion: Optional[str] = None
    precio: float
    descuento: Optional[float] = 0.0
    stock: int
    ubicacion: str
    destacado: Optional[int] = 0
    orden_destacado: Optional[int] = None
    activo: int = 1
    tiene_imagen: Optional[int] = 0

    class Config:
        from_attributes = True


class CreateProductRequest(BaseModel):
    nombre_producto: str
    sku: Optional[str] = None
    descripcion: str
    id_categoria: int
    id_subcategoria: Optional[int] = None
    precio: float
    descuento: Optional[float] = None
    stock: int = 10
    ubicacion: str
    imagen_principal: str
    imagen_secundaria_1: Optional[str] = None
    imagen_secundaria_2: Optional[str] = None
    imagen_secundaria_3: Optional[str] = None
    destacado: Optional[bool] = False
    orden_destacado: Optional[int] = None
    activo: Optional[bool] = True


class CheckOrderResponse(BaseModel):
    occupied: bool
    order: int


class ProductResponse(BaseModel):
    success: bool
    message: str
    id_producto: Optional[int] = None
