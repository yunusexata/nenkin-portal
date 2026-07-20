<?php

namespace App\Models;

use App\Enums\Service\EmailLogStatus;
use App\Mail\ClientCreateUserMail;
use App\Repositories\Service\SendEmailLogRepository;
use App\Traits\Models\LowercaseAttributes;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Muhammadyunus1072\TrackHistory\HasTrackHistory;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Support\Str;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, SoftDeletes, HasTrackHistory, LowercaseAttributes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'client_id',
        'name',
        'no_whatsapp',
        'email',
        'password',
        'pic',
        'is_active',
        'upod_type',
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

    protected array $lowercase = [
        'pic',
    ];

    const PIC_AINUL = 'ainul';
    const PIC_KIM = 'kim';
    const PIC_SLAMET = 'slamet';

    const PIC_CHOICE = [
        self::PIC_AINUL => 'AINUL',
        self::PIC_KIM => 'KIM',
        self::PIC_SLAMET => 'SLAMET',
    ];

    const ROLE_SUPER_ADMIN = 'SUPERADMIN';
    const ROLE_HS = 'HS';
    const ROLE_HS2 = 'HS2';
    const ROLE_HS3 = 'HS3';
    const ROLE_SALES = 'SALES';
    const ROLE_CLIENT = 'CLIENT';

    const UPOD_TYPE_RISET = 'riset';
    const UPOD_TYPE_GOOGLE = 'google';

    const UPOD_TYPE_CHOICE = [
        self::UPOD_TYPE_RISET => 'Riset Kepuasan',
        self::UPOD_TYPE_GOOGLE => 'Google Review',
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

        static::deleted(function (User $user) {
            $user->client?->magicLink()
                ->update([
                    'token' => Str::uuid(),
                    'email' => null,
                    'password' => null,
                    'used_at' => null,
                ]);
        });
    }


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
