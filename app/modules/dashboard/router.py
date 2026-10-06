from fastapi import APIRouter, Depends
from sqlalchemy.orm import Session
from app.core.database import get_db
from app.modules.dashboard.schemas import DashboardMetricsDto
from app.modules.dashboard.service import DashboardService

router = APIRouter(tags=["Dashboard"])


@router.get("/dashboard", response_model=DashboardMetricsDto, summary="Obtener métricas consolidadas del Dashboard")
def get_dashboard_metrics(db: Session = Depends(get_db)):
    service = DashboardService(db)
    return service.get_dashboard_metrics()
