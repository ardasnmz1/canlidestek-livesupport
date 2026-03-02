@echo off
echo messages tablosuna is_solution sütunu ekleniyor...
"C:\xampp\mysql\bin\mysql" -u root -p live_support_chat < add_is_solution.sql
echo Tamamlandı.
pause 