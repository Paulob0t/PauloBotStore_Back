import os
from typing import List
from pydantic import Field
from pydantic_settings import BaseSettings, SettingsConfigDict


class Settings(BaseSettings):
    # App General Settings
    APP_NAME: str = "PauloBot Store API"
    APP_VERSION: str = "1.0.0"
    APP_ENV: str = "development"
    DEBUG: bool = True
    PORT: int = 8000
    HOST: str = "0.0.0.0"

    # Database Settings (PostgreSQL)
    DB_TYPE: str = "pgsql"
    DB_HOST: str = "localhost"
    DB_PORT: int = 5432
    DB_USER: str = "paulobot"
    DB_PASS: str = "paulobot_password"
    DB_NAME: str = "paulobot_store"

    # JWT & Security
    SECRET_KEY: str = "paulobot_super_secret_jwt_key_2026_store_pos"
    ALGORITHM: str = "HS256"
    ACCESS_TOKEN_EXPIRE_MINUTES: int = 60 * 24 * 7  # 7 días

    # Uploads & Storage
    UPLOAD_DIR: str = os.path.join(os.path.dirname(__file__), "../../uploads")

    # CORS Origins
    CORS_ORIGINS: List[str] = [
        "http://localhost:4200",
        "http://127.0.0.1:4200",
        "https://*.trycloudflare.com",
        "https://*.loca.lt",
        "https://*.pinggy.net",
        "https://*.pinggy-free.link",
        "*"
    ]

    model_config = SettingsConfigDict(
        env_file=os.path.join(os.path.dirname(__file__), "../../.env"),
        env_file_encoding="utf-8",
        extra="ignore"
    )

    @property
    def database_url_sync(self) -> str:
        # Permite resolver 'db' o 'localhost' dinámicamente usando psycopg2
        return f"postgresql+psycopg2://{self.DB_USER}:{self.DB_PASS}@{self.DB_HOST}:{self.DB_PORT}/{self.DB_NAME}"

    @property
    def database_url_async(self) -> str:
        return f"postgresql+asyncpg://{self.DB_USER}:{self.DB_PASS}@{self.DB_HOST}:{self.DB_PORT}/{self.DB_NAME}"


settings = Settings()
