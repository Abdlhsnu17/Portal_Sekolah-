<?php

namespace App\Observers;

use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\Gallery;
use App\Models\Registration;
use App\Models\SchoolProfile;
use App\Models\StaffMember;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Notifications\ActivityNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Notification;

class ActivityObserver
{
    public function created(Model $model): void
    {
        $this->send($model, 'created');
    }

    public function updated(Model $model): void
    {
        if ($model->wasChanged()) {
            $this->send($model, 'updated');
        }
    }

    public function deleted(Model $model): void
    {
        $this->send($model, 'deleted');
    }

    private function send(Model $model, string $event): void
    {
        $config = $this->config($model);
        if (! $config) {
            return;
        }

        $action = match ($event) {
            'created' => 'ditambahkan',
            'updated' => 'diperbarui',
            default => 'dihapus',
        };
        $actor = auth()->user()?->name ?? 'Sistem/pendaftar';

        $activity = [
            'title' => $config['label'].' '.$action,
            'message' => $config['subject'].' '.$action.' oleh '.$actor.'.',
            'icon' => $config['icon'],
            'url' => $event === 'deleted' ? $config['index'] : ($config['url'] ?? $config['index']),
            'event' => $event,
            'model' => $model::class,
            'model_id' => $model->getKey(),
            'actor_id' => auth()->id(),
        ];

        if ($model instanceof Attendance) {
            Notification::send(
                User::where('is_active', true)->where('role', 'admin')->get(),
                new ActivityNotification($activity),
            );

            $ownerId = $model->teacher?->user_id ?? $model->staffMember?->user_id;
            $owner = $ownerId ? User::whereKey($ownerId)->where('is_active', true)->where('role', '!=', 'admin')->first() : null;
            if ($owner) {
                Notification::send($owner, new ActivityNotification([
                    ...$activity,
                    'url' => route('attendance.check-in'),
                ]));
            }

            return;
        }

        $recipients = User::where('is_active', true)->whereIn('role', $config['roles'])->get();
        Notification::send($recipients, new ActivityNotification($activity));
    }

    private function config(Model $model): ?array
    {
        $all = User::ROLES;

        return match (true) {
            $model instanceof Registration => [
                'label' => 'Pendaftaran', 'subject' => $model->nama_lengkap,
                'icon' => 'clipboard', 'roles' => $all,
                'index' => route('admin.registrations.index'),
                'url' => route('admin.registrations.show', $model),
            ],
            $model instanceof Student => [
                'label' => 'Data siswa', 'subject' => $model->nama,
                'icon' => 'users', 'roles' => $all,
                'index' => route('admin.students.index'),
                'url' => route('admin.students.edit', $model),
            ],
            $model instanceof Teacher => [
                'label' => 'Data guru', 'subject' => $model->nama,
                'icon' => 'academic', 'roles' => $all,
                'index' => route('admin.teachers.index'),
                'url' => route('admin.teachers.edit', $model),
            ],
            $model instanceof StaffMember => [
                'label' => 'Data staf', 'subject' => $model->nama,
                'icon' => 'users', 'roles' => ['admin'],
                'index' => route('admin.staff.index'),
                'url' => route('admin.staff.edit', $model),
            ],
            $model instanceof Attendance => [
                'label' => 'Absensi', 'subject' => $model->person_name,
                'icon' => 'clock', 'roles' => ['admin'],
                'index' => route('admin.attendances.index'),
            ],
            $model instanceof Announcement => [
                'label' => 'Berita', 'subject' => $model->judul,
                'icon' => 'newspaper', 'roles' => $all,
                'index' => route('admin.announcements.index'),
                'url' => route('admin.announcements.edit', $model),
            ],
            $model instanceof Gallery => [
                'label' => 'Galeri', 'subject' => $model->judul,
                'icon' => 'photo', 'roles' => $all,
                'index' => route('admin.galleries.index'),
            ],
            $model instanceof SchoolProfile => [
                'label' => 'Profil sekolah', 'subject' => $model->nama,
                'icon' => 'building', 'roles' => $all,
                'index' => route('admin.profile.edit'),
            ],
            $model instanceof User => [
                'label' => 'Pengguna', 'subject' => $model->name,
                'icon' => 'users', 'roles' => ['admin'],
                'index' => route('admin.users.index'),
                'url' => route('admin.users.edit', $model),
            ],
            default => null,
        };
    }
}
