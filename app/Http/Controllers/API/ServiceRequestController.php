<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Models\ServiceRequest;
use App\Http\Controllers\Controller;
use App\Models\ImportLog;

class ServiceRequestController extends Controller
{
    /**
     * List all service requests with optional filtering by status and date range
     */
    public function index(Request $request)
    {
        $query = ServiceRequest::with('student');

        // Filter by status if provided
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range if provided
        if ($request->has('from') && $request->has('to')) {
            $query->whereBetween('date_requested', [$request->from, $request->to]);
        }

        // Paginate results
        $serviceRequests = $query->paginate(10);

        // Transform to include student info
        $serviceRequests->getCollection()->transform(function ($request) {
            return [
                'id' => $request->id,
                'student_id' => $request->student_id,
                'student' => [
                    'id' => $request->student->id ?? null,
                    'first_name' => $request->student->first_name ?? null,
                    'last_name' => $request->student->last_name ?? null,
                ],
                'service_type' => $request->service_type,
                'date_requested' => $request->date_requested,
                'status' => $request->status,
                'remarks' => $request->remarks,
            ];
        });

        return response()->json($serviceRequests);
    }

    /**
     * Create a new service request
     */
    public function store(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'service_type' => 'required|in:ID Replacement,Good Moral Certificate,Form 137',
            'date_requested' => 'required|date',
            'remarks' => 'nullable|string',
        ]);

        $serviceRequest = ServiceRequest::create([
            'student_id' => $request->student_id,
            'service_type' => $request->service_type,
            'date_requested' => $request->date_requested,
            'status' => 'Pending',
            'remarks' => $request->remarks,
        ]);

        $serviceRequest->load('student');

        return response()->json($serviceRequest, 201);
    }

    /**
     * Update a service request
     * Staff can only approve/reject and add remarks
     */
    public function update(Request $request, ServiceRequest $serviceRequest)
    {
        $userRole = auth()->user()->role;

        if ($userRole === 'staff') {
            $request->validate([
                'status' => 'required|in:Pending,Approved,Rejected',
                'remarks' => 'nullable|string',
            ]);

            // Staff can only update status and remarks
            $serviceRequest->update($request->only(['status', 'remarks']));
        } else {
            // Admin or other roles can update all fields (if needed)
            $serviceRequest->update($request->all());
        }

        $serviceRequest->load('student');

        return response()->json($serviceRequest);
    }

    /**
     * Delete a service request
     * Only admins can delete
     */
    public function destroy(ServiceRequest $serviceRequest)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $serviceRequest->delete();

        return response()->json(['message' => 'Service request deleted successfully']);
    }

    public function lastImportSummary($userId)
    {
        $log = ImportLog::where('user_id', $userId)->latest()->first();
        if (!$log) return response()->json(['summary' => null]);

        return response()->json(['summary' => $log->summary_json]);
    }

}
