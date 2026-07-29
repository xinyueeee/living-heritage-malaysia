<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Seed a handful of dummy tourists for local login testing, since real
     * accounts are normally only created via the Google/Supabase OAuth flow.
     *
     * public.users has a foreign key into Supabase Auth's auth.users, so each
     * dummy profile needs a matching auth.users row created first. These are
     * real rows in the shared Supabase project (not a local throwaway DB),
     * so emails use the reserved .test TLD to mark them clearly as fake.
     */
    public function run(): void
    {
        $dummyUsers = [
            [
                'name' => 'Ariana Chen',
                'email' => 'ariana.chen@example.test',
                'avatar' => 'https://i.pravatar.cc/150?u=ariana.chen',
                'bio' => 'Loves exploring Peranakan heritage and street food trails.',
            ],
            [
                'name' => 'Farid Hakim',
                'email' => 'farid.hakim@example.test',
                'avatar' => 'https://i.pravatar.cc/150?u=farid.hakim',
                'bio' => "Weekend explorer of Malaysia's historic townships.",
            ],
            [
                'name' => 'Priya Nair',
                'email' => 'priya.nair@example.test',
                'avatar' => 'https://i.pravatar.cc/150?u=priya.nair',
                'bio' => 'Documents cultural festivals across the peninsula.',
            ],
        ];

        foreach ($dummyUsers as $dummy) {
            $authUser = DB::table('auth.users')->where('email', $dummy['email'])->first();

            if ($authUser) {
                $id = $authUser->id;
            } else {
                $id = (string) Str::uuid();

                DB::table('auth.users')->insert([
                    'id' => $id,
                    'instance_id' => '00000000-0000-0000-0000-000000000000',
                    'aud' => 'authenticated',
                    'role' => 'authenticated',
                    'email' => $dummy['email'],
                    'encrypted_password' => '',
                    'email_confirmed_at' => now(),
                    'raw_app_meta_data' => json_encode(['provider' => 'google', 'providers' => ['google']]),
                    'raw_user_meta_data' => json_encode([
                        'full_name' => $dummy['name'],
                        'avatar_url' => $dummy['avatar'],
                        'email' => $dummy['email'],
                    ]),
                    'is_sso_user' => false,
                    'is_anonymous' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            User::updateOrCreate(
                ['user_id' => $id],
                [
                    'user_name' => $dummy['name'],
                    'user_email' => $dummy['email'],
                    'profile_photo' => $dummy['avatar'],
                    'bio' => $dummy['bio'],
                ]
            );
        }
    }
}
