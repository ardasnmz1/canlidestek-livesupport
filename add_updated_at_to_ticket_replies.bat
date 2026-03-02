@echo off
echo ticket_replies tablosuna updated_at sütunu ekleniyor...
"C:\xampp\mysql\bin\mysql" -u root -p live_support_chat < add_updated_at_to_ticket_replies.sql
echo Tamamlandı.
pause 