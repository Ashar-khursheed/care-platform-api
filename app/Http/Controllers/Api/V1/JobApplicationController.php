<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\JobApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class JobApplicationController extends Controller
{
        /**
 *     @OA\Get(
 *         path="/api/v1/job-applications",
 *         summary="Get all job applications",
 *         tags={"Job Applications"},
 *     @OA\Response(
 *         response=200,
 *         description="Successful operation"
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthenticated"
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Resource not found"
 *     )
 *     )
 */
    public function index()
    {
        $applications = JobApplication::latest()->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $applications,
        ]);
    }

        /**
 *     @OA\Post(
 *         path="/api/v1/job-applications",
 *         summary="Submit job application",
 *         tags={"Job Applications"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(
 *                 type="object",
 *                 required={"first_name", "last_name", "email", "phone_number", "position", "experience", "availability", "video", "resume"},
 *                 @OA\Property(property="first_name", type="string"),
 *                 @OA\Property(property="last_name", type="string"),
 *                 @OA\Property(property="email", type="string", format="email"),
 *                 @OA\Property(property="phone_number", type="string"),
 *                 @OA\Property(property="position", type="string"),
 *                 @OA\Property(property="experience", type="string"),
 *                 @OA\Property(property="availability", type="string"),
 *                 @OA\Property(property="message", type="string"),
 *                 @OA\Property(property="video", type="string", format="binary"),
 *                 @OA\Property(property="resume", type="string", format="binary")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Successful operation"
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthenticated"
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Resource not found"
 *     )
 *     )
 */
    public function store(Request $request)
    {
        $request->validate([
            'first_name'     => 'required|string|max:100',
            'last_name'      => 'required|string|max:100',
            'email'          => 'required|email|max:150',
            'phone_number'   => 'required|string|max:20',
            'position'       => 'required|string|max:150',
            'experience'     => 'required|string|max:100',
            'availability'   => 'required|string|max:100',

            'video'          => 'required|file|mimes:mp4,mov,avi,webm|max:102400', // 100MB
            'resume'         => 'required|file|mimes:pdf,doc,docx|max:10240',     // 10MB

            'message'        => 'nullable|string|max:500',
        ]);

        // Upload video to S3
        $videoFile = $request->file('video');
        $videoExtension = $videoFile->getClientOriginalExtension();
        $videoFilename = 'video_' . \Illuminate\Support\Str::random(20) . '_' . time() . '.' . $videoExtension;
        $videoPath = "job-applications/videos/{$videoFilename}";
        Storage::disk('s3')->put($videoPath, file_get_contents($videoFile));
        
        // Upload resume to S3
        $resumeFile = $request->file('resume');
        $resumeExtension = $resumeFile->getClientOriginalExtension();
        $resumeFilename = 'resume_' . \Illuminate\Support\Str::random(20) . '_' . time() . '.' . $resumeExtension;
        $resumePath = "job-applications/resumes/{$resumeFilename}";
        Storage::disk('s3')->put($resumePath, file_get_contents($resumeFile));

        $application = JobApplication::create([
            'first_name'   => $request->first_name,
            'last_name'    => $request->last_name,
            'email'        => $request->email,
            'phone_number' => $request->phone_number,
            'position'     => $request->position,
            'experience'   => $request->experience,
            'availability' => $request->availability,
            'video_path'   => $videoPath,
            'resume_path'  => $resumePath,
            'message'      => $request->message,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Application submitted successfully',
            'data'    => $application,
        ], 201);
    }
}
