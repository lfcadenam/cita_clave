# ==============================================================================
# Script Local de Publicación a Git para Despliegue en Producción
# Uso en PowerShell: .\push-to-prod.ps1 "Tu mensaje opcional de commit"
# ==============================================================================
param(
    [string]$CommitMessage = "perf(calendar): optimizar consultas n+1, cache en memoria, indices e implementar lazy loading"
)

Write-Host "======================================================================" -ForegroundColor Cyan
Write-Host "       🚀 SUBIENDO CAMBIOS LOCALES AL REPOSITORIO CENTRAL            " -ForegroundColor Green
Write-Host "======================================================================" -ForegroundColor Cyan

# 1. Verificar cambios
Write-Host "`n[1/3] Verificando archivos modificados..." -ForegroundColor Yellow
git status --short

# 2. Agregar archivos
Write-Host "`n[2/3] Preparando cambios para commit..." -ForegroundColor Yellow
git add .
git commit -m "$CommitMessage"

# 3. Subir a main
Write-Host "`n[3/3] Enviando cambios a origin/main..." -ForegroundColor Yellow
git push origin main

Write-Host "`n✨ Cambios subidos exitosamente a origin/main." -ForegroundColor Green
Write-Host "Ahora en tu servidor VPS solo ejecuta: ./deploy.sh`n" -ForegroundColor Cyan
