@echo off
echo likes tablosu oluşturuluyor...
"C:\xampp\mysql\bin\mysql" -u root -p live_support_chat < create_likes_table.sql
echo Tamamlandı.
pause 