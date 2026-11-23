<?php

use App\Actions\SendWorkflowNotification;
use App\Enums\UserRole;
use App\Models\User;
use App\Notifications\WorkflowNotification;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

test('workflow notifications persist in app and queue email independently', function () {
    Queue::fake();
    $recipient = User::factory()->create();

    app(SendWorkflowNotification::class)->handle(
        $recipient,
        'report_approved',
        'Laporan disetujui',
        'Laporan mingguan Anda telah disetujui.',
        '/mahasiswa/laporan/1',
        'Lihat laporan',
    );

    $this->assertDatabaseHas('notifications', [
        'notifiable_type' => User::class,
        'notifiable_id' => $recipient->id,
        'type' => WorkflowNotification::class,
    ]);
    Queue::assertPushed(SendQueuedNotifications::class);
});

test('inactive recipients do not receive workflow notifications', function () {
    Queue::fake();
    $recipient = User::factory()->create(['is_active' => false]);

    app(SendWorkflowNotification::class)->handle(
        $recipient,
        'report_approved',
        'Laporan disetujui',
        'Laporan mingguan Anda telah disetujui.',
        '/mahasiswa/laporan/1',
        'Lihat laporan',
    );

    $this->assertDatabaseMissing('notifications', [
        'notifiable_type' => User::class,
        'notifiable_id' => $recipient->id,
    ]);
    Queue::assertNothingPushed();
});

test('students can read only their own in-app notifications', function () {
    $student = User::factory()->create(['role' => UserRole::Student]);
    $otherStudent = User::factory()->create(['role' => UserRole::Student]);
    $notification = new WorkflowNotification(
        event: 'report_approved',
        title: 'Laporan disetujui',
        message: 'Laporan mingguan Anda telah disetujui.',
        actionUrl: '/mahasiswa/laporan/1',
        actionLabel: 'Lihat laporan',
        channels: ['database'],
    );
    Notification::sendNow($student, $notification, ['database']);
    Notification::sendNow($otherStudent, $notification, ['database']);
    $notificationId = $student->notifications()->firstOrFail()->id;
    $otherNotificationId = $otherStudent->notifications()->firstOrFail()->id;

    $this->actingAs($student)
        ->get(route('mahasiswa.notifikasi.index'))
        ->assertOk()
        ->assertSee('Laporan disetujui')
        ->assertSee('Belum dibaca');

    $this->post(route('mahasiswa.notifikasi.open', $otherNotificationId))->assertNotFound();
    $this->post(route('mahasiswa.notifikasi.open', $notificationId))
        ->assertRedirect('/mahasiswa/laporan/1');

    expect($student->notifications()->whereKey($notificationId)->firstOrFail()->read_at)->not->toBeNull();
});

test('notification actions cannot redirect students to another host', function () {
    $student = User::factory()->create(['role' => UserRole::Student]);
    Notification::sendNow($student, new WorkflowNotification(
        event: 'report_approved',
        title: 'Laporan disetujui',
        message: 'Laporan mingguan Anda telah disetujui.',
        actionUrl: '//example.com',
        actionLabel: 'Lihat laporan',
        channels: ['database'],
    ), ['database']);
    $notificationId = $student->notifications()->firstOrFail()->id;

    $this->actingAs($student)
        ->post(route('mahasiswa.notifikasi.open', $notificationId))
        ->assertRedirect(route('dashboard', absolute: false));
});
