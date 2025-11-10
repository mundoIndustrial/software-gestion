@echo off
php artisan importar:todo-excel --dry-run > test-output.txt 2>&1
type test-output.txt
pause
