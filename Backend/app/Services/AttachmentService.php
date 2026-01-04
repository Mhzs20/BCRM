<?php

namespace App\Services;

use App\Models\AppointmentAttachment;
use App\Models\Appointment;
use App\Models\Customer;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class AttachmentService
{
    /**
     * Store or update attachment for an appointment.
     *
     * @param  int  $appointmentId
     * @param  array|null  $images
     * @param  string|null  $notes
     * @return AppointmentAttachment
     */
    public function storeOrUpdateAttachment($appointmentId, $images = null, $notes = null)
    {
        $appointment = Appointment::findOrFail($appointmentId);
        
        // Check if attachment already exists
        $attachment = AppointmentAttachment::where('appointment_id', $appointmentId)->first();
        
        $imagePaths = [];
        
        // Process images if provided
        if ($images && is_array($images)) {
            // Limit to 4 images
            $images = array_slice($images, 0, 4);
            
            foreach ($images as $image) {
                if ($image instanceof UploadedFile) {
                    $imagePath = $this->processAndStoreImage($image, $appointment->salon_id);
                    $imagePaths[] = $imagePath;
                }
            }
        }
        
        if ($attachment) {
            // Update existing attachment
            $updateData = [];
            
            if (!empty($imagePaths)) {
                // Delete old images
                $this->deleteImages($attachment->images);
                $updateData['images'] = $imagePaths;
            }
            
            if ($notes !== null) {
                $updateData['notes'] = $notes;
            }
            
            if (!empty($updateData)) {
                $attachment->update($updateData);
            }
        } else {
            // Create new attachment
            $attachment = AppointmentAttachment::create([
                'appointment_id' => $appointmentId,
                'customer_id' => $appointment->customer_id,
                'salon_id' => $appointment->salon_id,
                'images' => $imagePaths,
                'notes' => $notes,
            ]);
        }
        
        return $attachment;
    }

    /**
     * Process and store an image.
     *
     * @param  UploadedFile  $image
     * @param  int  $salonId
     * @return string
     */
    protected function processAndStoreImage(UploadedFile $image, $salonId)
    {
        // Generate unique filename
        $filename = uniqid('attachment_') . '.' . $image->getClientOriginalExtension();
        $path = "attachments/salon_{$salonId}/{$filename}";
        
        // Store the image directly
        // Note: For production, consider using intervention/image or similar for optimization
        $image->storeAs('public/attachments/salon_' . $salonId, $filename);
        
        return $path;
    }

    /**
     * Delete images from storage.
     *
     * @param  array|null  $images
     * @return void
     */
    protected function deleteImages($images)
    {
        if (empty($images)) {
            return;
        }
        
        foreach ($images as $image) {
            if (Storage::disk('public')->exists($image)) {
                Storage::disk('public')->delete($image);
            }
        }
    }

    /**
     * Get attachments history for a customer.
     *
     * @param  int  $customerId
     * @param  int  $salonId
     * @param  array  $filters
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getCustomerAttachmentsHistory($customerId, $salonId, $filters = [])
    {
        $query = AppointmentAttachment::where('appointment_attachments.customer_id', $customerId)
            ->where('appointment_attachments.salon_id', $salonId)
            ->with([
                'appointment' => function ($q) {
                    $q->with(['services', 'staff']);
                }
            ]);
        
        // Apply date filters
        if (!empty($filters['date_from']) && !empty($filters['date_to'])) {
            $query->dateRange($filters['date_from'], $filters['date_to']);
        }
        
        // Order by appointment date descending
        $query->join('appointments', 'appointment_attachments.appointment_id', '=', 'appointments.id')
            ->orderBy('appointments.appointment_date', 'desc')
            ->orderBy('appointments.start_time', 'desc')
            ->select('appointment_attachments.*');
        
        return $query->get();
    }

    /**
     * Get gallery images for a customer.
     *
     * @param  int  $customerId
     * @param  int  $salonId
     * @param  int|null  $serviceId
     * @return array
     */
    public function getCustomerGallery($customerId, $salonId, $serviceId = null)
    {
        $query = AppointmentAttachment::where('appointment_attachments.customer_id', $customerId)
            ->where('appointment_attachments.salon_id', $salonId)
            ->whereNotNull('images')
            ->with([
                'appointment' => function ($q) {
                    $q->with(['services']);
                }
            ]);
        
        // Filter by service if provided
        if ($serviceId) {
            $query->byService($serviceId);
        }
        
        // Order by appointment date descending
        $query->join('appointments', 'appointment_attachments.appointment_id', '=', 'appointments.id')
            ->orderBy('appointments.appointment_date', 'desc')
            ->orderBy('appointments.start_time', 'desc')
            ->select('appointment_attachments.*');
        
        $attachments = $query->get();
        
        // Flatten all images into a single gallery array
        $gallery = [];
        
        foreach ($attachments as $attachment) {
            if (!empty($attachment->images)) {
                foreach ($attachment->images as $image) {
                    $gallery[] = [
                        'url' => asset('storage/' . $image),
                        'path' => $image,
                        'appointment_id' => $attachment->appointment_id,
                        'appointment_date' => $attachment->appointment->appointment_date,
                        'services' => $attachment->appointment->services->pluck('name'),
                    ];
                }
            }
        }
        
        return $gallery;
    }

    /**
     * Delete an attachment.
     *
     * @param  int  $attachmentId
     * @param  int  $salonId
     * @return bool
     */
    public function deleteAttachment($attachmentId, $salonId)
    {
        $attachment = AppointmentAttachment::where('id', $attachmentId)
            ->where('salon_id', $salonId)
            ->first();
        
        if (!$attachment) {
            return false;
        }
        
        // Delete images from storage
        $this->deleteImages($attachment->images);
        
        // Delete the attachment record
        $attachment->delete();
        
        return true;
    }

    /**
     * Get single attachment by ID.
     *
     * @param  int  $attachmentId
     * @param  int  $salonId
     * @return AppointmentAttachment|null
     */
    public function getAttachment($attachmentId, $salonId)
    {
        return AppointmentAttachment::where('id', $attachmentId)
            ->where('salon_id', $salonId)
            ->with(['appointment.services', 'appointment.staff'])
            ->first();
    }
}
