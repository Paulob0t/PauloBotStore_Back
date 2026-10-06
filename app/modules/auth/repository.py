from typing import Optional, List
from sqlalchemy.orm import Session
from sqlalchemy import func
from app.modules.users.models import User


class UserRepository:
    def __init__(self, db: Session):
        self.db = db

    def find_by_email(self, email: str) -> Optional[User]:
        return self.db.query(User).filter(
            func.lower(User.correo) == func.lower(email.strip())
        ).first()

    def find_by_id(self, user_id: int) -> Optional[User]:
        return self.db.query(User).filter(User.id == user_id).first()

    def create(self, name: str, email: str, hashed_password: str, tipo_usuario: str = "Administrador") -> User:
        user = User(
            nombre=name,
            correo=email.strip().lower(),
            contrasena=hashed_password,
            tipo_usuario=tipo_usuario,
            activo=1
        )
        self.db.add(user)
        self.db.commit()
        self.db.refresh(user)
        return user

    def list_all(self) -> List[User]:
        return self.db.query(User).order_by(User.id.desc()).all()
