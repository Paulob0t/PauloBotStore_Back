from typing import Generator
from sqlalchemy import create_engine
from sqlalchemy.orm import declarative_base, sessionmaker, Session
from app.core.config import settings

# Engine síncrono para operaciones estándar y seeding
engine = create_engine(
    settings.database_url_sync,
    pool_pre_ping=True,
    pool_size=10,
    max_overflow=20,
)

SessionLocal = sessionmaker(autocommit=False, autoflush=False, bind=engine)

Base = declarative_base()


def get_db() -> Generator[Session, None, None]:
    """
    Dependency para inyección de sesión de base de datos en endpoints FastAPI.
    """
    db = SessionLocal()
    try:
        yield db
    finally:
        db.close()
