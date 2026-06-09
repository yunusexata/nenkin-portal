<?php

namespace App\Models\Client;

use App\Models\User;
use App\Repositories\Client\ClientMagicLinkRepository;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Muhammadyunus1072\TrackHistory\HasTrackHistory;

class Client extends Model
{
    // php artisan reverb:start
    use HasFactory, SoftDeletes, HasTrackHistory;

    protected $fillable = [

        'no_jp',
        'name',
        'name_normalized',
        'dob',
        'phone',
        'claim_type',
        'status',
        'raw_data',
        'source_file_name',
        'source_file_disk',
        'source_file_path',
        'imported_at',
        'facebook',
        'instagram',
        'pic',
        'lpk',
        'note',
    ];

    protected $guarded = ['id'];

    const STATUS_PENDING = 'pending';
    const STATUS_ACTIVE = 'active';
    const STATUS_COMPLETE = 'complete';

    // const CLAIM_TYPE_RE_ENTRY = 're_entry';
    const CLAIM_TYPE_ADJUSTMENT = 'adjusment';
    const CLAIM_TYPE_NORMAL = 'normal';
    const CLAIM_TYPE_SPEED = 'speed';

    protected static function onBoot()
    {
        self::created(function ($model) {
            $vlaidatedData = [
                'client_id' => $model->id,
                'token' => Str::uuid(),
                'expires_at' => Carbon::now()->addDays(7),
            ];

            ClientMagicLinkRepository::create($vlaidatedData);
        });
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }
    public function magicLink()
    {
        return $this->hasOne(ClientMagicLink::class);
    }
    public function user()
    {
        return $this->hasOne(User::class);
    }
    public function confirmation()
    {
        return $this->hasOne(ClientConfirmation::class);
    }
    public function esign()
    {
        return $this->hasOne(ClientEsign::class);
    }
    public function review()
    {
        return $this->hasOne(\App\Models\Review::class);
    }
    public function documents()
    {
        return $this->hasMany(\App\Models\Document::class);
    }
}
