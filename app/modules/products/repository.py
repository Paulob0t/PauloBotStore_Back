from typing import List, Optional
from sqlalchemy.orm import Session
from sqlalchemy import desc
from app.modules.products.models import Producto, Categoria, Subcategoria


class ProductRepository:
    def __init__(self, db: Session):
        self.db = db

    # ------------------ Métodos de Categorías ------------------ #

    def get_categories_with_subcategories(self) -> List[Categoria]:
        return self.db.query(Categoria).order_by(Categoria.id_categoria.asc()).all()

    def get_category_by_id(self, id_categoria: int) -> Optional[Categoria]:
        return self.db.query(Categoria).filter(Categoria.id_categoria == id_categoria).first()

    def get_subcategory_by_id(self, id_subcategoria: int) -> Optional[Subcategoria]:
        return self.db.query(Subcategoria).filter(Subcategoria.id_subcategoria == id_subcategoria).first()

    def create_category(self, nombre: str, imagen_path: Optional[str] = None) -> Categoria:
        cat = Categoria(nombre_categoria=nombre, imagen_categoria=imagen_path)
        self.db.add(cat)
        self.db.commit()
        self.db.refresh(cat)
        return cat

    def create_subcategory(self, id_categoria: int, nombre: str, imagen_path: Optional[str] = None) -> Subcategoria:
        sub = Subcategoria(id_categoria=id_categoria, nombre_subcategoria=nombre, imagen_subcategoria=imagen_path)
        self.db.add(sub)
        self.db.commit()
        self.db.refresh(sub)
        return sub

    # ------------------ Métodos de Productos ------------------ #

    def get_all_products(self) -> List[Producto]:
        return self.db.query(Producto).order_by(Producto.id_producto.desc()).all()

    def get_featured_products(self) -> List[Producto]:
        return self.db.query(Producto).filter(
            Producto.destacado == 1,
            Producto.activo == 1
        ).order_by(Producto.orden_destacado.asc()).all()

    def get_product_by_id(self, id_producto: int) -> Optional[Producto]:
        return self.db.query(Producto).filter(Producto.id_producto == id_producto).first()

    def is_featured_order_occupied(self, order: int, exclude_id: Optional[int] = None) -> bool:
        query = self.db.query(Producto).filter(
            Producto.destacado == 1,
            Producto.orden_destacado == order
        )
        if exclude_id:
            query = query.filter(Producto.id_producto != exclude_id)
        return query.first() is not None

    def create_product(self, product: Producto) -> Producto:
        self.db.add(product)
        self.db.commit()
        self.db.refresh(product)
        return product

    def update_product(self, product: Producto) -> Producto:
        self.db.commit()
        self.db.refresh(product)
        return product

    def delete_product(self, product: Producto) -> bool:
        self.db.delete(product)
        self.db.commit()
        return True
