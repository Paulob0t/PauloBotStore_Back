Set WshShell = CreateObject("WScript.Shell")
Set objFSO = CreateObject("Scripting.FileSystemObject")

' Obtener la ruta del script actual
strScriptPath = objFSO.GetParentFolderName(WScript.ScriptFullName)

' Buscar PHP
strPHPPath = ""

' Intentar con XAMPP primero
If objFSO.FileExists("C:\xampp\php\php.exe") Then
    strPHPPath = "C:\xampp\php\php.exe"
Else
    ' Buscar en PATH
    strPHPPath = "php.exe"
End If

' Construir el comando
strCommand = """" & strPHPPath & """ """ & strScriptPath & "\com5_manager.php"""

' Ejecutar en background sin ventana (WindowStyle 0 = oculto)
WshShell.Run strCommand, 0, False

' Mensaje opcional (comentar esta línea si no quieres ver el mensaje)
' MsgBox "Listener del monedero iniciado en background", vbInformation, "Monedero"
