@echo off
echo support_tickets tablosuna file_path sütunu ekleniyor...
"C:\xampp\mysql\bin\mysql" -u root -p live_support_chat < add_file_path_to_support_tickets.sql
echo Tamamlandı.
pause 