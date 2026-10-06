from typing import Optional, Tuple
from sqlalchemy.orm import Session
from fastapi import HTTPException, status
from app.modules.auth.repository import UserRepository
from app.modules.auth.schemas import LoginRequest, LoginResponse, UserDto, RegisterRequest
from app.core.security import verify_password, hash_password, create_access_token
from app.modules.users.models import User


class AuthService:
    def __init__(self, db: Session):
        self.repository = UserRepository(db)

    def authenticate_user(self, credentials: LoginRequest) -> LoginResponse:
        user = self.repository.find_by_email(credentials.correo)
        if not user:
            raise HTTPException(
                status_code=status.HTTP_401_UNAUTHORIZED,
                detail="El correo no se encuentra registrado en el sistema."
            )

        if user.activo != 1:
            raise HTTPException(
                status_code=status.HTTP_403_FORBIDDEN,
                detail="Tu usuario está inactivo. Contacta al administrador."
            )

        if not verify_password(credentials.contrasena, user.contrasena):
            raise HTTPException(
                status_code=status.HTTP_401_UNAUTHORIZED,
                detail="Contraseña incorrecta. Por favor verifica tus credenciales."
            )

        # Generar token JWT
        token_data = {
            "sub": str(user.id),
            "email": user.correo,
            "role": user.tipo_usuario
        }
        access_token = create_access_token(data=token_data)

        user_dto = UserDto(
            id=user.id,
            nombre=user.nombre,
            correo=user.correo,
            tipo_usuario=user.tipo_usuario or "Administrador",
            activo=user.activo or 1,
            created_at=user.created_at.isoformat() if user.created_at else None
        )

        return LoginResponse(
            success=True,
            message=f"¡Bienvenido de nuevo, {user.nombre}!",
            token=access_token,
            user=user_dto
        )

    def register_user(self, data: RegisterRequest) -> UserDto:
        existing = self.repository.find_by_email(data.correo)
        if existing:
            raise HTTPException(
                status_code=status.HTTP_400_BAD_REQUEST,
                detail="El correo ya se encuentra registrado."
            )

        hashed = hash_password(data.contrasena)
        new_user = self.repository.create(
            name=data.nombre,
            email=data.correo,
            hashed_password=hashed,
            tipo_usuario=data.tipo_usuario or "Administrador"
        )

        return UserDto(
            id=new_user.id,
            nombre=new_user.nombre,
            correo=new_user.correo,
            tipo_usuario=new_user.tipo_usuario,
            activo=new_user.activo,
            created_at=new_user.created_at.isoformat() if new_user.created_at else None
        )
