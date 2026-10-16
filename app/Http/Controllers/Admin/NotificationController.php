<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Get live appointments feed for the current authenticated user and firm.
     * Automatically scoped to the firm (FirmScope) and restricted to assigned lawyer if user is a Lawyer.
     */
    public function appointmentsFeed(Request $request): JsonResponse
    {
        $lastReadId = (int) session('appointment_notifications_last_read', 0);
        $clientLastId = (int) $request->query('last_id', 0);

        // Uses global scopes: FirmScope (only this firm) and RestrictsToAssignedLawyer (only this lawyer if role is Lawyer)
        $appointments = Appointment::query()
            ->with(['client', 'lead', 'lawyer.user', 'service'])
            ->latest('id')
            ->limit(15)
            ->get();

        $latestId = (int) ($appointments->first()?->id ?? 0);
        $hasBrandNew = $clientLastId > 0 && $latestId > $clientLastId;

        $unreadCount = $appointments->filter(fn (Appointment $a) => $a->id > $lastReadId)->count();

        $items = $appointments->map(function (Appointment $a) use ($lastReadId): array {
            $clientName = $a->client?->name ?: ($a->lead?->name ?: 'Caller');
            $clientPhone = $a->client?->phone ?: ($a->lead?->phone ?: ($a->relatedCall()?->caller_phone ?: 'N/A'));
            $lawyerName = $a->lawyer?->user?->name ?: ($a->lawyer?->displayName() ?: 'Assigned Lawyer');
            $serviceName = $a->service?->name ?: 'Consultation';

            return [
                'id' => $a->id,
                'client_name' => $clientName,
                'client_phone' => $clientPhone,
                'service_name' => $serviceName,
                'lawyer_name' => $lawyerName,
                'lawyer_id' => $a->lawyer_id,
                'date' => $a->start_at ? $a->start_at->format('M d, Y') : 'Date TBD',
                'time' => $a->start_at ? $a->start_at->format('g:i A') : 'Time TBD',
                'start_at_iso' => $a->start_at?->toIso8601String(),
                'created_at_human' => $a->created_at?->diffForHumans() ?: 'Just now',
                'status' => is_object($a->status) ? ($a->status->value ?? 'scheduled') : (string) ($a->status ?: 'scheduled'),
                'fee' => $a->fee_amount ? '$'.number_format((float) $a->fee_amount, 2) : 'Free',
                'source' => (string) ($a->source ?: 'phone_ai'),
                'is_ai' => $a->source === 'phone_ai' || (bool) $a->call_id,
                'is_unread' => $a->id > $lastReadId,
                'view_url' => route('appointments.index').'?appointment_id='.$a->id,
            ];
        });

        return response()->json([
            'success' => true,
            'latest_id' => $latestId,
            'unread_count' => $unreadCount,
            'has_brand_new' => $hasBrandNew,
            'brand_new_item' => $hasBrandNew ? $items->first() : null,
            'items' => $items,
        ]);
    }

    /**
     * Mark all appointment notifications as read up to the given ID.
     */
    public function markAppointmentsRead(Request $request): JsonResponse
    {
        $latestId = (int) $request->input('latest_id', 0);
        if ($latestId > 0) {
            session(['appointment_notifications_last_read' => $latestId]);
        }

        return response()->json([
            'success' => true,
            'last_read_id' => $latestId,
        ]);
    }
}
