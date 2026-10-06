import os
import sys
import re
from sqlalchemy import create_engine
from sqlalchemy.orm import sessionmaker
from app.core.config import settings
from app.core.database import Base
from app.core.image_storage import save_image_from_base64
from app.modules.users.models import User
from app.modules.products.models import Categoria, Subcategoria, Producto

def seed_database():
    engine = create_engine(settings.database_url_sync)
    Session = sessionmaker(bind=engine)
    db = Session()

    print("Iniciando seed de categorías y productos iniciales...")

    # Categorías iniciales comunes
    default_cats = [
        {"id": 1, "nombre": "Bebidas", "subs": ["Refrescos", "Aguas", "Jugos", "Energizantes"]},
        {"id": 2, "nombre": "Snacks & Botanas", "subs": ["Papas", "Galletas", "Chocolates", "Dulces"]},
        {"id": 3, "nombre": "Farmacia & Cuidado", "subs": ["Analgésicos", "Higiene", "Curación"]},
        {"id": 4, "nombre": "Servicios Digitales", "subs": ["Recargas Telefónicas", "Pago de Servicios", "Pines de Streaming"]}
    ]

    for cat_data in default_cats:
        cat = db.query(Categoria).filter(Categoria.id_categoria == cat_data["id"]).first()
        if not cat:
            cat = Categoria(id_categoria=cat_data["id"], nombre_categoria=cat_data["nombre"])
            db.add(cat)
            db.commit()
            db.refresh(cat)
            print(f"Categoría creada: {cat.nombre_categoria}")

        for sub_name in cat_data["subs"]:
            sub = db.query(Subcategoria).filter(
                Subcategoria.id_categoria == cat.id_categoria,
                Subcategoria.nombre_subcategoria == sub_name
            ).first()
            if not sub:
                sub = Subcategoria(id_categoria=cat.id_categoria, nombre_subcategoria=sub_name)
                db.add(sub)
                db.commit()
                print(f"  ↳ Subcategoría creada: {sub_name}")

    # Si no hay productos, crear productos de prueba de vending
    if db.query(Producto).count() == 0:
        bebidas_cat = db.query(Categoria).filter(Categoria.nombre_categoria == "Bebidas").first()
        snacks_cat = db.query(Categoria).filter(Categoria.nombre_categoria == "Snacks & Botanas").first()

        sample_products = [
            {
                "id_categoria": bebidas_cat.id_categoria,
                "nombre_producto": "Coca Cola Original 600ml",
                "sku": "CC-600",
                "descripcion": "Refresco de cola clásico botella 600ml bien fría.",
                "precio": 22.00,
                "stock": 15,
                "ubicacion": "A1",
                "destacado": 1,
                "orden_destacado": 1,
                "activo": 1
            },
            {
                "id_categoria": bebidas_cat.id_categoria,
                "nombre_producto": "Agua Purificada Ciel 1L",
                "sku": "CIEL-1L",
                "descripcion": "Agua purificada sin gas 1 Litro.",
                "precio": 15.00,
                "stock": 20,
                "ubicacion": "A2",
                "destacado": 1,
                "orden_destacado": 2,
                "activo": 1
            },
            {
                "id_categoria": snacks_cat.id_categoria,
                "nombre_producto": "Papas Sabritas Sal 45g",
                "sku": "SAB-SAL",
                "descripcion": "Papas fritas con sal crujientes bolsa individual.",
                "precio": 25.00,
                "stock": 12,
                "ubicacion": "B1",
                "destacado": 1,
                "orden_destacado": 3,
                "activo": 1
            },
            {
                "id_categoria": snacks_cat.id_categoria,
                "nombre_producto": "Galletas Emperador Chocolate",
                "sku": "EMP-CHOC",
                "descripcion": "Galletas tipo sándwich rellenas de crema sabor a chocolate.",
                "precio": 20.00,
                "stock": 18,
                "ubicacion": "B2",
                "destacado": 1,
                "orden_destacado": 4,
                "activo": 1
            }
        ]

        for p_data in sample_products:
            p = Producto(**p_data)
            db.add(p)
            db.commit()
            print(f"Producto creado: {p.nombre_producto} en slot {p.ubicacion}")

    print("Seed completado exitosamente.")
    db.close()

if __name__ == "__main__":
    seed_database()
