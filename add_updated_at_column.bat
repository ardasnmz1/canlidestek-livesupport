@echo off
echo messages tablosuna updated_at sütunu ekleniyor...
"C:\xampp\mysql\bin\mysql" -u root -p live_support_chat < add_updated_at.sql
echo Tamamlandı.
pause 