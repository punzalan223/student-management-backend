<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Student;

class StudentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $students = Student::paginate(10);
        return response()->json($students);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'student_number' => 'required|unique:students',
            'first_name' => 'required',
            'last_name' => 'required',
            'grade_level' => 'required',
            'email' => 'required|email|unique:students',
            'status' => 'required|in:Active,Inactive',
        ]);

        $student = Student::create($request->all());

        return response()->json($student, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $student = Student::findOrFail($id); // ✅ retrieve student
        return response()->json($student);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $student = Student::findOrFail($id); // ✅ retrieve student

        $request->validate([
            'student_number' => 'required|unique:students,student_number,' . $student->id,
            'first_name' => 'required',
            'last_name' => 'required',
            'grade_level' => 'required',
            'email' => 'required|email|unique:students,email,' . $student->id,
            'status' => 'required|in:Active,Inactive',
        ]);

        $student->update($request->all());

        return response()->json($student);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $student = Student::findOrFail($id); // ✅ retrieve student
        $student->delete();

        return response()->json(['message' => 'Student deleted successfully']);
    }
}
