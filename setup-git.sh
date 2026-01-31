#!/bin/bash
# Configuración Git para este proyecto
echo "Configurando Git para proyecto mundoindustrial..."

git config pull.rebase true
git config branch.autosetuprebase always

echo "✅ Configuración completada"
echo "Pull usará rebase en lugar de merge"
