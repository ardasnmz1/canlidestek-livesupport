@echo off
echo Veritabani kuruluyor...

REM XAMPP MySQL yolunu belirle
set MYSQL="C:\xampp\mysql\bin\mysql.exe"
set MYSQL_USER=root
set MYSQL_PASS=

echo Database tanitiliyor...
%MYSQL% -u %MYSQL_USER% < create_users_table.sql
if %errorlevel% neq 0 (
  echo create_users_table.sql yukleme hatasi!
  pause
  exit /b %errorlevel%
)

echo Messages tablosu olusturuluyor...
%MYSQL% -u %MYSQL_USER% < create_messages_table.sql
if %errorlevel% neq 0 (
  echo create_messages_table.sql yukleme hatasi!
  pause
  exit /b %errorlevel%
)

echo Demo kullanicilar olusturuluyor...
%MYSQL% -u %MYSQL_USER% < create_demo_users.sql
if %errorlevel% neq 0 (
  echo create_demo_users.sql yukleme hatasi!
  pause
  exit /b %errorlevel%
)

echo Veritabani kurulumu tamamlandi!
echo Kullanici bilgileri:
echo   Kullanici adi: admin@example.com, Sifre: password123
echo   Kullanici adi: teacher1@example.com, Sifre: password123
echo   Kullanici adi: student1@example.com, Sifre: password123
echo   Kullanici adi: student2@example.com, Sifre: password123

pause 