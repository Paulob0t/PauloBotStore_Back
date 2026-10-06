from typing import List, Optional
from pydantic import BaseModel, Field


class ChartDto(BaseModel):
    labels: List[str] = Field(default_factory=list, description="Etiquetas de días (dd/mm)")
    montos: List[float] = Field(default_factory=list, description="Monto total vendido por día")
    ventas: List[int] = Field(default_factory=list, description="Cantidad de transacciones por día")


class InventoryMetricsDto(BaseModel):
    totalProductos: int = Field(0, description="Total de productos registrados")
    stockTotal: int = Field(0, description="Suma total de unidades en stock")
    stockBajo: int = Field(0, description="Cantidad de productos con stock crítico (<= 5)")
    productosInactivos: int = Field(0, description="Cantidad de productos desactivados")


class RecentSaleDto(BaseModel):
    folio: Optional[str] = None
    fecha_venta: Optional[str] = None
    total: Optional[float] = 0.0
    metodo_pago: Optional[str] = None
    tipo_pago: Optional[str] = None


class SalesMetricsDto(BaseModel):
    fechaLabel: str = Field(..., description="Fecha actual en formato legible")
    promedioVenta: float = Field(0.0, description="Ticket promedio del mes")
    ventasHoyCnt: int = Field(0, description="Número de ventas del día de hoy")
    ventasHoyMonto: float = Field(0.0, description="Monto total vendido hoy")
    ventasMesCnt: int = Field(0, description="Número de ventas del mes en curso")
    ventasMesMonto: float = Field(0.0, description="Monto total vendido en el mes")


class TopProductDto(BaseModel):
    nombre_producto: Optional[str] = None
    unidades: Optional[int] = 0
    ingresos: Optional[float] = 0.0


class DashboardMetricsDto(BaseModel):
    sales: SalesMetricsDto
    inventory: InventoryMetricsDto
    chart: ChartDto
    topProducts: List[TopProductDto] = Field(default_factory=list)
    recentSales: List[RecentSaleDto] = Field(default_factory=list)
