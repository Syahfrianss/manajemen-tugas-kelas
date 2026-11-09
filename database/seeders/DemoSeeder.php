<?php

namespace Database\Seeders;

use App\Models\Classroom;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $teacher = User::create([
            'name' => 'Bu Guru', 'email' => 'guru@test.com',
            'password' => 'password123', 'role' => 'teacher',
        ]);

        $classA = Classroom::create(['name' => 'Kelas 10A', 'teacher_id' => $teacher->id]);
        $classB = Classroom::create(['name' => 'Kelas 10B', 'teacher_id' => $teacher->id]);

        User::create([
            'name' => 'Budi', 'email' => 'budi@test.com',
            'password' => 'password123', 'role' => 'student', 'class_id' => $classA->id,
        ]);
        User::create([
            'name' => 'Siti', 'email' => 'siti@test.com',
            'password' => 'password123', 'role' => 'student', 'class_id' => $classB->id,
        ]);

        foreach (['Matematika Bab 1', 'Esai Bahasa Indonesia', 'Laporan Praktikum IPA'] as $i => $title) {
            Task::create([
                'class_id' => $classA->id, 'created_by' => $teacher->id,
                'title' => $title, 'description' => 'Kerjakan dan kumpulkan tepat waktu.',
                'due_date' => now()->addDays($i + 2),
            ]);
        }
    }
}