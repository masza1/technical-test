<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\User;
use Faker\Factory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CustumerTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Factory::create();
        $users = [];
        $customers = [];
        $bank_name = ['BCA', 'BRI', 'BNI', 'MANDIRI', 'BTPN', 'BTN', 'PANIN BANK', 'PERMATA BANK'];
        for ($i = 0; $i < 15; $i++) {
            array_push($users, [
                'email' => $faker->unique()->safeEmail(),
                'password' => Hash::make('Password1!'),
                'email_verified_at' => now(),
                'remember_token' => \Str::random(10),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        User::insert($users);
        $users = User::all()->pluck('id')->toArray();
        foreach ($users as $key => $value) {
            array_push($customers, [
                'user_id' => $value,
                'firstname' => $faker->firstName(),
                'lastname' => $faker->lastName(),
                'bank_name' => $bank_name[array_rand($bank_name)],
                'rekening' => rand(00000001,99999999),
                'saldo' => rand(0000001,9999999),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        Customer::insert($customers);
    }
}
