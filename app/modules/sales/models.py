from datetime import datetime
from sqlalchemy import Column, Integer, String, Numeric, Text, SmallInteger, DateTime, ForeignKey
from sqlalchemy.orm import relationship
from app.core.database import Base


class VentaComanda(Base):
    __tablename__ = "ventas_comanda"

    id_comanda = Column(Integer, primary_key=True, autoincrement=True)
    folio = Column(String(200), nullable=False, unique=True)
    id_usuario = Column(Integer, ForeignKey("usuarios.id", ondelete="SET NULL"), nullable=True)
    id_cliente = Column(Integer, nullable=True)
    fecha_venta = Column(DateTime, default=datetime.utcnow, nullable=False)
    subtotal = Column(Numeric(10, 2), default=0.0, nullable=False)
    iva = Column(Numeric(10, 2), default=0.0, nullable=False)
    descuento_global = Column(Numeric(10, 2), default=0.0, nullable=False)
    total = Column(Numeric(10, 2), default=0.0, nullable=False)
    metodo_pago = Column(String(50), nullable=True)
    estatus = Column(Integer, default=1, nullable=False)  # 1 = Pagada / Completada, 0 = Cancelada
    notas = Column(Text, nullable=True)
    fecha_creacion = Column(DateTime, default=datetime.utcnow, nullable=False)
    fecha_actualizacion = Column(DateTime, default=datetime.utcnow, onupdate=datetime.utcnow, nullable=False)
    id_pago = Column(Text, nullable=True)
    tipo_pago = Column(Integer, default=1, nullable=False)  # 1 = Tarjeta / MercadoPago, 2 = Efectivo
    tipo_tarjeta = Column(Integer, default=0, nullable=False)  # 0 = N/A, 1 = Débito, 2 = Crédito
    sincronizado = Column(SmallInteger, default=0)

    # Relaciones
    detalles = relationship("VentaDetalle", back_populates="comanda", cascade="all, delete-orphan")


class VentaDetalle(Base):
    __tablename__ = "ventas_detalle"

    id_detalle = Column(Integer, primary_key=True, autoincrement=True)
    id_comanda = Column(Integer, ForeignKey("ventas_comanda.id_comanda", ondelete="CASCADE"), nullable=False)
    id_producto = Column(Integer, ForeignKey("productos.id_producto", ondelete="RESTRICT"), nullable=False)
    cantidad = Column(Integer, default=1, nullable=False)
    precio_unitario = Column(Numeric(10, 2), default=0.0, nullable=False)
    descuento_unitario = Column(Numeric(10, 2), default=0.0, nullable=False)
    subtotal = Column(Numeric(10, 2), default=0.0, nullable=False)
    iva_unitario = Column(Numeric(10, 2), default=0.0, nullable=False)
    total = Column(Numeric(10, 2), default=0.0, nullable=False)
    notas = Column(Text, nullable=True)

    # Relaciones
    comanda = relationship("VentaComanda", back_populates="detalles")
    producto = relationship("Producto")
