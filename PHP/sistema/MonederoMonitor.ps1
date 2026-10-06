# ============================================
# Monedero Monitor - VendingBox Desktop App
# ============================================
# Aplicacion de escritorio para monitorear el listener del monedero
# Muestra logs en tiempo real, estado del sistema, inventario de cambio
# Se puede compilar a .exe con ps2exe
# ============================================

# ============================================
# SISTEMA DE LOGGING DE ERRORES GLOBAL
# ============================================
$ErrorActionPreference = "Continue"

# Determinar directorio del script PRIMERO
if ($PSScriptRoot) {
    $InitScriptDir = $PSScriptRoot
} elseif ($MyInvocation.MyCommand.Path) {
    $InitScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
} else {
    $InitScriptDir = Get-Location
}

$ErrorLogPath = "$InitScriptDir\admin\dist\logs\monitor_startup_errors.log"
$ErrorLogDir = Split-Path $ErrorLogPath -Parent

# Crear directorio de logs si no existe
if (!(Test-Path $ErrorLogDir)) {
    try {
        New-Item -ItemType Directory -Path $ErrorLogDir -Force | Out-Null
    } catch {
        $ErrorLogPath = "$env:TEMP\monedero_monitor_errors.log"
    }
}

# Función de logging que SIEMPRE funciona
function Write-StartupLog {
    param($Message, [switch]$IsError)
    
    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    $prefix = if ($IsError) { "[ERROR]" } else { "[INFO]" }
    $logEntry = "$timestamp $prefix $Message"
    
    try {
        Add-Content -Path $ErrorLogPath -Value $logEntry -Force
    } catch {
        # Si no puede escribir al archivo, mostrar en consola
        Write-Host $logEntry
    }
    
    # También mostrar en consola
    if ($IsError) {
        Write-Host $logEntry -ForegroundColor Red
    } else {
        Write-Host $logEntry -ForegroundColor Green
    }
}

# Capturar TODOS los errores no manejados
trap {
    Write-StartupLog "ERROR NO MANEJADO: $_" -IsError
    Write-StartupLog "Stack Trace: $($_.ScriptStackTrace)" -IsError
    Write-StartupLog "InnerException: $($_.Exception.InnerException)" -IsError
    
    # Guardar error en archivo separado
    $crashFile = "$InitScriptDir\admin\dist\logs\CRASH_REPORT.txt"
    try {
        $crashReport = @"
========================================
CRASH REPORT
========================================
Fecha: $(Get-Date -Format "yyyy-MM-dd HH:mm:ss")
Error: $_
Stack Trace:
$($_.ScriptStackTrace)

Exception:
$($_.Exception | Format-List * | Out-String)

Inner Exception:
$($_.Exception.InnerException | Format-List * | Out-String)
========================================
"@
        Set-Content -Path $crashFile -Value $crashReport -Force
        Write-Host "`n[CRASH] Reporte guardado en: $crashFile" -ForegroundColor Red
    } catch {
        Write-Host "[CRASH] No se pudo guardar reporte: $_" -ForegroundColor Red
    }
    
    # Mostrar en consola
    Write-Host "`n========================================" -ForegroundColor Red
    Write-Host "ERROR CRITICO - LA APLICACION SE CERRARA" -ForegroundColor Red
    Write-Host "========================================" -ForegroundColor Red
    Write-Host "Error: $_" -ForegroundColor Yellow
    Write-Host "`nStack Trace:" -ForegroundColor Yellow
    Write-Host $_.ScriptStackTrace -ForegroundColor Gray
    Write-Host "`nLog completo en: $ErrorLogPath" -ForegroundColor Cyan
    Write-Host "Reporte de crash en: $crashFile" -ForegroundColor Cyan
    Write-Host "`n========================================" -ForegroundColor Red
    
    # Mostrar ventana de error
    try {
        Add-Type -AssemblyName System.Windows.Forms
        [System.Windows.Forms.MessageBox]::Show(
            "ERROR CRITICO al iniciar Monedero Monitor:`n`n$_`n`n" +
            "Revisa el log en:`n$ErrorLogPath`n`n" +
            "Reporte completo en:`n$crashFile",
            "Error de Inicio",
            [System.Windows.Forms.MessageBoxButtons]::OK,
            [System.Windows.Forms.MessageBoxIcon]::Error
        )
    } catch { }
    
    Write-Host "`nPresione Enter para salir..." -ForegroundColor Yellow
    Read-Host
    exit 1
}

Write-StartupLog "=========================================="
Write-StartupLog "INICIANDO MONEDERO MONITOR"
Write-StartupLog "=========================================="
Write-StartupLog "Directorio del script: $InitScriptDir"
Write-StartupLog "Log de errores: $ErrorLogPath"

# ============================================
# CARGAR ASSEMBLIES NECESARIOS
# ============================================
try {
    Write-StartupLog "Cargando assemblies de .NET..."
    Add-Type -AssemblyName PresentationFramework, PresentationCore, WindowsBase, System.Windows.Forms, System.Drawing
    Write-StartupLog "Assemblies cargados exitosamente"
} catch {
    Write-StartupLog "ERROR al cargar assemblies: $_" -IsError
    throw
}

# Importar funciones de Windows API necesarias
try {
    Write-StartupLog "Cargando API de Windows..."
    Add-Type @"
    using System;
    using System.Runtime.InteropServices;
    public class Win32 {
        [DllImport("user32.dll", SetLastError = true)]
        public static extern bool DestroyIcon(IntPtr hIcon);
    }
"@
    Write-StartupLog "API de Windows cargada"
} catch {
    Write-StartupLog "ERROR al cargar Win32 API: $_" -IsError
    throw
}

# ============================================
# DETECCION DE INSTANCIA UNICA
# ============================================
Write-StartupLog "Verificando instancia unica..."
$mutexName = "Global\VendingBoxMonederoMonitor_SingleInstance"
$script:AppMutex = New-Object System.Threading.Mutex($false, $mutexName, [ref]$null)

if (-not $script:AppMutex.WaitOne(0, $false)) {
    Write-StartupLog "Ya hay una instancia corriendo - intentando restaurarla..."
    # Ya hay otra instancia corriendo
    # Intentar encontrar y activar la ventana existente
    try {
        Add-Type @"
        using System;
        using System.Runtime.InteropServices;
        public class WindowHelper {
            [DllImport("user32.dll")]
            public static extern IntPtr FindWindow(string lpClassName, string lpWindowName);
            
            [DllImport("user32.dll")]
            public static extern bool SetForegroundWindow(IntPtr hWnd);
            
            [DllImport("user32.dll")]
            public static extern bool ShowWindow(IntPtr hWnd, int nCmdShow);
            
            [DllImport("user32.dll")]
            public static extern bool IsIconic(IntPtr hWnd);
            
            public const int SW_RESTORE = 9;
            public const int SW_SHOW = 5;
        }
"@
    } catch {
        Write-StartupLog "Error al cargar WindowHelper: $_" -IsError
    }
    
    # Buscar ventana por titulo
    $hwnd = [WindowHelper]::FindWindow($null, "Monedero Monitor - VendingBox")
    
    if ($hwnd -ne [IntPtr]::Zero) {
        # Ventana encontrada - restaurarla
        if ([WindowHelper]::IsIconic($hwnd)) {
            [WindowHelper]::ShowWindow($hwnd, [WindowHelper]::SW_RESTORE) | Out-Null
        } else {
            [WindowHelper]::ShowWindow($hwnd, [WindowHelper]::SW_SHOW) | Out-Null
        }
        [WindowHelper]::SetForegroundWindow($hwnd) | Out-Null
        
        [System.Windows.MessageBox]::Show(
            "Monedero Monitor ya esta corriendo!`n`n" +
            "La ventana ha sido restaurada.`n`n" +
            "Si no la ves, busca el icono de moneda dorada ($) en la bandeja del sistema.",
            "Aplicacion Restaurada",
            [System.Windows.MessageBoxButton]::OK,
            [System.Windows.MessageBoxImage]::Information
        ) | Out-Null
    } else {
        # No se encontro ventana pero hay mutex - probablemente minimizada
        [System.Windows.MessageBox]::Show(
            "Monedero Monitor ya esta corriendo!`n`n" +
            "Busca el icono de MONEDA DORADA ($) en la bandeja del sistema (junto al reloj).`n`n" +
            "HAZ DOBLE CLIC en el icono dorado para mostrar la ventana.",
            "Aplicacion en Bandeja del Sistema",
            [System.Windows.MessageBoxButton]::OK,
            [System.Windows.MessageBoxImage]::Information
        ) | Out-Null
    }
    Write-StartupLog "Instancia unica ya corriendo - saliendo..."
    exit 0
}
Write-StartupLog "Instancia unica verificada - continuando..."

# Capturar la ruta del script ANTES de que se pierda
if ($PSScriptRoot) {
    $script:ScriptDirectory = $PSScriptRoot
} else {
    $script:ScriptDirectory = Split-Path -Parent $MyInvocation.MyCommand.Path
}

# Si aun asi esta vacio, usar directorio actual
if ([string]::IsNullOrEmpty($script:ScriptDirectory)) {
    $script:ScriptDirectory = Get-Location
}

Write-StartupLog "Directorio de trabajo: $($script:ScriptDirectory)"

# Variables globales
$script:ListenerProcess = $null
$script:LogFilePath = "$script:ScriptDirectory\admin\dist\logs\monedero_listener.log"
$script:InventoryFilePath = "$script:ScriptDirectory\admin\dist\logs\coin_inventory.log"
$script:SaldoFilePath = "$script:ScriptDirectory\admin\dist\logs\saldo_actual.json"
$script:ModeFilePath = "$script:ScriptDirectory\admin\dist\logs\coin_insert_mode.json"
$script:CargaMaquinaActiva = $false
$script:IsMinimized = $false
$script:NotifyIcon = $null
$script:PhpServerPreference = $null  # Laragon, XAMPP, o null para auto-detectar
$script:PreferenceFilePath = "$script:ScriptDirectory\admin\dist\logs\server_preference.txt"
$script:StateFilePath = "$script:ScriptDirectory\admin\dist\logs\monitor_running.state"
# Mantener referencias fuertes para evitar que el GC las limpie
$script:TrayBitmap = $null
$script:TrayGraphics = $null
$script:TrayIconHandle = $null
$script:IconCheckTimer = $null
$script:LogFilePosition = 0  # Posicion de lectura del log de PHP

# Limpiar procesos seriales (mata TODOS los procesos que puedan usar COM)
function Force-ReleaseCOMPort {
    param(
        [switch]$KillPhpOnly  # Opción para matar solo procesos PHP
    )
    
    Write-StartupLog "[FORCE-PORT] Iniciando Force-ReleaseCOMPort..."
    Write-StartupLog "[FORCE-PORT] KillPhpOnly: $KillPhpOnly"
    Write-Host "[FORCE-PORT] Limpiando procesos seriales..." -ForegroundColor Cyan
    
    try {
        Add-LogMessage "=========================================="
        Add-LogMessage "FORZANDO LIMPIEZA DE PROCESOS SERIALES"
        Add-LogMessage "=========================================="
        Write-StartupLog "[FORCE-PORT] Mensajes de log agregados"
        
        $closed = 0
        $failed = 0
        
        # Lista de programas que usan puertos seriales (SIN PowerShell para evitar suicidio)
        $serialApps = @(
            "Hercules", "putty", "arduino", "HTerm", "RealTerm", "TeraTerm",
            "hyperterminal", "serialport", "com0com", "vspe", "php", "php-cgi",
            "httpd", "xampp-control"
        )
        
        # Si KillPhpOnly está activado, solo matar PHP
        if ($KillPhpOnly) {
            $serialApps = @("php", "php-cgi", "httpd")
            Add-LogMessage "Modo: Solo matar procesos PHP"
            Write-StartupLog "[FORCE-PORT] Modo KillPhpOnly activado"
        }
        
        # Obtener PID del proceso actual para no matarse a si mismo
        $currentPID = $PID
        Write-StartupLog "[FORCE-PORT] PID actual (no matar): $currentPID"
        
        Write-StartupLog "[FORCE-PORT] Buscando procesos: $($serialApps -join ', ')"
        
        foreach ($appName in $serialApps) {
            Write-StartupLog "[FORCE-PORT] Buscando procesos '$appName'..."
            try {
                $processes = Get-Process -Name "*$appName*" -ErrorAction SilentlyContinue
                Write-StartupLog "[FORCE-PORT] Procesos '$appName' encontrados: $($processes.Count)"
            } catch {
                Write-StartupLog "[FORCE-PORT ERROR] Error buscando '$appName': $_" -IsError
                continue
            }
            
            if ($processes) {
                foreach ($proc in $processes) {
                    Write-StartupLog "[FORCE-PORT] Procesando: $($proc.ProcessName) (PID: $($proc.Id))"
                    
                    # PROTECCION: No matarse a si mismo
                    if ($proc.Id -eq $currentPID) {
                        Write-StartupLog "[FORCE-PORT] Ignorando proceso actual (PID: $currentPID)"
                        Write-Host "[FORCE-PORT] Saltando proceso actual" -ForegroundColor Yellow
                        continue
                    }
                    
                    # Si es PHP, verificar si está ejecutando el listener
                    if ($appName -eq "php") {
                        try {
                            Write-StartupLog "[FORCE-PORT] Verificando linea de comandos del proceso PHP..."
                            $cmdLine = (Get-WmiObject Win32_Process -Filter "ProcessId = $($proc.Id)" -ErrorAction Stop).CommandLine
                            Write-StartupLog "[FORCE-PORT] CommandLine: $cmdLine"
                            
                            # Si KillPhpOnly está activado, matar TODOS los PHP sin preguntar
                            # (útil cuando el puerto queda bloqueado por un proceso zombi)
                            if ($KillPhpOnly) {
                                Add-LogMessage "→ Cerrando proceso PHP: $($proc.ProcessName) (PID: $($proc.Id))"
                                Write-StartupLog "[FORCE-PORT] Matando proceso PHP (modo KillPhpOnly)..."
                                Stop-Process -Id $proc.Id -Force -ErrorAction Stop
                                Add-LogMessage "  [OK] Proceso PHP detenido"
                                Write-StartupLog "[FORCE-PORT] Proceso PHP detenido exitosamente"
                                $closed++
                                Start-Sleep -Milliseconds 200
                            }
                            # Si NO es KillPhpOnly, solo matar si está ejecutando monedero_listener.php
                            elseif ($cmdLine -and ($cmdLine -match "com5_manager" -or $cmdLine -match "monedero_listener")) {
                                Add-LogMessage "→ Cerrando GESTOR COM5: $($proc.ProcessName) (PID: $($proc.Id))"
                                Write-StartupLog "[FORCE-PORT] Matando gestor COM5 PHP..."
                                Stop-Process -Id $proc.Id -Force -ErrorAction Stop
                                Add-LogMessage "  [OK] Listener detenido"
                                Write-StartupLog "[FORCE-PORT] Listener detenido exitosamente"
                                $closed++
                                Start-Sleep -Milliseconds 200
                            } else {
                                Write-StartupLog "[FORCE-PORT] Proceso PHP no es el listener, ignorando"
                            }
                        } catch {
                            Add-LogMessage "  [ERROR] Error: $_"
                            Write-StartupLog "[FORCE-PORT ERROR] Error procesando PHP: $_" -IsError
                            $failed++
                        }
                    } else {
                        # Otros procesos, cerrar directamente
                        try {
                            Add-LogMessage "→ Cerrando: $($proc.ProcessName) (PID: $($proc.Id))"
                            Write-StartupLog "[FORCE-PORT] Matando proceso $($proc.ProcessName)..."
                            Stop-Process -Id $proc.Id -Force -ErrorAction Stop
                            Add-LogMessage "  [OK] Cerrado exitosamente"
                            Write-StartupLog "[FORCE-PORT] Proceso cerrado exitosamente"
                            $closed++
                            Start-Sleep -Milliseconds 100
                        } catch {
                            Add-LogMessage "  [ERROR] Error: $_"
                            Write-StartupLog "[FORCE-PORT ERROR] Error cerrando proceso: $_" -IsError
                            $failed++
                        }
                    }
                }
            }
        }
        
        # Esperar a que el SO libere el recurso
        if ($closed -gt 0) {
            Add-LogMessage "Esperando a que el SO libere el puerto..."
            Write-StartupLog "[FORCE-PORT] Esperando 1 segundo..."
            Start-Sleep -Seconds 1
        }
        
        Add-LogMessage "=========================================="
        Add-LogMessage "Cerrados: $closed | Fallidos: $failed"
        Add-LogMessage "=========================================="
        Write-StartupLog "[FORCE-PORT] Cerrados: $closed | Fallidos: $failed"
        
        if ($closed -eq 0) {
            Add-LogMessage "[INFO] No se encontraron programas usando puertos seriales"
            Write-StartupLog "[FORCE-PORT] No se encontraron programas"
        } else {
            Add-LogMessage "[OK] Procesos seriales cerrados"
            Write-StartupLog "[FORCE-PORT] Procesos seriales cerrados"
        }
        
        Write-StartupLog "[FORCE-PORT] Force-ReleaseCOMPort completado"
        Write-Host "[FORCE-PORT] Completado - Cerrados: $closed" -ForegroundColor Green
        return $closed
        
    } catch {
        Write-StartupLog "[FORCE-PORT FATAL ERROR] Error fatal: $_" -IsError
        Write-StartupLog "[FORCE-PORT FATAL ERROR] Stack: $($_.ScriptStackTrace)" -IsError
        Write-Host "[FORCE-PORT FATAL ERROR] Error fatal: $_" -ForegroundColor Red
        Add-LogMessage "[ERROR] Error fatal liberando puerto: $_"
        return 0
    }
}

# Guardar estado de ejecucion
function Save-RunningState {
    try {
        $logDir = Split-Path $script:StateFilePath -Parent
        if (!(Test-Path $logDir)) {
        New-Item -ItemType Directory -Path $logDir -Force | Out-Null
        }
        $state = @{
            running = $true
            pid = $PID
            timestamp = (Get-Date -Format "yyyy-MM-dd HH:mm:ss")
        } | ConvertTo-Json
        Set-Content -Path $script:StateFilePath -Value $state -Force
    } catch {
        # Ignorar errores
    }
}

# Limpiar estado de ejecucion
function Clear-RunningState {
    try {
        if (Test-Path $script:StateFilePath) {
            Remove-Item -Path $script:StateFilePath -Force
        }
    } catch {
        # Ignorar errores
    }
}

# Verificar y recrear icono si desaparece
function Ensure-TrayIconVisible {
    if ($script:NotifyIcon) {
        if (-not $script:NotifyIcon.Visible) {
            try {
                $script:NotifyIcon.Visible = $true
            } catch {
                # Si falla, recrear icono
                Cleanup-TrayIcon
                Create-NotifyIcon
            }
        }
    } else {
        # No existe, crearlo
        Create-NotifyIcon
    }
}

# Crear icono para system tray
function Create-NotifyIcon {
    if ($script:NotifyIcon -and $script:NotifyIcon.Visible) {
        # Ya existe y es visible, no crear otro
        return
    }
    
    $script:NotifyIcon = New-Object System.Windows.Forms.NotifyIcon
    
    # Crear icono desde bitmap - MANTENER REFERENCIAS FUERTES
    $script:TrayBitmap = New-Object System.Drawing.Bitmap 32, 32
    $script:TrayGraphics = [System.Drawing.Graphics]::FromImage($script:TrayBitmap)
    $script:TrayGraphics.Clear([System.Drawing.Color]::Transparent)
    
    # Dibujar circulo dorado
    $brush = New-Object System.Drawing.SolidBrush([System.Drawing.Color]::Gold)
    $script:TrayGraphics.FillEllipse($brush, 4, 4, 24, 24)
    
    # Dibujar simbolo $ en negro
    $font = New-Object System.Drawing.Font("Arial", 16, [System.Drawing.FontStyle]::Bold)
    $blackBrush = New-Object System.Drawing.SolidBrush([System.Drawing.Color]::Black)
    $script:TrayGraphics.DrawString("`$", $font, $blackBrush, 8, 4)
    
    # Obtener handle del icono y mantener referencia
    $script:TrayIconHandle = $script:TrayBitmap.GetHicon()
    $icon = [System.Drawing.Icon]::FromHandle($script:TrayIconHandle)
    
    $script:NotifyIcon.Icon = $icon
    $script:NotifyIcon.Text = "Monedero Monitor - VendingBox`nDoble clic para mostrar"
    
    # Forzar visibilidad ANTES de configurar eventos
    $script:NotifyIcon.Visible = $true
    
    # Limpiar objetos temporales
    $brush.Dispose()
    $font.Dispose()
    $blackBrush.Dispose()
    
    # Forzar actualizacion del icono
    [System.Windows.Forms.Application]::DoEvents()
    
    # Evento de doble clic para restaurar
    $script:NotifyIcon.Add_DoubleClick({
        Show-Window
    })
    
    # Menu contextual
    $contextMenu = New-Object System.Windows.Forms.ContextMenuStrip
    
    $showItem = New-Object System.Windows.Forms.ToolStripMenuItem
    $showItem.Text = "Mostrar Ventana"
    $showItem.Add_Click({ Show-Window })
    $contextMenu.Items.Add($showItem) | Out-Null
    
    $separatorItem = New-Object System.Windows.Forms.ToolStripSeparator
    $contextMenu.Items.Add($separatorItem) | Out-Null
    
    $resetItem = New-Object System.Windows.Forms.ToolStripMenuItem
    $resetItem.Text = "Reset Saldo y Monedas"
    $resetItem.Add_Click({ Reset-SystemLogs })
    $contextMenu.Items.Add($resetItem) | Out-Null
    
    $separatorItem2 = New-Object System.Windows.Forms.ToolStripSeparator
    $contextMenu.Items.Add($separatorItem2) | Out-Null
    
    $exitItem = New-Object System.Windows.Forms.ToolStripMenuItem
    $exitItem.Text = "Salir Completamente"
    $exitItem.Add_Click({ 
        Stop-ListenerProcess
        Clear-RunningState
        Cleanup-TrayIcon
        if ($script:AppMutex) {
            $script:AppMutex.ReleaseMutex()
            $script:AppMutex.Dispose()
        }
        $window.Close() 
    })
    $contextMenu.Items.Add($exitItem) | Out-Null
    
    $script:NotifyIcon.ContextMenuStrip = $contextMenu
}

# Limpiar icono de la bandeja correctamente
function Cleanup-TrayIcon {
    # Detener timer de verificacion
    if ($script:IconCheckTimer) {
        $script:IconCheckTimer.Stop()
        $script:IconCheckTimer = $null
    }
    
    if ($script:NotifyIcon) {
        try {
            $script:NotifyIcon.Visible = $false
            $script:NotifyIcon.Dispose()
        } catch {
            # Ignorar errores
        }
        $script:NotifyIcon = $null
    }
    
    if ($script:TrayGraphics) {
        $script:TrayGraphics.Dispose()
        $script:TrayGraphics = $null
    }
    
    if ($script:TrayBitmap) {
        $script:TrayBitmap.Dispose()
        $script:TrayBitmap = $null
    }
    
    if ($script:TrayIconHandle -and $script:TrayIconHandle -ne [IntPtr]::Zero) {
        # Liberar handle del icono
        try {
            [Win32]::DestroyIcon($script:TrayIconHandle) | Out-Null
        } catch {
            # Ignorar errores al liberar
        }
        $script:TrayIconHandle = $null
    }
}

function Show-Window {
    $window.Show()
    $window.WindowState = [System.Windows.WindowState]::Normal
    $window.Activate()
    $script:IsMinimized = $false
    Add-LogMessage "Ventana restaurada desde bandeja del sistema"
}

function Hide-Window {
    # Asegurar icono ANTES de ocultar ventana
    Ensure-TrayIconVisible
    
    # Esperar un momento para asegurar que el icono se cree
    Start-Sleep -Milliseconds 200
    
    # Ocultar ventana
    $window.Hide()
    $script:IsMinimized = $true
    
    # Verificar nuevamente que el icono siga visible
    if ($script:NotifyIcon) {
        $script:NotifyIcon.Visible = $true
        [System.Windows.Forms.Application]::DoEvents()
        Add-LogMessage "Ventana minimizada - Icono en bandeja: VISIBLE"
        Add-LogMessage "Busca el icono de MONEDA DORADA ($) junto al reloj"
        Show-TrayNotification "Minimizado a bandeja" "Doble clic en la MONEDA DORADA ($) para restaurar"
    } else {
        Add-LogMessage "ERROR: No se pudo crear icono en bandeja!"
    }
}

function Show-TrayNotification {
    param($title, $message)
    if ($script:NotifyIcon -and $script:NotifyIcon.Visible) {
        try {
            $script:NotifyIcon.BalloonTipTitle = $title
            $script:NotifyIcon.BalloonTipText = $message
            $script:NotifyIcon.BalloonTipIcon = [System.Windows.Forms.ToolTipIcon]::Info
            $script:NotifyIcon.ShowBalloonTip(3000)
        } catch {
            # Ignorar errores de notificacion
        }
    }
}

# Funcion para mostrar estado en el tooltip del icono
function Update-TrayTooltip {
    param($isRunning)
    if ($script:NotifyIcon) {
        $status = if ($isRunning) { "Corriendo" } else { "Detenido" }
        $server = if ($script:PhpServerPreference) { " ($script:PhpServerPreference)" } else { "" }
        $script:NotifyIcon.Text = "Monedero Monitor - $status$server`nDoble clic para mostrar"
    }
}

# Cargar preferencia de servidor
function Load-ServerPreference {
    if (Test-Path $script:PreferenceFilePath) {
        try {
            $script:PhpServerPreference = Get-Content $script:PreferenceFilePath -Raw -ErrorAction SilentlyContinue
            $script:PhpServerPreference = $script:PhpServerPreference.Trim()
            if ($script:PhpServerPreference -ne "Laragon" -and $script:PhpServerPreference -ne "XAMPP") {
                $script:PhpServerPreference = $null
            }
        } catch {
            $script:PhpServerPreference = $null
        }
    }
}

# Guardar preferencia de servidor
function Save-ServerPreference {
    param($preference)
    try {
        $logDir = Split-Path $script:PreferenceFilePath -Parent
        if (!(Test-Path $logDir)) {
            New-Item -ItemType Directory -Path $logDir -Force | Out-Null
        }
        Set-Content -Path $script:PreferenceFilePath -Value $preference -Force
        $script:PhpServerPreference = $preference
    } catch {
        # Ignorar errores al guardar preferencia
    }
}

# Detectar servidores disponibles
function Detect-AvailableServers {
    $servers = @{}
    
    # Detectar Laragon
    $laravelPaths = @(
        "C:\laragon\bin\php\php-8.2.0\php.exe",
        "C:\laragon\bin\php\php-8.1.10\php.exe",
        "C:\laragon\bin\php\php-8.0.30\php.exe",
        "C:\laragon\bin\php\php-7.4.33\php.exe"
    )
    
    foreach ($path in $laravelPaths) {
        if (Test-Path $path) {
            $servers["Laragon"] = $path
            break
        }
    }
    
    # Buscar cualquier version en Laragon
    if (!$servers.ContainsKey("Laragon") -and (Test-Path "C:\laragon\bin\php")) {
        $phpDirs = Get-ChildItem "C:\laragon\bin\php" -Directory -ErrorAction SilentlyContinue | Sort-Object Name -Descending
        foreach ($dir in $phpDirs) {
            $exePath = Join-Path $dir.FullName "php.exe"
            if (Test-Path $exePath) {
                $servers["Laragon"] = $exePath
                break
            }
        }
    }
    
    # Detectar XAMPP
    $xamppPaths = @(
        "C:\xampp\php\php.exe",
        "D:\xampp\php\php.exe",
        "C:\Program Files\xampp\php\php.exe",
        "C:\Program Files (x86)\xampp\php\php.exe"
    )
    
    foreach ($path in $xamppPaths) {
        if (Test-Path $path) {
            $servers["XAMPP"] = $path
            break
        }
    }
    
    return $servers
}

# Mostrar dialogo de seleccion de servidor
function Show-ServerSelectionDialog {
    param($servers)
    
    $form = New-Object System.Windows.Forms.Form
    $form.Text = "Seleccionar Servidor PHP"
    $form.Size = New-Object System.Drawing.Size(400, 250)
    $form.StartPosition = "CenterScreen"
    $form.FormBorderStyle = "FixedDialog"
    $form.MaximizeBox = $false
    $form.MinimizeBox = $false
    $form.BackColor = [System.Drawing.Color]::FromArgb(30, 30, 30)
    
    $label = New-Object System.Windows.Forms.Label
    $label.Location = New-Object System.Drawing.Point(20, 20)
    $label.Size = New-Object System.Drawing.Size(360, 40)
    $label.Text = "Se detectaron multiples servidores PHP instalados.`nSelecciona cual deseas usar:"
    $label.ForeColor = [System.Drawing.Color]::White
    $label.Font = New-Object System.Drawing.Font("Segoe UI", 10)
    $form.Controls.Add($label)
    
    $yPos = 70
    $radioButtons = @{}
    
    foreach ($server in $servers.Keys) {
        $radio = New-Object System.Windows.Forms.RadioButton
        $radio.Location = New-Object System.Drawing.Point(30, $yPos)
        $radio.Size = New-Object System.Drawing.Size(340, 30)
        $radio.Text = "$server - $($servers[$server])"
        $radio.ForeColor = [System.Drawing.Color]::White
        $radio.Font = New-Object System.Drawing.Font("Segoe UI", 9)
        $radio.Tag = $server
        $form.Controls.Add($radio)
        $radioButtons[$server] = $radio
        $yPos += 35
        
        # Marcar el preferido o el primero
        if ($script:PhpServerPreference -eq $server -or $radioButtons.Count -eq 1) {
            $radio.Checked = $true
        }
    }
    
    $okButton = New-Object System.Windows.Forms.Button
    $okButton.Location = New-Object System.Drawing.Point(120, $yPos + 20)
    $okButton.Size = New-Object System.Drawing.Size(80, 30)
    $okButton.Text = "Aceptar"
    $okButton.BackColor = [System.Drawing.Color]::FromArgb(29, 185, 84)
    $okButton.ForeColor = [System.Drawing.Color]::White
    $okButton.FlatStyle = "Flat"
    $okButton.DialogResult = [System.Windows.Forms.DialogResult]::OK
    $form.Controls.Add($okButton)
    
    $cancelButton = New-Object System.Windows.Forms.Button
    $cancelButton.Location = New-Object System.Drawing.Point(210, $yPos + 20)
    $cancelButton.Size = New-Object System.Drawing.Size(80, 30)
    $cancelButton.Text = "Cancelar"
    $cancelButton.BackColor = [System.Drawing.Color]::FromArgb(231, 76, 60)
    $cancelButton.ForeColor = [System.Drawing.Color]::White
    $cancelButton.FlatStyle = "Flat"
    $cancelButton.DialogResult = [System.Windows.Forms.DialogResult]::Cancel
    $form.Controls.Add($cancelButton)
    
    $form.AcceptButton = $okButton
    $form.CancelButton = $cancelButton
    
    $result = $form.ShowDialog()
    
    if ($result -eq [System.Windows.Forms.DialogResult]::OK) {
        foreach ($radio in $radioButtons.Values) {
            if ($radio.Checked) {
                return $radio.Tag
            }
        }
    }
    
    return $null
}

# Cambiar servidor PHP manualmente
function Change-PhpServer {
    $servers = Detect-AvailableServers
    
    if ($servers.Count -eq 0) {
        [System.Windows.MessageBox]::Show(
            "No se detectaron servidores PHP instalados (Laragon o XAMPP)." + "`n`n" +
            "Por favor instala uno de ellos.",
            "Sin servidores PHP",
            [System.Windows.MessageBoxButton]::OK,
            [System.Windows.MessageBoxImage]::Warning
        ) | Out-Null
        return
    }
    
    if ($servers.Count -eq 1) {
        $serverName = $servers.Keys | Select-Object -First 1
        [System.Windows.MessageBox]::Show(
            "Solo se detecto: $serverName`n`nRuta: $($servers[$serverName])",
            "Un servidor detectado",
            [System.Windows.MessageBoxButton]::OK,
            [System.Windows.MessageBoxImage]::Information
        ) | Out-Null
        return
    }
    
    $selected = Show-ServerSelectionDialog $servers
    
    if ($selected) {
        Save-ServerPreference $selected
        Add-LogMessage "Servidor cambiado a: $selected"
        Add-LogMessage "PHP: $($servers[$selected])"
        
        [System.Windows.MessageBox]::Show(
            "Servidor cambiado a: $selected`n`nRuta: $($servers[$selected])`n`nSi el listener esta corriendo, detenerlo y reiniciarlo.",
            "Servidor actualizado",
            [System.Windows.MessageBoxButton]::OK,
            [System.Windows.MessageBoxImage]::Information
        ) | Out-Null
    }
}

# Funcion para leer logs
function Get-LogTail {
    param($Lines = 50)
    
    if (Test-Path $script:LogFilePath) {
        $content = Get-Content $script:LogFilePath -Tail $Lines -ErrorAction SilentlyContinue
        return $content -join "`n"
    }
    return "Esperando logs..."
}

function Get-InsertMode {
    if (Test-Path $script:ModeFilePath) {
        try {
            return Get-Content $script:ModeFilePath -Raw | ConvertFrom-Json
        } catch { }
    }
    return [PSCustomObject]@{ activo_carga_maquina = $false; modo = "cliente" }
}

function Set-CargaMaquinaMode {
    param([bool]$Activa)

    $logDir = Split-Path $script:ModeFilePath -Parent
    if (!(Test-Path $logDir)) {
        New-Item -ItemType Directory -Path $logDir -Force | Out-Null
    }

    $mode = [ordered]@{
        activo_carga_maquina = $Activa
        modo                 = if ($Activa) { "maquina" } else { "cliente" }
        fecha                = (Get-Date -Format "yyyy-MM-dd HH:mm:ss")
        actualizado_por      = "MonederoMonitor"
        descripcion          = if ($Activa) {
            "Monedas insertadas suman SOLO al inventario (saldo maquina)"
        } else {
            "Monedas insertadas suman al saldo del cliente Y al inventario"
        }
    }
    $mode | ConvertTo-Json | Set-Content -Path $script:ModeFilePath -Force
    $script:CargaMaquinaActiva = $Activa

    if ($Activa) {
        Add-LogMessage "=========================================="
        Add-LogMessage "MODO CARGA MAQUINA ACTIVO"
        Add-LogMessage "Las monedas iran SOLO al inventario (cambio)"
        Add-LogMessage "Los billetes se IGNORAN (no hay cambio en billetes)"
        Add-LogMessage "NO se sumaran al saldo del cliente"
        Add-LogMessage "=========================================="
        Show-TrayNotification "Carga Maquina" "Solo monedas cuentan para inventario"
    } else {
        Add-LogMessage "=========================================="
        Add-LogMessage "MODO CLIENTE ACTIVO"
        Add-LogMessage "Las monedas suman al saldo del cliente"
        Add-LogMessage "=========================================="
        Show-TrayNotification "Modo Cliente" "Monedas cuentan para comprar"
    }

    Update-CargaMaquinaUI
}

function Sync-CargaMaquinaFromFile {
    param([switch]$ForceUI)
    $mode = Get-InsertMode
    $nuevo = [bool]$mode.activo_carga_maquina
    if ($ForceUI -or $nuevo -ne $script:CargaMaquinaActiva) {
        $script:CargaMaquinaActiva = $nuevo
        Update-CargaMaquinaUI
    }
}

function Update-CargaMaquinaUI {
    if (-not $btnCargaMaquina) { return }

    $btnCargaMaquina.Dispatcher.Invoke([action]{
        if ($script:CargaMaquinaActiva) {
            $btnCargaMaquina.Content = "[OK] FIN CARGA - VOLVER A MODO CLIENTE"
            $btnCargaMaquina.Background = "#E67E22"
            if ($lblModoInsercion) {
                $lblModoInsercion.Text = "CARGANDO MAQUINA: monedas -> solo inventario (no saldo cliente)"
                $lblModoInsercion.Foreground = "#1DB954"
            }
            if ($borderModoInsercion) {
                $borderModoInsercion.Background = "#1a3d2a"
                $borderModoInsercion.BorderBrush = "#1DB954"
            }
        } else {
            $btnCargaMaquina.Content = "[+] CARGAR MAQUINA (solo inventario / cambio)"
            $btnCargaMaquina.Background = "#2980B9"
            if ($lblModoInsercion) {
                $lblModoInsercion.Text = "MODO CLIENTE: monedas insertadas cuentan para comprar"
                $lblModoInsercion.Foreground = "#FFD700"
            }
            if ($borderModoInsercion) {
                $borderModoInsercion.Background = "#2D2D30"
                $borderModoInsercion.BorderBrush = "#3F3F46"
            }
        }
    })
}

# Funcion para obtener saldo
function Get-Saldo {
    if (Test-Path $script:SaldoFilePath) {
        try {
            $data = Get-Content $script:SaldoFilePath -Raw | ConvertFrom-Json
            return [decimal]$data.saldo
        } catch {
            return 0
        }
    }
    return 0
}

# Funcion para obtener inventario
function Get-Inventory {
    if (Test-Path $script:InventoryFilePath) {
        try {
            $data = Get-Content $script:InventoryFilePath -Raw | ConvertFrom-Json
            return $data
        } catch {
            return $null
        }
    }
    return $null
}

# Iniciar listener
function Start-ListenerProcess {
    Write-StartupLog "[FUNC] Start-ListenerProcess - INICIANDO"
    Write-Host "`n[FUNC] Start-ListenerProcess iniciando..." -ForegroundColor Cyan
    
    try {
        # Envolver todo en try-catch para capturar cualquier error
        $ErrorActionPreference = "Stop"
        
        if ($script:ListenerProcess -and !$script:ListenerProcess.HasExited) {
            Add-LogMessage "El listener ya esta corriendo"
            Write-StartupLog "[FUNC] Listener ya corriendo - saliendo"
            return
        }
        
        Add-LogMessage "Iniciando listener del monedero..."
        Write-StartupLog "[FUNC] Paso inicial completado"
        
        # PASO 1: DETECTAR SERVIDOR PHP
        Add-LogMessage "[PASO 1/2] Detectando servidor PHP..."
        Write-StartupLog "[FUNC] Iniciando paso 1 - Detectar servidor PHP"
    
    # Detectar servidores disponibles
    $servers = Detect-AvailableServers
    Write-StartupLog "[FUNC] Servidores detectados: $($servers.Count)"
    
    if ($servers.Count -eq 0) {
        Add-LogMessage "ERROR: No se encuentra PHP (Laragon o XAMPP)"
        Add-LogMessage "Por favor instala Laragon o XAMPP"
        [System.Windows.MessageBox]::Show(
            "No se detectaron servidores PHP instalados.`n`nPor favor instala Laragon o XAMPP.",
            "PHP No Encontrado",
            [System.Windows.MessageBoxButton]::OK,
            [System.Windows.MessageBoxImage]::Error
        ) | Out-Null
        return
    }
    
    $phpPath = $null
    $selectedServer = $null
    
    # Si hay preferencia guardada, usarla
    if ($script:PhpServerPreference -and $servers.ContainsKey($script:PhpServerPreference)) {
        $phpPath = $servers[$script:PhpServerPreference]
        $selectedServer = $script:PhpServerPreference
        Add-LogMessage "Usando servidor guardado: $selectedServer"
    }
    # Si solo hay uno, usarlo automaticamente
    elseif ($servers.Count -eq 1) {
        $selectedServer = $servers.Keys | Select-Object -First 1
        $phpPath = $servers[$selectedServer]
        Add-LogMessage "Auto-detectado: $selectedServer"
        Save-ServerPreference $selectedServer
    }
    # Si hay multiples, preguntar al usuario
    else {
        Add-LogMessage "Multiples servidores detectados: $($servers.Keys -join ', ')"
        $selectedServer = Show-ServerSelectionDialog $servers
        
        if ($selectedServer) {
            $phpPath = $servers[$selectedServer]
            Save-ServerPreference $selectedServer
            Add-LogMessage "Usuario selecciono: $selectedServer"
        } else {
            Add-LogMessage "Seleccion cancelada por el usuario"
            return
        }
    }
    
    if (!$phpPath) {
        Add-LogMessage "ERROR: No se pudo determinar ruta de PHP"
        Write-StartupLog "[FUNC ERROR] phpPath es null - saliendo" -IsError
        return
    }
    
    Add-LogMessage "PHP: $phpPath"
    Write-StartupLog "[FUNC] PHP Path: $phpPath"
    Write-StartupLog "[FUNC] Paso 1 completado"
    
    # PASO 2: INICIAR COM5 MANAGER (PROCESO PADRE)
    Add-LogMessage "[PASO 2/2] Iniciando COM5 Manager (proceso padre)..."
    Write-StartupLog "[FUNC] Iniciando paso 2 - Iniciar COM5 Manager"
    
    $listenerScript = "$script:ScriptDirectory\com5_manager.php"
    Write-StartupLog "[FUNC] Manager script: $listenerScript"
    
    if (!(Test-Path $listenerScript)) {
        Add-LogMessage "ERROR: No se encuentra com5_manager.php"
        Add-LogMessage "ERROR: Ruta buscada: $listenerScript"
        Write-StartupLog "[FUNC ERROR] Archivo no encontrado: $listenerScript" -IsError
        return
    }
    
    Write-StartupLog "[FUNC] Archivo manager encontrado, creando proceso..."
    
    # Matar cualquier PHP residual que pueda tener COM5 ocupado
    Add-LogMessage "Liberando COM5 (matando PHP residual)..."
    try {
        $phpResidual = Get-Process -Name "php" -ErrorAction SilentlyContinue
        foreach ($p in $phpResidual) {
            & taskkill /F /T /PID $p.Id 2>&1 | Out-Null
        }
        if ($phpResidual) {
            Add-LogMessage "PHP residual eliminado - esperando liberacion de COM5..."
            Start-Sleep -Milliseconds 800
        }
    } catch { }
    
    # Resetear posicion de lectura del log para mostrar solo logs nuevos
    $script:LogFilePosition = 0
    # Truncar log viejo para que el logBox solo muestre la sesion actual
    try {
        if (Test-Path $script:LogFilePath) {
            Clear-Content -Path $script:LogFilePath -Force
        }
    } catch { }
    
    try {
        $psi = New-Object System.Diagnostics.ProcessStartInfo
        $psi.FileName = $phpPath
        $psi.Arguments = "`"$listenerScript`""
        $psi.UseShellExecute = $false
        # NO redirigir stdout/stderr - PHP escribe al archivo log directamente
        $psi.RedirectStandardOutput = $false
        $psi.RedirectStandardError = $false
        $psi.CreateNoWindow = $true
        $psi.WorkingDirectory = $script:ScriptDirectory
        
        Write-StartupLog "[FUNC] Iniciando proceso COM5 Manager (PADRE)..."
        $script:ListenerProcess = [System.Diagnostics.Process]::Start($psi)
        Write-StartupLog "[FUNC] Manager iniciado - PID: $($script:ListenerProcess.Id)"
        
        Add-LogMessage "[OK] COM5 Manager iniciado (PID: $($script:ListenerProcess.Id))"
        Add-LogMessage "=========================================="
        Add-LogMessage "✅ SISTEMA COMPLETAMENTE INICIALIZADO"
        Add-LogMessage "=========================================="
        Add-LogMessage "📥 Puerto COM5: ABIERTO (modo padre)"
        Add-LogMessage "📥 Aceptador de monedas: HABILITADO"
        Add-LogMessage "📤 Dispensador: LISTO"
        Add-LogMessage "=========================================="
        Update-StatusIndicator $true
        
        Write-StartupLog "[FUNC] Paso 2 completado - Manager corriendo"
        Show-TrayNotification "Sistema Activo" "✅ Aceptador y dispensador listos"
        
    } catch {
        Add-LogMessage "ERROR al iniciar COM5 Manager: $_"
        Write-StartupLog "[FUNC ERROR] Exception al iniciar proceso: $_" -IsError
        Write-StartupLog "[FUNC ERROR] Stack: $($_.ScriptStackTrace)" -IsError
        Write-Host "[FUNC ERROR] Exception: $_" -ForegroundColor Red
        Write-Host "[FUNC ERROR] Stack: $($_.ScriptStackTrace)" -ForegroundColor Red
        Update-StatusIndicator $false
        throw  # Re-lanzar para que el botón lo capture
    }
    
    } catch {
        # Catch externo para cualquier error fuera del try interno
        Write-StartupLog "[FUNC FATAL ERROR] Error fatal en Start-ListenerProcess: $_" -IsError
        Write-StartupLog "[FUNC FATAL ERROR] Stack: $($_.ScriptStackTrace)" -IsError
        Write-Host "`n[FUNC FATAL ERROR] Error fatal: $_" -ForegroundColor Red
        Write-Host "[FUNC FATAL ERROR] Stack: $($_.ScriptStackTrace)" -ForegroundColor Red
        Add-LogMessage "[FATAL ERROR] Error critico al iniciar: $_"
        Update-StatusIndicator $false
        throw
    } finally {
        $ErrorActionPreference = "Continue"
    }
    
    Write-StartupLog "[FUNC] Start-ListenerProcess - FINALIZADO"
}

# Detener listener
function Stop-ListenerProcess {
    if ($script:ListenerProcess -and !$script:ListenerProcess.HasExited) {
        Add-LogMessage "Deteniendo listener..."
        try {
            $phpPID = $script:ListenerProcess.Id
            Add-LogMessage "Matando arbol de procesos (PID: $phpPID)..."
            
            # Usar taskkill /T para matar PHP + todos sus hijos (incluyendo el PowerShell que tiene COM5)
            $tkResult = & taskkill /F /T /PID $phpPID 2>&1
            Add-LogMessage "taskkill: $tkResult"
            
            # Esperar que termine
            $script:ListenerProcess.WaitForExit(3000)
            
            # Cerrar streams para liberar handles de pipes
            try { $script:ListenerProcess.StandardOutput.Close() } catch {}
            try { $script:ListenerProcess.StandardError.Close() } catch {}
            $script:ListenerProcess.Dispose()
            $script:ListenerProcess = $null
            
            Add-LogMessage "Listener detenido - COM5 liberado"
            Update-StatusIndicator $false
        } catch {
            Add-LogMessage "Error al detener listener: $_"
            Update-StatusIndicator $false
        }
    } else {
        Add-LogMessage "El listener no esta corriendo"
        Update-StatusIndicator $false
    }
}

# Agregar mensaje al log
function Add-LogMessage {
    param($message)
    $timestamp = Get-Date -Format "HH:mm:ss"
    
    # Escribir al archivo de log siempre
    try {
        Add-Content -Path $ErrorLogPath -Value "[$timestamp] $message" -Force -ErrorAction SilentlyContinue
    } catch { }
    
    # Intentar mostrar en la ventana
    try {
        if ($logBox -and $logBox.Dispatcher) {
            $logBox.Dispatcher.Invoke([action]{
                $logBox.AppendText("[$timestamp] $message`n")
                $logBox.ScrollToEnd()
            })
        }
    } catch {
        # Si falla, al menos escribir a consola
        Write-Host "[$timestamp] $message"
    }
}

# Actualizar indicador de estado
function Update-StatusIndicator {
    param($isRunning)
    $statusIndicator.Dispatcher.Invoke([action]{
        if ($isRunning) {
            $statusIndicator.Fill = "LimeGreen"
            $statusText.Text = "CORRIENDO"
            $statusText.Foreground = "LimeGreen"
            $btnStart.IsEnabled = $false
            $btnStop.IsEnabled = $true
        } else {
            $statusIndicator.Fill = "Red"
            $statusText.Text = "DETENIDO"
            $statusText.Foreground = "Red"
            $btnStart.IsEnabled = $true
            $btnStop.IsEnabled = $false
        }
    })
    Update-TrayTooltip $isRunning
}

# Actualizar estadisticas
function Update-Stats {
    $saldo = Get-Saldo
    $inventory = Get-Inventory
    
    $lblSaldo.Dispatcher.Invoke([action]{
        $lblSaldo.Text = "`$$($saldo.ToString('F2'))"
    })
    
    if ($inventory) {
        $total = 0
        if ($null -ne $inventory.float_operador) {
            $total = [decimal]$inventory.float_operador
        }
        $lblInventario.Dispatcher.Invoke([action]{
            $lblInventario.Text = "`$$($total.ToString('F2'))"
        })
        
        # Actualizar desglose
        $desglose = ""
        foreach ($denom in @('20', '10', '5', '2', '1')) {
            $cant = $inventory.denominaciones.$denom
            if ($cant -gt 0) {
                $desglose += "`$$denom x $cant   "
            }
        }
        $lblDesglose.Dispatcher.Invoke([action]{
            $lblDesglose.Text = $desglose.Trim()
        })
    }
}

# Resetear archivos de log del sistema
function Reset-SystemLogs {
    $result = [System.Windows.MessageBox]::Show(
        "ATENCION: Esto limpiara completamente:`n`n" +
        "- Saldo actual del monedero`n" +
        "- Inventario de monedas`n" +
        "- Archivo de logs`n`n" +
        "Solo hazlo si sacaste fisicamente el dinero del monedero.`n`n" +
        "Deseas continuar?",
        "Confirmar Reset de Sistema",
        [System.Windows.MessageBoxButton]::YesNo,
        [System.Windows.MessageBoxImage]::Warning
    )
    
    if ($result -eq [System.Windows.MessageBoxResult]::Yes) {
        try {
            $resetCount = 0
            
            # Resetear saldo a 0
            if (Test-Path $script:SaldoFilePath) {
                $resetData = @{ saldo = 0 } | ConvertTo-Json
                Set-Content -Path $script:SaldoFilePath -Value $resetData -Force
                Add-LogMessage "Saldo reseteado a $0.00"
                $resetCount++
            }
            
            # Resetear inventario de monedas
            if (Test-Path $script:InventoryFilePath) {
                $resetInventory = @{
                    tubos = @{
                        "A" = @{ cantidad = 0 }
                        "B" = @{ cantidad = 0 }
                        "C" = @{ cantidad = 0 }
                        "D" = @{ cantidad = 0 }
                        "E" = @{ cantidad = 0 }
                    }
                    denominaciones = @{
                        "10" = 0
                        "5" = 0
                        "2" = 0
                        "1" = 0
                    }
                    total_pesos = 0
                    float_operador = 0
                    timestamp = (Get-Date -Format "yyyy-MM-dd HH:mm:ss")
                } | ConvertTo-Json -Depth 5
                Set-Content -Path $script:InventoryFilePath -Value $resetInventory -Force
                Add-LogMessage "Inventario de monedas reseteado"
                $resetCount++
            }
            
            # Limpiar archivo de logs (opcional)
            if (Test-Path $script:LogFilePath) {
                Clear-Content -Path $script:LogFilePath -Force
                Add-LogMessage "Archivo de logs limpiado"
                $resetCount++
            }
            
            Set-CargaMaquinaMode -Activa $false

            # Actualizar interfaz
            Update-Stats
            
            Add-LogMessage "========================================"
            Add-LogMessage "SISTEMA RESETEADO EXITOSAMENTE"
            Add-LogMessage "Archivos limpiados: $resetCount"
            Add-LogMessage "========================================"
            
            [System.Windows.MessageBox]::Show(
                "Sistema reseteado correctamente.`n`n" +
                "Saldo: $0.00`n" +
                "Inventario: 0 monedas`n" +
                "Logs: Limpiados",
                "Reset Completado",
                [System.Windows.MessageBoxButton]::OK,
                [System.Windows.MessageBoxImage]::Information
            ) | Out-Null
            
        } catch {
            Add-LogMessage "ERROR al resetear sistema: $_"
            [System.Windows.MessageBox]::Show(
                "Error al resetear el sistema:`n`n$_",
                "Error",
                [System.Windows.MessageBoxButton]::OK,
                [System.Windows.MessageBoxImage]::Error
            ) | Out-Null
        }
    } else {
        Add-LogMessage "Reset cancelado por el usuario"
    }
}

# Detener TODOS los listeners PHP corriendo
function Kill-AllPhpListeners {
    Add-LogMessage "=========================================="
    Add-LogMessage "BUSCANDO Y ELIMINANDO PROCESOS PHP"
    Add-LogMessage "=========================================="
    
    try {
        # Buscar todos los procesos PHP
        $phpProcesses = Get-Process -Name "php" -ErrorAction SilentlyContinue
        
        if ($phpProcesses) {
            $count = ($phpProcesses | Measure-Object).Count
            Add-LogMessage "Procesos PHP encontrados: $count"
            Add-LogMessage ""
            
            # Mostrar detalles de cada proceso
            foreach ($proc in $phpProcesses) {
                try {
                    $startTime = $proc.StartTime.ToString("HH:mm:ss")
                    $memory = [math]::Round($proc.WorkingSet64 / 1MB, 2)
                    Add-LogMessage "  - PID: $($proc.Id) | Inicio: $startTime | RAM: $memory MB"
                } catch {
                    Add-LogMessage "  - PID: $($proc.Id) | (Info no disponible)"
                }
            }
            
            Add-LogMessage ""
            Add-LogMessage "Eliminando procesos..."
            
            # Matar todos los procesos PHP
            $killed = 0
            foreach ($proc in $phpProcesses) {
                try {
                    Stop-Process -Id $proc.Id -Force -ErrorAction Stop
                    Add-LogMessage "  [OK] PID $($proc.Id) eliminado"
                    $killed++
                } catch {
                    Add-LogMessage "  [ERROR] PID $($proc.Id) - Error: $_"
                }
            }
            
            Add-LogMessage ""
            Add-LogMessage "=========================================="
            Add-LogMessage "RESUMEN: $killed de $count procesos eliminados"
            Add-LogMessage "=========================================="
            
            # Si el listener propio estaba corriendo, actualizarlo
            if ($script:ListenerProcess -and !$script:ListenerProcess.HasExited) {
                $script:ListenerProcess = $null
                Update-StatusIndicator $false
            }
            
            [System.Windows.MessageBox]::Show(
                "Procesos PHP eliminados: $killed de $count`n`n" +
                "Revisa los logs para ver detalles.",
                "Limpieza Completada",
                [System.Windows.MessageBoxButton]::OK,
                [System.Windows.MessageBoxImage]::Information
            ) | Out-Null
            
        } else {
            Add-LogMessage "No se encontraron procesos PHP corriendo"
            Add-LogMessage "=========================================="
            
            [System.Windows.MessageBox]::Show(
                "No se encontraron procesos PHP corriendo.`n`n" +
                "El sistema está limpio.",
                "Sin Procesos",
                [System.Windows.MessageBoxButton]::OK,
                [System.Windows.MessageBoxImage]::Information
            ) | Out-Null
        }
        
    } catch {
        Add-LogMessage "ERROR al buscar/eliminar procesos: $_"
        Add-LogMessage "=========================================="
        
        [System.Windows.MessageBox]::Show(
            "Error al buscar/eliminar procesos PHP:`n`n$_",
            "Error",
            [System.Windows.MessageBoxButton]::OK,
            [System.Windows.MessageBoxImage]::Error
        ) | Out-Null
    }
}

# XAML de la interfaz
Write-StartupLog "Preparando interfaz XAML..."
try {
    [xml]$xaml = @"
<Window xmlns="http://schemas.microsoft.com/winfx/2006/xaml/presentation"
        xmlns:x="http://schemas.microsoft.com/winfx/2006/xaml"
        Title="Monedero Monitor - VendingBox" 
        Height="750" Width="1050"
        WindowStartupLocation="CenterScreen"
        Background="#1E1E1E"
        ResizeMode="CanResize">
    
    <Grid Margin="15">
        <Grid.RowDefinitions>
            <RowDefinition Height="Auto"/>
            <RowDefinition Height="Auto"/>
            <RowDefinition Height="Auto"/>
            <RowDefinition Height="Auto"/>
            <RowDefinition Height="*"/>
            <RowDefinition Height="Auto"/>
        </Grid.RowDefinitions>
        
        <!-- HEADER -->
        <Border Grid.Row="0" Background="#FFD700" CornerRadius="5" Padding="15,10" Margin="0,0,0,15">
            <StackPanel>
                <TextBlock Text="MONEDERO MONITOR" FontSize="24" FontWeight="Bold" Foreground="#1E1E1E" HorizontalAlignment="Center"/>
                <TextBlock Text="VendingBox Control Panel" FontSize="12" Foreground="#1E1E1E" HorizontalAlignment="Center" Opacity="0.8"/>
            </StackPanel>
        </Border>
        
        <!-- STATS CARDS -->
        <Grid Grid.Row="1" Margin="0,0,0,15">
            <Grid.ColumnDefinitions>
                <ColumnDefinition Width="*"/>
                <ColumnDefinition Width="10"/>
                <ColumnDefinition Width="*"/>
                <ColumnDefinition Width="10"/>
                <ColumnDefinition Width="*"/>
            </Grid.ColumnDefinitions>
            
            <!-- Estado -->
            <Border Grid.Column="0" Background="#2D2D30" CornerRadius="5" Padding="15">
                <StackPanel>
                    <TextBlock Text="ESTADO" FontSize="10" Foreground="#808080" FontWeight="Bold"/>
                    <StackPanel Orientation="Horizontal" Margin="0,5,0,0">
                        <Ellipse x:Name="statusIndicator" Width="12" Height="12" Fill="Red" Margin="0,0,8,0"/>
                        <TextBlock x:Name="statusText" Text="DETENIDO" FontSize="16" Foreground="Red" FontWeight="Bold"/>
                    </StackPanel>
                </StackPanel>
            </Border>
            
            <!-- Saldo Cliente -->
            <Border Grid.Column="2" Background="#2D2D30" CornerRadius="5" Padding="15">
                <StackPanel>
                    <TextBlock Text="SALDO CLIENTE" FontSize="10" Foreground="#808080" FontWeight="Bold"/>
                    <TextBlock x:Name="lblSaldo" Text="$0.00" FontSize="16" Foreground="#FFD700" FontWeight="Bold" Margin="0,5,0,0"/>
                    <TextBlock Text="Para comprar" FontSize="9" Foreground="#666666" Margin="0,2,0,0"/>
                </StackPanel>
            </Border>
            
            <!-- Saldo Maquina -->
            <Border Grid.Column="4" Background="#2D2D30" CornerRadius="5" Padding="15">
                <StackPanel>
                    <TextBlock Text="SALDO MAQUINA" FontSize="10" Foreground="#808080" FontWeight="Bold"/>
                    <TextBlock x:Name="lblInventario" Text="$0.00" FontSize="16" Foreground="#7DD3FC" FontWeight="Bold" Margin="0,5,0,0"/>
                    <TextBlock Text="Solo carga operador" FontSize="9" Foreground="#666666" Margin="0,2,0,0"/>
                </StackPanel>
            </Border>
        </Grid>
        
        <!-- MODO INSERCION -->
        <Border x:Name="borderModoInsercion" Grid.Row="2" Background="#2D2D30" CornerRadius="5" Padding="12" Margin="0,0,0,15" BorderBrush="#3F3F46" BorderThickness="2">
            <StackPanel>
                <TextBlock x:Name="lblModoInsercion" Text="MODO CLIENTE: monedas insertadas cuentan para comprar" FontSize="11" Foreground="#FFD700" FontWeight="Bold" Margin="0,0,0,8" TextWrapping="Wrap"/>
                <Button x:Name="btnCargaMaquina" Content="[+] CARGAR MAQUINA (solo inventario / cambio)" Height="42" FontSize="12" FontWeight="Bold" Background="#2980B9" Foreground="White" BorderThickness="0" Cursor="Hand"/>
            </StackPanel>
        </Border>
        
        <!-- DESGLOSE -->
        <Border Grid.Row="3" Background="#2D2D30" CornerRadius="5" Padding="15" Margin="0,0,0,15">
            <StackPanel>
                <TextBlock Text="DESGLOSE DE MONEDAS" FontSize="10" Foreground="#808080" FontWeight="Bold"/>
                <TextBlock x:Name="lblDesglose" Text="Sin datos" FontSize="14" Foreground="#FFFFFF" Margin="0,5,0,0"/>
            </StackPanel>
        </Border>
        
        <!-- LOGS -->
        <Border Grid.Row="4" Background="#2D2D30" CornerRadius="5" Padding="15" Margin="0,0,0,15">
            <Grid>
                <Grid.RowDefinitions>
                    <RowDefinition Height="Auto"/>
                    <RowDefinition Height="*"/>
                </Grid.RowDefinitions>
                
                <TextBlock Grid.Row="0" Text="LOGS EN TIEMPO REAL" FontSize="12" Foreground="#FFD700" FontWeight="Bold" Margin="0,0,0,10"/>
                
                <Border Grid.Row="1" Background="#1E1E1E" CornerRadius="3" BorderBrush="#3F3F46" BorderThickness="1">
                    <TextBox x:Name="logBox" 
                             FontFamily="Consolas" 
                             FontSize="11" 
                             Foreground="#CCCCCC" 
                             Background="Transparent" 
                             BorderThickness="0" 
                             IsReadOnly="True" 
                             VerticalScrollBarVisibility="Auto"
                             TextWrapping="Wrap"
                             Padding="10"/>
                </Border>
            </Grid>
        </Border>
        
        <!-- CONTROLS -->
        <Grid Grid.Row="5">
            <Grid.RowDefinitions>
                <RowDefinition Height="Auto"/>
                <RowDefinition Height="10"/>
                <RowDefinition Height="Auto"/>
            </Grid.RowDefinitions>
            
            <!-- Fila superior: Controles principales -->
            <Grid Grid.Row="0">
                <Grid.ColumnDefinitions>
                    <ColumnDefinition Width="*"/>
                    <ColumnDefinition Width="10"/>
                    <ColumnDefinition Width="*"/>
                    <ColumnDefinition Width="10"/>
                    <ColumnDefinition Width="*"/>
                    <ColumnDefinition Width="10"/>
                    <ColumnDefinition Width="*"/>
                    <ColumnDefinition Width="10"/>
                    <ColumnDefinition Width="*"/>
                    <ColumnDefinition Width="10"/>
                    <ColumnDefinition Width="Auto"/>
                </Grid.ColumnDefinitions>
                
                <Button x:Name="btnStart" Content="[>] INICIAR" Grid.Column="0" Height="40" FontSize="12" FontWeight="Bold" Background="#1DB954" Foreground="White" BorderThickness="0" Cursor="Hand"/>
                <Button x:Name="btnStop" Content="[X] DETENER" Grid.Column="2" Height="40" FontSize="12" FontWeight="Bold" Background="#E74C3C" Foreground="White" BorderThickness="0" Cursor="Hand" IsEnabled="False"/>
                <Button x:Name="btnKillAll" Content="[**] MATAR TODO" Grid.Column="4" Height="40" FontSize="12" FontWeight="Bold" Background="#C0392B" Foreground="White" BorderThickness="0" Cursor="Hand"/>
                <Button x:Name="btnClear" Content="[!] LIMPIAR" Grid.Column="6" Height="40" FontSize="12" FontWeight="Bold" Background="#3498DB" Foreground="White" BorderThickness="0" Cursor="Hand"/>
                <Button x:Name="btnReset" Content="[!] RESET" Grid.Column="8" Height="40" FontSize="12" FontWeight="Bold" Background="#E67E22" Foreground="White" BorderThickness="0" Cursor="Hand"/>
                <Button x:Name="btnMinimize" Content="[-] MIN" Grid.Column="10" Height="40" Width="100" FontSize="12" FontWeight="Bold" Background="#9B59B6" Foreground="White" BorderThickness="0" Cursor="Hand"/>
            </Grid>
            
            <!-- Fila inferior: Config servidor y limpieza serial -->
            <Grid Grid.Row="2">
                <Grid.RowDefinitions>
                    <RowDefinition Height="35"/>
                    <RowDefinition Height="5"/>
                    <RowDefinition Height="35"/>
                </Grid.RowDefinitions>
                
                <Button x:Name="btnChangeServer" Content="[CFG] CAMBIAR SERVIDOR PHP (Laragon/XAMPP)" Grid.Row="0" FontSize="11" FontWeight="Bold" Background="#34495E" Foreground="White" BorderThickness="0" Cursor="Hand"/>
                <Button x:Name="btnCleanSerial" Content="[COM] LIMPIAR PROCESOS SERIALES (Cierra TODO)" Grid.Row="2" FontSize="11" FontWeight="Bold" Background="#C0392B" Foreground="White" BorderThickness="0" Cursor="Hand"/>
            </Grid>
        </Grid>
    </Grid>
</Window>
"@
    Write-StartupLog "XAML parseado exitosamente"
} catch {
    Write-StartupLog "ERROR al parsear XAML: $_" -IsError
    throw
}

# Cargar XAML
Write-StartupLog "Cargando ventana WPF..."
try {
    $reader = New-Object System.Xml.XmlNodeReader $xaml
    $window = [Windows.Markup.XamlReader]::Load($reader)
    Write-StartupLog "Ventana WPF cargada exitosamente"
} catch {
    Write-StartupLog "ERROR al cargar ventana WPF: $_" -IsError
    Write-StartupLog "Stack Trace: $($_.ScriptStackTrace)" -IsError
    throw
}

# Referencias a controles
Write-StartupLog "Obteniendo referencias a controles..."
try {
    $logBox = $window.FindName("logBox")
    $statusIndicator = $window.FindName("statusIndicator")
    $statusText = $window.FindName("statusText")
    $lblSaldo = $window.FindName("lblSaldo")
    $lblInventario = $window.FindName("lblInventario")
    $lblDesglose = $window.FindName("lblDesglose")
    $btnStart = $window.FindName("btnStart")
    $btnStop = $window.FindName("btnStop")
    $btnKillAll = $window.FindName("btnKillAll")
    $btnClear = $window.FindName("btnClear")
    $btnReset = $window.FindName("btnReset")
    $btnMinimize = $window.FindName("btnMinimize")
    $btnChangeServer = $window.FindName("btnChangeServer")
    $btnCleanSerial = $window.FindName("btnCleanSerial")
    $btnCargaMaquina = $window.FindName("btnCargaMaquina")
    $lblModoInsercion = $window.FindName("lblModoInsercion")
    $borderModoInsercion = $window.FindName("borderModoInsercion")
    Write-StartupLog "Controles obtenidos exitosamente"
} catch {
    Write-StartupLog "ERROR al obtener controles: $_" -IsError
    throw
}

# Event Handlers
Write-StartupLog "Configurando event handlers..."
try {
    $btnStart.Add_Click({
        try {
            Write-StartupLog "[BTN] Boton INICIAR presionado"
            Write-Host "`n[BTN] INICIAR presionado - iniciando listener..." -ForegroundColor Cyan
            
            # Forzar que cualquier error se convierta en exception
            $ErrorActionPreference = "Stop"
            
            Start-ListenerProcess
            
            Write-StartupLog "[BTN] Start-ListenerProcess completado"
            Write-Host "[BTN] Listener iniciado correctamente" -ForegroundColor Green
            
        } catch {
            Write-StartupLog "[BTN ERROR] Error al iniciar listener: $_" -IsError
            Write-StartupLog "[BTN ERROR] Stack: $($_.ScriptStackTrace)" -IsError
            Write-StartupLog "[BTN ERROR] Exception: $($_.Exception | Out-String)" -IsError
            
            # Mostrar en consola
            Write-Host "`n========================================" -ForegroundColor Red
            Write-Host "ERROR AL INICIAR LISTENER" -ForegroundColor Red
            Write-Host "========================================" -ForegroundColor Red
            Write-Host "Error: $_" -ForegroundColor Yellow
            Write-Host "`nStack Trace:" -ForegroundColor Yellow
            Write-Host $_.ScriptStackTrace -ForegroundColor Gray
            Write-Host "========================================`n" -ForegroundColor Red
            
            Add-LogMessage "[ERROR] Fallo al iniciar: $_"
            Add-LogMessage "[ERROR] Ver log completo para detalles"
            
            [System.Windows.MessageBox]::Show(
                "Error al iniciar el listener:`n`n$_`n`n" +
                "Revisa la consola de PowerShell y el log para mas detalles.",
                "Error",
                [System.Windows.MessageBoxButton]::OK,
                [System.Windows.MessageBoxImage]::Error
            ) | Out-Null
        } finally {
            $ErrorActionPreference = "Continue"
        }
    })

    $btnStop.Add_Click({
        try {
            Write-StartupLog "[BTN] Boton DETENER presionado"
            Stop-ListenerProcess
        } catch {
            Write-StartupLog "[BTN ERROR] Error al detener listener: $_" -IsError
            Add-LogMessage "[ERROR] Fallo al detener: $_"
        }
    })

$btnKillAll.Add_Click({
    Kill-AllPhpListeners
})

$btnClear.Add_Click({
    $logBox.Clear()
    Add-LogMessage "Logs de pantalla limpiados"
})

$btnReset.Add_Click({
    Reset-SystemLogs
})

$btnMinimize.Add_Click({
    Hide-Window
})

$btnChangeServer.Add_Click({
    Change-PhpServer
})

$btnCargaMaquina.Add_Click({
    if ($script:CargaMaquinaActiva) {
        $confirm = [System.Windows.MessageBox]::Show(
            "¿Terminar carga de maquina y volver a MODO CLIENTE?`n`n" +
            "A partir de ahora las monedas insertadas sumaran al saldo del cliente.",
            "Fin de carga",
            [System.Windows.MessageBoxButton]::YesNo,
            [System.Windows.MessageBoxImage]::Question
        )
        if ($confirm -eq [System.Windows.MessageBoxResult]::Yes) {
            Set-CargaMaquinaMode -Activa $false
        }
    } else {
        $confirm = [System.Windows.MessageBox]::Show(
            "MODO CARGA MAQUINA`n`n" +
            "Las MONEDAS que insertes iran SOLO al inventario (cambio).`n" +
            "Los BILLETES se ignoran (no podemos dar cambio en billetes).`n" +
            "NO se sumaran al saldo del cliente.`n`n" +
            "Usa esto para llenar la maquina con cambio.`n`n" +
            "¿Activar?",
            "Cargar maquina",
            [System.Windows.MessageBoxButton]::YesNo,
            [System.Windows.MessageBoxImage]::Information
        )
        if ($confirm -eq [System.Windows.MessageBoxResult]::Yes) {
            Set-CargaMaquinaMode -Activa $true
        }
    }
})

$btnCleanSerial.Add_Click({
    $result = [System.Windows.MessageBox]::Show(
        "Esto cerrará TODOS los programas que puedan estar usando puertos seriales:`n`n" +
        "- Hercules`n- PuTTY`n- Arduino IDE`n- PHP processes`n- Otros terminales seriales`n`n" +
        "¿Continuar?",
        "Limpiar Procesos Seriales",
        [System.Windows.MessageBoxButton]::YesNo,
        [System.Windows.MessageBoxImage]::Warning
    )
    
    if ($result -eq [System.Windows.MessageBoxResult]::Yes) {
        $closed = Force-ReleaseCOMPort
        if ($closed -gt 0) {
            [System.Windows.MessageBox]::Show(
                "Se cerraron $closed proceso(s).`n`n" +
                "Revisa los logs para detalles.",
                "Limpieza Completada",
                [System.Windows.MessageBoxButton]::OK,
                [System.Windows.MessageBoxImage]::Information
            ) | Out-Null
        }
    }
})

# Evento de cerrar ventana
$window.Add_Closing({
    param($sender, $e)
    
    $result = [System.Windows.MessageBox]::Show(
        "Desea cerrar completamente la aplicacion o minimizar a la bandeja?`n`nSI = Cerrar completamente`nNO = Minimizar a bandeja`nCANCELAR = No hacer nada",
        "Confirmar Accion",
        [System.Windows.MessageBoxButton]::YesNoCancel,
        [System.Windows.MessageBoxImage]::Question
    )
    
    if ($result -eq [System.Windows.MessageBoxResult]::Yes) {
        # Cerrar completamente
        Stop-ListenerProcess
        
        Clear-RunningState
        Cleanup-TrayIcon
        # Liberar el mutex para permitir nuevas instancias
        if ($script:AppMutex) {
            $script:AppMutex.ReleaseMutex()
            $script:AppMutex.Dispose()
        }
    } elseif ($result -eq [System.Windows.MessageBoxResult]::No) {
        # Minimizar a bandeja
        $e.Cancel = $true
        Hide-Window
    } else {
        # Cancelar
        $e.Cancel = $true
    }
})
    Write-StartupLog "Evento de cierre configurado"
} catch {
    Write-StartupLog "ERROR al configurar evento de cierre: $_" -IsError
    throw
}

# Timer para actualizar stats cada 2 segundos
Write-StartupLog "Configurando timer de actualizacion..."
try {
    $timer = New-Object System.Windows.Threading.DispatcherTimer
    $timer.Interval = [TimeSpan]::FromSeconds(2)
    $timer.Add_Tick({
        Sync-CargaMaquinaFromFile
        Update-Stats
        
        # Leer log de PHP y mostrarlo en el logBox
        try {
            if (Test-Path $script:LogFilePath) {
                $fileStream = [System.IO.FileStream]::new(
                    $script:LogFilePath,
                    [System.IO.FileMode]::Open,
                    [System.IO.FileAccess]::Read,
                    [System.IO.FileShare]::ReadWrite
                )
                $fileLen = $fileStream.Length
                if ($fileLen -gt $script:LogFilePosition) {
                    $fileStream.Seek($script:LogFilePosition, [System.IO.SeekOrigin]::Begin) | Out-Null
                    $reader = [System.IO.StreamReader]::new($fileStream)
                    $newContent = $reader.ReadToEnd()
                    $script:LogFilePosition = $fileStream.Position
                    $reader.Close()
                    if ($newContent) {
                        $lineas = $newContent -split "`n"
                        foreach ($linea in $lineas) {
                            $linea = $linea.Trim()
                            if ($linea) {
                                $logBox.Dispatcher.Invoke([action]{
                                    $logBox.AppendText("[PHP] $linea`n")
                                    $logBox.ScrollToEnd()
                                })
                            }
                        }
                    }
                } else {
                    $fileStream.Close()
                }
                try { $fileStream.Close() } catch {}
            }
        } catch { }
        
        # Verificar si el listener sigue corriendo
        if ($script:ListenerProcess -and $script:ListenerProcess.HasExited) {
            Add-LogMessage "El listener se detuvo inesperadamente"
            Update-StatusIndicator $false
            $script:ListenerProcess = $null
        }
    })
    $timer.Start()
    Write-StartupLog "Timer configurado y iniciado"
} catch {
    Write-StartupLog "ERROR al configurar timer: $_" -IsError
    throw
}

# Crear icono de bandeja ANTES de mostrar ventana
Write-StartupLog "Creando icono de bandeja del sistema..."
try {
    Create-NotifyIcon
    Write-StartupLog "Icono de bandeja creado exitosamente"
} catch {
    Write-StartupLog "ERROR al crear icono de bandeja: $_" -IsError
    # No lanzar error, el programa puede funcionar sin el icono
}

# Timer para verificar que el icono siga visible cada 5 segundos
Write-StartupLog "Configurando timer de verificacion de icono..."
try {
    $script:IconCheckTimer = New-Object System.Windows.Threading.DispatcherTimer
    $script:IconCheckTimer.Interval = [TimeSpan]::FromSeconds(5)
    $script:IconCheckTimer.Add_Tick({
        Ensure-TrayIconVisible
    })
    $script:IconCheckTimer.Start()
    Write-StartupLog "Timer de verificacion configurado"
} catch {
    Write-StartupLog "ERROR al configurar timer de icono: $_" -IsError
    # No lanzar error, no es crítico
}

# Guardar estado de ejecucion
Write-StartupLog "Guardando estado de ejecucion..."
try {
    Save-RunningState
    Write-StartupLog "Estado guardado"
} catch {
    Write-StartupLog "WARN: No se pudo guardar estado: $_"
}

# Cargar preferencia de servidor
Write-StartupLog "Cargando preferencias..."
try {
    Load-ServerPreference
    Write-StartupLog "Preferencias cargadas"
} catch {
    Write-StartupLog "WARN: Error cargando preferencias: $_"
}

# Mensaje inicial
Add-LogMessage "=========================================="
Add-LogMessage "Monedero Monitor iniciado"
Add-LogMessage "Instancia unica - Solo puede correr una vez"
Add-LogMessage "Directorio: $script:ScriptDirectory"

Add-LogMessage "Icono de bandeja: ACTIVO"

# Mostrar servidores detectados
$detectedServers = Detect-AvailableServers
if ($detectedServers.Count -gt 0) {
    Add-LogMessage "Servidores PHP detectados:"
    foreach ($server in $detectedServers.Keys) {
        $indicator = if ($server -eq $script:PhpServerPreference) { " [SELECCIONADO]" } else { "" }
        Add-LogMessage "  - $server$indicator"
    }
} else {
    Add-LogMessage "ADVERTENCIA: No se detectaron servidores PHP"
}

Add-LogMessage "=========================================="
Add-LogMessage "Presiona INICIAR para arrancar el listener"
Add-LogMessage ""

# Sincronizar modo de insercion y stats iniciales
Sync-CargaMaquinaFromFile -ForceUI
Update-Stats

# Actualizar tooltip inicial
Update-TrayTooltip $false

Write-StartupLog "=========================================="
Write-StartupLog "INICIALIZACION COMPLETADA EXITOSAMENTE"
Write-StartupLog "=========================================="
Write-StartupLog "Mostrando ventana principal..."

# Mostrar ventana
try {
    $window.ShowDialog() | Out-Null
} catch {
    Write-StartupLog "ERROR al mostrar ventana: $_" -IsError
    throw
}

Write-StartupLog "Aplicacion cerrada normalmente"
