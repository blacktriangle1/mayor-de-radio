@echo off
title Tunel - Libro de Guardia
color 0A
echo ============================================
echo   LIBRO DE GUARDIA - TUNEL PUBLICO
echo ============================================
echo.
echo Tu URL publica aparecera abajo.
echo Comparte esa URL para que cualquiera pueda ver el Libro de Guardia.
echo.
echo IMPORTANTE: Manten esta ventana abierta.
echo Para cerrar el tunel, presiona Ctrl+C
echo.
echo ============================================
echo.
ssh -o StrictHostKeyChecking=no -R 80:localhost:80 nokey@localhost.run
pause
