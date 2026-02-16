<?php

namespace App\Http\Controllers;

use App\Models\AppointmentAttachment;
use App\Models\Appointment;
use App\Services\AttachmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AppointmentAttachmentController extends Controller
{
    protected $attachmentService;

    public function __construct(AttachmentService $attachmentService)
    {
        $this->attachmentService = $attachmentService;
    }

    /**
     * Store or update attachment for an appointment.
     *
     * @param  Request  $request
     * @param  int  $salonId
     * @param  int  $appointmentId
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeOrUpdate(Request $request, $salonId, $appointmentId)
    {
        // Verify salon ownership
        if (auth('api')->user()->active_salon_id !== (int) $salonId) {
            return response()->json(['message' => 'دسترسی غیرمجاز'], 403);
        }

        // Verify appointment belongs to salon
        $appointment = Appointment::where('id', $appointmentId)
            ->where('salon_id', $salonId)
            ->first();

        if (!$appointment) {
            return response()->json(['message' => 'نوبت یافت نشد'], 404);
        }

        $validator = Validator::make($request->all(), [
            'images' => 'nullable|array|max:4',
            'images.*' => 'image|mimes:jpeg,png,jpg|max:5120', // Max 5MB per image
            'notes' => 'nullable|string|max:2000',
        ], [
            'images.max' => 'حداکثر 4 عکس مجاز است',
            'images.*.image' => 'فایل باید تصویر باشد',
            'images.*.mimes' => 'فرمت تصویر باید jpeg، png یا jpg باشد',
            'images.*.max' => 'حجم هر تصویر نباید بیشتر از 5 مگابایت باشد',
            'notes.max' => 'یادداشت نباید بیشتر از 2000 کاراکتر باشد',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'خطا در اعتبارسنجی داده‌ها',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $images = $request->hasFile('images') ? $request->file('images') : null;
            $notes = $request->input('notes');

            $attachment = $this->attachmentService->storeOrUpdateAttachment(
                $appointmentId,
                $images,
                $notes
            );

            $attachment->load(['appointment.services', 'appointment.staff']);

            return response()->json([
                'message' => 'پیوست با موفقیت ذخیره شد',
                'data' => [
                    'id' => $attachment->id,
                    'appointment_id' => $attachment->appointment_id,
                    'notes' => $attachment->notes,
                    'images' => $attachment->image_urls,
                    'appointment' => [
                        'date' => $attachment->appointment->appointment_date->format('Y-m-d'),
                        'time' => $attachment->appointment->start_time,
                        'services' => $attachment->appointment->services->map(fn($s) => [
                            'id' => $s->id,
                            'name' => $s->name,
                        ]),
                        'staff' => [
                            'id' => $attachment->appointment->staff->id,
                            'name' => $attachment->appointment->staff->full_name,
                        ],
                    ],
                    'created_at' => $attachment->created_at,
                    'updated_at' => $attachment->updated_at,
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'خطا در ذخیره پیوست',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get attachment for a specific appointment.
     *
     * @param  int  $salonId
     * @param  int  $appointmentId
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($salonId, $appointmentId)
    {
        // Verify salon ownership
        if (auth('api')->user()->active_salon_id !== (int) $salonId) {
            return response()->json(['message' => 'دسترسی غیرمجاز'], 403);
        }

        $attachment = AppointmentAttachment::where('appointment_id', $appointmentId)
            ->where('salon_id', $salonId)
            ->with(['appointment.services', 'appointment.staff'])
            ->first();

        if (!$attachment) {
            return response()->json(['message' => 'پیوستی یافت نشد'], 404);
        }

        return response()->json([
            'data' => [
                'id' => $attachment->id,
                'appointment_id' => $attachment->appointment_id,
                'notes' => $attachment->notes,
                'images' => $attachment->image_urls,
                'appointment' => [
                    'date' => $attachment->appointment->appointment_date->format('Y-m-d'),
                    'time' => $attachment->appointment->start_time,
                    'services' => $attachment->appointment->services->map(fn($s) => [
                        'id' => $s->id,
                        'name' => $s->name,
                    ]),
                    'staff' => [
                        'id' => $attachment->appointment->staff->id,
                        'name' => $attachment->appointment->staff->full_name,
                    ],
                ],
                'created_at' => $attachment->created_at,
                'updated_at' => $attachment->updated_at,
            ]
        ], 200);
    }

    /**
     * Delete attachment.
     *
     * @param  int  $salonId
     * @param  int  $attachmentId
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($salonId, $attachmentId)
    {
        // Verify salon ownership
        if (auth('api')->user()->active_salon_id !== (int) $salonId) {
            return response()->json(['message' => 'دسترسی غیرمجاز'], 403);
        }

        $deleted = $this->attachmentService->deleteAttachment($attachmentId, $salonId);

        if (!$deleted) {
            return response()->json(['message' => 'پیوست یافت نشد'], 404);
        }

        return response()->json(['message' => 'پیوست با موفقیت حذف شد'], 200);
    }

    /**
     * Get customer attachments history (Timeline view).
     *
     * @param  Request  $request
     * @param  int  $salonId
     * @param  int  $customerId
     * @return \Illuminate\Http\JsonResponse
     */
    public function customerHistory(Request $request, $salonId, $customerId)
    {
        // Verify salon ownership
        if (auth('api')->user()->active_salon_id !== (int) $salonId) {
            return response()->json(['message' => 'دسترسی غیرمجاز'], 403);
        }

        $filters = [
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
        ];

        $attachments = $this->attachmentService->getCustomerAttachmentsHistory(
            $customerId,
            $salonId,
            $filters
        );

        $data = $attachments->map(function ($attachment) {
            return [
                'id' => $attachment->id,
                'appointment_id' => $attachment->appointment_id,
                'appointment_date' => $attachment->appointment->appointment_date->format('Y-m-d'),
                'appointment_time' => $attachment->appointment->start_time,
                'services' => $attachment->appointment->services->map(fn($s) => [
                    'id' => $s->id,
                    'name' => $s->name,
                ]),
                'staff' => [
                    'id' => $attachment->appointment->staff->id,
                    'name' => $attachment->appointment->staff->full_name,
                ],
                'notes' => $attachment->notes,
                'images' => $attachment->image_urls,
                'created_at' => $attachment->created_at,
            ];
        });

        return response()->json([
            'data' => $data,
            'total' => $data->count(),
        ], 200);
    }

    /**
     * Get customer gallery (Grid view with service filter).
     *
     * @param  Request  $request
     * @param  int  $salonId
     * @param  int  $customerId
     * @return \Illuminate\Http\JsonResponse
     */
    public function customerGallery(Request $request, $salonId, $customerId)
    {
        // Verify salon ownership
        if (auth('api')->user()->active_salon_id !== (int) $salonId) {
            return response()->json(['message' => 'دسترسی غیرمجاز'], 403);
        }

        $serviceId = $request->input('service_id');

        $gallery = $this->attachmentService->getCustomerGallery(
            $customerId,
            $salonId,
            $serviceId
        );

        return response()->json([
            'data' => $gallery,
            'total' => count($gallery),
        ], 200);
    }
}
