from datetime import datetime
from sqlalchemy import Column, Integer, String, Numeric, Text, SmallInteger, DateTime, ForeignKey
from sqlalchemy.orm import relationship
from app.core.database import Base


class Categoria(Base):
    __tablename__ = "categorias"

    id_categoria = Column(Integer, primary_key=True, autoincrement=True)
    nombre_categoria = Column(String(100), nullable=False)
    imagen_categoria = Column(String(255), nullable=True)
    fecha_creacion = Column(DateTime, default=datetime.utcnow)

    # Relaciones
    subcategorias = relationship("Subcategoria", back_populates="categoria", cascade="all, delete-orphan")
    productos = relationship("Producto", back_populates="categoria")


class Subcategoria(Base):
    __tablename__ = "subcategorias"

    id_subcategoria = Column(Integer, primary_key=True, autoincrement=True)
    id_categoria = Column(Integer, ForeignKey("categorias.id_categoria", ondelete="CASCADE"), nullable=False)
    nombre_subcategoria = Column(String(100), nullable=False)
    imagen_subcategoria = Column(String(255), nullable=True)
    fecha_creacion = Column(DateTime, default=datetime.utcnow)

    # Relaciones
    categoria = relationship("Categoria", back_populates="subcategorias")
    productos = relationship("Producto", back_populates="subcategoria")


class Producto(Base):
    __tablename__ = "productos"

    id_producto = Column(Integer, primary_key=True, autoincrement=True)
    id_usuario = Column(Integer, ForeignKey("usuarios.id", ondelete="SET NULL"), nullable=True)
    id_categoria = Column(Integer, ForeignKey("categorias.id_categoria", ondelete="RESTRICT"), nullable=False)
    id_subcategoria = Column(Integer, ForeignKey("subcategorias.id_subcategoria", ondelete="SET NULL"), nullable=True)

    nombre_producto = Column(String(150), nullable=False)
    sku = Column(String(50), nullable=True)
    descripcion = Column(Text, nullable=False)
    precio = Column(Numeric(10, 2), nullable=False)
    descuento = Column(Numeric(10, 2), nullable=True, default=0.0)
    stock = Column(Integer, nullable=False, default=0)
    ubicacion = Column(String(10), nullable=False)

    # Rutas locales de almacenamiento WebP (no Base64)
    imagen_principal = Column(String(255), nullable=True)
    imagen_secundaria_1 = Column(String(255), nullable=True)
    imagen_secundaria_2 = Column(String(255), nullable=True)
    imagen_secundaria_3 = Column(String(255), nullable=True)

    destacado = Column(SmallInteger, default=0)
    orden_destacado = Column(Integer, nullable=True)
    activo = Column(SmallInteger, default=1)

    fecha_creacion = Column(DateTime, default=datetime.utcnow)
    fecha_actualizacion = Column(DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)

    # Relaciones
    categoria = relationship("Categoria", back_populates="productos")
    subcategoria = relationship("Subcategoria", back_populates="productos")
