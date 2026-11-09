<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskCompletion;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    // Daftar tugas: guru melihat semua, siswa hanya tugas kelasnya
    public function index(Request $request)
    {
        $request->validate([
            'search'   => ['nullable', 'string', 'max:100'],
            'status'   => ['nullable', 'in:done,pending'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $user = $request->user();
        $query = Task::with('classroom:id,name')->orderBy('due_date');

        if (! $user->isTeacher()) {
            $query->where('class_id', $user->class_id);
        }

        if ($request->filled('search')) {
            $query->where('title', 'like', '%'.$request->search.'%');
        }

        // Filter status hanya bermakna untuk siswa
        if (! $user->isTeacher() && $request->filled('status')) {
            $done = fn ($q) => $q->where('user_id', $user->id);
            $request->status === 'done'
                ? $query->whereHas('completions', $done)
                : $query->whereDoesntHave('completions', $done);
        }

        $tasks = $query->paginate($request->integer('per_page', 10));

        if (! $user->isTeacher()) {
            $doneIds = TaskCompletion::where('user_id', $user->id)
                ->whereIn('task_id', $tasks->pluck('id'))
                ->pluck('task_id')->all();
            $tasks->getCollection()->transform(function ($t) use ($doneIds) {
                $t->is_done = in_array($t->id, $doneIds);
                return $t;
            });
        }

        return response()->json($tasks);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'due_date'    => ['required', 'date'],
            'class_id'    => ['required', 'exists:classes,id'],
        ]);

        $task = Task::create($data + ['created_by' => $request->user()->id]);

        return response()->json(['data' => $task], 201);
    }

    public function update(Request $request, Task $task)
    {
        $data = $request->validate([
            'title'       => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'due_date'    => ['sometimes', 'required', 'date'],
            'class_id'    => ['sometimes', 'required', 'exists:classes,id'],
        ]);

        $task->update($data);

        return response()->json(['data' => $task]);
    }

    public function destroy(Task $task)
    {
        $task->delete();

        return response()->json(null, 204);
    }

    // Siswa menandai selesai (idempotent: dua kali klik tidak membuat duplikat)
    public function complete(Request $request, Task $task)
    {
        $user = $request->user();

        if ($task->class_id !== $user->class_id) {
            return response()->json(['message' => 'Tugas bukan untuk kelas Anda.'], 403);
        }

        TaskCompletion::firstOrCreate(
            ['task_id' => $task->id, 'user_id' => $user->id],
            ['completed_at' => now()]
        );

        return response()->json(['message' => 'Tugas ditandai selesai.']);
    }
}