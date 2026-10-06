from typing import Optional
from pydantic import BaseModel, EmailStr, Field


class LoginRequest(BaseModel):
    correo: str = Field(..., min_length=3, max_length=150, description="Correo electrónico del usuario")
    contrasena: str = Field(..., min_length=1, description="Contraseña de acceso")


class RegisterRequest(BaseModel):
    nombre: str = Field(..., min_length=2, max_length=100)
    correo: str = Field(..., min_length=3, max_length=150)
    contrasena: str = Field(..., min_length=6)
    tipo_usuario: Optional[str] = Field("Administrador")


class UserDto(BaseModel):
    id: int
    nombre: str
    correo: str
    tipo_usuario: str = "Administrador"
    activo: int = 1
    created_at: Optional[str] = None

    class Config:
        from_attributes = True


class LoginResponse(BaseModel):
    success: bool = True
    message: str = "¡Autenticación exitosa!"
    token: Optional[str] = None
    user: Optional[UserDto] = None


class ApiResponse(BaseModel):
    success: bool
    message: str
    data: Optional[dict] = None
