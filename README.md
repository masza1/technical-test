<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400"></a></p>

<p align="center">
<a href="https://travis-ci.org/laravel/framework"><img src="https://travis-ci.org/laravel/framework.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## Tentang Project Technical Test API

Project ini untuk soal soal technical test, yang terdapat fitur:
- Test Logika Kontainer.
- Authentication JWT 
- Transaksi banking/ewallet


## Tata Cara penggunaan
 - jika menggunakan XAMPP clone repository ini didalam folder htdocs
 - copy dan paste file .env.example, lalu ubah menjadi .env, lalu setting database sesuai database Anda.
 - ketikkan perintah `composer update` atau `composer install`
 - ketikkan perintah `php artisan key:generate`
 - ketikkan perintah `php artisan migrate --seed` tekan enter, perintah ini untuk membuat table dari database dan generate user dari seeder
 - jika menggunakan XAMPP akses [http://localhost/nama_folder/api/documentation](http://localhost/nama_folder/api/documentation) untuk melihat dokumentasi API
