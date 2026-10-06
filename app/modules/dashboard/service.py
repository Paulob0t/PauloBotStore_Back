from datetime import datetime, timedelta, date
from typing import List
from sqlalchemy.orm import Session
from sqlalchemy import func, case, desc, cast, Date

from app.modules.products.models import Producto
from app.modules.sales.models import VentaComanda, VentaDetalle
from app.modules.dashboard.schemas import (
    DashboardMetricsDto,
    SalesMetricsDto,
    InventoryMetricsDto,
    ChartDto,
    TopProductDto,
    RecentSaleDto
)


class DashboardService:
    def __init__(self, db: Session):
        self.db = db

    def get_dashboard_metrics(self) -> DashboardMetricsDto:
        return DashboardMetricsDto(
            sales=self.get_sales_metrics(),
            inventory=self.get_inventory_metrics(),
            chart=self.get_sales_last_7_days_chart(),
            topProducts=self.get_top_products(5),
            recentSales=self.get_recent_sales(6)
        )

    def get_sales_metrics(self) -> SalesMetricsDto:
        now = datetime.utcnow()
        today_start = datetime(now.year, now.month, now.day)
        month_start = datetime(now.year, now.month, 1)

        # Ventas de hoy
        today_res = self.db.query(
            func.count(VentaComanda.id_comanda).label("cnt"),
            func.coalesce(func.sum(VentaComanda.total), 0).label("monto")
        ).filter(
            VentaComanda.fecha_venta >= today_start,
            VentaComanda.estatus == 1
        ).first()

        ventas_hoy_cnt = int(today_res.cnt or 0)
        ventas_hoy_monto = float(today_res.monto or 0.0)

        # Ventas del mes
        month_res = self.db.query(
            func.count(VentaComanda.id_comanda).label("cnt"),
            func.coalesce(func.sum(VentaComanda.total), 0).label("monto")
        ).filter(
            VentaComanda.fecha_venta >= month_start,
            VentaComanda.estatus == 1
        ).first()

        ventas_mes_cnt = int(month_res.cnt or 0)
        ventas_mes_monto = float(month_res.monto or 0.0)

        promedio_venta = round(ventas_mes_monto / ventas_mes_cnt, 2) if ventas_mes_cnt > 0 else 0.0

        return SalesMetricsDto(
            fechaLabel=now.strftime("%d/%m/%Y"),
            promedioVenta=promedio_venta,
            ventasHoyCnt=ventas_hoy_cnt,
            ventasHoyMonto=round(ventas_hoy_monto, 2),
            ventasMesCnt=ventas_mes_cnt,
            ventasMesMonto=round(ventas_mes_monto, 2)
        )

    def get_inventory_metrics(self) -> InventoryMetricsDto:
        res = self.db.query(
            func.count(Producto.id_producto).label("total"),
            func.coalesce(func.sum(Producto.stock), 0).label("stock_total"),
            func.coalesce(func.sum(case((Producto.stock <= 5, 1), else_=0)), 0).label("stock_bajo"),
            func.coalesce(func.sum(case((Producto.activo == 0, 1), else_=0)), 0).label("inactivos")
        ).first()

        return InventoryMetricsDto(
            totalProductos=int(res.total or 0),
            stockTotal=int(res.stock_total or 0),
            stockBajo=int(res.stock_bajo or 0),
            productosInactivos=int(res.inactivos or 0)
        )

    def get_sales_last_7_days_chart(self) -> ChartDto:
        now = datetime.utcnow()
        days_map = {}
        chart_labels = []

        for i in range(6, -1, -1):
            d = (now - timedelta(days=i)).date()
            label = d.strftime("%d/%m")
            chart_labels.append(label)
            days_map[d.strftime("%Y-%m-%d")] = {"ventas": 0, "monto": 0.0}

        start_date = (now - timedelta(days=6)).replace(hour=0, minute=0, second=0, microsecond=0)

        results = self.db.query(
            cast(VentaComanda.fecha_venta, Date).label("dia"),
            func.count(VentaComanda.id_comanda).label("ventas"),
            func.coalesce(func.sum(VentaComanda.total), 0).label("monto")
        ).filter(
            VentaComanda.fecha_venta >= start_date,
            VentaComanda.estatus == 1
        ).group_by(
            cast(VentaComanda.fecha_venta, Date)
        ).all()

        for r in results:
            d_str = str(r.dia)
            if d_str in days_map:
                days_map[d_str]["ventas"] = int(r.ventas or 0)
                days_map[d_str]["monto"] = float(r.monto or 0.0)

        ventas_list = [v["ventas"] for v in days_map.values()]
        montos_list = [round(v["monto"], 2) for v in days_map.values()]

        return ChartDto(
            labels=chart_labels,
            ventas=ventas_list,
            montos=montos_list
        )

    def get_top_products(self, limit: int = 5) -> List[TopProductDto]:
        now = datetime.utcnow()
        thirty_days_ago = now - timedelta(days=30)

        results = self.db.query(
            Producto.nombre_producto,
            func.sum(VentaDetalle.cantidad).label("unidades"),
            func.coalesce(func.sum(VentaDetalle.total), 0).label("ingresos")
        ).join(
            VentaDetalle, Producto.id_producto == VentaDetalle.id_producto
        ).join(
            VentaComanda, VentaComanda.id_comanda == VentaDetalle.id_comanda
        ).filter(
            VentaComanda.fecha_venta >= thirty_days_ago,
            VentaComanda.estatus == 1
        ).group_by(
            Producto.id_producto,
            Producto.nombre_producto
        ).order_by(
            desc("unidades")
        ).limit(limit).all()

        return [
            TopProductDto(
                nombre_producto=r.nombre_producto,
                unidades=int(r.unidades or 0),
                ingresos=float(r.ingresos or 0.0)
            )
            for r in results
        ]

    def get_recent_sales(self, limit: int = 6) -> List[RecentSaleDto]:
        results = self.db.query(VentaComanda).filter(
            VentaComanda.estatus == 1
        ).order_by(
            desc(VentaComanda.fecha_venta)
        ).limit(limit).all()

        return [
            RecentSaleDto(
                folio=v.folio,
                fecha_venta=v.fecha_venta.strftime("%d/%m/%Y %H:%M") if v.fecha_venta else None,
                total=float(v.total or 0.0),
                metodo_pago=v.metodo_pago,
                tipo_pago="Efectivo" if v.tipo_pago == 2 else "Tarjeta"
            )
            for v in results
        ]
