<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class SubjectController extends Controller
{
    public function index(Request $request)
    {
        $query = Subject::query();

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('name_khmer', 'like', "%{$search}%")
                    ->orWhere('subject_code', 'like', "%{$search}%");
            });
        }

        $subjects = $query->latest()->paginate(15)->withQueryString();

        return Inertia::render('Subjects/Index', [
            'subjects' => $subjects,
            'filters' => $request->only(['search']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Subjects/Create');
    }

    public function store(Request $request)
    {
        $validated = $this->validateData($request);

        DB::beginTransaction();

        try {
            Subject::create($validated);
            DB::commit();

            return redirect()->route('subjects.index')
                ->with('success', 'Subject created successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    public function show(Subject $subject)
    {
        $subject->loadCount(['students', 'teachers']);

        return Inertia::render('Subjects/Show', [
            'subject' => $subject,
        ]);
    }

    public function edit(Subject $subject)
    {
        return Inertia::render('Subjects/Edit', [
            'subject' => $subject,
        ]);
    }

    public function update(Request $request, Subject $subject)
    {
        $validated = $this->validateData($request);

        DB::beginTransaction();

        try {
            $subject->update($validated);
            DB::commit();

            return redirect()->route('subjects.show', $subject->uuid)
                ->with('success', 'Subject updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    public function destroy(Subject $subject)
    {
        DB::beginTransaction();

        try {
            $subject->delete();
            DB::commit();

            return redirect()->route('subjects.index')
                ->with('success', 'Subject deleted successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'name_khmer' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'grade_level' => ['required', 'integer', 'min:1', 'max:12'],
            'subject_type' => ['required', 'string', 'in:core,elective,extra'],
            'credits' => ['nullable', 'integer', 'min:1'],
            'hours_per_week' => ['nullable', 'integer', 'min:1'],
            'fee' => ['nullable', 'numeric', 'min:0'],
            'monthly_fee' => ['nullable', 'numeric', 'min:0'],
            'syllabus' => ['nullable', 'string'],
            'prerequisites' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }
}
