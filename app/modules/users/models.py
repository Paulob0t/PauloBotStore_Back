from datetime import datetime
from sqlalchemy import Column, Integer, String, SmallInteger, DateTime
from app.core.database import Base


class User(Base):
    __tablename__ = "usuarios"

    id = Column(Integer, primary_key=True, index=True, autoincrement=True)
    nombre = Column(String(100), nullable=False)
    correo = Column(String(150), unique=True, index=True, nullable=False)
    contrasena = Column(String(255), nullable=False)
    tipo_usuario = Column(String(50), default="Administrador")
    activo = Column(SmallInteger, default=1)
    created_at = Column(DateTime, default=datetime.utcnow)

    def to_dict(self) -> dict:
        return {
            "id": self.id,
            "nombre": self.nombre,
            "correo": self.correo,
            "tipo_usuario": self.tipo_usuario,
            "activo": self.activo,
            "created_at": self.created_at.isoformat() if self.created_at else None
        }
