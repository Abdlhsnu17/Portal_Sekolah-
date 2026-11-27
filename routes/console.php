<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\InvoicePaymentRequest;
use App\Models\LibraryReservation;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('school:expire-service-requests', function () {
    $payments = InvoicePaymentRequest::where('status', 'pending')->whereNotNull('expires_at')->where('expires_at', '<', now())->update(['status' => 'expired']);
    $reservations = LibraryReservation::where('status', 'available')->whereNotNull('expires_at')->where('expires_at', '<', now())->update(['status' => 'expired']);
    $this->info("{$payments} permintaan pembayaran dan {$reservations} reservasi kedaluwarsa.");
})->purpose('Mark expired payment requests and book reservations');

Schedule::command('school:expire-service-requests')->hourly()->withoutOverlapping();
