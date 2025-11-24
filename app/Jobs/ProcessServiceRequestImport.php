<?php

namespace App\Jobs;

use App\Models\Student;
use App\Models\ServiceRequest;
use App\Models\ImportLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ProcessServiceRequestImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $filePath;
    protected int $userId;

    public function __construct(string $filePath, int $userId)
    {
        // This is the relative path passed from the controller, e.g., 'imports/filename.csv'
        $this->filePath = $filePath;
        $this->userId = $userId;
    }

    public function handle()
    {
        // Use Storage facade to check and get the correct absolute path
        if (!Storage::disk('local')->exists($this->filePath)) {
            \Log::error("File not found in storage: {$this->filePath}");
            return;
        }

        $absolutePath = Storage::disk('local')->path($this->filePath);

        $rowsProcessed = 0;
        $successfulRequests = 0;
        $newStudents = 0;
        $skippedRows = [];

        // Get the data from the first sheet
        $data = Excel::toArray([], $absolutePath)[0] ?? [];

        $dateFormats = ['m/d/Y', 'Y-m-d', 'd-m-Y', 'm-d-Y', 'd/m/Y'];

        foreach ($data as $index => $row) {
            // FIX: Skip the header row (index 0) immediately.
            if ($index === 0) {
                continue;
            }

            // FIX: Only increment rowsProcessed for actual data rows
            $rowsProcessed++;

            // Use the correct array access syntax for potentially null values
            $studentNumber = trim($row[0] ?? '');
            $serviceTypeRaw = trim($row[1] ?? '');
            $requestedDateRaw = trim($row[2] ?? '');

            // Skip row if student number is missing
            if (!$studentNumber) {
                // FIX: Use the actual Excel row number ($index + 1) for reporting skipped rows
                $skippedRows[] = ['row' => $index + 1, 'reason' => 'Missing student number'];
                continue;
            }

            // Parse requested date
            $requestedDate = null;
            if ($requestedDateRaw) {
                try {
                    // Try automatic Carbon parsing first
                    $requestedDate = Carbon::parse($requestedDateRaw)->format('Y-m-d');
                } catch (\Exception $e) {
                    // Fallback to specific formats if automatic parse fails
                    foreach ($dateFormats as $format) {
                        $dt = \DateTime::createFromFormat($format, $requestedDateRaw);
                        if ($dt) {
                            $requestedDate = $dt->format('Y-m-d');
                            break;
                        }
                    }
                }
            }

            if (!$requestedDate) {
                // FIX: Use the actual Excel row number ($index + 1) for reporting skipped rows
                $skippedRows[] = ['row' => $index + 1, 'reason' => 'Invalid date format: ' . $requestedDateRaw];
                continue;
            }

            // Find or create student
            $student = Student::firstOrCreate(
                ['student_number' => $studentNumber],
                [
                    'first_name' => $row[3] ?? 'Unknown',
                    'last_name' => $row[4] ?? 'Unknown',
                    'grade_level' => $row[5] ?? 'Unknown',
                    'email' => $row[6] ?? null,
                    'status' => 'Active',
                    'is_imported' => true
                ]
            );

            if ($student->wasRecentlyCreated) {
                $newStudents++;
            }

            // Normalize service type
            $serviceType = strtolower($serviceTypeRaw);
            if (str_contains($serviceType, 'good')) {
                $serviceType = 'Good Moral Certificate';
            } elseif (str_contains($serviceType, 'id')) {
                $serviceType = 'ID Replacement';
            } elseif (str_contains($serviceType, 'form 137')) {
                $serviceType = 'Form 137';
            } else {
                // FIX: Use the actual Excel row number ($index + 1) for reporting skipped rows
                $skippedRows[] = ['row' => $index + 1, 'reason' => 'Invalid service type'];
                continue;
            }

            // Duplicate check
            $exists = ServiceRequest::where('student_id', $student->id)
                ->where('service_type', $serviceType)
                ->where('date_requested', $requestedDate)
                ->exists();

            if ($exists) {
                // FIX: Use the actual Excel row number ($index + 1) for reporting skipped rows
                $skippedRows[] = ['row' => $index + 1, 'reason' => 'Duplicate request'];
                continue;
            }

            // Create request
            ServiceRequest::create([
                'student_id' => $student->id,
                'service_type' => $serviceType,
                'date_requested' => $requestedDate,
                'status' => 'Pending'
            ]);

            $successfulRequests++;
        }

        // Save import summary
        ImportLog::create([
            'filename' => basename($this->filePath),
            'user_id' => $this->userId,
            'summary_json' => json_encode([
                'rows_processed' => $rowsProcessed,
                'successful_requests' => $successfulRequests,
                'new_students' => $newStudents,
                'skipped_rows' => $skippedRows
            ])
        ]);

        // Clean up the file after the import is successfully logged
        Storage::disk('local')->delete($this->filePath);
    }
}
