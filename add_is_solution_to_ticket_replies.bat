@echo off
echo ticket_replies tablosuna is_solution sütunu ekleniyor...
"C:\xampp\mysql\bin\mysql" -u root -p live_support_chat < add_is_solution_to_ticket_replies.sql
echo Tamamlandı.
pause 