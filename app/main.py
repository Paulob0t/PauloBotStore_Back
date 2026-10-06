from fastapi import FastAPI, Request, status
from fastapi.middleware.cors import CORSMiddleware
from fastapi.responses import JSONResponse
from fastapi.exceptions import RequestValidationError
from app.core.config import settings
from app.core.database import engine, Base
from app.core.image_storage import ensure_upload_dirs
from app.modules.auth.router import router as auth_router
from app.modules.products.router import router as products_router
from app.modules.dashboard.router import router as dashboard_router
import app.modules.users.models  # asegurar registro de modelos
import app.modules.products.models  # asegurar registro de modelos
import app.modules.sales.models  # asegurar registro de modelos

# Asegurar directorios de uploads en disco
ensure_upload_dirs()

# Crear tablas en PostgreSQL si no existen
Base.metadata.create_all(bind=engine)

app = FastAPI(
    title=settings.APP_NAME,
    version=settings.APP_VERSION,
    description="Backend Modular & Clean Architecture para PauloBot Store (FastAPI + PostgreSQL)",
    docs_url="/docs",
    redoc_url="/redoc",
    openapi_url="/api/v1/openapi.json"
)

# Configuración de CORS
app.add_middleware(
    CORSMiddleware,
    allow_origins=settings.CORS_ORIGINS,
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Manejador de validación de esquemas Pydantic
@app.exception_handler(RequestValidationError)
async def validation_exception_handler(request: Request, exc: RequestValidationError):
    errors = exc.errors()
    first_error = errors[0]["msg"] if errors else "Datos de entrada inválidos."
    return JSONResponse(
        status_code=status.HTTP_400_BAD_REQUEST,
        content={
            "success": False,
            "message": f"Error de validación: {first_error}",
            "errors": errors
        }
    )

# Agregación de routers modulares bajo el prefijo /api/v1
app.include_router(auth_router, prefix="/api/v1")
app.include_router(products_router, prefix="/api/v1")
app.include_router(dashboard_router, prefix="/api/v1")


@app.get("/api/health", tags=["Salud"])
def health_check():
    return {
        "status": "healthy",
        "app": settings.APP_NAME,
        "version": settings.APP_VERSION,
        "database": settings.DB_NAME
    }


@app.get("/", tags=["Root"])
def root():
    return {
        "message": f"Bienvenido a {settings.APP_NAME}",
        "docs": "/docs",
        "openapi": "/api/v1/openapi.json"
    }
