<?php

namespace App\Models;

use App\Enums\Service\EmailLogStatus;
use App\Mail\ClientCreateUserMail;
use App\Repositories\Service\SendEmailLogRepository;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Muhammadyunus1072\TrackHistory\HasTrackHistory;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, SoftDeletes, HasTrackHistory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'client_id',
        'name',
        'n_whatsapp',
        'email',
        'password',
        'pic',
        'is_active'

    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];


    protected static function onBoot()
    {
        self::created(function ($model) {
            SendEmailLogRepository::create(
                [
                    'subject_type' => self::class,
                    'subject_id' => $model->id,
                    'data' => json_encode([], true),
                    'email' => $model->email,
                    'mailable' => ClientCreateUserMail::class,
                    'subject_line' => "Pembuatan Akun Berhasil",
                    'status' => EmailLogStatus::PENDING,
                    'queued_at' => now(),
                ]
            );
        });
    }

    const ROLE_SUPER_ADMIN = 'SUPERADMIN';
    const ROLE_HS = 'HS';
    const ROLE_CLIENT = 'CLIENT';

    public function sendPasswordResetNotification($token)
    {
        \App\Repositories\Service\SendEmailLogRepository::create([
            'subject_type' => self::class,
            'subject_id' => $this->id,
            'data' => json_encode([
                'token' => $token,
                'email' => $this->email,
            ], true),
            'email' => $this->email,
            'mailable' => \App\Mail\ResetPasswordMail::class,
            'subject_line' => "Reset Password Anda",
            'status' => \App\Enums\Service\EmailLogStatus::PENDING,
        ]);
    }

    public function client()
    {
        return $this->belongsTo(\App\Models\Client\Client::class, 'client_id');
    }
}
