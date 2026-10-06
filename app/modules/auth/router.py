from fastapi import APIRouter, Depends, status
from sqlalchemy.orm import Session
from app.core.database import get_db
from app.modules.auth.schemas import LoginRequest, LoginResponse, RegisterRequest, UserDto, ApiResponse
from app.modules.auth.service import AuthService
from app.modules.auth.dependencies import get_current_user
from app.modules.users.models import User

router = APIRouter(prefix="/auth", tags=["Autenticación"])


@router.post(
    "/login",
    response_model=LoginResponse,
    status_code=status.HTTP_200_OK,
    summary="Iniciar sesión",
    description="Autentica credenciales y devuelve el JWT token y la información del usuario."
)
def login(
    credentials: LoginRequest,
    db: Session = Depends(get_db)
):
    service = AuthService(db)
    return service.authenticate_user(credentials)


@router.post(
    "/register",
    response_model=UserDto,
    status_code=status.HTTP_201_CREATED,
    summary="Registrar nuevo usuario"
)
def register(
    data: RegisterRequest,
    db: Session = Depends(get_db)
):
    service = AuthService(db)
    return service.register_user(data)


@router.get(
    "/me",
    response_model=UserDto,
    summary="Obtener información del usuario autenticado"
)
def get_me(current_user: User = Depends(get_current_user)):
    return UserDto(
        id=current_user.id,
        nombre=current_user.nombre,
        correo=current_user.correo,
        tipo_usuario=current_user.tipo_usuario or "Administrador",
        activo=current_user.activo or 1,
        created_at=current_user.created_at.isoformat() if current_user.created_at else None
    )


@router.post(
    "/logout",
    response_model=ApiResponse,
    summary="Cerrar sesión"
)
def logout():
    return ApiResponse(
        success=True,
        message="Sesión cerrada correctamente."
    )
