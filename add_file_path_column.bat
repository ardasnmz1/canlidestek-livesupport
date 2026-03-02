@echo off
echo ticket_replies tablosuna file_path sütunu ekleniyor...
"C:\xampp\mysql\bin\mysql" -u root -p live_support_chat < add_file_path.sql
echo Tamamlandı.
pause 